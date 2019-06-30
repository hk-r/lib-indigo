<?php
/**
 * test for Indigo
 */
class mainTest extends PHPUnit_Framework_TestCase{

	private $options = array();
	private $fs;

	public function setup(){

		register_shutdown_function(
		    function(){
		        $e = error_get_last();
		        // if ($e === null) {
		        // 	return;
		        // }
		        if( $e['type'] == E_ERROR ||
		        	$e['type'] == E_WARNING ||
		            $e['type'] == E_PARSE ||
		            $e['type'] == E_CORE_ERROR ||
		            $e['type'] == E_COMPILE_ERROR ||
		            $e['type'] == E_USER_ERROR ){
		            
		            $datetime = gmdate("Y-m-d H:i:s", time());

            		$logstr = "[" . $datetime . "]" . " " . $e['file'] . " in " . $e['line'] . "\r\n";
					$logstr .= "Error message:" . $e['message'] . "\r\n";
					error_log($logstr, 3, __DIR__.'/error.log');
		        }
		    }
		);

		$this->fs = new tomk79\filesystem();

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

			// 予定最大件数
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
		// touch(__DIR__.'/testdata/indigo_dir/.gitkeep');
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
	private function create_honban_dir(){
		
		$this->fs->mkdir_r(__DIR__.'/testdata/honban1/');
	}


	/**
	 * 画面表示
	 */
	public function testDisp(){

		$this->clear_indigo_dir();
		$this->create_honban_dir();

		//============================================================
		// 初期表示画面表示
		//============================================================

		$options = $this->options;
		
		$indigo = new indigo\main( $options );
		$stdout = $indigo->run();

		$html = str_get_html( $stdout, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT );

		$this->assertEquals( 6, count($html->find('div')) );

		$this->assertEquals( 1, count($html->find('form')) );
		$this->assertEquals( 2, count($html->find('ul')) );
		$this->assertEquals( 6, count($html->find('li')) );
		$this->assertEquals( 6, count($html->find('input')) );

		$this->assertEquals( 1, count($html->find('table')) );
		$this->assertEquals( 1, count($html->find('thead')) );
		$this->assertEquals( 1, count($html->find('tr')) );
		$this->assertEquals( 9, count($html->find('tr',0)->find('th')) );
		$this->assertEquals( '公開予定日時', $html->find('tr',0)->childNodes(1)->innertext );
		$this->assertEquals( 0, count($html->find('td')) );

		$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/backup/' ) );
		$this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/waiting/' ) );
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
	 * 予定公開ロック確認
	 */
	public function testReservePublishLock(){

		sleep(1);

		//============================================================
		// ロック処理
		//============================================================
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/indigo_dir/applock/');
		touch(__DIR__.'/testdata/indigo_dir/applock/applock.txt');
		clearstatcache();

		//============================================================
		// 予定公開実行
		//============================================================
		$options = $this->options;

		// 画面入力項目の設定
		$options['_POST'] = array(
								'add_confirm' => 1,	
								'branch_select_value' => 'release/2018-04-01',	
								// 'gmt_reserve_datetime' => gmdate('Y-m-d H:i:s', strtotime('+1 minute', time())),

								'reserve_date' => date('Y-m-d', time() + 10),
								'reserve_time' => date('H:i:s', time() + 10),
								// 'gmt_reserve_datetime' => gmdate('Y-m-d H:i:s', strtotime('+10 second', $current_datetime)),
								
								'commit_hash' => 'f9fd330',	
								'comment' => '予定登録テスト001',	
								'ver_no' => null,
								'selected_id' => null
							);

		$main = new indigo\main( $options );
		$initScn = new indigo\screen\initScreen( $main );

		// var_dump($options);
		//============================================================
		// 入力情報を公開予定テーブルへ登録
		//============================================================
		$result = $initScn->do_confirm_add();

		$this->assertEquals('', $result['message']);
		$this->assertTrue( $result['status'] );
		$this->assertEquals('', $result['dialog_html']);

		sleep(13); // 10秒後に予約した配信予約を有効にするため、10秒以上待つ
		
		//============================================================
		// 予定公開実行
		//============================================================
		$publish = new indigo\publish( $main );

		$result = $publish->exec_publish(1, null);

		$this->assertEquals( "公開ロック中となっております。しばらくお待ちいただいてもロックが解除されない場合は、管理者にお問い合わせください。" , $result['message']);


		//============================================================
		// ロック解除
		//============================================================
		clearstatcache();
		if( !$this->fs->rm(__DIR__.'/testdata/indigo_dir/applock/') ){
			var_dump('Failed to cleaning test data directory.');
		}
		clearstatcache();
	}

	/**
	 * 即時公開ロック確認
	 */
	public function testImmediatePublishLock(){

		sleep(1);

		//============================================================
		// ロック処理
		//============================================================
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/indigo_dir/applock/');
		touch(__DIR__.'/testdata/indigo_dir/applock/applock.txt');
		clearstatcache();

		//============================================================
		// 即時公開実行
		//============================================================
		$options = $this->options;

		// 画面入力項目の設定
		$options['_POST'] = array('branch_select_value' => 'release/2018-04-01',	
								'reserve_date' => null,
								'reserve_time' => null,	
								'commit_hash' => 'f9fd330',	
								'comment' => 'phpUnitテスト001',	
								'ver_no' => null,	
								'selected_id' => null
							);

		$main = new indigo\main( $options );
		$publish = new indigo\publish( $main );

		$result = $publish->exec_publish(2, null);

		$this->assertEquals( "公開ロック中となっております。しばらくお待ちいただいてもロックが解除されない場合は、管理者にお問い合わせください。" , $result['message']);


		//============================================================
		// ロック解除
		//============================================================
		clearstatcache();
		if( !$this->fs->rm(__DIR__.'/testdata/indigo_dir/applock/') ){
			var_dump('Failed to cleaning test data directory.');
		}
		clearstatcache();
	}

	/**
	 * 手動復元公開ロック確認
	 */
	public function testManualRestorePublishLock(){

		sleep(1);

		//============================================================
		// ロック処理
		//============================================================
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/indigo_dir/applock/');
		touch(__DIR__.'/testdata/indigo_dir/applock/applock.txt');
		clearstatcache();

		//============================================================
		// 手動復元公開実行
		//============================================================
		$options = $this->options;

		// 画面入力項目の設定
		$options['_POST'] = array('branch_select_value' => 'release/2018-04-01',	
								'reserve_date' => null,
								'reserve_time' => null,	
								'commit_hash' => 'f9fd330',	
								'comment' => 'phpUnitテスト001',	
								'ver_no' => null,	
								'selected_id' => null
							);

		$main = new indigo\main( $options );
		$publish = new indigo\publish( $main );

		$result = $publish->exec_publish(3, null);

		$this->assertEquals( "公開ロック中となっております。しばらくお待ちいただいてもロックが解除されない場合は、管理者にお問い合わせください。" , $result['message']);


		//============================================================
		// ロック解除
		//============================================================
		clearstatcache();
		if( !$this->fs->rm(__DIR__.'/testdata/indigo_dir/applock/') ){
			var_dump('Failed to cleaning test data directory.');
		}
		clearstatcache();
	}


	/**
	 * 自動復元公開ロック確認
	 */
	public function testAutoRestorePublishLock(){

		sleep(1);

		//============================================================
		// ロック処理
		//============================================================
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/indigo_dir/applock/');
		touch(__DIR__.'/testdata/indigo_dir/applock/applock.txt');
		clearstatcache();

		//============================================================
		// 自動復元公開実行
		//============================================================
		$options = $this->options;

		// 画面入力項目の設定
		$options['_POST'] = array('branch_select_value' => 'release/2018-04-01',	
								'reserve_date' => null,
								'reserve_time' => null,	
								'commit_hash' => 'f9fd330',	
								'comment' => 'phpUnitテスト001',	
								'ver_no' => null,	
								'selected_id' => null
							);

		$main = new indigo\main( $options );
		$publish = new indigo\publish( $main );

		$result = $publish->exec_publish(4, null);

		$this->assertEquals( "公開ロック中となっております。しばらくお待ちいただいてもロックが解除されない場合は、管理者にお問い合わせください。" , $result['message']);


		//============================================================
		// ロック解除
		//============================================================
		clearstatcache();
		if( !$this->fs->rm(__DIR__.'/testdata/indigo_dir/applock/') ){
			var_dump('Failed to cleaning test data directory.');
		}
		clearstatcache();
	}

	/**
	 * 予定公開処理
	 */
	public function testReservePublish(){

		sleep(1);

		//============================================================
		// 予定公開実行
		//============================================================
		$options = $this->options;

		$current_datetime = time();

		// 画面入力項目の設定
		$options['_POST'] = array(
								'add_confirm' => 1,	
								'branch_select_value' => 'release/2018-05-01',	

								'reserve_date' => date('Y-m-d', time() + 10),
								'reserve_time' => date('H:i:s', time() + 10),
								// 'gmt_reserve_datetime' => gmdate('Y-m-d H:i:s', strtotime('+10 second', $current_datetime)),

								'commit_hash' => '0c39b3d',	
								'comment' => '予定登録テスト002',	
								'ver_no' => null,
								'selected_id' => null
							);

		$main = new indigo\main( $options );
		$initScn = new indigo\screen\initScreen( $main );
		
		//============================================================
		// 入力情報を公開予定テーブルへ登録
		//============================================================
		$result = $initScn->do_confirm_add();

		$this->assertEquals('', $result['message']);
		$this->assertTrue( $result['status'] );
		$this->assertEquals('', $result['dialog_html']);

		sleep(13); // 10秒後に予約した配信予約を有効にするため、10秒以上待つ

		//============================================================
		// 予定公開実行
		//============================================================
		$publish = new indigo\publish( $main );

		$result = $publish->exec_publish(1, null);

		$this->assertEquals( '公開処理が成功しました。', $result['message'] );
		$this->assertTrue( $result['status'] );
		$this->assertEquals( 1, $result['output_id'] );
		$this->assertEquals( 1, $result['backup_id'] );


	}


	/**
	 * 即時公開処理処理
	 *
	 */
	public function testImmediatePublish(){

		sleep(1);

		//============================================================
		// 即時公開処理（失敗）　画面入力項目nullの場合
		//============================================================
		$options = $this->options;
		$options['_POST'] = array('immediate_confirm' => 1);	

		$main = new indigo\main( $options );
		$publish = new indigo\publish( $main );

		$result = $publish->exec_publish(2, null);

		$this->assertEquals( '公開処理が失敗しました。', $result['message'] );
		$this->assertTrue( !$result['status'] );
		$this->assertEquals( '', $result['output_id'] );
		$this->assertEquals( '', $result['backup_id'] );

		//============================================================
		// 即時公開処理（成功）
		//============================================================
		$options = $this->options;

		// 画面入力項目の設定
		$options['_POST'] = array('immediate_confirm' => 1,	
								'branch_select_value' => 'release/2018-06-01',	
								'reserve_date' => null,
								'reserve_time' => null,	
								'commit_hash' => 'ee404da',	
								'comment' => 'phpUnitテスト_即時公開',	
								'ver_no' => null,	
								'selected_id' => null
							);

		$main = new indigo\main( $options );
		$publish = new indigo\publish( $main );

		// 即時公開
		$result = $publish->exec_publish(2, null);

		$this->assertEquals( '公開処理が成功しました。', $result['message'] );
		$this->assertTrue( $result['status'] );
		$this->assertEquals( 3, $result['output_id'] );	// 1,2は予約公開済みとスキップデータ
		$this->assertEquals( 2, $result['backup_id'] );

		return $result['output_id'];
	}

	/**
	 * 自動復元処理公開
	 *
	 * @depends testImmediatePublish
	 */
	public function testAutoRestorePublish($output_id){

		sleep(1);

		//============================================================
		// 復元公開処理（成功）
		//============================================================
		$options = $this->options;

		// 画面入力項目の設定
		// $options['_POST'] = array('restore' => 1
		// 					);

		$main = new indigo\main( $options );
		$publish = new indigo\publish( $main );

		// 手動復元公開
		$result = $publish->exec_publish(4, $output_id);

		$this->assertEquals( '公開処理が成功しました。', $result['message'] );

		$this->assertTrue( $result['status'] );
		
		// 1,2は予約公開済みとスキップデータ、3は即時公開済みデータ
		$this->assertEquals( 4, $result['output_id'] );

		// 自動復元公開はバックアップを取得しない
		$this->assertEquals( '', $result['backup_id'] );
		
	}


	/**
	 * 手動復元公開処理
	 *
	 */
	public function testManualRestorePublish(){

		sleep(1);

		//============================================================
		// 復元公開処理（失敗）　画面入力項目nullの場合
		//============================================================
		$options = $this->options;
		$options['_POST'] = array('restore' => 1);	

		$main = new indigo\main( $options );
		$publish = new indigo\publish( $main );

		$result = $publish->exec_publish(3, null);

		$this->assertEquals( '公開処理が失敗しました。', $result['message'] );
		$this->assertTrue( !$result['status'] );

		//============================================================
		// 復元公開処理（成功）
		//============================================================
		$options = $this->options;

		// 画面入力項目の設定
		$options['_POST'] = array('restore' => 1,	
								'selected_id' => 2	// backup_id(予定公開のブランチに戻る想定。即時公開の時に取得したバックアップデータに戻る。)
							);

		$main = new indigo\main( $options );
		$publish = new indigo\publish( $main );

		// 手動復元公開
		$result = $publish->exec_publish(3, null);


		$this->assertEquals( '公開処理が成功しました。', $result['message'] );

		$this->assertTrue( $result['status'] );
		
		// 1,2は予約公開済みとスキップデータ、3は即時公開済みデータ、4は自動復元公開済みデータ
		$this->assertEquals( 5, $result['output_id'] );

		// 1は予約公開のバックアップデータ、2は即時公開のバックアップデータ
		$this->assertEquals( 3, $result['backup_id'] );

	}


	/**
	 * 新規ダイアログ表示処理
	 */
	public function testInsertReserve(){

		//============================================================
		// 初期表示画面表示
		//============================================================
		$options = $this->options;
		$options['_POST'] = array('add' => 1);	

		$indigo = new indigo\main( $options );


		$stdout = $indigo->run();

		$html = str_get_html( $stdout, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT );

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
	}

}
