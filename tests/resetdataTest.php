<?php
/**
 * test for Indigo
 */
class resetdataTest extends PHPUnit_Framework_TestCase{
	private $options = array();
	private $fs;

	public function setup(){
		mb_internal_encoding('UTF-8');
		$this->fs = new tomk79\filesystem();
	}


	/**
	 * テストデータを初期化する
	 */
	public function testResetData(){
		// --------------------------------------
		// リモートリポジトリを初期化
		$this->fs->chmod_r(__DIR__.'/testdata/remote' , 0777);
		if( !$this->fs->rm(__DIR__.'/testdata/remote/') ){
			var_dump('Failed to cleaning test remote directory.');
		}
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/remote/');
		touch(__DIR__.'/testdata/remote/.gitkeep');

		$current_dir = realpath('.');
		chdir(__DIR__.'/testdata/remote/');
		exec('git init');

		// master
		$this->fs->copy_r(
			__DIR__.'/testdata/remote_data/main',
			__DIR__.'/testdata/remote'
		);

		exec('git add ./');
		exec('git commit -m "initial commit";');
		exec('git checkout -b "master";');

		// main
		exec('git checkout -b "main";');

		// branches
		$branch_list = array(
			'2018-07-01',
			'2018-06-02',
			'2018-06-01',
			'2018-05-15',
			'2018-05-01',
			'2018-04-30',
			'2018-04-01',
			'2017-03-31',
		);
		foreach( $branch_list as $branch_name ){
			exec('git checkout "main";');
			exec('git checkout -b "release/'.$branch_name.'";');
			$this->fs->copy_r(
				__DIR__.'/testdata/remote_data/'.$branch_name.'',
				__DIR__.'/testdata/remote'
			);
			exec('git add ./');
			exec('git commit -m "commit to release/'.$branch_name.'";');
		}

		chdir($current_dir);

		// --------------------------------------
		// ローカルリポジトリを削除
		$this->fs->chmod_r(__DIR__.'/testdata/repos' , 0777);
		if( !$this->fs->rm(__DIR__.'/testdata/repos/') ){
			var_dump('Failed to cleaning test data directory.');
		}
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/repos/');
		touch(__DIR__.'/testdata/repos/.gitkeep');
		clearstatcache();

	}

}
