<?php
/**
 * test for Plum
 */
class mainTest extends PHPUnit_Framework_TestCase{
	private $options = array();
	private $fs;

	public function setup(){
		mb_internal_encoding('UTF-8');
		$this->fs = new tomk79\filesystem();
		$this->options = array(
			'_POST' => array(),
			'_GET' => array(),
			'preview_server' => array(
				array(
					'name' => 'preview1',
					'path' => __DIR__.'/testdata/repos/preview1/',
					'url' => 'http://example.com/repos/preview1/',
				),
				array(
					'name' => 'preview2',
					'path' => __DIR__.'/testdata/repos/preview2/',
					'url' => 'http://example.com/repos/preview2/',
				),
				array(
					'name' => 'preview3',
					'path' => __DIR__.'/testdata/repos/preview3/',
					'url' => 'http://example.com/repos/preview3/',
				)
			),
			'git' => array(
				'url' => 'https://github.com/pickles2/lib-plum.git',
				'repository' => __DIR__.'/testdata/repos/master/',
			),
		);
	}

	private function clear_repos(){
		$this->chmod_r();//パーミッションを変えないと削除できない
		if( !$this->fs->rm(__DIR__.'/testdata/repos/') ){
			var_dump('Failed to cleaning test data directory.');
		}
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/repos/');
		touch(__DIR__.'/testdata/repos/.gitkeep');
		clearstatcache();
	}
	private function chmod_r($path = null){
		$base = __DIR__.'/testdata/repos';
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
	 * Initialize
	 */
	public function testInitialize(){
		$this->clear_repos();

		// Plum
		$options = $this->options;
		$plum = new hk\plum\main( $options );
		$stdout = $plum->run();
		// var_dump($stdout);

		$this->assertTrue( strpos($stdout, 'Initializeを実行してください。') !== false );

		$options = $this->options;
		$options['_POST'] = array('init' => 1);
		$plum = new hk\plum\main( $options );
		$stdout = $plum->run();
		// var_dump($stdout);
		$this->assertTrue( is_dir( __DIR__.'/testdata/repos/master/.git/' ) );
		$this->assertTrue( is_dir( __DIR__.'/testdata/repos/master/php/' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/php/main.php' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview2/php/main.php' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview3/php/main.php' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/tests/testdata/contents/index.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview2/tests/testdata/contents/index.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview3/tests/testdata/contents/index.html' ) );

	}

	/**
	 * Change Branch
	 */
	public function testChangeBranch(){
		$options = $this->options;
		$options['_POST'] = array(
			'reflect' => 1,
			'preview_server_name' => 'preview1',
			'branch_form_list' => 'origin/tests/branch_001',
		);
		$plum = new hk\plum\main( $options );
		$stdout = $plum->run();
		// var_dump($stdout);
		$this->assertTrue( is_dir( __DIR__.'/testdata/repos/master/.git/' ) );
		$this->assertTrue( is_dir( __DIR__.'/testdata/repos/master/php/' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/php/main.php' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview2/php/main.php' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview3/php/main.php' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/tests/testdata/contents/index.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview2/tests/testdata/contents/index.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview3/tests/testdata/contents/index.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/tests/testdata/contents/branch_001.html' ) );
		$this->assertFalse( is_file( __DIR__.'/testdata/repos/preview2/tests/testdata/contents/branch_001.html' ) );
		$this->assertFalse( is_file( __DIR__.'/testdata/repos/preview3/tests/testdata/contents/branch_001.html' ) );

	}

}
