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
	public function do_publish($running_dirname) {

		$this->common->debug_echo('■ do_publish start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$this->common->debug_echo('　□ 公開ファイル日時：');
		$this->common->debug_echo($running_dirname);

		try {

			// 本番環境ディレクトリの絶対パスを取得。
			$project_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->project_real_path . "/"));

			$this->common->debug_echo('　□ project_real_path' . $project_real_path);

			// runningディレクトリの絶対パスを取得。
			$running_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->indigo_workdir_path . self::PATH_RUNNING));

			// releasedディレクトリの絶対パスを取得。
			$released_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->indigo_workdir_path . self::PATH_RELEASED));

			// logディレクトリの絶対パスを取得。
			$log_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->indigo_workdir_path . self::PATH_LOG));


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
				$command = 'rsync -rtvzP --delete ' . $running_real_path . $dirname . '/' . ' ' . $project_real_path . '/' . ' ' . '--log-file=' . $log_real_path . $dirname . '/rsync_' . $dirname . '.log' ;

				$this->common->debug_echo('　□ $command：');
				$this->common->debug_echo($command);

				$ret = $this->common->command_execute($command, true);

				$this->common->debug_echo('　▼本番反映の公開処理結果');

				foreach ( (array)$ret['output'] as $element ) {
					$this->common->debug_echo($element);
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

				$this->common->debug_echo('　□ $command：');
				$this->common->debug_echo($command);

				$ret = $this->common->command_execute($command, true);

				$this->common->debug_echo('　▼REALEASEDへの移動の公開処理結果');

				foreach ( (array)$ret['output'] as $element ) {
					$this->common->debug_echo($element);
				}


				// runningの空ディレクトリを削除する
				$command = 'find ' .  $running_real_path . $dirname . '/ -type d -empty -delete' ;

				$this->common->debug_echo('　□ $command：');
				$this->common->debug_echo($command);

				$ret = $this->common->command_execute($command, true);

				$this->common->debug_echo('　▼Runningディレクトリの削除');

				foreach ( (array)$ret['output'] as $element ) {
					$this->common->debug_echo($element);
				}

			} else {
					// エラー処理
					throw new \Exception('Running or released directory not found.');
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

}

