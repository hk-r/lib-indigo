<?php

namespace indigo;

class cron
{
	public $options;

	private $fileManager;
	private $pdoManager;
	private $tsReserve;
	private $tsOutput;
	public $publish;
	private $common;


	/**
	 * PDOインスタンス
	 */
	private $dbh;

	
	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {

		$this->options = json_decode(json_encode($options));

		$this->fileManager = new fileManager($this);
		$this->pdoManager = new pdoManager($this);
		$this->tsReserve = new tsReserve($this);
		$this->tsOutput = new tsOutput($this);
		$this->publish = new publish($this);
		$this->common = new common($this);

	}

	/**
	 * 
	 */
    public function run(){
	
		$this->common->debug_echo('■ [cron] run start');

		// 処理実行結果格納
		$ret = '';
		$dirname =  '';

		try {

			//============================================================
			// データベース接続
			//============================================================
			$this->dbh = $this->pdoManager->connect();

			$this->common->debug_echo('　□ $this->dbh：');
			$this->common->debug_var_dump($this->dbh);


			//============================================================
			// 公開予約テーブルより、公開対象データの取得
			//============================================================
			// GMTの現在日時
			$start_datetime = $this->common->get_current_datetime_of_gmt();

			// 公開予約ディレクトリ名の取得
			$running_dirname = $this->common->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

			$this->common->debug_echo('　□ 現在日時：');
			$this->common->debug_echo($start_datetime);

			// 公開予約の一覧を取得
			$data_list = json_decode($this->tsReserve->get_ts_reserve_publish_list($this->dbh, $start_datetime));

			// TODO:ここで複数件取れてきた場合は、最新データ以外はスキップデータとして公開処理結果テーブルへ登録する
			foreach ( (array)$data_list as $data ) {

				$this->common->debug_echo('　□公開取得データ[配列]');
				$this->common->debug_var_dump($data);

				$dirname = $this->common->format_datetime($data[tsReserve::TS_RESERVE_COLUMN_RESERVE], define::DATETIME_FORMAT_SAVE);
		
				$this->common->debug_echo('　□公開ディレクトリ名');
				$this->common->debug_var_dump($dirname);
			}

			if (!$dirname) {
				// エラー処理
				throw new \Exception('Publish target not found.');
			}

			//============================================================
			// 公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動
			//============================================================

			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_WAITING));

			// runningディレクトリの絶対パスを取得。
			$running_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_RUNNING));

			// logディレクトリの絶対パスを取得。
			$log_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_LOG));

			if ( file_exists($waiting_real_path) && file_exists($running_real_path) ) {

				// TODO:ログフォルダに出力する
				$command = 'rsync -rtvzP --remove-source-files ' . $waiting_real_path . $dirname . '/ ' . $running_real_path . $running_dirname . '/' . ' --log-file=' . $log_real_path . $dirname . '/rsync_' . $dirname . '.log' ;

				$this->common->debug_echo('　□ $command：');
				$this->common->debug_echo($command);

				$ret = $this->common->command_execute($command, true);

				$this->common->debug_echo('　▼ waiting⇒runningのファイル移動結果');

				foreach ( (array)$ret['output'] as $element ) {
					$this->common->debug_echo($element);
				}

				// waitingの空ディレクトリを削除する
				$command = 'find ' .  $waiting_real_path . $dirname . '/ -type d -empty -delete' ;

				$this->common->debug_echo('　□ $command：');
				$this->common->debug_echo($command);

				$ret = $this->common->command_execute($command, true);

				$this->common->debug_echo('　▼ Waitingディレクトリの削除');

				foreach ( (array)$ret['output'] as $element ) {
					$this->common->debug_echo($element);
				}

			} else {
					// エラー処理
					throw new \Exception('Waiting or running directory not found.');
			}


			//============================================================
			// 公開処理結果テーブルの登録処理
			//============================================================
			$ret = json_decode($this->tsOutput->insert_ts_output($this->dbh, $this->options, $start_datetime, define::PUBLISH_TYPE_RESERVE));
			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT insert failed. " . $ret->message);
			}

			// インサートしたシーケンスIDを取得（処理終了時の更新処理にて使用）
			$insert_id = $this->dbh->lastInsertId();

			$this->common->debug_echo('　□ $insert_id：');
			$this->common->debug_echo($insert_id);


			//============================================================
			// 本番ソースを「backup」ディレクトリへコピー
			//============================================================

	 		$this->common->debug_echo('　□ -----本番ソースを「backup」ディレクトリへコピー-----');
			
			// 本番環境ディレクトリの絶対パスを取得。
			$project_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->project_real_path . "/"));

			// backupディレクトリの絶対パスを取得。
			$backup_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_BACKUP));

			// logディレクトリの絶対パスを取得。
			$log_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_LOG));


			// GMTの現在日時
			$backup_datetime = $this->common->get_current_datetime_of_gmt();
			$backup_dirname = $this->common->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);

			if ( file_exists($backup_real_path) && file_exists($project_real_path) ) {

				// TODO:ログフォルダに出力する
				$command = 'rsync -rtvzP' . ' ' . $project_real_path . ' ' . $backup_real_path . $backup_dirname . '/' . ' --log-file=' . $log_real_path . '/rsync_' . $dirname . '.log' ;

				$this->common->debug_echo('　□ $command：');
				$this->common->debug_echo($command);

				$ret = $this->common->command_execute($command, true);

				$this->common->debug_echo('　▼ 本番バックアップの公開処理結果');

				foreach ( (array)$ret['output'] as $element ) {
					$this->common->debug_echo($element);
				}

			} else {
					// エラー処理
					throw new \Exception('Backup or project directory not found.');
			}


			//============================================================
			// バックアップテーブルの登録処理
			//============================================================
			$ret = json_decode($this->tsBackup->insert_ts_backup($this->dbh, $this->options, $backup_datetime, define::PUBLISH_TYPE_RESERVE));
			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT insert failed.");
			}

			// インサートしたシーケンスIDを取得（処理終了時の更新処理にて使用）
			$insert_id = $this->dbh->lastInsertId();

			$this->common->debug_echo('　□ $insert_id：');
			$this->common->debug_echo($insert_id);



			//============================================================
			// ※公開処理※
			//============================================================
			$ret = json_decode($this->publish->do_publish($dirname));

			// 公開ステータスの設定
			$publish_status;
			if ( $ret->status) {
				$publish_status = define::PUBLISH_STATUS_SUCCESS;
			} else {
				$publish_status = define::PUBLISH_STATUS_FAILED;
			}


			//============================================================
			// 公開処理結果テーブルの更新処理
			//============================================================
			// GMTの現在日時
			$end_datetime = $this->common->get_current_datetime_of_gmt();

	 		$ret = $this->tsOutput->update_ts_output($this->dbh, $insert_id, $end_datetime, $publish_status);

			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT update failed. " . $ret->message);
			}

	
		} catch (\Exception $e) {

			// データベース接続を閉じる
			$this->pdoManager->close($this->dbh);

			echo $e->getMessage();

			$this->common->debug_echo('■ [cron] run error end');

			return;
		}

		// データベース接続を閉じる
		$this->pdo->close();

		$this->common->debug_echo('■ [cron] run end');

		return;
    }

}
