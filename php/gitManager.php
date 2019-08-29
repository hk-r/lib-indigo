<?php

namespace indigo;

/**
 * Git操作クラス
 *
 * Git操作の処理を共通化したクラス。
 *
 */
class gitManager
{
	public $main;

	/**
	 * gitURL分解パス
	 */
	public $protocol, $host, $path;

	/**
	 * コンストラクタ
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct($main) {

		$this->main = $main;

		if (isset($main->options->git)) {
			$this->protocol = \parse_url($main->options->git->giturl, PHP_URL_SCHEME);
			$this->host = \parse_url($main->options->git->giturl, PHP_URL_HOST);
			$this->path = \parse_url($main->options->git->giturl, PHP_URL_PATH);
		}
	}

	/**
	 * 指定リポジトリ内のブランチリストを返却する
	 *	
	 * @param  array   $options mainクラスのオプション情報
	 *
	 * @return array[] $ret_array ブランチリスト
	 * 
	 * @throws Exception masterブランチディレクトリへの移動に失敗した場合
	 */
	public function get_branch_list($options) {

		$current_dir = \realpath('.');

		// masterディレクトリの絶対パス
		$master_real_path = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($options->realpath_workdir . define::PATH_MASTER));

		// リストの先頭を空にする
		$ret_array[] = "";

		if ( \chdir( $master_real_path )) {

			// fetch
			$command = 'git fetch';
			$this->main->common()->command_execute($command, true);

			// ブランチの一覧取得
			$command = 'git branch -r';
			$ret = $this->main->common()->command_execute($command, true);

			foreach ((array)$ret['output'] as $key => $value) {
				if( \strpos($value, '/HEAD') !== false ){
					continue;
				}

				// リモート名は非表示とする
				$findme   = '/';
				$pos = \strpos($value, $findme);
				$trimed = \substr($value, $pos + 1);
				$ret_array[] = \trim($trimed);
			}

		} else {
			// ディレクトリ移動に失敗

			\chdir($current_dir);
			throw new \Exception('Move to master directory failed.');
		}

		\chdir($current_dir);

