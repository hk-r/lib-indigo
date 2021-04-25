<?php
/**
 * test for Indigo
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
		$this->rmdir_f(__DIR__.'/testdata/remote/');
		$this->fs->mkdir_r(__DIR__.'/testdata/remote/');
		touch(__DIR__.'/testdata/remote/.gitkeep');
		$this->assertTrue( is_file(__DIR__.'/testdata/remote/.gitkeep') );

		clearstatcache();

		$this->rmdir_f(__DIR__.'/testdata/honban1/');
		$this->assertFalse( is_dir( __DIR__.'/testdata/honban1/' ) );

		$this->rmdir_f(__DIR__.'/testdata/honban2/');
		$this->assertFalse( is_dir( __DIR__.'/testdata/honban2/' ) );

		$this->rmdir_f(__DIR__.'/testdata/indigo_dir/');
		$this->assertFalse( is_dir( __DIR__.'/testdata/indigo_dir/' ) );

	} // testClear()

	/**
	 * フォルダを強制的に削除する
	 */
	private function rmdir_f( $realpath_target ){
		clearstatcache();
		if( $this->fs->is_dir($realpath_target) ){
			$this->fs->chmod_r($realpath_target , 0777);
			if( !$this->fs->rm($realpath_target) ){
				var_dump('Failed to cleaning test remote directory.');
			}
			clearstatcache();
		}
	}
}
