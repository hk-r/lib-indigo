<?php
/**
 * test for Plum
 */
class mainTest extends PHPUnit_Framework_TestCase{

	// public function testEqual() {
	//     // 期待値
	//     $expected = 5;
	//     // 実際の値
	//     $actual = 2 + 4;
	//     // チェック
	//     $this->assertEquals($expected, $actual);
	// }

	private $options = array();
	private $fs;

	public function setup(){

		$this->fs = new tomk79\filesystem();

		// mb_language('Japanese');
		mb_internal_encoding('UTF-8');

		require_once(__DIR__.'/libs/simple_html_dom.php');

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
			)
		);
	}

	private function clear_indigo_dir(){
		$this->chmod_r();//パーミッションを変えないと削除できない
		if( !$this->fs->rm(__DIR__.'/testdata/indigo_dir/') ){
			var_dump('Failed to cleaning test data directory.');
		}
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/indigo_dir/');
		touch(__DIR__.'/testdata/indigo_dir/.gitkeep');
		clearstatcache();
	}
	private function chmod_r($path = null){
		$base = __DIR__.'/testdata/indigo_dir';
		// var_dump($base.'/'.$path);
		$this->fs->chmod($base.'/'.$path , 0777);
		if(is_dir($base.'/'.$path)){
			$ls = $this->fs->ls($base.'/'.$path);
			foreach($ls as $basename){
				$this->chmod_r($path.'/'.$basename);
			}
		}
	}



	/**
	 * 画面表示
	 */
	public function testDisp(){

		// var_dump($this->fs);

		$this->clear_indigo_dir();

		//============================================================
		// 初期表示画面表示
		//============================================================
		$options = $this->options;
		
		$indigo = new indigo\main( $options );
		$stdout = $indigo->run();

		$html = str_get_html( $stdout, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT );
		// var_dump($stdout) . "\n";

		$this->assertEquals( 6, count($html->find('div')) );

		$this->assertEquals( 1, count($html->find('form')) );
		$this->assertEquals( 2, count($html->find('ul')) );
		$this->assertEquals( 6, count($html->find('li')) );
		$this->assertEquals( 6, count($html->find('input')) );

		$this->assertEquals( 1, count($html->find('table')) );
		$this->assertEquals( 1, count($html->find('thead')) );
		$this->assertEquals( 1, count($html->find('tr')) );
		$this->assertEquals( 9, count($html->find('tr',0)->find('th')) );
		$this->assertEquals( '公開予約日時', $html->find('tr',0)->childNodes(1)->innertext );
		$this->assertEquals( 0, count($html->find('td')) );

		$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/waiting/' ) );
		$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/backup/' ) );
		$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/running/' ) );
		$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/released/' ) );
		$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/log/' ) );

		$date = gmdate("Ymd", time());
		$this->assertTrue( is_file( __DIR__.'/testdata/indigo_dir/log/log_process_' . $date . '.log') );

		$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/master_repository/' ) );
		$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/master_repository/.git/' ) );

		$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/sqlite/' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/indigo_dir/sqlite/indigo.db' ) );

		//============================================================
		// 履歴一覧画面表示
		//============================================================
		$options = $this->options;
		$options['_POST'] = array('history' => 1);

		$indigo = new indigo\main( $options );

		$stdout = $indigo->run();

		$html = str_get_html( $stdout, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT );
		var_dump($stdout) . "\n";

		$this->assertEquals( 7, count($html->find('div')) );

		$this->assertEquals( 1, count($html->find('form')) );
		$this->assertEquals( 3, count($html->find('ul')) );
		$this->assertEquals( 3, count($html->find('li')) );
		// $this->assertEquals( 2, count($html->find('input')) );

		$this->assertEquals( 1, count($html->find('table')) );
		$this->assertEquals( 1, count($html->find('thead')) );
		// $this->assertEquals( 1, count($html->find('tr')) );
		// $this->assertEquals( 11, count($html->find('tr',0)->find('th')) );
		// $this->assertEquals( '状態', $html->find('tr',0)->childNodes(1)->innertext );
		// $this->assertEquals( 0, count($html->find('td')) );

		//============================================================
		// バックアップ一覧表示
		//============================================================
		$options = $this->options;
		$options['_POST'] = array('backup' => 1);

		$indigo = new indigo\main( $options );

		$stdout = $indigo->run();

		$html = str_get_html( $stdout, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT );
		// var_dump($stdout) . "\n";

		$this->assertEquals( 7, count($html->find('div')) );

		$this->assertEquals( 1, count($html->find('form')) );
		$this->assertEquals( 3, count($html->find('ul')) );
		$this->assertEquals( 3, count($html->find('li')) );
		$this->assertEquals( 2, count($html->find('input')) );

		$this->assertEquals( 1, count($html->find('table')) );
		$this->assertEquals( 1, count($html->find('thead')) );
		$this->assertEquals( 1, count($html->find('tr')) );
		$this->assertEquals( 8, count($html->find('tr',0)->find('th')) );
		$this->assertEquals( 'バックアップ日時', $html->find('tr',0)->childNodes(1)->innertext );
		$this->assertEquals( 0, count($html->find('td')) );

	}


	/**
	 * 即時公開処理処理
	 */
	public function testImmediatePublish(){

		// var_dump($this->fs);

		// $this->clear_indigo_dir();

		//============================================================
		// 即時公開処理（失敗）
		//============================================================
		$options = $this->options;
		$options['_POST'] = array('immediate_confirm' => 1);	

		$main = new indigo\main( $options );
		
		// var_dump($indigo->options);

		$publish = new indigo\publish( $main );


		$define = new indigo\define();
		// var_dump($define);

		$result = $publish->exec_publish(2, null);

		var_dump($main->get_dbh());

		// $output = $this->passthru( [
		// 	// $result['status'],
		// 	$result['message']
		// 	// __DIR__.'/testData/standard/.px_execute.php' ,
		// 	// '/?PX=publish.run' ,
		// ] );
		// var_dump($result);

		$this->assertTrue( !$result['status'] );
		$this->assertEquals( '公開処理が失敗しました。', $result['message'] );
		// $this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/running/' ) )


		//============================================================
		// 即時公開処理（成功）
		//============================================================
		$options = $this->options;
		$options['_POST'] = array('immediate_confirm' => 1,	
								'branch_select_value' => 'release/2018-04-01',	
								'reserve_date' => null,
								'reserve_time' => null,	
								'commit_hash' => 'f9fd330',	
								'comment' => 'phpUnitテスト001',	
								'ver_no' => null,	
								'selected_id' => null
							);

		$indigo = new indigo\main( $options );
		
		$publish = new indigo\publish( $indigo );

		$define = new indigo\define();

		// 即時公開
		$result = $publish->exec_publish(2, null);


		$this->assertTrue( $result['status'] );
		$this->assertEquals( '', $result['message'] );
		$this->assertTrue( isset($result['output_id']) );
		// $this->assertTrue( !isset($result['backup_id']) );
		// $this->assertTrue( $result['status'] );
		// $this->assertEquals( '', $result['message'] );

	}

	/**
	 * 新規ダイアログ表示処理
	 */
	public function testInsertReserve(){

		// var_dump($this->fs);

		// $this->clear_indigo_dir();

		//============================================================
		// 初期表示画面表示
		//============================================================
		$options = $this->options;
		$options['_POST'] = array('add' => 1);	

		$indigo = new indigo\main( $options );


		$stdout = $indigo->run();

		$html = str_get_html( $stdout, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT );
		// var_dump($stdout) . "\n";

		// ダイアログの表示確認		
		$this->assertEquals( 6, count($html->find('.dialog div')) );

		$this->assertEquals( 1, count($html->find('.dialog h4')) );
		$this->assertEquals( '新規', $html->find('.dialog h4',0)->plaintext );

		$this->assertEquals( 1, count($html->find('.dialog form')) );

		$this->assertEquals( 1, count($html->find('.dialog ul')) );
		$this->assertEquals( 2, count($html->find('.dialog li')) );
		$this->assertEquals( 10, count($html->find('.dialog input')) );

		$this->assertEquals( 1, count($html->find('.dialog table')) );
		$this->assertEquals( 0, count($html->find('.dialog thead')) );
		$this->assertEquals( 4, count($html->find('.dialog tr')) );
		$this->assertEquals( 0, count($html->find('.dialog tr',0)->find('th')) );
		$this->assertEquals( 2, count($html->find('.dialog tr',0)->find('td')) );
		$this->assertEquals( 'ブランチ', $html->find('.dialog tr',0)->childNodes(0)->innertext );


		// ダイアログ裏で表示する初期表示画面の表示確認		
		$this->assertEquals( 3, count($html->find('.scr_content div')) );

		$this->assertEquals( 1, count($html->find('.scr_content form')) );
		$this->assertEquals( 2, count($html->find('.scr_content ul')) );
		$this->assertEquals( 6, count($html->find('.scr_content li')) );
		$this->assertEquals( 6, count($html->find('.scr_content input')) );

		$this->assertEquals( 1, count($html->find('.scr_content table')) );
		$this->assertEquals( 1, count($html->find('.scr_content thead')) );
		$this->assertEquals( 1, count($html->find('.scr_content tr')) );
		$this->assertEquals( 9, count($html->find('.scr_content tr',0)->find('th')) );
		$this->assertEquals( '公開予約日時', $html->find('.scr_content tr',0)->childNodes(1)->innertext );
		$this->assertEquals( 0, count($html->find('.scr_content td')) );

		// ダイアログ裏で表示する初期表示画面の表示確認		
		$this->assertEquals( 1, count($html->find('#loader-bg div')) );
	}










 // public function testEqual() {
 //    // 期待値
 //    $expected = 5;
 //    // 実際の値
 //    $actual = 2 + 3;
 //    // チェック
 //    $this->assertEquals($expected, $actual);
 //  }

	// /**
	//  * 初期表示画面表示
	//  */
	// public function testInitDisp(){

	// 	var_dump($this->fs);

	// 	$this->clear_indigo_dir();

	// 	// Plum
	// 	$options = $this->options;
	// 	$indigo = new indigo\main( $options );
	// 	$stdout = $indigo->run();

	// 	$html = str_get_html( $stdout, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT );
	// 	// var_dump($stdout) . "\n";

	// 	$this->assertEquals( 6, count($html->find('div')) );

	// 	$this->assertEquals( 1, count($html->find('form')) );
	// 	$this->assertEquals( 2, count($html->find('ul')) );
	// 	$this->assertEquals( 6, count($html->find('li')) );
	// 	$this->assertEquals( 6, count($html->find('input')) );

	// 	$this->assertEquals( 1, count($html->find('table')) );
	// 	$this->assertEquals( 1, count($html->find('thead')) );
	// 	$this->assertEquals( 1, count($html->find('tr')) );
	// 	$this->assertEquals( 9, count($html->find('tr',0)->find('th')) );
	// 	$this->assertEquals( '公開予約日時', $html->find('tr',0)->childNodes(1)->innertext );
	// 	$this->assertEquals( 0, count($html->find('td')) );

	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/waiting/' ) );
	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/backup/' ) );
	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/running/' ) );
	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/released/' ) );
	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/log/' ) );

	// 	$date = gmdate("Ymd", time());
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/indigo_dir/log/log_process_' . $date . '.log') );

	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/master_repository/' ) );
	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/master_repository/.git/' ) );

	// 	$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/sqlite/' ) );
	// 	$this->assertTrue( is_file( __DIR__.'/testdata/indigo_dir/sqlite/indigo.db' ) );
	// }
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

	/**
	 * コマンドを実行し、標準出力値を返す
	 * @param array $ary_command コマンドのパラメータを要素として持つ配列
	 * @return string コマンドの標準出力値
	 */
	private function passthru( $ary_command ){
		set_time_limit(60*10);
		$cmd = array();
		foreach( $ary_command as $row ){
			$param = escapeshellcmd($row);
			array_push( $cmd, $param );
		}
		$cmd = implode( ' ', $cmd );
		ob_start();
		passthru( $cmd );
		$bin = ob_get_clean();
		set_time_limit(30);
		return $bin;
	}
}