		return $ret_array;
	}

	/**
	 * 公開ソースディレクトリを作成し、Gitファイルをコピーする
	 *
	 * @param  array  $options 	mainクラスのオプション情報
	 * @param  string $path 	コピー先親ディレクトリパス
	 * @param  string $dirname  コピー先ディレクトリ名
	 * 
	 * @throws Exception 既に同名ディレクトリが存在した場合
	 * @throws Exception ディレクトリの作成に失敗した場合
	 * @throws Exception 作成したディレクトリへの移動に失敗した場合
	 * @throws Exception コマンド実行が異常終了した場合
	 */
	public function git_file_copy($options, $path, $dirname) {

		$current_dir = \realpath('.');

		\set_time_limit(12*60*60);

		// 公開日時ディレクトリの絶対パスを取得。
		// すでに存在している場合はエラーメッセージを表示する。
		$dir_real_path = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($path . $dirname));

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '【file copy path】 ' . $dir_real_path);

		if (\file_exists($dir_real_path)) {
			throw new \Exception('同日時に公開予定のGitファイルが既に存在しています。公開予定情報を確認してください。' . $dir_real_path);
		}

		if ( !$this->main->fs()->mkdir($dir_real_path) ) {
			throw new \Exception('Git file copy failed. Creation of directory failed. ' . $dir_real_path);
		}

		//============================================================
		// 作成ディレクトリに移動し、指定ブランチのGit情報をコピーする
		//============================================================
		if ( \chdir($dir_real_path) ) {

			// 指定ブランチ
			$branch_name = \trim($options->_POST->branch_select_value);

			//============================================================
			// git init
			//============================================================
			$command = 'git init';
			$this->main->common()->command_execute($command, true);

			//============================================================
			// git urlのセット
			//============================================================
			$url = $this->get_git_remote_url();
			
			// initしたリポジトリに名前を付ける
			$command = 'git remote add ' . escapeshellarg(define::GIT_REMOTE_NAME) .  ' ' . escapeshellarg($url);
			$this->main->common()->command_execute($command, true);
			
			//============================================================
			// git fetch（リモートリポジトリの指定ブランチの情報をローカルブランチへ反映）
			//============================================================
			$command = 'git fetch ' . escapeshellarg(define::GIT_REMOTE_NAME) .  ' ' . escapeshellarg($branch_name);
			$this->main->common()->command_execute($command, true);
			
			//============================================================
			// git pull（リモート取得ブランチを任意のローカルブランチにマージするコマンド）
			//============================================================
			$command = 'git pull ' . escapeshellarg(define::GIT_REMOTE_NAME) .  ' ' . escapeshellarg($branch_name);
			$this->main->common()->command_execute($command, true);
			
		} else {
			throw new \Exception('Git file copy failed. Move directory not found. ' . $dir_real_path);
		}

		\chdir($current_dir);
	}

	/**
	 * 公開ソースディレクトリをGitファイルごと削除
	 *
	 * @param  string $path 	親ディレクトリパス
	 * @param  string $dirname	ディレクトリ名
	 * 
	 * @throws Exception 削除対象のディレクトリが見つからない場合
	 * @throws Exception 削除に失敗した場合
	 */
	public function file_delete($path, $dirname) {
			
		// 公開ソースディレクトリの絶対パスを取得
		$dir_real_path = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($path . $dirname));

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '【file delete path】 ' . $dir_real_path);

		if( $dir_real_path && is_dir( $dir_real_path ) && strlen($dir_real_path) > 3 ) {
			// ディレクトリが存在する場合、削除コマンド実行
			$command = 'rm -rf '. $dir_real_path;
			$ret = $this->main->common()->command_execute($command, true);

			if ( $ret['return'] !== 0 ) {
				throw new \Exception('Delete directory failed. ' . $dir_real_path);
			}

		} else {
			throw new \Exception('Delete directory not found. ' . $dir_real_path);
		}
	}


	/**
	 * Gitのmaster情報を取得
	 *
	 * @param  array  $options 	mainクラスのオプション情報
	 * 
	 * @throws Exception デプロイ先ディレクトリが見つからない場合
	 * @throws Exception デプロイ先ディレクトリへの移動に失敗した場合
	 */
	public function get_git_master($options) {

		$current_dir = \realpath('.');

		// masterディレクトリの絶対パス
		$master_real_path = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($options->realpath_workdir . define::PATH_MASTER));

		if ( $master_real_path ) {

			// デプロイ先のディレクトリが無い場合は作成
			if ( !$this->main->fs()->mkdir( $master_real_path ) ) {
				// ディレクトリ作成に失敗
				throw new \Exception('Failed to get git master. Creation of master directory failed.');
			}

			// 「.git」フォルダが存在すれば初期化済みと判定
			if ( !\file_exists( $master_real_path . "/.git") ) {
				// 存在しない場合

				// ディレクトリ移動
				if ( \chdir( $master_real_path ) ) {

					// git セットアップ
					$command = 'git init';
					$this->main->common()->command_execute($command, true);

					// git urlのセット
					$url = $this->get_git_remote_url();

					$command = 'git remote add ' . escapeshellarg(define::GIT_REMOTE_NAME) . ' ' . escapeshellarg($url);
					$this->main->common()->command_execute($command, true);

					// git fetch
					$command = 'git fetch ' . escapeshellarg(define::GIT_REMOTE_NAME);
					$this->main->common()->command_execute($command, true);

					// git pull
					$command = 'git pull ' . escapeshellarg(define::GIT_REMOTE_NAME) . ' master';
					$this->main->common()->command_execute($command, true);

				} else {

					// ディレクトリ移動に失敗
					throw new \Exception('Failed to get git master. Move to master directory failed.');
				}
			}
		}

		\chdir($current_dir);
	}

	/**
	 * gitリモートサーバーのURLを取得する
	 */
	private function get_git_remote_url(){
		$url = $this->protocol . "://";
		if( strlen($this->main->options->git->username) ){
			$url .= urlencode($this->main->options->git->username);
			if( strlen($this->main->options->git->password) ){
				$url .= ":" . urlencode($this->main->options->git->password);
			}
			$url .= "@";
		}
		$url .= $this->host;
		$url .= $this->path;
		return $url;
	}

}
