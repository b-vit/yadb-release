<?php

// Kickstart the framework
$yadb=require('lib/base.php');

// DEBUG=3 debugging -> VYPNOUT VE FINAL VERZI!!!!!!!!!!!!
if ((float)PCRE_VERSION<8.0)
    trigger_error('PCRE version is out of date');

// Load configuration
$yadb->config('config.ini');
$yadb->set('DB', new \DB\SQL($yadb->get('db.dsn'),$yadb->get('db.username'),$yadb->get('db.password')));

$cron=Cron::instance();
$cron->log=TRUE;
$cron->set('Restart','MainController->restart','*/5 * * * *'); // Každých 5 minut
$cron->set('Update','MainController->test_cron','*/1 * * * *'); // Každou minutu

$yadb->run();
