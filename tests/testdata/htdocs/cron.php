<?php
require_once(__DIR__.'/../../../vendor/autoload.php');
$fs = new \tomk79\filesystem();
if( !is_dir( __DIR__.'/../indigo_dir/' ) ){
	$fs->mkdir( __DIR__.'/../indigo_dir/' );
}
if( !is_dir( __DIR__.'/../honban1/' ) ){
	$fs->mkdir( __DIR__.'/../honban1/' );
}

// indigo options
require(__DIR__.'/parameter.php');

$indigo = new indigo\main( call_parameter() );
echo $indigo->cron_run();

exit;
