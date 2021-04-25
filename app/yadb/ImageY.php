<?php

namespace yadb;

/**
 * Class ImageY
 * Třída ImageY se stará o funkcionalitu správy reklamy / obrázků, ty se zobrazují na desce v idle režimu.
 * @package yadb
 */
class ImageY extends MainController
{

    /**
     * Nastavení tabulky image.
     * @param \Base $base
     */
    public function install(\Base $base)
    {
        $table_name = "image";
        $schema = new \DB\SQL\Schema($base->get('DB'));
        $this->drop_if_table_exists($schema,$table_name);

        $table = $schema->createTable($table_name);
        $table->addColumn('name')->type($schema::DT_VARCHAR256)->nullable(false);
        $table->addColumn('owner')->type($schema::DT_INT)->nullable(false);
        $table->addColumn('size')->type($schema::DT_INT)->nullable(false);
        $table->addColumn('uploaded')->type($schema::DT_DATE)->nullable(false);
        $table->build();
    }

    /**
     * Vykreslení šablony pro správu obrázků.
     * @param \Base $base
     * @throws \Exception
     */
    public function get_images_page(\Base $base)
    {
        $base->set('admin_content', 'admin_dashboard.html');
        $base->set('dashboard_content', 'admin_dashboard_images.html');
        $base->set('current_user', $base->get('SESSION.user'));
        $csrf = bin2hex(random_bytes(24));
        $base->set('csrf',$csrf);
        $base->set('SESSION.csrf',  $base->get('csrf'));

        $images = new data\ImageY();
        $arr = $images->find(['id >= ?',0]);

        $base->set('images_array',$arr);

        echo \Template::instance()->render('admin_index.html');
    }

    /**
     * Zpracovává nahrání obrázku správcem.
     * @param \Base $base
     */
    public function post_images(\Base $base)
    {
        if ($base->get('POST.token') == $base->get('SESSION.csrf')) {
            $overwrite = false; // true=povolí přepsání souboru
            $slug = false; // true=přejmenuje soubor na filesystem-friendly
            $web = \Web::instance();
            $logger = new \Log('system.log');
            $file = $base->get('FILES');
            $imageFileType = strtolower(pathinfo($base->get('FILES[image][name]'), PATHINFO_EXTENSION));

            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                \Flash::instance()->addMessage("Uploadujte pouze formáty typu: JPG, JPEG, PNG.", 'danger');
                $base->reroute('/admin/images');
            }

            $image = new data\ImageY();
            if ($image->find(['name =?', $file['image']['name']])) {
                echo 'Soubor s tímto jménem je již na serveru.';
                \Flash::instance()->addMessage('Soubor: "' . $file['image']['name'] . '" se na serveru již nachází.', 'danger');
                $base->reroute('/admin/images');
            }

            $formFieldName = $base->get('POST.image');
            $files = $web->receive(function ($file, $formFieldName) {
                if ($file['size'] > (10 * 1024 * 1024)) // < 10 MB povoleno
                    return false;

                return true;
            },
                $overwrite,
                $slug
            );

            $img = new \Image($file['image']['name'],false,$base->get('ROOT').'/uploads/'); // Image z /lib
            $h=$img->height();
            $w=$img->width();
            $tfile = $base->get('ROOT')."/uploads/". $file['image']['name'];
            if($h < $w) { // Pokud nahraje obrázek na šířku
                \Flash::instance()->addMessage('Soubor: "' . $file['image']['name'] . '" nebyl nahrán, nahrávejte pouze obrázky na výšku! Nejlépe v poměru 9:16 (např. 1080x1920).', 'danger');
                unlink($tfile);
                $base->reroute('/admin/images');
            }

            $image->name = $file['image']['name'];
            $image->owner = $base->get('SESSION.user[id]');
            $image->size = $file['image']['size'];
            $image->uploaded = date('Y-m-d');
            $image->save();

            $logger->write('Administrátor '. $base->get('SESSION.user[name]').' nahrál obrázek '. $image->name .'.','d.m.Y [H:i:s] O');
            \Flash::instance()->addMessage('Soubor: "' . $file['image']['name'] . '" úspěšně nahrán.', 'success');
            $base->reroute('/admin/images');
        }else{
            $logger = new \Log('error.log');
            $logger->write('Pokus o CSRF útok.','d.m.Y [H:i:s] O');
        }
    }

    /**
     * Zpracovává smazání obrázku správcem.
     * @param \Base $base
     */
    public function delete_image(\Base $base)
    {
        $logger = new \Log('system.log');

        if ($base->get('POST.token') == $base->get('SESSION.csrf'))
        {
            $image = new data\ImageY();
            $image->load(['id = ?',$base->get('POST.i_id')]);
            $file = $base->get('ROOT')."/uploads/". $image->name;
            if (!unlink($file)) {
                \Flash::instance()->addMessage("Obrázek " . $image->name . " se nepodařilo odstranit.", 'success');
            }
            else {
                $logger->write('Administrátor '. $base->get('SESSION.user[name]').' vymazal obrázek: '. $image->name .'.','d.m.Y [H:i:s] O');
                \Flash::instance()->addMessage("Obrázek " . $image->name . " odstraněn", 'danger');
            }
            $image->erase();
            $image->save();
            $base->reroute('/admin/images');
        }
        else{
            $logger->write('Pokus o CSRF útok.','d.m.Y [H:i:s] O');
        }
    }
}