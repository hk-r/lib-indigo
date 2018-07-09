<?php

namespace indigo;

class publish
{
	private $main;

	private $pdoManager;

	/**
	 * PDOインスタンス
	 */
	private $dbh;

	// /**
	//  * 公開種別
	//  */
	// // 予約公開
	// const PUBLISH_TYPE_RESERVE = 1;
	// // 復元公開
	// const PUBLISH_TYPE_RESTORE = 2;
	// // 即時公開
	// const PUBLISH_TYPE_SOKUJI = 3;


	/**
	 * 公開ステータス
	 */
	// 処理中
	const PUBLISH_STATUS_RUNNING = 0;
	// 成功
	const PUBLISH_STATUS_SUCCESS = 1;
	// 成功（警告あり）
	const PUBLISH_STATUS_ALERT = 2;
	// 失敗
	const PUBLISH_STATUS_FAILED = 3;
	// スキップ
	const PUBLISH_STATUS_SKIP = 4;

	/**
	 * 公開用の操作ディレクトリパス定義
	 */
	// backupディレクトリパス
	const PATH_BACKUP = '/backup/';
	// waitingディレクトリパス
	const PATH_WAITING = '/waiting/';
	// runnningディレクトリパス
	const PATH_RUNNING = '/running/';
	// releasedディレクトリパス
	const PATH_RELEASED = '/released/';
	// logディレクトリパス
	const PATH_LOG = '/log/';

	// 削除済み
	const DELETE_FLG_ON = 1;
	// 未削除
	const DELETE_FLG_OFF = 0;

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;
		$this->fileManager = new fileManager($this);
		$this->pdoManager = new pdoManager($this);
	}


	/**
	 * 公開処理
	 */
	public function do_publish($dirname) {

		$this->debug_echo('■ do_publish start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		// GMTの現在日時
		$start_datetime = gmdate(self::DATETIME_FORMAT);
		$start_datetime_dir = gmdate(self::DATETIME_FORMAT_SAVE);

		$this->debug_echo('　□ 公開ファイル日時：');
		$this->debug_echo($dirname);

		try {

			// 本番環境ディレクトリの絶対パスを取得。
			$project_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->project_real_path . "/"));

			$this->debug_echo('　□ project_real_path' . $project_real_path);

			// backupディレクトリの絶対パスを取得。
			$backup_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->indigo_workdir_path . self::PATH_BACKUP));

			$this->debug_echo('　□ backup_real_path' . $backup_real_path);

			// runningディレクトリの絶対パスを取得。
			$running_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->indigo_workdir_path . self::PATH_RUNNING));

			// releasedディレクトリの絶対パスを取得。
			$released_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->indigo_workdir_path . self::PATH_RELEASED));

			// logディレクトリの絶対パスを取得。
			$log_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->indigo_workdir_path . self::PATH_LOG));


			//============================================================
			// 本番ソースを「backup」ディレクトリへコピー
			//============================================================

	 		$this->debug_echo('　□ -----本番ソースを「backup」ディレクトリへコピー-----');
			
			// 公開ソースディレクトリの絶対パスを取得。すでに存在している場合は削除して再作成する。
			$backup_dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($backup_real_path . $dirname . "/"));

			$this->debug_echo('　□ backup_dir_real_path' . $backup_dir_real_path);

			if ( file_exists($backup_dir_real_path) && file_exists($project_real_path) ) {

				// TODO:ログフォルダに出力する
				$command = 'rsync -avzP' . ' ' . $project_real_path . ' ' . $backup_dir_real_path . ' --log-file=' . $log_real_path . '/rsync_' . $dirname . '.log' ;

				$this->debug_echo('　□ $command：');
				$this->debug_echo($command);

				$ret = $this->main->command_execute($command, true);

				$this->debug_echo('　▼ 本番バックアップの公開処理結果');

				foreach ( (array)$ret['output'] as $element ) {
					$this->debug_echo($element);
				}

			} else {
					// エラー処理
					throw new \Exception('Backup or project directory not found.');
			}



			//============================================================
			// 「running」ディレクトリのソースを本番環境へ同期
			//============================================================

	 		$this->debug_echo('　□ -----「running」ディレクトリのソースを本番環境へ同期ー-----');
			

			if ( file_exists($running_real_path) && file_exists($project_real_path) ) {

				// 以下のコマンド（-a）だと、パーミッションまで変えようとするためエラーが発生する。
				// $command = 'rsync -avzP ' . $running_real_path . $dirname . '/' . ' ' . $project_real_path . ' --log-file=' . $log_real_path . $dirname . '/rsync_' . $dirname . '.log' ;

				// r ディレクトリを再帰的に調べる。
				// -l シンボリックリンクをリンクとして扱う
				// -p パーミッションも含める
				// -t 更新時刻などの時刻情報も含める
				// -o 所有者情報も含める
				// -g ファイルのグループ情報も含める
				// -D デバイスファイルはそのままデバイスとして扱う
				$command = 'rsync -rtvzP --delete ' . $running_real_path . $dirname . '/' . ' ' . $project_real_path . '/' . ' ' . '--log-file=' . $log_real_path . $dirname . '/rsync_' . $dirname . '.log' ;

				$this->debug_echo('　□ $command：');
				$this->debug_echo($command);

				$ret = $this->main->command_execute($command, true);

				$this->debug_echo('　▼本番反映の公開処理結果');

				foreach ( (array)$ret['output'] as $element ) {
					$this->debug_echo($element);
				}

			} else {
					// エラー処理
					throw new \Exception('Running or project directory not found.');
			}



			//============================================================
			// 公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動
			//============================================================

			if ( file_exists($running_real_path) && file_exists($released_real_path)  ) {

				// TODO:ログフォルダに出力する
				$command = 'rsync -avzP --remove-source-files ' . $running_real_path . $dirname . '/' . ' ' . $released_real_path . $dirname . '/' . ' --log-file=' . $log_real_path . $dirname . '/rsync_' . $dirname . '.log' ;

				$this->debug_echo('　□ $command：');
				$this->debug_echo($command);

				$ret = $this->main->command_execute($command, true);

				$this->debug_echo('　▼REALEASEDへの移動の公開処理結果');

				foreach ( (array)$ret['output'] as $element ) {
					$this->debug_echo($element);
				}


				// runningの空ディレクトリを削除する
				$command = 'find ' .  $running_real_path . $dirname . '/ -type d -empty -delete' ;

				$this->debug_echo('　□ $command：');
				$this->debug_echo($command);

				$ret = $this->main->command_execute($command, true);

				$this->debug_echo('　▼Runningディレクトリの削除');

				foreach ( (array)$ret['output'] as $element ) {
					$this->debug_echo($element);
				}

			} else {
					// エラー処理
					throw new \Exception('Running or released directory not found.');
			}
		
		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			$this->debug_echo('■ immediate_release error end');

			chdir($current_dir);
			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->debug_echo('■ immediate_release end');

		return json_encode($result);
	}




	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_echo($text) {
	
		echo strval($text);
		echo "<br>";

		return;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_var_dump($text) {
	
		var_dump($text);
		echo "<br>";

		return;
	}

}

