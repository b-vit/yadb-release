<?php

namespace yadb;

/**
 * Class User
 * @package yadb
 * Třída uživatel implementuje rozhraní pro ovládání aplikace yadb přihlášenými uživateli - správci.
 */

class User extends MainController
{
    /**
     * Implementace funkce install, vytvoří databázové tabulky users a role.
     * @param \Base $base
     */
    public function install(\Base $base)
    {
        $table_name = "y_user";
        $schema = new \DB\SQL\Schema($base->get('DB'));
        $this->drop_if_table_exists($schema, $table_name);

        $table = $schema->createTable($table_name);
        $table->addColumn('name')->type($schema::DT_VARCHAR256)->nullable(false);
        $table->addColumn('role')->type($schema::DT_INT)->nullable(false);
        $table->addColumn('password')->type($schema::DT_VARCHAR256)->nullable(false);

        $table->build();

        $table_name = "role";
        $this->drop_if_table_exists($schema, $table_name);

        $table = $schema->createTable($table_name);
        $table->addColumn('id')->type($schema::DT_INT);
        $table->addColumn('name')->type($schema::DT_VARCHAR256)->nullable(false);
        $table->addColumn('permissions')->type($schema::DT_INT)->nullable(false);
        $table->primary(array('id', 'permissions'));
        $table->build();


        $role_mod = new data\Roles();
        $role_mod->name = "Moderator";
        $role_mod->permissions = 1;
        $role_mod->save();

        $role_admin = new data\Roles();
        $role_admin->name = "Admin";
        $role_admin->permissions = 2;
        $role_admin->save();
    }

    /**
     * Vykresluje šablonu admin_index.html.
     * @param \Base $base
     */
    public function get_admin_register(\Base $base)
    {
        $base->set('admin_content', 'admin_register.html');
        echo \Template::instance()->render('admin_index.html');
    }

    /**
     * Zpracovává registraci hlavního administrátora.
     * @param \Base $base
     */
    public function post_admin_register(\Base $base)
    {
        $secret_file = $base->get('ROOT') . '/config/secret.ini';
        $secret = $base->read($secret_file);
        if ($_POST['secret'] == $secret) {
            $user = new data\User();
            if ($user->find(array('name=?', $base->get('POST.name'))) == true) {
                \Flash::instance()->addMessage('Jméno je již zabrané, zvolte jiné.', 'danger');
                $base->reroute('/register');
            } elseif ($base->get('POST.name') == $base->get('POST.password')) {
                \Flash::instance()->addMessage('Heslo se shoduje s uživatelským jménem.', 'danger');
                $base->reroute('/register');
            } else {
                $user->name = $_POST['name'];
                $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $user->role = 2;
                $user->save();
                \Flash::instance()->addMessage('Administrátor ' . $user->name . ' zaregistrován.', 'success');
                $base->reroute('/admin');
            }
        } else {
            \Flash::instance()->addMessage('Neznáte tajné heslo pro registraci! Pokud jste správce aplikace, naleznete ho ve složce config v souboru secret.ini', 'danger');
            $logger = new \Log('error.log');
            $logger->write('Někdo se pokusil zaregistrovat účet administrátora, bez znalosti tajného hesla.', 'd.m.Y [H:i:s] O');
            $base->reroute('/admin');
        }
    }

    /**
     * Vykresluje šablonu admin_index.html.
     * @param \Base $base
     */
    public function get_admin_login(\Base $base)
    {
        $base->set('admin_content', 'admin_login.html');
        echo \Template::instance()->render('admin_index.html');
    }

    /**
     * Zpracovává přihlášení správce.
     * @param \Base $base
     */
    public function post_admin_login(\Base $base)
    {
        $user = new data\User();
        $user->load(['name=?', $base->get('POST.name')]);
        $postpass = $_POST['password'];

        if (password_verify($postpass, $user->password)) {
            $base->set('SESSION.user[id]', $user->id);
            $base->set('SESSION.user[name]', $user->name);
            $base->set('SESSION.user[role]', $user->role->name);
            $logger = new \Log('user.log');
            $logger->write('Uživatel ' . $user->name . ' se přihlásil.', 'd.m.Y [H:i:s] O');
            $base->reroute('/admin/dashboard');
        } else {
            \Flash::instance()->addMessage("Zadali jste špatné uživatelské jméno nebo heslo, zkuste to znovu.", 'danger');
            $base->reroute('/admin');
        }
    }

