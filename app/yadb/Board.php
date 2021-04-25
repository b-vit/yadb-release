<?php

namespace yadb;

/**
 * Class Board
 * Třída Board zajišťuje vykreslení interaktivní HTML tabulky, která slouží jako srdce uživatelského rozhraní elektronické úřední desky.
 * @package yadb
 */
class Board extends MainController
{
    /**
     * @param \Base $base
     */
    public function install(\Base $base)
    {

    }

    /**
     * Umožňuje filtrovat dokumenty na úřední desce podle kategorie.
     * @param \Base $base
     */
    public function get_filtered(\Base $base)
    {
        $category = $base->get('GET.category');
        $base->set('filtered',TRUE);
        switch ($category){
            case "dotace":
                $base->set('filter_by',"Dotace");
                $base->set('filter_by_header',"Dotace");
                break;
            case "drazba":
                $base->set('filter_by',"Dražba");
                $base->set('filter_by_header',"Dražba");
                break;
            case "zvirata":
                $base->set('filter_by',"Nalezená zvířata");
                $base->set('filter_by_header',"Nalezená zvířata");
                break;
            case "ostatni":
                $base->set('filter_by',"Ostatní");
                $base->set('filter_by_header',"Ostatní");
                break;
            case "oznameni":
                $base->set('filter_by',"Oznámení");
                $base->set('filter_by_header',"Oznámení");
                break;
            case "rozhodnuti":
                $base->set('filter_by',"Rozhodnutí, Usnesení");
                $base->set('filter_by_header',"Rozhodnutí, Usnesení");
                break;
            case "smlouvy":
                $base->set('filter_by',"Veřejnoprávní smlouvy");
                $base->set('filter_by_header',"Veřejnoprávní smlouvy");
                break;
            case "vyhlasky":
                $base->set('filter_by',"Veřejné vyhlášky");
                $base->set('filter_by_header',"Veřejné vyhlášky");
                break;
            case "rizeni":
                $base->set('filter_by',"Výběrová řízení");
                $base->set('filter_by_header',"Výběrová řízení");
                break;
            case "nalezy":
                $base->set('filter_by',"Ztráty a nálezy");
                $base->set('filter_by_header',"Ztráty a nálezy");
                break;
            default:
                $base->set('filter_by_header',"Všechny dokumenty");
                $base->set('filtered',FALSE);
                break;
        }
        $this->get_all($base);
    }

    /**
     * Vytvoří HTML úřední desky a vykreslí jí. Zpracovává XML soubor ze spisové služby, uložený lokálně.
     * @param \Base $base
     */
    public function get_all(\Base $base)
    {
        if(!$base->exists('filter_by_header')){
            $base->set('filter_by_header',"Všechny dokumenty");
            $base->set('filtered',FALSE);
        } // pokud není nastaven filter_by_header, nastaví "všechny dokumenty"

        $xml = simplexml_load_file($base->get('ROOT')."/ui/xml/main_xml.xml");
        $tablehtml = '
            <div class="table-responsive">
            <table class="table table-striped" data-page-length="5" id="main_table">
            <thead>
                <tr style="background: #006fb7; color: white;font-size:x-large">
                    <th>Typ</th>
                    <th>Vyvěšení Stažení</th>
                    <th>Popis</th>
                    <th>Zdroj</th>
                    <th>Přílohy</th>
                </tr>
            </thead>
           <tbody style="font-size:large">';

        $counter = 1;
        if ($xml->item) {
            foreach ($xml->item as $item) {
                $tablehtml .= '<tr>';

                foreach ($item as $element => $value) {
                    if ($element === "PRILOHY") {
                        $attachments = '<td>';
                        $pcount = 1;
                        foreach ($value->item as $m => $it) {
                            $hashname = new Content;
                            $hashname = $hashname->get_hashname($it->ID, $it->DATETIME);
                            $onclick = 'onclick="post_pdf(`'.$hashname.'`)"';
                            $attachments .='<div id="'. $hashname .'" class="priloha"  data-idf="'.ltrim($it->ID, "U").'"><a class="btn text-white vice-background btn-rounded my-1" href="javascript:void(0);" '.$onclick.'>Příloha(' . $pcount . '.)</a></div>';
                            $pcount++;
                        }
                        $attachments .= '</td>';
                        $tablehtml .= "$attachments";
                    } else if ($element === "VYVESENO") {
                        $newDate = date("d.m.Y", strtotime($value));
                        $tablehtml .= "<td data-sort='$value'> <b>$newDate</b>";

                    } else if ($element === "STAZENO") {
                        $newDate = date("d.m.Y", strtotime($value));
                        $tablehtml .= "<br><div style='color: #ff0000'><b>$newDate</b></div></td>";
                    } else
                        $tablehtml .= "<td>$value</td>";
                }

                $tablehtml .= '</tr>';
                $counter++;
            }
        } else echo "Žádné veřejné dokumenty!";
        $tablehtml.="</tbody>";
        $tablehtml .= "</table>";
        $tablehtml.="</div>";

        $base->set('table', $tablehtml);
        $base->set('board', true);
        $base->set('content', 'inner_content.html');
        echo \Template::instance()->render('index.html');
    }
}