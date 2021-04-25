<?php

namespace yadb;

/**
 * Class MainController
 * Předek controller, ze kterého dědí všechny ostatní, nachází se zde různé užitečné funkce pro běh aplikace.
 * @package yadb
 */
class MainController
{
    /**
     * Funkce, kterou dědí všechny ostatní controllers a pomocí které se instaluje aplikace pro prvotní nastavení.
     * @param \Base $base - instance FatFreeFrameworku
     */
    public function install(\Base $base)
    {

    }

    /**
     * Vymaže rekurzivně soubory ve složce i s ní
     * Převzato z - https://paulund.co.uk/php-delete-directory-and-files-in-directory
     * @param string $target
     */
    function delete_files(string $target)
    {
        if (is_dir($target)) {
            $files = glob($target . '*', GLOB_MARK); //GLOB_MARK adds a slash to directories returned
            foreach ($files as $file) {
                $this->delete_files($file);
            }
            rmdir($target);
        } elseif (is_file($target)) {
            unlink($target);
        }
    }

    /**
     * Smaže tabulku, pokud již existuje.
     * @param \DB\SQL\Schema $schema
     * @param string $table
     */
    function drop_if_table_exists(\DB\SQL\Schema $schema, string $table)
    {
        if ($this->check_if_table_exists($schema, $table)) {
            $schema->dropTable($table);
        }
    }

    /**
     * Kontroluje, jestli v databázi existuje specifikovaná tabulka.
     * @param DB\SQL\Schema $schema - instance DB schema builderu z F3
     * @param $table - název tabulky
     *
     * @return bool
     */
    function check_if_table_exists(\DB\SQL\Schema $schema, string $table): bool
    {
        if (in_array($table, $schema->getTables())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Kontroluje, jestli je aplikace připojená online, nebo na určený server.
     * @return bool
     */
    function is_online(string $host = "https://www.google.com"): bool
    {
        $host = parse_url($host,PHP_URL_HOST);
        if (!$sock = @fsockopen($host, 80)) return false; // Kontrola připojení k serveru
        else return true;
    }

    /**
     * Implementace funkce beforeRoute z FatFreeFrameworku, volá se před každým přesměrováním.
     * @param \Base $base
     */
    public function beforeRoute(\Base $base)
    {
        $this->rules($base);
        $this->check_idle();
        $this->check_handicap();
        \Base::instance()->set('title', 'yadb');
    }

    /**
     * Kontrola přístupových práv uživatelů
     * @param \Base $base
     */
    public function rules(\Base $base)
    {
        $access = \Access::instance();
        /* Defaultně povoleny všechny routes */
        $access->policy('allow');
        $access->deny('/install');

        /* Zakázány všechny routes /admin/ kromě pro Admina a Moderatora */
        $access->deny('/admin/*');
        $access->allow('/admin/*', 'Admin');
        $access->allow('/admin/*', 'Moderator');

        /*Zákaz některých /admin/ routes pro moderatora -> nemůže mazat adminy apod. */
        $access->deny('POST /admin/administrators', 'Moderator');
        $access->deny('/admin/remove_admin', 'Moderator');
        $access->deny('/admin/edit_admin', 'Moderator');
        $access->deny('/admin/config', 'Moderator');
        $access->deny('/admin/config/*', 'Moderator');
        $access->deny('/admin/reinstall', 'Moderator');

        /* Pokud je aplikace nainstalovaná, může jí přeinstalovat pouze admin, pokud ještě není nainstalovaná, může jí nainstalovat kdokoliv -> bez účtu */
        if ($this->is_yadb_installed($base)) $access->allow('/install', 'Admin');
        else $access->allow('/install');

        $user = $base->get('SESSION.user[role]');
        $access->authorize($user);
    }

    /**
     * Kontroluje, jestli je aplikace yadb již na tomto stroji nainstalovaná
     * pomocí textového souboru vytvořeného při instalaci
     * @param \Base $base
     *
     * @return bool
     */
    function is_yadb_installed(\Base $base): bool
    {
        $filename = $base->get('ROOT') . '/config/installed.txt';
        return file_exists($filename);
    }

    /**
     * Zkontroluje, jestli je aplikace v idle režimu podle session a nastaví hive proměnnou pro další zpracování v templatech
     */
    public function check_idle()
    {
        $idle = \Base::instance()->get('SESSION.idle');
        if ($idle == true) {
            \Base::instance()->set('idle', TRUE);
        } else {
            \Base::instance()->set('idle', FALSE);
        }
    }

    /**
     * Zkontroluje, jestli je aplikace v režimu handicap a nastaví hive proměnnou pro další zpracování v templatech
     */
    public function check_handicap()
    {
        $handicap = \Base::instance()->get('SESSION.handicap');
        if ($handicap == true) {
            \Base::instance()->set('handicap', TRUE);
        } else {
            \Base::instance()->set('handicap', FALSE);
        }
    }

    /**
     * Nastavení návštěvnosti
     */
    function visits()
    {
        $visits = new data\Stats();
        $today = date('Y-m-d');

        if ($visits->find(['date = ?', $today]) == FALSE) {
            /* Nenašel se dnešek, vytvořím a nastavím na 1 */
            $visits->visitors = 1;
            $visits->date = $today;
            $visits->save();
        } else {
            /* Našel se dnešek, přidám +1 */
            $visits->load(['date = ?', $today]);
            $visits->visitors = $visits->visitors + 1;
            $visits->save();
        }
    }

    /**
     * Restartování desky
     * @param \Base $base
     */
    public function restart(\Base $base)
    {
        exec('nohup sudo /sbin/reboot');
    }

    public function test_cron(\Base $base)
    {
        $logger = new \Log('system.log');
        $logger->write('TESTCRON','d.m.Y [H:i:s] O');
    }
}