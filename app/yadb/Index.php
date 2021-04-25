<?php

namespace yadb;

/**
 * Class Index
 * Dědí z yadb\MainController, tato třída slouží pro základní funkčnost aplikace, instalace, přeinstalace,
 * idle režim a přesměrování na index.html.
 * @package yadb
 */

class Index extends MainController {

    /**
     * Instalace všech datových tříd ze složky yadb/data.
     * @param \Base $base
     */
    public function install(\Base $base)
    {
        $Stats = new Stats();
        $Stats->install($base);

        $ImageY = new ImageY();
        $ImageY->install($base);

        $User = new User();
        $User->install($base);
    }

    /**
     * Přeinstaluje již nainstalovanou aplikaci.
     * @param \Base $base
     */
    public function reinstall(\Base $base)
    {
        $pdf = $base->get('ROOT') . '/ui/pdf/';
        $xml = $base->get('ROOT') . '/ui/xml/';
        $uploads = $base->get('ROOT') . '/uploads/';

        $this->delete_files($pdf);
        $this->delete_files($xml);
        $this->delete_files($uploads);

        $installed_file = $base->get('ROOT') . '/config/installed.txt';
        unlink($installed_file);
        $logger = new \Log('system.log');
        $logger->write('Aplikace byla přeinstalována.','d.m.Y [H:i:s] O');
        $this->setup_everything($base);
    }

    /**
     * Nainstaluje webovou aplikaci a inicializuje databázi, pokud ještě není nainstalovaná, jestliže to dělá oprávněný uživatel - Administrátor.
     * @param $base
     */
    public function setup_everything(\Base $base)
    {
        if ($this->is_yadb_installed($base)) {
            \Flash::instance()->addMessage("Aplikace yadb již byla nainstalována, pokud chcete aplikaci přeinstalovat, smažte soubor installed.txt ve složce config a zavolejte /install znova (VAROVÁNÍ, toto nenávratně přeinstaluje celou aplikaci - vymaže databázi a všechna data)", 'danger');
            $base->reroute("/admin");
        } else {

            $config_path=$base->get('ROOT') . '/config';
            if (!file_exists($config_path)) mkdir($config_path,0777);

            $secret_file = $base->get('ROOT') . '/config/secret.ini';
            $secret = bin2hex(random_bytes(24));
            $base->write($secret_file,$secret);

            $this->install($base);

            $installed_file = $base->get('ROOT') . '/config/installed.txt';
            $base->write($installed_file,"0");

            $installed_file = $base->get('ROOT') . '/config/url.ini';
            $base->write($installed_file,"http://77.95.47.242/ost/xml/export.php?command=ud");

            $installed_file = $base->get('ROOT') . '/config/file_url.ini';
            $base->write($installed_file,"http://77.95.47.242/ost/upload/get_file.php?idf=");

            $installed_file = $base->get('ROOT') . '/config/cron_preset.ini';
            $base->write($installed_file,"10");

            $installed_file = $base->get('ROOT') . '/config/cron_restart.ini';
            $base->write($installed_file,"8");

            $installed_file = $base->get('ROOT') . '/config/rss.ini';
            $base->write($installed_file,"https://www.mmdecin.cz/index.php?format=feed&type=rss");


            $url ="http://77.95.47.242/ost/xml/export.php?command=ud";
            $xml = file_get_contents($url);
            $path = $base->get('ROOT') . "/ui/xml";
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
                file_put_contents($base->get('ROOT') . "/ui/xml/main_xml.xml", $xml, LOCK_EX);
            }

            $logger = new \Log('system.log');
            $logger->write("Aplikace yadb byla nainstalována.",'d.m.Y [H:i:s] O');

            $content = new \yadb\Content();
            $content->retrieve_xml_feed($base,array("/reinstall"));
        }
    }

    /**
     * Přesměruje uživatele na index.html.
     * @param \Base $base
     */
    public function index(\Base $base)
    {
        $base->set('content','carousel.html');
        $images = new data\ImageY();
        $base->set('images',$images->find(['id > ?',0]));
        if (empty($base->get('images')))$base->set('empty',TRUE);

        $base->set('active',true);

        echo \Template::instance()->render('index.html');
    }

    /**
     * Spustí režim idle.
     * @param \Base $base
     */
    public function idle(\Base $base)
    {
        $base->clear('SESSION');
        $base->set('SESSION.idle',TRUE);
        $base->set('SESSION.visitor',FALSE);
        $base->reroute('/');
    }
}