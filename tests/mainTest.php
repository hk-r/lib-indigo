<?php
/**
 * test for Plum
 */
class mainTest extends PHPUnit_Framework_TestCase{

	public function testEqual() {
	    // 期待値
	    $expected = 5;
	    // 実際の値
	    $actual = 2 + 4;
	    // チェック
	    $this->assertEquals($expected, $actual);
	}

	private $options = array();
	private $fs;

	public function setup(){
		mb_internal_encoding('UTF-8');
		$this->fs = new tomk79\filesystem();
		$this->options = array(
			'_POST' => array(),
			'_GET' => array(),
		// indigo作業用ディレクトリ（絶対パス）
		'realpath_workdir'	 	=> __DIR__.'/testdata/indigo_dir/',

		// リソースディレクトリ（ドキュメントルートからの相対パス）
		'relativepath_resourcedir'	=> __DIR__.'/../res/',

		// ajax呼出クラス（ドキュメントルートからの相対パス）
		'realpath_ajax_call'		=> './ajax.php',

		// 画面表示上のタイムゾーン
		'time_zone' => 'Asia/Tokyo',

		// ユーザID
		'user_id' => 'user01',

		// DB設定
		'db' => array(
			// 'mysql' or null（nullの場合はSQLite3を使用する）
			'db_type' => null,
			'mysql_db_name' => '',
			'mysql_db_host' => '',
			'mysql_db_user' => '',
			'mysql_db_pass' => ''
		),

		// 予約最大件数
		'max_reserve_record' => 10,

		// 本番環境パス（同期先）※バージョン0.1.0時点では先頭の設定内容のみ有効
		'server' => array(
				array(
						'name' => 'server1',
						'real_path' => __DIR__.'/testdata/honban1/'
				),
				array(
						'name' => 'server2',
						'real_path' => __DIR__.'/testdata/honban2/'
				)
		),

		// 同期除外ディレクトリ、またはファイル
		'ignore' => array(
			'.git',
			'.htaccess'
		),

		// Git情報定義
		'git' => array(
			'giturl' => 'https://github.com/gk-r/indigo-test-project.git',
			'username' => 'hoge',
			'password' => 'fuga'
		);


		// 	'preview_server' => array(
		// 		array(
		// 			'name' => 'preview1',
		// 			'path' => __DIR__.'/testdata/repos/preview1/',
		// 			'url' => 'http://example.com/repos/preview1/',
		// 		),
		// 		array(
		// 			'name' => 'preview2',
		// 			'path' => __DIR__.'/testdata/repos/preview2/',
		// 			'url' => 'http://example.com/repos/preview2/',
		// 		),
		// 		array(
		// 			'name' => 'preview3',
		// 			'path' => __DIR__.'/testdata/repos/preview3/',
		// 			'url' => 'http://example.com/repos/preview3/',
		// 		)
		// 	),
		// 	'git' => array(
		// 		'url' => 'https://github.com/pickles2/lib-plum.git',
		// 		'repository' => __DIR__.'/testdata/repos/master/',
		// 	),
		// );
	}

	// private $options = array();
	// private $fs;

	// public function setup(){
	// 	mb_internal_encoding('UTF-8');
	// 	$this->fs = new tomk79\filesystem();
	// 	$this->options = array(
	// 		'_POST' => array(),
	// 		'_GET' => array(),
	// 		'preview_server' => array(
	// 			array(
	// 				'name' => 'preview1',
	// 				'path' => __DIR__.'/testdata/repos/preview1/',
	// 				'url' => 'http://example.com/repos/preview1/',
	// 			),
	// 			array(
	// 				'name' => 'preview2',
	// 				'path' => __DIR__.'/testdata/repos/preview2/',
	// 				'url' => 'http://example.com/repos/preview2/',
	// 			),
	// 			array(
	// 				'name' => 'preview3',
	// 				'path' => __DIR__.'/testdata/repos/preview3/',
	// 				'url' => 'http://example.com/repos/preview3/',
	// 			)
	// 		),
	// 		'git' => array(
	// 			'url' => 'https://github.com/pickles2/lib-plum.git',
	// 			'repository' => __DIR__.'/testdata/repos/master/',
	// 		),
	// 	);
	// }

	// private function clear_repos(){
	// 	$this->chmod_r();//パーミッションを変えないと削除できない
	// 	if( !$this->fs->rm(__DIR__.'/testdata/repos/') ){
	// 		var_dump('Failed to cleaning test data directory.');
	// 	}
	// 	clearstatcache();
	// 	$this->fs->mkdir_r(__DIR__.'/testdata/repos/');
	// 	touch(__DIR__.'/testdata/repos/.gitkeep');
	// 	clearstatcache();
	// }
	// private function chmod_r($path = null){
	// 	$base = __DIR__.'/testdata/repos';
	// 	// var_dump($base.'/'.$path);
	// 	$this->fs->chmod($base.'/'.$path , 0777);
	// 	if(is_dir($base.'/'.$path)){
	// 		$ls = $this->fs->ls($base.'/'.$path);
	// 		foreach($ls as $basename){
	// 			$this->chmod_r($path.'/'.$basename);
	// 		}
	// 	}
	// }


	// /**
	//  * Initialize
	//  */
	// public function testInitialize(){
	// 	$this->clear_repos();

	// 	// Plum
	// 	$options = $this->options;
	// 	$plum = new hk\plum\main( $options );
	// 	$stdout = $plum->run();
	// 	// var_dump($stdout);

	// 	$this->assertTrue( strpos($stdout, 'Initializeを実行してください。') !== false );

	// 	$options = $this->options;
	// 	$options['_POST'] = array('init' => 1);
	// 	$plum = new hk\plum\main( $options );
	// 	$stdout = $plum->run();
	// 	// var_dump($stdout);
	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/repos/master/.git/' ) );
	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/repos/master/php/' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/php/main.php' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview2/php/main.php' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview3/php/main.php' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/tests/testdata/contents/index.html' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview2/tests/testdata/contents/index.html' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview3/tests/testdata/contents/index.html' ) );

	// }

	// /**
	//  * Change Branch
	//  */
	// public function testChangeBranch(){
	// 	$options = $this->options;
	// 	$options['_POST'] = array(
	// 		'reflect' => 1,
	// 		'preview_server_name' => 'preview1',
	// 		'branch_form_list' => 'origin/tests/branch_001',
	// 	);
	// 	$plum = new hk\plum\main( $options );
	// 	$stdout = $plum->run();
	// 	// var_dump($stdout);
	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/repos/master/.git/' ) );
	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/repos/master/php/' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/php/main.php' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview2/php/main.php' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview3/php/main.php' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/tests/testdata/contents/index.html' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview2/tests/testdata/contents/index.html' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview3/tests/testdata/contents/index.html' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/tests/testdata/contents/branch_001.html' ) );
	// 	$this->assertFalse( is_file( __DIR__.'/testdata/repos/preview2/tests/testdata/contents/branch_001.html' ) );
	// 	$this->assertFalse( is_file( __DIR__.'/testdata/repos/preview3/tests/testdata/contents/branch_001.html' ) );

	// }

}
