<?php

namespace yadb;

/**
 * Class Content
 * Třída Content obsahuje funkce týkající se hlavního algoritmu, který zpracovává XML feed ze spisové služby. S tím souvisí správa PDF dokumentů k tomu navíc novinky.
 * @package yadb
 */
class Content extends MainController
{

    /**
     * @param \Base $base
     */
    public function install(\Base $base)
    {

    }

    /**
     * Vykreslí šablonu obsahu, zde je menu nebo úřední deska.
     * Kontroluje jestli je deska v režimu idle a zajišťuje funkcionalitu "návštěvníka".
     * Měří návštěvnost pouze při přechodu z idle režimu.
     * @param \Base $base
     */
    public function get_inner_content(\Base $base)
    {
        $base->set('content', 'inner_content.html');
        $base->set('SESSION.idle', FALSE);
        $this->check_idle();
        if (!$this->is_online())\Flash::instance()->addMessage("Elektronická úřední deska NENÍ PŘIPOJENA K INTERNETU, zobrazují se stará data.", 'danger');
        if ($base->get('SESSION.visitor') == FALSE)
        {
            $base->set('SESSION.visitor', TRUE);
            $this->visits();
        }
        echo \Template::instance()->render('index.html');
    }

    /**
     * Nastavuje session proměnnou, která zajišťuje funkcionalitu režimu pro invalidy.
     * @param \Base $base
     */
    public function get_handicap(\Base $base)
    {
        if ($base->get('SESSION.handicap') == true) {
            $base->set('SESSION.handicap', false);
        } else {
            $base->set('SESSION.handicap', true);
        }
        $base->reroute("/inner_content");
    }

    /**
     * Vykresluje šablonu s prohlížečem PDF souborů pro určitý PDF soubor.
     * @param \Base $base
     * @param array $params
     */
    public function get_pdf_viewer(\Base $base, array $params)
    {
        $base->set('pdf_file', $params[]);
        echo \Template::instance()->render('pdf_viewer.html');
    }

    /**
     * Obstarává získání PDF souboru pro následné zobrazení v PDF prohlížeči.
     * @param \Base $base
     */
    public function post_pdf_viewer(\Base $base)
    {
        $payload = $base->get('POST.textdata');
        $idf = $base->get('POST.idf');
        $base->set('pdf_file', $payload . ".pdf");
        $base->set('pdf_file_id', $idf);

        $file = $base->get('ROOT') . '/config/file_url.ini';
        $base->set('pdf_file_url', $base->read($file));
        echo \Template::instance()->render('pdf_viewer.html');
    }


    /**
     * Vstupní bod pro spuštění aktualizovací sekvence, která stáhne nejnovější PDF dokumenty a nejnovější XML feed ze spisové služby.
     * @param \Base $base
     * @param array $reinstall
     */
    public function retrieve_xml_feed(\Base $base,array $reinstall = array('/update'))
    {
        $url_config_file = $base->get('ROOT') . '/config/url.ini';
        $url = $base->read($url_config_file);

        $urlf_config_file = $base->get('ROOT') . '/config/file_url.ini';
        $urlf = $base->read($urlf_config_file);

        if (!$this->is_online($url) OR !$this->is_online($urlf))
        {
            //echo 'Nepřipojeno';
            $logger = new \Log('system.log');
            $logger->write('Dokumenty na desce se nepodařilo aktualizovat, není připojena ke zdrojovému serveru.','d.m.Y [H:i:s] O');
            $base->reroute("/inner_content");
        } else {
            $this->get_actual_xml($base, $url);
            $this->retrieve_documents($base, $urlf);
            if($reinstall[0] == "/update")$base->reroute('/');
            else if ($reinstall[0] == "/reinstall"){
                \Flash::instance()->addMessage("Aplikace yadb byla nainstalována. Zaregistrujte účet hlavního administrátora, tajné heslo naleznete ve složce config v souboru secret.ini.", 'success');
                $base->reroute('/register');
            }
        }
    }

