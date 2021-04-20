<?php
/**
 * test for pickles2\px2-sitemapexcel
 */

class cleaningTest extends PHPUnit_Framework_TestCase{

	/**
	 * ファイルシステムユーティリティ
	 */
	private $fs;

	/**
	 * setup
	 */
	public function setup(){
		$this->fs = new \tomk79\filesystem();
		mb_internal_encoding('utf-8');
		@date_default_timezone_set('Asia/Tokyo');
	}

	/**
	 * 後始末
	 */
	public function testClear(){

		// 変換後ファイルの後始末
		clearstatcache();
		if( $this->fs->is_dir(__DIR__.'/testdata/honban1/') ){
			$this->fs->chmod_r(__DIR__.'/testdata/honban1/', 0777);
			if( !$this->fs->rm(__DIR__.'/testdata/honban1/') ){
				var_dump('Failed to cleaning test remote directory.');
			}
		}

		clearstatcache();
		if( $this->fs->is_dir(__DIR__.'/testdata/indigo_dir/') ){
			$this->fs->chmod_r(__DIR__.'/testdata/indigo_dir/', 0777);
			if( !$this->fs->rm(__DIR__.'/testdata/indigo_dir/') ){
				var_dump('Failed to cleaning test remote directory.');
			}
		}

		clearstatcache();
		$this->assertFalse( is_dir( __DIR__.'/testdata/honban1/' ) );
		$this->assertFalse( is_dir( __DIR__.'/testdata/indigo_dir/' ) );

	}//testClear()

}
