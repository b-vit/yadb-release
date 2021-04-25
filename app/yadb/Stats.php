<?php

namespace yadb;

/**
 * Class Stats
 * Třída Stats obsahuje funkcionalitu, díky které je možno měřit návštěvnost elektronické úřední desky.
 * @package yadb
 */
class Stats extends MainController {

    /**
     * Vytvoření a nastavení tabulky stat.
     * @param \Base $base
     */
    public function install(\Base $base)
    {
        $table_name = "stat";
        $schema = new \DB\SQL\Schema( $base->get('DB'));
        $this->drop_if_table_exists($schema,$table_name);

        $table = $schema->createTable($table_name);
        $table->addColumn('date')->type($schema::DT_DATE)->nullable(false);
        $table->addColumn('visitors')->type($schema::DT_INT)->nullable(false);
        $table->build();
    }

    /**
     * Zpracuje data z tabulky stats a vrátí je v poli. Voláno AJAXem ze šablony admin_dashboard_stats.html.
     * @param \Base $base
     */
    public function get_statistics(\Base $base)
    {
        $visitors = new data\Stats();
        $today = date('Y-m-d');
        $month = date('Y-m');
        $year = date('Y');

        $visitors->load(["date = ?",$today]);
        $today_total = $visitors->visitors;

        $this_month = $visitors->find(["date LIKE ?",'%'.$month.'%']);
        $this_month_total = 0;
        $this_month_array = [];
        $counter=0;
        foreach($this_month as $i){
            $this_month_total += $i->visitors;
            $this_month_array[$counter]['date']=date('d.m.Y',strtotime($i->date));
            $this_month_array[$counter]['visitors']=$i->visitors;
            $counter++;
        }

        usort($this_month_array, function($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        $this_year = $visitors->find(["date LIKE ?",'%'.$year.'%']);
        $this_year_total = 0;
        foreach($this_year as $j){
            $this_year_total += $j->visitors;
        }
        $alltime = $visitors->find(["id >= 0"]);
        $total = 0;
        foreach ($alltime as $k){
            $total += $k->visitors;
        }

        $js_stats_array['graph'] = $this_month_array;
        $js_stats_array['today'] = $today_total;
        $js_stats_array['month'] = $this_month_total;
        $js_stats_array['year']  = $this_year_total;
        $js_stats_array['total'] = $total;

        echo json_encode($js_stats_array);
    }

    /**
     * Vykresluje šablonu se statistikou návštěvnosti.
     * @param \Base $base
     * @throws \Exception
     */
    public function get_statistics_page(\Base $base)
    {
        $base->set('admin_content', 'admin_dashboard.html');
        $base->set('dashboard_content', 'admin_dashboard_stats.html');
        $base->set('current_user', $base->get('SESSION.user'));
        $csrf = bin2hex(random_bytes(24));
        $base->set('csrf',$csrf);
        $base->set('SESSION.csrf',  $base->get('csrf'));
        echo \Template::instance()->render('admin_index.html');
    }
}