    /**
     * Stáhne aktuální XML feed ze spisové služby.
     * @param $base
     * @param $url - Odkud stáhnout XML feed
     */
    function get_actual_xml($base, $url)
    {
        $xml = file_get_contents($url);
        $path = $base->get('ROOT') . "/ui/xml";
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        } else if (!file_exists($base->get('ROOT') . "/ui/xml/main_xml.xml")) {
            file_put_contents($base->get('ROOT') . "/ui/xml/main_xml.xml", $xml, LOCK_EX);
        } else {
            file_put_contents($base->get('ROOT') . "/ui/xml/main_xml_new.xml", $xml, LOCK_EX);
            $this->compare_xml_feeds($base);
        }
    }

    /**
     * Porovná aktuální XML feed se starým, smaže soubory, které se nachází ve starém XML feedu ale né v novém, přejmenuje nový xml feed a starý smaže.
     * @param $base
     */
    function compare_xml_feeds($base)
    {
        $xml = simplexml_load_file($base->get('ROOT') . "/ui/xml/main_xml.xml");
        $xml_new = simplexml_load_file($base->get('ROOT') . "/ui/xml/main_xml_new.xml");

        $old_hashes = [];
        $new_hashes = [];

        /* Načte hashe příloh do dvou polí, old a new, mají rozdílné počty příloh -> dva for cykly */
        if ($xml->item) {
            foreach ($xml->item as $item) {
                foreach ($item as $element => $value) {
                    if ($element === "PRILOHY") {
                        $pcount = 1;
                        foreach ($value->item as $attachment => $it) {
                            $hashname = $this->get_hashname($it->ID, $it->DATETIME);
                            array_push($old_hashes, $hashname);
                            $pcount++;
                        }
                    }
                }
            }
        }

        if ($xml_new->item) {
            foreach ($xml_new->item as $item) {
                foreach ($item as $element => $value) {
                    if ($element === "PRILOHY") {
                        $pcount = 1;
                        foreach ($value->item as $attachment => $it) {
                            $hashname = $this->get_hashname($it->ID, $it->DATETIME);
                            array_push($new_hashes, $hashname);
                            $pcount++;
                        }
                    }
                }
            }
        }

        /* Zjistí, které hashe jsou ve starém XML souboru ale né v novém -> tyto vymaže */
        $hash_diff = array_diff($old_hashes,$new_hashes);

        foreach ($hash_diff as $hashd) {

            /* Našel se hash souboru, který je ve starém ale není v novém -> už nemá být vyvěšen -> je vymazán */
            $file = $base->get('ROOT') . DIRECTORY_SEPARATOR . "ui" . DIRECTORY_SEPARATOR . "pdf" . DIRECTORY_SEPARATOR . $hashd . ".pdf";

            /* Smaže pdfko, které se nachází pouze ve starém xml souboru */
            if (file_exists($file)) {
                unlink($file);
            } else {
                // echo 'Nepodařilo se odstranit soubor';
            }
        }
        rename($base->get('ROOT') . "/ui/xml/main_xml_new.xml", $base->get('ROOT') . "/ui/xml/main_xml.xml"); // Přepsání starého XML souboru na nový
    }

    /**
     * Vrátí hash vytvořený z datumu a uid souboru z XML feedu,
     * vznikne tak jedinečné jméno souboru pro další manipulaci - ukládání, mazání, vyhledávání...
     * @param $uid - uid z XML feedu ze spisové služby, přiděleno magistrátem
     * @param $date - datum změny přílohy
     * @return string
     */
    function get_hashname($uid, $date): string
    {
        return hash("sha256", $uid . $date);
    }

    /**
     * Stáhne pouze ty PDFka, která ještě nejsou na disku -> zjistí podle hashe.
     * @param \Base $base
     * @param $url
     */
    function retrieve_documents(\Base $base, $url)
    {
        $xml = simplexml_load_file($base->get('ROOT') . "/ui/xml/main_xml.xml");
        $dir = $base->get('ROOT') . DIRECTORY_SEPARATOR . "ui" . DIRECTORY_SEPARATOR . "pdf" . DIRECTORY_SEPARATOR;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $filehashnames = scandir($dir); /* Pole se všemi pdfky na disku ve složce ui/pdf */

        if ($xml->item) {
            foreach ($xml->item as $item) {
                foreach ($item as $element => $value) {
                    if ($element === "PRILOHY") {
                        $pcount = 1;
                        foreach ($value->item as $attachment => $it) {
                            $hashname = $this->get_hashname($it->ID, $it->DATETIME);
                            if (!$this->hash_lookup($hashname, $filehashnames)) {/* Pokud nenalezne hash na disku, stáhne daný soubor */
                                $this->download_attachment_pdf($base, $it, $hashname, $url);
                            }
                            $pcount++;
                        }
                    }
                }
            }
        }
    }

    /**
     * Vrátí true, pokud najde hashovaný filename na disku, false pokud nenajde.
     * @param $hash - hash / jméno hledaného souboru
     * @param $filehashnames - pole se všemi hashy / jmény souborů ve složce ui/pdf/
     * @return bool
     */
    function hash_lookup($hash, $filehashnames): bool
    {
        if (in_array($hash . ".pdf", $filehashnames)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Stažení jednoho souboru (přílohy) z URL, na které se nachází dokumenty od magistrátu.
     * @param \Base $base
     * @param $item - item z XML feedu
     * @param $hashname - jméno souboru pro uložení
     * @param $url - url se soubory
     */
    function download_attachment_pdf(\Base $base, $item, $hashname, $url)
    {
        file_put_contents($base->get('ROOT') . DIRECTORY_SEPARATOR . "ui" . DIRECTORY_SEPARATOR . "pdf" . DIRECTORY_SEPARATOR . $hashname . ".pdf", fopen($url . ltrim($item->ID, "U"), 'rb'), LOCK_EX);
    }

    /**
     * Zavolá vykreslení novinek, pokud je úřední deska online zobrazí čerstvé novinky.
     * @param \Base $base
     */
    public function get_news(\Base $base)
    {
        $rss_file = $base->get('ROOT') . '/config/rss.ini';
        $rss_url = file_get_contents($rss_file);

        if($this->is_online($rss_url)){
            $feed = \Web::instance()->rss($rss_url, 20);
            $base->set("sneed",$feed["feed"]);
            $base->set('online',TRUE);
        }else{
            $base->set('online',FALSE);
        }
        $base->set("content","news.html");
        echo \Template::instance()->render('index.html');

    }

    /**
     * @deprecated Již nepoužívaná funkce.
     * @param \Base $base
     * @param bool $online
     */
    function render_news(\Base $base,$online=TRUE)
    {
        if($online){
            $rss_file = $base->get('ROOT') . '/config/rss.ini';
            $rss_url = file_get_contents($rss_file);
            $feed = \Web::instance()->rss($rss_url, 20);
            $base->set("sneed",$feed["feed"]);
            $base->set('online',TRUE);
        }else{
            $base->set('online',FALSE);
        }
        $base->set("content","news.html");
        echo \Template::instance()->render('index.html');
    }

    /**
     * Vykreslí šablonu s návodem na ovládání úřední desky.
     * @param \Base $base
     */
    public function get_info(\Base $base)
    {
        $base->set("content","info.html");
        echo \Template::instance()->render('index.html');

    }
}