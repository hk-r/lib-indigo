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
$indigo_stdout = $indigo->run();
?>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>Indigo</title>

		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js"></script>
		<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">

		<link rel="stylesheet" href="/res/bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" href="/res/styles/common.css">
		<script src="/res/bootstrap/js/bootstrap.min.js"></script>
		<script src="/res/scripts/common.js"></script>

		<script>
			$(function() {
				
				var dateFormat = 'yy-mm-dd';
				
				$.datepicker.setDefaults($.datepicker.regional["ja"]);
				
				$("#datepicker").datepicker({
					dateFormat: dateFormat
				});
			});
		</script>

		<style>
			.theme-outline{
				padding: 14px;
			}
		</style>
	</head>
	<body>
		<div class="theme-outline">
			<h1>Indigo Test</h1>

			<div class="contents">
<?php
echo $indigo_stdout;
?>
			</div>
		</div>
	</body>
</html>
