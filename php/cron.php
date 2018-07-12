<?php

namespace indigo;

class cron
{
	public $options;

	private $fileManager;
	private $pdoManager;
	private $tsReserve;
	private $tsOutput;
	private $tsBackup;
	private $publish;
	private $common;


	/**
	 * PDOインスタンス
	 */
	public $dbh;

	
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
		$this->tsBackup = new tsBackup($this);
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

			// $this->common->debug_echo('　□ $this->dbh：' . $this->dbh);


			//============================================================
			// 公開処理実施
			//============================================================
			$this->do_cron_publish();

	
		} catch (\Exception $e) {

			// データベース接続を閉じる
			$this->pdoManager->close($this->dbh);

			echo '例外キャッチ：' . $e->getMessage() . "<br>";

			return;
		}

		// データベース接続を閉じる
		$this->pdoManager->close();

		$this->common->debug_echo('■ [cron] run end');

		return;
    }



	/**
	 * 予約公開処理
	 */
	private function do_cron_publish() {

		$this->common->debug_echo('■ do_cron_publish start');

		$ret_flg = true;

		$publish_data = array();

		// GMTの現在日時
		$start_datetime = $this->common->get_current_datetime_of_gmt();

		$this->common->debug_echo('　□ 現在日時：' . $start_datetime);

		// 本番環境ディレクトリの絶対パスを取得。
		$project_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->project_real_path . "/"));

		// backupディレクトリの絶対パスを取得。
		$backup_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_BACKUP));

		// waitingディレクトリの絶対パスを取得。
		$waiting_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_WAITING));

		// runningディレクトリの絶対パスを取得。
		$running_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_RUNNING));

		// logディレクトリの絶対パスを取得。
		$log_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_LOG));


		//============================================================
		// 公開予約テーブルより、公開対象データの取得
		//============================================================
		// 公開予約の一覧を取得
		$data_list = $this->tsReserve->get_ts_reserve_publish_list($this->dbh, $start_datetime);

		if (!$data_list) {
			$this->common->debug_echo('　□ 公開対象のデータが存在しない');
			return $ret_flg;
		}

		// TODO:ここで複数件取れてきた場合は、最新データ以外はスキップデータとして公開処理結果テーブルへ登録する
		foreach ( (array) $data_list as $data ) {

			$this->common->debug_echo('　□ 公開取得データ[配列]');
			$this->common->debug_var_dump($data);

			$dirname = $this->common->format_gmt_datetime($data[tsReserve::RESERVE_ENTITY_RESERVE_GMT], define::DATETIME_FORMAT_SAVE) . define::DIR_NAME_RESERVE;
		
			$publish_data = $data;

			$this->common->debug_echo('　□ 公開ディレクトリ名');
			$this->common->debug_var_dump($dirname);
		}

		if (!$dirname) {
			// エラー処理
			throw new \Exception('Publish dirname create failed.');
		}

		//============================================================
		// 公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動
		//============================================================

		// 公開予約ディレクトリ名の取得
		$running_dirname = $this->common->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

		$ret = json_decode($this->publish->move_dir($waiting_real_path, $dirname, $running_real_path, $running_dirname));

		if ( !$ret->status) {
			throw new \Exception($ret->message);
		}


		//============================================================
		// 公開処理結果テーブルの登録処理
		//============================================================

		$dataArray = array(
			tsOutput::TS_OUTPUT_RESERVE_ID => $publish_data[tsReserve::RESERVE_ENTITY_ID_SEQ],
			tsOutput::TS_OUTPUT_BACKUP_ID => null,
			tsOutput::TS_OUTPUT_RESERVE => $publish_data[tsReserve::RESERVE_ENTITY_RESERVE_GMT],
			tsOutput::TS_OUTPUT_BRANCH => $publish_data[tsReserve::RESERVE_ENTITY_BRANCH],
			tsOutput::TS_OUTPUT_COMMIT => "dummy_commit_hash",
			tsOutput::TS_OUTPUT_COMMENT => $publish_data[tsReserve::RESERVE_ENTITY_COMMENT],
			tsOutput::TS_OUTPUT_PUBLISH_TYPE => define::PUBLISH_TYPE_RESERVE,
			tsOutput::TS_OUTPUT_STATUS => define::PUBLISH_STATUS_RUNNING,
			tsOutput::TS_OUTPUT_DIFF_FLG1 => null,
			tsOutput::TS_OUTPUT_DIFF_FLG2 => null,
			tsOutput::TS_OUTPUT_DIFF_FLG3 => null,
			tsOutput::TS_OUTPUT_START => $start_datetime,
			tsOutput::TS_OUTPUT_END => null,
			tsOutput::TS_OUTPUT_DELETE_FLG => define::DELETE_FLG_OFF,
			tsOutput::TS_OUTPUT_DELETE => null
			// . tsOutput::TS_OUTPUT_INSERT_DATETIME => $now,
			// . tsOutput::TS_OUTPUT_INSERT_USER_ID => "dummy_insert_user",
			// . tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
			// . tsOutput::TS_OUTPUT_UPDATE_USER_ID => null
		);


		$ret = json_decode($this->tsOutput->insert_ts_output($this->dbh, $dataArray));

		if ( !$ret->status) {
			throw new \Exception("TS_OUTPUT insert failed. " . $ret->message);
		}
 // array( 'cell'	=> $this->_topLeftCellRef,
	// 				  'xOffset'	=> $this->_topLeftXOffset,
	// 				  'yOffset'	=> $this->_topLeftYOffset
	// 				);

		// インサートしたシーケンスIDを取得（処理終了時の更新処理にて使用）
		$insert_id = $ret->insert_id;

		$this->common->debug_echo('　□ $insert_id：' . $insert_id);



		//============================================================
		// 本番ソースを「backup」ディレクトリへコピー
		//============================================================

 		$this->common->debug_echo('　□ -----本番ソースを「backup」ディレクトリへコピー-----');
		
		// GMTの現在日時
		$backup_datetime = $this->common->get_current_datetime_of_gmt();
		$backup_dirname = $this->common->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);

		$this->common->debug_echo('　□ バックアップ日時：' . $backup_datetime);

		// バックアップファイル作成
		$ret = json_decode($this->publish->create_backup($project_real_path, $backup_real_path, $log_real_path, $backup_dirname));
	
		if ( !$ret->status) {
			throw new \Exception($ret->message);
		}


		//============================================================
		// バックアップテーブルの登録処理
		//============================================================
		
 		$this->common->debug_echo('　□ -----バックアップテーブルの登録処理-----');
		

		$ret = json_decode($this->tsBackup->insert_ts_backup($this->dbh, $this->options, $backup_datetime, $insert_id));
		if ( !$ret->status) {
			throw new \Exception("TS_OUTPUT insert failed." . $ret->status);
		}


		//============================================================
		// ※公開処理※
		//============================================================

 		$this->common->debug_echo('　□ -----公開処理-----');
			

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

 		$this->common->debug_echo('　□ -----公開処理結果テーブルの更新処理-----');

		// GMTの現在日時
		$end_datetime = $this->common->get_current_datetime_of_gmt();

 		$ret = json_decode($this->tsOutput->update_ts_output($this->dbh, $insert_id, $end_datetime, $publish_status));

		if ( !$ret->status) {
			throw new \Exception("TS_OUTPUT update failed. " . $ret->message);
		}
	
		return $ret_flg;
	}
}