    /**
     * Zpracovává odhlášení správce.
     * @param \Base $base
     */
    public function get_admin_logout(\Base $base)
    {
        $base->set('admin_content', 'admin_login.html');
        $logger = new \Log('user.log');
        $logger->write('Uživatel ' . $base->get('SESSION.user[name]') . ' se odhlásil.', 'd.m.Y [H:i:s] O');
        $base->clear('SESSION.user');
        \Flash::instance()->addMessage("Byli jste úspěšně odhlášeni." . $base->get('SESSION.user[name]'), 'success');
        $base->reroute('/admin');
    }

    /**
     * Vykreslí šablonu se všemi zaregistrovanými správci.
     * @param \Base $base
     * @throws \Exception
     */
    public function get_admin_administrators(\Base $base)
    {
        $base->set('admin_content', 'admin_dashboard.html');
        $base->set('dashboard_content', 'admin_dashboard_administrators.html');
        $base->set('current_user', $base->get('SESSION.user'));


        $users = new data\User();
        $base->set('moderators', $users->find(array('id >?', 0)));

        $csrf = bin2hex(random_bytes(24));
        $base->set('csrf', $csrf);
        $base->set('SESSION.csrf', $base->get('csrf'));

        echo \Template::instance()->render('admin_index.html');
    }


    /**
     * Zpracovává vytváření správců hlavním administrátorem.
     * @param \Base $base
     */
    public function post_admin_administrators(\Base $base)
    {
        if ($base->get('POST.token') == $base->get('SESSION.csrf')) {
            $user = new data\User();

            if ($user->find(array('name=?', $base->get('POST.name'))) == true) {
                \Flash::instance()->addMessage('Jméno je již zabrané, zvolte jiné.', 'danger');
                $base->reroute('/admin/administrators');
            } elseif ($base->get('POST.password') != $base->get('POST.password_check')) {
                \Flash::instance()->addMessage('Hesla se neshodují! ', 'danger');
                $base->reroute('/admin/administrators');
            } elseif ($base->get('POST.name') == $base->get('POST.password')) {
                \Flash::instance()->addMessage('Heslo se shoduje s uživatelským jménem.', 'danger');
                $base->reroute('/admin/administrators');
            } else {
                $user->name = $_POST['name'];
                $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $user->role = 1;
                $user->save();
                \Flash::instance()->addMessage('Správce ' . $user->name . ' zaregistrován.', 'success');

                $logger = new \Log('user.log');
                $logger->write('Administrátor ' . $base->get('SESSION.user[name]') . ' zaregistroval správce ' . $user->name . '.', 'd.m.Y [H:i:s] O');

                $base->reroute('/admin/administrators');
            }
        } else {
            $logger = new \Log('error.log');
            $logger->write('Pokus o CSRF útok.', 'd.m.Y [H:i:s] O');
        }
    }


    /**
     * Vykresluje hlavní dashboard pro správce a získává informace o provozu desky.
     * @param \Base $base
     */
    public function get_admin_dashboard(\Base $base)
    {
        $base->set('admin_content', 'admin_dashboard.html');
        $base->set('dashboard_content', 'admin_dashboard_home.html');
        $base->set('current_user', $base->get('SESSION.user'));
        $sys_inf = $this->sys_info($base);
        $t = $sys_inf['uptime']; //Uptime v sekundách
        $sys_inf['uptime'] = sprintf('%02d:%02d:%02d', ($t / 3600), ($t / 60 % 60), $t % 60);
        $sys_inf['temp']=intval($sys_inf['temp'])/1000;
        $base->set('system_info', $sys_inf);
        echo \Template::instance()->render('admin_index.html');
    }

