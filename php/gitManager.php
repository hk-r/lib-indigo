<?php

namespace indigo;

class gitManager
{
	private $main;

	private $fileManager;

	private $common;


	// git一時ディレクトリパス
	const PATH_GIT_WORK = '/work_repository/';

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->fileManager = new fileManager($this);
		$this->common = new common($this);
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
		$master_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->indigo_workdir_path . define::PATH_MASTER));

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
	public function git_file_copy($path, $dirname) {

		$this->common->debug_echo('■ git_file_copy start');

		$current_dir = realpath('.');

		// 公開日時ディレクトリの絶対パスを取得。
		// すでに存在している場合は削除して再作成する。
		$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($path . $dirname));
		if ( !$this->common->is_exists_remkdir($dir_real_path) ) {
			throw new \Exception('Git file copy failed. Creation of directory failed. ' . $dir_real_path);
		}

		//============================================================
		// 作成ディレクトリに移動し、指定ブランチのGit情報をコピーする
		//============================================================
		if ( chdir($dir_real_path) ) {
			$this->git_pull();
		} else {
			throw new \Exception('Git file copy failed. Move directory not found. ' . $dir_real_path);
		}

		chdir($current_dir);
			
		$this->common->debug_echo('■ git_file_copy end');
	}

	/**
	 * 新規追加時のGitファイルのコピー
	 *
	 * @return なし
	 */
	private function git_pull() {

		$this->common->debug_echo('■ git_pull start');

		// 指定ブランチ
		$branch_name = trim(str_replace("origin/", "", $this->main->options->_POST->branch_select_value));

		// git init
		$command = 'git init';
		$this->common->command_execute($command, false);

		// git urlのセット
		$url = $this->main->options->git->protocol . "://" . urlencode($this->main->options->git->username) . ":" . urlencode($this->main->options->git->password) . "@" . $this->main->options->git->url;
		
		// initしたリポジトリに名前を付ける
		$command = 'git remote add origin ' . $url;
		$this->common->command_execute($command, false);

		// git fetch（リモートリポジトリの指定ブランチの情報をローカルブランチへ反映）
		$command = 'git fetch origin' . ' ' . $branch_name;
		$this->common->command_execute($command, false);

		// git pull（リモート取得ブランチを任意のローカルブランチにマージするコマンド）
		$command = 'git pull origin' . ' ' . $branch_name;
		$this->common->command_execute($command, false);

		$this->common->debug_echo('■ git_pull end');
	}

	/**
	 * 公開ソースディレクトリをGitファイルごと削除
	 *
	 * @return なし
	 */
	public function file_delete($path, $dirname) {
		
		$this->common->debug_echo('■ file_delete start');

		// 公開ソースディレクトリの絶対パスを取得
		$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($path . $dirname));

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
		$master_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->indigo_workdir_path . define::PATH_MASTER));

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
	public function get_git_commit_hash($options) {

		$this->common->debug_echo('■ get_git_commit_hash start');

		$commit_hash;

		$current_dir = realpath('.');

		// 指定ブランチ
		$branch_name = trim(str_replace("origin/", "", $options->_POST->branch_select_value));

		if (!$branch_name) {
			// ディレクトリ移動に失敗
			throw new \Exception('Failed to get git commitHash. Get branch name failed.');
		}
		
		// masterディレクトリの絶対パス
		$master_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->indigo_workdir_path . define::PATH_MASTER));

		if ( $master_real_path ) {

			// ディレクトリ移動
			if ( chdir( $master_real_path ) ) {

				// コミットハッシュ値取得
				$command = 'git log --pretty=%h ' . $branch_name . ' -1';
				$ret = $this->command_execute($command, false);

				foreach ( (array)$ret['output'] as $data ) {
					$commit_hash = $data;
				}

			} else {

				// ディレクトリ移動に失敗
				throw new \Exception('Failed to get git commitHash. Move to work directory failed.');
			}
		}

		chdir($current_dir);

		$this->common->debug_echo('■ get_git_commit_hash end');

		return $commit_hash;
	}

}
