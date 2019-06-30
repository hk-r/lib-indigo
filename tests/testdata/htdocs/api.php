<?php
require_once(__DIR__.'/../../../vendor/autoload.php');

// indigo options
require(__DIR__.'/parameter.php');

// load indigo\ajax
$indigo = new indigo\ajax( call_parameter() );
echo $indigo->ajax_run();
