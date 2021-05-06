<?php

// Kickstart the framework
$yadb=require('lib/base.php');

// DEBUG=3 debugging -> VYPNOUT VE FINAL VERZI!!!!!!!!!!!!
if ((float)PCRE_VERSION<8.0)
    trigger_error('PCRE version is out of date');

// Load configuration
$yadb->config('config.ini');
$yadb->set('DB', new \DB\SQL($yadb->get('db.dsn'),$yadb->get('db.username'),$yadb->get('db.password')));

$yadb->run();
