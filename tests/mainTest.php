<?php
/**
 * test for Plum
 */
class mainTest extends PHPUnit_Framework_TestCase{

	private $options = array();
	private $fs;

	public function setup(){

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

								'reserve_date' => date('Y-m-d', time()),
								'reserve_time' => date('H:i:s', strtotime('-10 second', time())),
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

		// sleep(20);
		
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

    // public function reserveTestData()
    // {
    //     return array(
    //       array(
    //       	1,
    //       	'2018-08-18 10:00:00',
    //       	'release/2018-07-01	',
    //       	'86daeb8',
    //       	'ユニットテスト001',
    //       	'0',
    //       	'0',
    //       	'2018-08-16 13:15:09',
    //       	'user01',
    //       	null,
    //       	null,
    //       	'0')
    //     );
    // }


	/**
	 * 予定公開処理
	 */
	public function testReservePublish(){

		// //============================================================
		// // 即時公開処理（失敗）　画面入力項目nullの場合
		// //============================================================
		// $options = $this->options;
		// // $options['_POST'] = array('immediate_confirm' => 1);	

		// $main = new indigo\main( $options );
		// $publish = new indigo\publish( $main );

		// $result = $publish->exec_publish(1, null);

		// $this->assertTrue( !$result['status'] );
		// $this->assertEquals( '公開処理が失敗しました。', $result['message'] );
		// // TODO:ログなどのアウトプットファイルも要確認
		// // $this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/running/' ) )


		//============================================================
		// 予定公開実行
		//============================================================
		$options = $this->options;

		$current_datetime = time();

		// 画面入力項目の設定
		$options['_POST'] = array(
								'add_confirm' => 1,	
								'branch_select_value' => 'release/2018-05-01',	

								'reserve_date' => date('Y-m-d', time()),
								'reserve_time' => date('H:i:s', strtotime('-10 second', time())),
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

		// sleep(20);

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

		// TODO:ログなどのアウトプットファイルも要確認
		// $this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/running/' ) )

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


		// TODO:ログなどのアウトプットファイルも要確認
		// $this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/running/' ) )

		
		// $path = '';
		// // 本番環境ディレクトリの絶対パスを取得。（配列1番目のサーバを設定）
		// foreach ( (array)$options['server'] as $server ) {
		// 	$path = $this->fs->normalize_path($this->fs->get_realpath($server['real_path'] . "/"));
		// 	break; // 現時点では最初の1つのみ有効なのでブレイク
		// }
		// $current_branch_name = $this->get_current_branch_name($path);

		// $this->assertEquals( $branch_name, $current_branch_name );
		
		return $result['backup_id'];
	}

	/**
	 * 手動復元公開処理
	 *
	 * @depends testImmediatePublish
	 */
	public function testManualRestorePublish($backup_id){

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

		// TODO:ログなどのアウトプットファイルも要確認
		// $this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/running/' ) )

		//============================================================
		// 復元公開処理（成功）
		//============================================================
		$options = $this->options;

		// 画面入力項目の設定
		$options['_POST'] = array('restore' => 1,	
								'selected_id' => $backup_id
							);

		$main = new indigo\main( $options );
		$publish = new indigo\publish( $main );

		// 手動復元公開
		$result = $publish->exec_publish(3, null);


		$this->assertEquals( '公開処理が成功しました。', $result['message'] );

		$this->assertTrue( $result['status'] );
		
		// 1,2は予約公開済みとスキップデータ、3は即時公開済みデータ
		$this->assertEquals( 4, $result['output_id'] );

		// 1は予約公開のバックアップデータ、2は即時公開のバックアップデータ
		$this->assertEquals( 3, $result['backup_id'] );


		// TODO:ログなどのアウトプットファイルも要確認
		// $this->assertTrue( is_dir( __DIR__.'/testdata/indigo_dir/running/' ) )

		
		// $path = '';
		// // 本番環境ディレクトリの絶対パスを取得。（配列1番目のサーバを設定）
		// foreach ( (array)$options['server'] as $server ) {
		// 	$path = $this->fs->normalize_path($this->fs->get_realpath($server['real_path'] . "/"));
		// 	break; // 現時点では最初の1つのみ有効なのでブレイク
		// }
		// $current_branch_name = $this->get_current_branch_name($path);

		// $this->assertEquals( $branch_name, $current_branch_name );
		
	}


	/**
	 * [自動復元のテスト！]手動復元公開処理
	 *
	 * @depends testImmediatePublish
	 */
	public function testAutoRestorePublish($backup_id){

		//============================================================
		// 復元公開処理（成功）
		//============================================================
		$options = $this->options;

		// 画面入力項目の設定
		$options['_POST'] = array('restore' => 1,	
								'selected_id' => $backup_id
							);

		$main = new indigo\main( $options );
		$publish = new indigo\publish( $main );

		// 手動復元公開
		$result = $publish->exec_publish(4, 2);

		$this->assertEquals( '公開処理が成功しました。', $result['message'] );

		$this->assertTrue( $result['status'] );
		
		// 1,2は予約公開済みとスキップデータ、3は即時公開済みデータ、4は手動復元済みデータ
		$this->assertEquals( 5, $result['output_id'] );

		// 1は予約公開のバックアップデータ、2は即時公開のバックアップデータ、3は手動復元のバックアップデータ
		$this->assertEquals( 4, $result['backup_id'] );
		
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


		// // ダイアログ裏で表示する初期表示画面の表示確認		
		// $this->assertEquals( 3, count($html->find('.scr_content div')) );

		// $this->assertEquals( 1, count($html->find('.scr_content form')) );
		// $this->assertEquals( 2, count($html->find('.scr_content ul')) );
		// $this->assertEquals( 6, count($html->find('.scr_content li')) );
		// $this->assertEquals( 6, count($html->find('.scr_content input')) );

		// $this->assertEquals( 1, count($html->find('.scr_content table')) );
		// $this->assertEquals( 1, count($html->find('.scr_content thead')) );
		// $this->assertEquals( 1, count($html->find('.scr_content tr')) );
		// $this->assertEquals( 9, count($html->find('.scr_content tr',0)->find('th')) );
		// $this->assertEquals( '公開予定日時', $html->find('.scr_content tr',0)->childNodes(1)->innertext );
		// $this->assertEquals( 0, count($html->find('.scr_content td')) );

		// // ダイアログ裏で表示する初期表示画面の表示確認		
		// $this->assertEquals( 1, count($html->find('#loader-bg div')) );
	}

}