    /**
     * Dekóduje JSON soubor ve kterém jsou informace z operačního systému.
     * @param \Base $base
     * @return mixed
     */
    function sys_info(\Base $base)
    {
        $file = join(DIRECTORY_SEPARATOR, array($base->get('ROOT'), 'logs', 'system_info.json'));
        $jstr = file_get_contents($file);
        $system_info = json_decode($jstr, true);
        return $system_info;
    }

    /**
     * Zpracovává odstranění správce.
     * @param \Base $base
     */
    public function post_remove_admin(\Base $base)
    {
        $a_id = $base->get('POST.a_id');
        $user = new data\User();
        $user->load(['id =?', $a_id]);
        \Flash::instance()->addMessage("Správce " . $user->name . " odstraněn.", 'success');
        $logger = new \Log('user.log');
        $logger->write('Administrátor ' . $base->get('SESSION.user[name]') . ' vymazal správce ' . $user->name . '.', 'd.m.Y [H:i:s] O');
        $user->erase();
        $user->save();
        $base->reroute('/admin/administrators');
    }

    /**
     * Zpracovává úpravu správce hlavním administrátorem.
     * @param \Base $base
     */
    public function post_edit_admin(\Base $base)
    {
        if ($base->get('POST.token') == $base->get('SESSION.csrf')) {
            $logger = new \Log('user.log');
            $user = new data\User();
            $user->load(['id = ?', $base->get('POST.a_id')]);
            $old_name = $user->name;
            $postpass = $_POST['new-password'];
            if ($postpass != $base->get('POST.new-password_check')) {
                \Flash::instance()->addMessage('Hesla se neshodují! ', 'danger');
                $base->reroute('/admin/administrators');
            } elseif ($user->name == $postpass) {
                \Flash::instance()->addMessage('Heslo se shoduje s uživatelským jménem.', 'danger');
                $base->reroute('/admin/administrators');
            } else {
                if ($user->name != $_POST['new-name'] and !empty($_POST['new-name'])) {
                    $user->name = $_POST['new-name'];
                    $logger->write('Administrátor ' . $base->get('SESSION.user[name]') . ' upravil správce ' . $old_name . ' na ' . $user->name . '.', 'd.m.Y [H:i:s] O');
                }
                if (!password_verify($postpass, $user->password) and !empty($postpass)) {
                    $user->password = password_hash($postpass, PASSWORD_DEFAULT);
                    $logger->write('Administrátor ' . $base->get('SESSION.user[name]') . ' změnil správci ' . $user->name . ' heslo.', 'd.m.Y [H:i:s] O');
                }
                $user->save();
                \Flash::instance()->addMessage('Správce ' . $user->name . ' upraven.', 'success');

                $base->reroute('/admin/administrators');
            }
        } else {
            $logger = new \Log('error.log');
            $logger->write('Pokus o CSRF útok.', 'd.m.Y [H:i:s] O');
        }
    }

    /**
     * Vykreslí šablonu s logy.
     * @param \Base $base
     */
    public function get_admin_logs(\Base $base)
    {
        $base->set('admin_content', 'admin_dashboard.html');
        $base->set('dashboard_content', 'admin_dashboard_logs.html');
        $base->set('current_user', $base->get('SESSION.user'));
        $user_log = file_get_contents($base->get('ROOT') . "/logs/user.log");
        $base->set('user_log', $user_log);
        $error_log = file_get_contents($base->get('ROOT') . "/logs/error.log");
        $base->set('error_log', $error_log);
        $system_log = file_get_contents($base->get('ROOT') . "/logs/system.log");
        $base->set('system_log', $system_log);
        echo \Template::instance()->render('admin_index.html');
    }

    /**
     * Vykreslí šablonu s nastavením správcova účtu.
     * @param \Base $base
     * @throws \Exception
     */
    public function get_admin_settings(\Base $base)
    {
        $base->set('admin_content', 'admin_dashboard.html');
        $base->set('dashboard_content', 'admin_dashboard_user_settings.html');
        $base->set('current_user', $base->get('SESSION.user'));

        $csrf = bin2hex(random_bytes(24));
        $base->set('csrf', $csrf);
        $base->set('SESSION.csrf', $base->get('csrf'));

        echo \Template::instance()->render('admin_index.html');
    }

