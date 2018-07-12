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

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$this->common->debug_echo('　□ 公開ファイル日時：');
		$this->common->debug_echo($running_dirname);

		try {

			// 本番環境ディレクトリの絶対パスを取得。
			$project_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->project_real_path . "/"));

			$this->common->debug_echo('　□ project_real_path' . $project_real_path);

			// runningディレクトリの絶対パスを取得。
			$running_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->indigo_workdir_path . self::PATH_RUNNING));

			// releasedディレクトリの絶対パスを取得。
			$released_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->indigo_workdir_path . self::PATH_RELEASED));

			// logディレクトリの絶対パスを取得。
			$log_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->indigo_workdir_path . self::PATH_LOG));


			//============================================================
			// 「running」ディレクトリのソースを本番環境へ同期
			//============================================================

	 		$this->common->debug_echo('　□ -----「running」ディレクトリのソースを本番環境へ同期ー-----');
			
			if ( file_exists($running_real_path) && file_exists($project_real_path) ) {

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
				$command = 'rsync -rtvzP --delete ' . $running_real_path . $running_dirname . '/' . ' ' . $project_real_path . '/' . ' ' . '--log-file=' . $log_real_path . $running_dirname . '/rsync_' . $running_dirname . '.log' ;

				$this->common->debug_echo('　□ $command：');
				$this->common->debug_echo($command);

				$ret = $this->common->command_execute($command, true);

				// $this->common->debug_echo('　▼本番反映の公開処理結果');

				// foreach ( (array)$ret['output'] as $element ) {
				// 	$this->common->debug_echo($element);
				// }

			} else {
					// エラー処理
					throw new \Exception('Running or project directory not found.');
			}

			//============================================================
			// 公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動
			//============================================================

	 		$this->common->debug_echo('　□ -----公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動-----');

			$ret = json_decode($this->move_dir($running_real_path, $running_dirname, $released_real_path, $running_dirname));

			if ( !$ret->status) {
				throw new \Exception($ret->message);
			}

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			echo "例外キャッチ：", $e->getMessage() . "<br>";

			chdir($current_dir);
			return json_encode($result);
		}

		$result['status'] = true;

		chdir($current_dir);

		$this->common->debug_echo('■ immediate_release end');

		return json_encode($result);
	}

	/**
	 * バックアップファイルの作成（コマンド実行）
	 */
	public function create_backup($project_real_path, $backup_real_path, $backup_dirname) {

		$this->common->debug_echo('■ create_backup start');

		try{

			if ( file_exists($backup_real_path) && file_exists($project_real_path) ) {

				$command = 'rsync -rtvzP' . ' ' . $project_real_path . ' ' . $backup_real_path . $backup_dirname . '/' . ' --log-file=' . $log_real_path . '/rsync_' . $backup_dirname . '.log' ;

				$this->common->debug_echo('　□ $command：' . $command);

				$ret = $this->common->command_execute($command, true);

				$this->common->debug_echo('　★ バックアップ作成の処理結果');

				foreach ( (array) $ret['output'] as $element ) {
					$this->common->debug_echo($element);
				}

			} else {
					// エラー処理
					throw new \Exception('Backup or project directory not found.');
			}
		
		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			$this->common->debug_echo('■ immediate_release error end');

			chdir($current_dir);
			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->common->debug_echo('■ immediate_release end');

		return json_encode($result);
	}

	/**
	 * ディレクトリの移動（コマンド実行）
	 */
	public function move_dir($from_real_path, $from_dirname, $to_real_path, $to_dirname) {

		$this->common->debug_echo('■ move_running_dir start');

		try{

			if ( file_exists($from_real_path) && file_exists($to_real_path) ) {

				//============================================================
				// runningディレクトリへファイルを移動する
				//============================================================
				$command = 'rsync -rtvzP --remove-source-files ' . $from_real_path . $from_dirname . '/ ' . $to_real_path . $to_dirname . '/' . ' --log-file=' . $log_real_path . $from_dirname . '/rsync_' . $to_dirname . '.log' ;

				$ret = $this->common->command_execute($command, true);

				$this->common->debug_echo('　★ ファイル移動結果');

				// foreach ( (array)$ret['output'] as $element ) {
				// 	$this->common->debug_echo($element);
				// }

				//============================================================
				// 移動元のディレクトリを削除する
				//============================================================
				$command = 'find ' .  $from_real_path . $from_dirname . '/ -type d -empty -delete' ;

				$ret = $this->common->command_execute($command, true);

				$this->common->debug_echo('　★ 移動元のディレクトリ削除結果');

				// foreach ( (array)$ret['output'] as $element ) {
				// 	$this->common->debug_echo($element);
				// }

			} else {
					// エラー処理
					throw new \Exception('Base or running directory not found.');
			}
		
		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			$this->common->debug_echo('■ move_running_dir error end');

			chdir($current_dir);
			return json_encode($result);
		}

		$result['status'] = true;

		chdir($current_dir);

		$this->common->debug_echo('■ move_running_dir end');

		return json_encode($result);
	}
}

