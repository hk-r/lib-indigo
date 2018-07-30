<?php

namespace indigo;

class gitManager
{
	public $main;

	private $fs;

	private $common;


	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = json_decode(json_encode($main));

		$this->common = new common($this);

		$this->fs = new \tomk79\filesystem(array(
		  'file_default_permission' => define::FILE_DEFAULT_PERMISSION,
		  'dir_default_permission' => define::DIR_DEFAULT_PERMISSION,
		  'filesystem_encoding' => define::FILESYSTEM_ENCODING
		));
	}

	/**
	 * ブランチリストを取得
	 *	 
	 * @return 指定リポジトリ内のブランチリストを返す
	 */
	public function get_branch_list($options) {

		$this->common->debug_echo('■ get_branch_list start');

		$current_dir = realpath('.');

		// masterディレクトリの絶対パス
		$master_real_path = $this->fs->normalize_path($this->fs->get_realpath($options->workdir_relativepath . define::PATH_MASTER));

		if ( chdir( $master_real_path )) {

			// fetch
			$command = 'git fetch';
			$this->common->command_execute($command, false);

			// ブランチの一覧取得
			$command = 'git branch -r';
			$ret = $this->common->command_execute($command, false);

			// リストの先頭を空にする
			$output_array[] = "";

			foreach ((array)$ret['output'] as $key => $value) {
				if( strpos($value, '/HEAD') !== false ){
					continue;
				}
				$output_array[] = trim($value);
			}

			$result['branch_list'] = $output_array;

		} else {
			// ディレクトリ移動に失敗

			chdir($current_dir);

			throw new \Exception('Move to master directory failed.');
		}

		chdir($current_dir);

		$this->common->debug_echo('■ get_branch_list end');
		return json_encode($result);
	}

	/**
	 * 公開ソースディレクトリを作成し、Gitファイルのコピー
	 *
	 * @return なし
	 */
	public function git_file_copy($options, $path, $dirname) {

		$this->common->debug_echo('■ git_file_copy start');

		$current_dir = realpath('.');

		// 公開日時ディレクトリの絶対パスを取得。
		// すでに存在している場合は削除して再作成する。
		$dir_real_path = $this->fs->normalize_path($this->fs->get_realpath($path . $dirname));
		if ( !$this->common->is_exists_remkdir($dir_real_path) ) {
			throw new \Exception('Git file copy failed. Creation of directory failed. ' . $dir_real_path);
		}

		//============================================================
		// 作成ディレクトリに移動し、指定ブランチのGit情報をコピーする
		//============================================================
		if ( chdir($dir_real_path) ) {
			
			// 指定ブランチ
			$branch_name = trim(str_replace("origin/", "", $options->_POST->branch_select_value));

			// git init
			$command = 'git init';
			$this->common->command_execute($command, false);

			// git urlのセット
			$url = $options->git->protocol . "://" . urlencode($options->git->username) . ":" . urlencode($options->git->password) . "@" . $options->git->url;
			
			// initしたリポジトリに名前を付ける
			$command = 'git remote add origin ' . $url;
			$ret = $this->common->command_execute($command, false);
			if ($ret['return']) {
				// 戻り値が0以外の場合
				throw new \Exception('Git pull command error. url:' . $url);
			}
			$this->common->debug_echo('　□ コマンド実行結果1：' . $ret['return']);

			// git fetch（リモートリポジトリの指定ブランチの情報をローカルブランチへ反映）
			$command = 'git fetch origin' . ' ' . $branch_name;
			$ret = $this->common->command_execute($command, false);
			if ($ret['return']) {
				// 戻り値が0以外の場合
				throw new \Exception('Git pull command error. branch_name:' . $branch_name);
			}

			// git pull（リモート取得ブランチを任意のローカルブランチにマージするコマンド）
			$command = 'git pull origin' . ' ' . $branch_name;
			$ret = $this->common->command_execute($command, false);
			if ($ret['return']) {
				// 戻り値が0以外の場合

				chdir($current_dir);
				throw new \Exception('Git pull command error. branch_name:' . $branch_name);
			}

		} else {
			throw new \Exception('Git file copy failed. Move directory not found. ' . $dir_real_path);
		}

		chdir($current_dir);
			
		$this->common->debug_echo('■ git_file_copy end');
	}

	/**
	 * 公開ソースディレクトリをGitファイルごと削除
	 *
	 * @return なし
	 */
	public function file_delete($path, $dirname) {
		
		$this->common->debug_echo('■ file_delete start');

		// 公開ソースディレクトリの絶対パスを取得
		$dir_real_path = $this->fs->normalize_path($this->fs->get_realpath($path . $dirname));

		if( $dir_real_path && file_exists( $dir_real_path )) {
			// ディレクトリが存在する場合、削除コマンド実行
			$command = 'rm -rf --preserve-root '. $dir_real_path;
			$ret = $this->common->command_execute($command, true);

			if ( $ret['return'] !== 0 ) {
				throw new \Exception('Delete directory failed. ' . $dir_real_path);
			}

		} else {
			throw new \Exception('Delete directory not found. ' . $dir_real_path);
		}

		$this->common->debug_echo('■ file_delete end');
	}


	/**
	 * Gitのmaster情報を取得
	 */
	public function get_git_master($options) {

		$this->common->debug_echo('■ get_git_master start');

		$current_dir = realpath('.');

		// masterディレクトリの絶対パス
		$master_real_path = $this->fs->normalize_path($this->fs->get_realpath($options->workdir_relativepath . define::PATH_MASTER));

		if ( $master_real_path ) {

			// デプロイ先のディレクトリが無い場合は作成
			if ( !$this->common->is_exists_mkdir( $master_real_path ) ) {
				// ディレクトリ作成に失敗
				throw new \Exception('Failed to get git master. Creation of master directory failed.');
			}

			// 「.git」フォルダが存在すれば初期化済みと判定
			if ( !file_exists( $master_real_path . "/.git") ) {
				// 存在しない場合

				// ディレクトリ移動
				if ( chdir( $master_real_path ) ) {

					// git セットアップ
					$command = 'git init';
					$this->common->command_execute($command, false);

					// git urlのセット
					$url = $options->git->protocol . "://" . urlencode($options->git->username) . ":" . urlencode($options->git->password) . "@" . $options->git->url;

					$command = 'git remote add origin ' . $url;
					$this->common->command_execute($command, false);

					// git fetch
					$command = 'git fetch origin';
					$this->common->command_execute($command, false);

					// git pull
					$command = 'git pull origin master';
					$this->common->command_execute($command, false);

				} else {

					// ディレクトリ移動に失敗
					throw new \Exception('Failed to get git master. Move to master directory failed.');
				}
			}
		}

		chdir($current_dir);

		$this->common->debug_echo('■ get_git_master end');
	}

	/**
	 * Gitブランチのコミットハッシュ値を取得
	 */
	public function get_commit_hash() {

		// $this->common->debug_echo('■ get_commit_hash start');

		$commit_hash;

		$data = array(
					'commit_hash' => ''
				);

		$current_dir = realpath('.');

	    if (isset($this->main->branch_name) && isset($this->main->workdir_relativepath)) {
	    
	        // masterディレクトリの絶対パス
	        $master_real_path = $this->fs->normalize_path($this->fs->get_realpath($this->main->workdir_relativepath . define::PATH_MASTER));

	        if ( $master_real_path ) {

	            if ( chdir( $master_real_path ) ) {

	                // コミットハッシュ値取得
	                $command = 'git log --pretty=%h ' . $this->main->branch_name . ' -1';
	                $ret = $this->common->command_execute($command, false);
	                foreach ( (array)$ret['output'] as $element ) {
	                    $commit_hash = $element;
	                }

				} else {

					// ディレクトリ移動に失敗
					throw new \Exception('Failed to get git commitHash. Move to work directory failed.');
				} 
	        }
		} else {

			// ディレクトリ移動に失敗
			throw new \Exception('Parameter is empty.');
		} 
	    
	    if ($commit_hash) {
	    	$data['commit_hash'] = $commit_hash;
	    }
		
        chdir($current_dir);

		header('Content-Type: application/json; charset=utf-8');
		return json_encode($data);
	}

}