    /**
     * Zpracovává úpravu nastavení správcova účtu.
     * @param \Base $base
     */
    public function post_admin_settings(\Base $base)
    {
        if ($base->get('POST.token') == $base->get('SESSION.csrf')) {
            $logger = new \Log('user.log');
            $user = new data\User();
            $user->load(['id = ?', $base->get('SESSION.user[id]')]);

            $old_name = $user->name;
            $new_name = $base->get('POST.name');
            $old_password = $base->get('POST.password');
            $new_password = $base->get('POST.new_password');
            $new_password_check = $base->get('POST.new_password_check');

            if ($new_password != $new_password_check) {
                \Flash::instance()->addMessage('Nová hesla se neshodují! ', 'danger');
                $base->reroute('/admin/settings');
            } elseif ($user->name == $new_password or $new_name == $new_password) {
                \Flash::instance()->addMessage('Heslo se shoduje s uživatelským jménem.', 'danger');
                $base->reroute('/admin/settings');
            } elseif (!password_verify($old_password, $user->password)) {
                \Flash::instance()->addMessage('Nezadali jste správně své dosavadní heslo.', 'danger');
                $base->reroute('/admin/settings');
            } else {
                if ($old_name != $new_name and !empty($new_name)) {
                    $user->name = $new_name;
                    $logger->write('Správce ' . $base->get('SESSION.user[name]') . ' upravil své jméno na ' . $user->name . '.', 'd.m.Y [H:i:s] O');
                }
                if (!password_verify($new_password, $user->password) and !empty($new_password)) {
                    $user->password = password_hash($new_password, PASSWORD_DEFAULT);
                    $logger->write('Správce ' . $base->get('SESSION.user[name]') . ' změnil své heslo.', 'd.m.Y [H:i:s] O');
                }
                $user->save();
                $base->set('SESSION.user[name]', $user->name);
                \Flash::instance()->addMessage('Váš profil byl upraven.', 'success');

                $base->reroute('/admin/dashboard');
            }
        } else {
            $logger = new \Log('error.log');
            $logger->write('Pokus o CSRF útok.', 'd.m.Y [H:i:s] O');
        }
    }

    /**
     * Vykresluje šablonu "informace o programu".
     * @param \Base $base
     */
    public function get_admin_info(\Base $base)
    {
        $base->set('admin_content', 'admin_dashboard.html');
        $base->set('dashboard_content', 'admin_dashboard_info.html');
        $base->set('current_user', $base->get('SESSION.user'));
        echo \Template::instance()->render('admin_index.html');
    }

    /**
     * Vykresluje šablonu konfigurace aplikace yadb.
     * @param \Base $base
     */
    public function get_admin_config(\Base $base)
    {
        $base->set('admin_content', 'admin_dashboard.html');
        $base->set('dashboard_content', 'admin_dashboard_config.html');

        $file = $base->get('ROOT') . '/config/cron_restart.ini';
        $base->set('current_restart_time', $base->read($file));

        $file = $base->get('ROOT') . '/config/cron_preset.ini';
        $base->set('current_update_time', $base->read($file));

        $file = $base->get('ROOT') . '/config/url.ini';
        $base->set('current_xml_feed', $base->read($file));

        $file = $base->get('ROOT') . '/config/file_url.ini';
        $base->set('current_files', $base->read($file));

        $file = $base->get('ROOT') . '/config/rss.ini';
        $base->set('rss', $base->read($file));

        $base->set('current_user', $base->get('SESSION.user'));

        \Flash::instance()->addMessage('Špatné nastavení může vést ke ztrátě funkčnosti desky a nutnosti přeinstalace. Měňte pouze v případě, že víte, co děláte.', 'warning');
        echo \Template::instance()->render('admin_index.html');
    }

