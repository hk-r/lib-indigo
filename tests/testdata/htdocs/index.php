<?php
require_once('../../../vendor/autoload.php');
$conf = json_decode(file_get_contents(__DIR__.'/../../../config/default.json'), true);
$conf['git']['repository'] = __DIR__.'/../repos/master/';
?>
<!doctype html>
<html>
<head>
<title>indigo - develop</title>
<link rel="stylesheet" href="../../../res/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../../../res/styles/common.css">

<script src="../../../res/bootstrap/js/bootstrap.min.js"></script>
<script src="../../../res/scripts/common.js"></script>
</head>
<body>
<h1>indigo</h1>

<?php
// Plum
$indigo = new indigo\main(
	array(
		// POST
		'_POST' => $_POST,

		// GET
		'_GET' => $_GET,

		// プレビューサーバ定義
		'preview_server' => array(

			// プレビューサーバの数だけ設定する
			//
			//   string 'name':
			//     - プレビューサーバ名(任意)
			//   string 'path':
			//     - プレビューサーバ(デプロイ先)のパス
			//   string 'url':
			//     - プレビューサーバのURL
			//       Webサーバのvirtual host等で設定したURL
			//
			array(
				'name' => 'preview1',
				'path' => __DIR__.'/../repos/preview1/',
				'url' => 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])).'/repos/preview1/tests/testdata/contents/',
			),
			array(
				'name' => 'preview2',
				'path' => __DIR__.'/../repos/preview2/',
				'url' => 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])).'/repos/preview2/tests/testdata/contents/',
			),
			array(
				'name' => 'preview3',
				'path' => __DIR__.'/../repos/preview3/',
				'url' => 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])).'/repos/preview3/tests/testdata/contents/',
			)
		),

		// Git情報定義
		'git' => $conf['git'],
	)
);
// return: 結果表示用HTML
echo $indigo->run();
?>
</body>
</html>
