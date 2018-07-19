<?php

namespace indigo;

class publish
{
	private $main;

	private $tsBackup;
	private $fileManager;
	private $pdoManager;
	private $common;

	/**
	 * PDOインスタンス
	 */
	private $dbh;

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

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->tsBackup = new tsBackup($this);
		$this->fileManager = new fileManager($this);
		$this->pdoManager = new pdoManager($this);
		$this->common = new common($this);
	}


	/**
	 * 公開処理
	 */
	public function do_publish($running_dirname, $options) {

		$this->common->debug_echo('■ do_publish start');

		$current_dir = realpath('.');

		$this->common->debug_echo('　□ 公開ファイル日時：');
		$this->common->debug_echo($running_dirname);

		// 作業用ディレクトリの絶対パスを取得
		$result = json_decode($this->common->get_workdir_real_path($options));


		//============================================================
		// 「running」ディレクトリのソースを本番環境へ同期
		//============================================================

 		$this->common->debug_echo('　□ -----「running」ディレクトリのソースを本番環境へ同期ー-----');
		
		if ( file_exists($result->running_real_path) ) {

			if ( file_exists($result->project_real_path) ) {

			// 以下のコマンド（-a）だと、パーミッションまで変えようとするためエラーが発生する。
			// $command = 'rsync -avzP ' . $running_real_path . $dirname . '/' . ' ' . $project_real_path . ' --log-file=' . $log_real_path . $dirname . '/rsync_' . $dirname . '.log' ;

			// -r ディレクトリを再帰的に調べる。
			// -l シンボリックリンクをリンクとして扱う（？）
			// -p パーミッションも含める（除外）
			// -t 更新時刻などの時刻情報も含める
			// -o 所有者情報も含める（除外）
			// -g ファイルのグループ情報も含める（除外）
			// -D デバイスファイルはそのままデバイスとして扱う（？）

			// -v 進捗を表示
			// -P ファイル転送中の場合、途中から再開するように

			// ※runningディレクトリパスの後ろにはスラッシュは付けない（スラッシュを付けると日付ディレクトリも含めて同期してしまう）
			$command = 'rsync -rtvzP --delete ' . $result->running_real_path . $running_dirname . '/' . ' ' . $result->project_real_path . ' ' . '--log-file=' . $result->log_real_path . $running_dirname . '/rsync_' . $running_dirname . '.log' ;

			$this->common->debug_echo('　□ $command：');
			$this->common->debug_echo($command);

			$ret = $this->common->command_execute($command, true);

			// $this->common->debug_echo('　▼本番反映の公開処理結果');

			// foreach ( (array)$ret['output'] as $element ) {
			// 	$this->common->debug_echo($element);
			// }

			} else {
				// エラー処理
				throw new \Exception('Project directory not found. ' . $result->project_real_path);
			}

		} else {
			// エラー処理
			throw new \Exception('Running directory not found .' . $result->running_real_path);
		}
		//============================================================
		// 公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動
		//============================================================

 		$this->common->debug_echo('　□ -----公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動-----');

		$this->move_dir($result->running_real_path, $running_dirname, $result->released_real_path, $running_dirname, $result->log_real_path);

		$this->common->debug_echo('■ do_publish end');
	}

	/**
	 * バックアップファイルの作成（コマンド実行）
	 */
	public function create_backup($backup_dirname, $real_path) {

		$this->common->debug_echo('■ create_backup start');

		// // 作業用ディレクトリの絶対パスを取得
		// $result = json_decode($this->common->get_workdir_real_path($options));

		if ( file_exists($real_path->backup_real_path) ) {

		$this->common->debug_echo('■ 1');

			if ( file_exists($real_path->project_real_path) ) {

		$this->common->debug_echo('■ 2');

				$command = 'rsync -rtvzP' . ' ' . $real_path->project_real_path . ' ' . $real_path->backup_real_path . $backup_dirname . '/' . ' --log-file=' . $real_path->log_real_path . '/rsync_' . $backup_dirname . '.log' ;

				$this->common->debug_echo('　□ $command：' . $command);

				$ret = $this->common->command_execute($command, true);

				$this->common->debug_echo('　★ バックアップ作成の処理結果');

				foreach ( (array) $ret['output'] as $element ) {
					$this->common->debug_echo($element);
				}

			} else {
				// エラー処理
				throw new \Exception('Project directory not found. ' . $real_path->project_real_path);
			}
		} else {
			// エラー処理
			throw new \Exception('Backup directory not found. ' . $real_path->backup_real_path);
		}

		$this->common->debug_echo('■ create_backup end');

	}

	/**
	 * ディレクトリの移動（コマンド実行）
	 */
	public function move_dir($from_real_path, $from_dirname, $to_real_path, $to_dirname, $log_real_path) {

		$this->common->debug_echo('■ move_dir start');

			$this->common->debug_echo($from_real_path);
			$this->common->debug_echo($to_real_path);

		if ( file_exists($from_real_path)  ) {

			if ( file_exists($to_real_path) ) {

				//============================================================
				// runningディレクトリへファイルを移動する
				//============================================================
				$command = 'rsync -rtvzP --remove-source-files ' . $from_real_path . $from_dirname . '/ ' . $to_real_path . $to_dirname . '/' . ' --log-file=' . $log_real_path . '/rsync_' . $to_dirname . '.log' ;

				$ret = $this->common->command_execute($command, true);
				if ($ret['return']) {
					// 戻り値が0以外の場合
					throw new \Exception('Command error. command:' . $command);
				}
				$this->common->debug_echo('　★ ファイル移動結果');

				// foreach ( (array)$ret['output'] as $element ) {
				// 	$this->common->debug_echo($element);
				// }

				//============================================================
				// 移動元のディレクトリを削除する
				//============================================================
				$command = 'find ' .  $from_real_path . $from_dirname . '/ -type d -empty -delete' ;

				$ret = $this->common->command_execute($command, true);
				if ($ret['return']) {
					// 戻り値が0以外の場合
					throw new \Exception('Command error. command:' . $command);
				}
				$this->common->debug_echo('　★ 移動元のディレクトリ削除結果');

				// foreach ( (array)$ret['output'] as $element ) {
				// 	$this->common->debug_echo($element);
				// }

			} else {
				// エラー処理
				throw new \Exception('Copy to directory not found. ' . $to_real_path);
			}
		
		} else {
			// エラー処理
			throw new \Exception('Copy base directory not found. ' . $from_real_path);
		}

		chdir($current_dir);

		$this->common->debug_echo('■ move_dir end');
	}
}