    /**
     * Zpracovává změnu konfigurace aplikace.
     * @param \Base $base
     */
    public function post_admin_config(\Base $base)
    {
        $url = $base->get('POST.new_url_xml');
        $urlf = $base->get('POST.new_url_files');
        $hours = $base->get('POST.hours');
        $minutes = $base->get('POST.minutes');
        $rss = $base->get('POST.new_rss');

        $audit = \Audit::instance();
        $logger = new \Log('system.log');

        if (!empty($url)) {
            if ($audit->url($url)) {
                $configured_file = $base->get('ROOT') . '/config/url.ini';
                $base->write($configured_file, $url);
                $logger->write('URL adresa pro XML feed byl upravena na ' . $url . "  - " . $base->get('SESSION.user.name'), 'd.m.Y [H:i:s] O');
                \Flash::instance()->addMessage('URL adresa pro XML feed byla upravena na ' . $url, 'success');
            } else {
                \Flash::instance()->addMessage('Zadejte platnou URL pro XML feed', 'danger');
            }
        }

        if (!empty($urlf)) {
            if ($audit->url($urlf)) {
                $configured_file = $base->get('ROOT') . '/config/file_url.ini';
                $base->write($configured_file, $urlf);
                $logger->write('URL adresa pro PDF soubory byla upravena na ' . $urlf . "  - " . $base->get('SESSION.user.name'), 'd.m.Y [H:i:s] O');
                \Flash::instance()->addMessage('URL adresa pro PDF soubory byla upravena na ' . $url, 'success');
            } else {
                \Flash::instance()->addMessage('Zadejte platnou URL pro soubory', 'danger');
            }
        }

        if (!empty($hours)) {
            if (is_numeric($hours) and intval($hours) <= 23 and intval($hours) >= 2) {
                $configured_file = $base->get('ROOT') . '/config/cron_restart.ini';
                $base->write($configured_file, $hours);
                $logger->write('Interval restartování desky byl upraven na ' . $hours . " h",'d.m.Y [H:i:s] O');
                \Flash::instance()->addMessage('Interval restartování desky byl upraven na ' . $hours . " h", 'success');
            } else {
                \Flash::instance()->addMessage('Zadejte hodiny mezi 2 až 23, například 10', 'danger');
            }
        }

        if (!empty($minutes)) {
            if (is_numeric($minutes) and intval($minutes) <= 59 and intval($minutes) >= 5) {
                $configured_file = $base->get('ROOT') . '/config/cron_preset.ini';
                $base->write($configured_file, $minutes);
                $logger->write('Interval aktualizace byl upraven na ' . $minutes . " m",'d.m.Y [H:i:s] O');
                \Flash::instance()->addMessage('Interval aktualizace byl upraven na ' . $minutes . " m", 'success');
            } else {
                \Flash::instance()->addMessage('Zadejte minuty mezi 5 až 59, například 10', 'danger');
            }
        }

        if (!empty($rss)) {
            if ($audit->url($rss)) {
                $configured_file = $base->get('ROOT') . '/config/rss.ini';
                $base->write($configured_file, $rss);
                $logger->write('URL adresa pro RSS feed s novinkama byla upravena na ' . $rss . "  - " . $base->get('SESSION.user.name'), 'd.m.Y [H:i:s] O');
                \Flash::instance()->addMessage('URL adresa pro RSS feed s novinkama byla upravena na ' . $rss , 'success');
            } else {
                \Flash::instance()->addMessage('Zadejte platnou URL s RSS feedem', 'danger');
            }
        }

        $base->reroute('/admin/config');
    }

    /**
     * Smaže logy.
     * @param \Base $base
     */
    public function delete_logs(\Base $base)
    {
        $logs = $base->get('ROOT') . '/logs/';
        $this->delete_files($logs);
        mkdir($logs);
        $logger = new \Log('system.log');
        $logger->write('Logy vymazány.','d.m.Y [H:i:s] O');
        $logger = new \Log('user.log');
        $logger->write('Logy vymazány.','d.m.Y [H:i:s] O');
        $logger = new \Log('error.log');
        $logger->write('Logy vymazány.','d.m.Y [H:i:s] O');
        $base->write($logs.DIRECTORY_SEPARATOR."system_info.json"," ");
        \Flash::instance()->addMessage('Logy byly vymazány.', 'success');
        $base->reroute('/admin/config');
    }
}