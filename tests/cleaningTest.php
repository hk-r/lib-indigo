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
		$this->fs->rm(__DIR__.'/testdata/honban1/');
		$this->fs->rm(__DIR__.'/testdata/indigo_dir/');

		clearstatcache();
		// $this->assertFalse( is_dir( __DIR__.'/testdata/' ) );

	}//testClear()

}
