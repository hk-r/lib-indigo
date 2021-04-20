<?php
/**
 * test for Indigo
 */
class parameterTest extends PHPUnit_Framework_TestCase{

	private $options = array();
	private $fs;

	public function setup1(){

		$this->fs = new tomk79\filesystem();

		mb_internal_encoding('UTF-8');

		require_once(__DIR__.'/libs/simple_html_dom.php');

		$this->options = array();
	}


	/**
	 * 画面表示（パラメタのキーがすべて存在しない）
	 */
	public function testInitView1(){

		//============================================================
		// 初期表示画面表示
		//============================================================
		$options = $this->options;
		
		$indigo = new indigo\main( $options );
		
		$stdout = $indigo->run();
		
		$html = str_get_html( $stdout, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT );

		$this->assertEquals( 'エラーが発生しました。', $html->find('h3',0)->plaintext );
		$this->assertEquals( 'Error message:パラメタが不足しています。', $html->find('p',1)->plaintext );
	}


	public function setup2(){

		$this->fs = new tomk79\filesystem();

		mb_internal_encoding('UTF-8');

		require_once(__DIR__.'/libs/simple_html_dom.php');

		$this->options = array(
			'_POST' => array(),
			'_GET' => array(),

			// indigo作業用ディレクトリ（絶対パス）
			// 'realpath_workdir'	 	=> __DIR__.'/testdata/indigo_dir/',

			// リソースディレクトリ（ドキュメントルートからの相対パス）
			'relativepath_resourcedir'	=> __DIR__.'/../res/',

			// ajax呼出クラス（ドキュメントルートからの相対パス）
			'url_ajax_call'		=> './ajax.php',

			// 画面表示上のタイムゾーン
			'time_zone' => 'Asia/Tokyo',

			// ユーザID
			'user_id' => 'user01',

			// 空間名
			'space_name' => 'project001',

			// DB設定
			'db' => array(
				'dbms' => null,
				'prefix' => 'indigo_',
				'database' => null,
				'host' => null,
				'port' => null,
				'username' => null,
				'password' => null,
			),

			// 予定最大件数
			'max_reserve_record' => 10,

			// 本番環境パス（同期先）※バージョン0.1.0時点では先頭の設定内容のみ有効
			'server' => array(
					array(
						'name' => 'server1',
						'dist' => __DIR__.'/testdata/honban1/'
					),
					array(
						'name' => 'server2',
						'dist' => __DIR__.'/testdata/honban2/'
					)
			),

			// 同期除外ディレクトリ、またはファイル
			'ignore' => array(
				'.git',
				'.htaccess'
			),

			// Git情報定義
			'git' => array(
				'giturl' => __DIR__.'/testdata/remote',
				'username' => 'hoge',
				'password' => 'fuga'
			)
		);
	}


	/**
	 * 画面表示（パラメタの作業用ディレクトリキーが存在しない）
	 */
	public function testInitView2(){

		//============================================================
		// 初期表示画面表示
		//============================================================
		$options = $this->options;
		
		$indigo = new indigo\main( $options );
		
		$stdout = $indigo->run();
		
		$html = str_get_html( $stdout, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT );

		$this->assertEquals( 'エラーが発生しました。', $html->find('h3',0)->plaintext );
		$this->assertEquals( 'Error message:パラメタが不足しています。', $html->find('p',1)->plaintext );
	}



	public function setup3(){

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
			'url_ajax_call'		=> './ajax.php',

			// 画面表示上のタイムゾーン
			'time_zone' => 'Asia/Tokyo',

			// ユーザID
			'user_id' => 'user01',

			// 空間名
			'space_name' => 'project001',

			// DB設定
			'db' => array(
				'dbms' => null,
				'prefix' => 'indigo_',
				'database' => null,
				'host' => null,
				'port' => null,
				'username' => null,
				'password' => null,
			),

			// 予定最大件数
			'max_reserve_record' => 10,

			// 本番環境パス（同期先）※バージョン0.1.0時点では先頭の設定内容のみ有効
			'server' => array(
				array(
					'name' => 'server1',
					'dist' => __DIR__.'/testdata/honban1/'
				),
				array(
					'name' => 'server2',
					'dist' => __DIR__.'/testdata/honban2/'
				)
			),

			// 同期除外ディレクトリ、またはファイル
			'ignore' => array(
				'.git',
				'.htaccess'
			),

			// Git情報定義
			'git' => array(
				// 'giturl' => __DIR__.'/testdata/remote',
				'username' => 'hoge',
				'password' => 'fuga'
			)
		);
	}


	/**
	 * 画面表示（パラメタのGitUrlキーが存在しない）
	 */
	public function testInitView3(){

		//============================================================
		// 初期表示画面表示
		//============================================================
		$options = $this->options;
		
		$indigo = new indigo\main( $options );
		
		$stdout = $indigo->run();
		
		$html = str_get_html( $stdout, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT );

		$this->assertEquals( 'エラーが発生しました。', $html->find('h3',0)->plaintext );
		$this->assertEquals( 'Error message:パラメタが不足しています。', $html->find('p',1)->plaintext );
	}
}
