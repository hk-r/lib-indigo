<?php

function call_parameter () {

	$parameter = array(
		// POST
		'_POST' => $_POST,

		// GET
		'_GET' => $_GET,

		// フォーム送信時に付加する追加のパラメータ (省略可)
		'additional_params' => array(
			'hoge' => 'fuga',
		),

		// indigo作業用ディレクトリ（絶対パス）
		'realpath_workdir' => __DIR__.'/../indigo_dir/',

		// リソースディレクトリ（ドキュメントルートからの相対パス）
		'relativepath_resourcedir'	=> './../../../res/',

		// ajax呼出クラス（ドキュメントルートからの相対パス）
		'url_ajax_call' => './api.php',

		// 画面表示上のタイムゾーン
		'time_zone' => 'Asia/Tokyo',

		// ユーザID
		'user_id' => 'user01',

		// DB設定
		'db' => array(

			// 'mysql' or null（nullの場合はSQLite3を使用）　※バージョン0.1.0時点ではmysql未対応
			'dbms' => null,
		),

		// 予約最大件数
		'max_reserve_record' => 10,

		// バックアップ世代管理件数　※バージョン0.1.0時点では未対応
		'max_backup_generation' => 5,

		// 本番環境パス（同期先）※バージョン0.1.0時点では先頭の設定内容のみ有効
		'server' => array(
			array(
				// 任意の名前
				'name' => 'server1',
				// 同期先絶対パス
				'real_path' => __DIR__.'/../honban1/'
			),
		),

		// 同期除外ディレクトリ、またはファイル
		'ignore' => array(
			'.git',
			'.htaccess'
		),

		// Git情報定義
		'git' => array(

			// Gitリポジトリのurl（現在はhttpsプロトコルのみ対応）
			'giturl' => 'https://github.com/gk-r/indigo-test-project.git',

			// ユーザ名
			// Gitリポジトリのユーザ名を設定
			'username' => 'hoge',

			// パスワード
			// Gitリポジトリのパスワードを設定
			'password' => 'fuga'
		)
	);
	return $parameter;
};
