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
		$result = json_decode(json_encode(
					array('status' => true,
					      'message' => '')
				  ));

		try {

			//============================================================
			// データベース接続
			//============================================================
			$this->dbh = $this->pdoManager->connect();


			//============================================================
			// 公開処理実施
			//============================================================
			$result = json_decode($this->do_cron_publish());

	
			if ( !$result->status ) {
				// 処理失敗の場合

				// TODO:エラーログ出力
				echo '例外キャッチ：' . $result->message;
			}

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

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$insert_id;

		$publish_data = array();

		try {

			// GMTの現在日時
			$start_datetime = $this->common->get_current_datetime_of_gmt();

			$this->common->debug_echo('　□ 公開処理開始日時：' . $start_datetime);

			// 作業用ディレクトリの絶対パスを取得
			$real_path = json_decode($this->common->get_workdir_real_path($this->options));


			//============================================================
			// 公開予約テーブルより、公開対象データの取得
			//============================================================
			// 公開予約の一覧を取得
			$data_list = $this->tsReserve->get_ts_reserve_publish_list($this->dbh, $start_datetime);

			if (!$data_list) {
				$this->common->debug_echo('Target data does not exist.');
				return $result;
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
			// 公開処理結果テーブルの登録処理
			//============================================================

		 	$this->common->debug_echo('　□ -----[時限公開]公開処理結果テーブルの登録処理-----');

			// 現在時刻
			$now = $this->common->get_current_datetime_of_gmt();

			$dataArray = array(
				tsOutput::TS_OUTPUT_RESERVE_ID => $publish_data[tsReserve::RESERVE_ENTITY_ID_SEQ],
				tsOutput::TS_OUTPUT_BACKUP_ID => null,
				tsOutput::TS_OUTPUT_RESERVE => $publish_data[tsReserve::RESERVE_ENTITY_RESERVE_GMT],
				tsOutput::TS_OUTPUT_BRANCH => $publish_data[tsReserve::RESERVE_ENTITY_BRANCH],
				tsOutput::TS_OUTPUT_COMMIT_HASH => $publish_data[tsReserve::RESERVE_ENTITY_COMMIT_HASH],
				tsOutput::TS_OUTPUT_COMMENT => $publish_data[tsReserve::RESERVE_ENTITY_COMMENT],
				tsOutput::TS_OUTPUT_PUBLISH_TYPE => define::PUBLISH_TYPE_RESERVE,
				tsOutput::TS_OUTPUT_STATUS => define::PUBLISH_STATUS_RUNNING,
				tsOutput::TS_OUTPUT_DIFF_FLG1 => null,
				tsOutput::TS_OUTPUT_DIFF_FLG2 => null,
				tsOutput::TS_OUTPUT_DIFF_FLG3 => null,
				tsOutput::TS_OUTPUT_START => $start_datetime,
				tsOutput::TS_OUTPUT_END => null,
				tsOutput::TS_OUTPUT_DELETE_FLG => define::DELETE_FLG_OFF,
				tsOutput::TS_OUTPUT_DELETE => null,
				tsOutput::TS_OUTPUT_INSERT_DATETIME => $now,
				tsOutput::TS_OUTPUT_INSERT_USER_ID => $this->options->user_id,
				tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
				tsOutput::TS_OUTPUT_UPDATE_USER_ID => null
			);


			// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
			$insert_id = $this->tsOutput->insert_ts_output($this->dbh, $dataArray);


			//============================================================
			// 公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動
			//============================================================

	 		$this->common->debug_echo('　□ -----公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動-----');

			// runningディレクトリの絶対パスを取得。
			$running_dirname = $this->common->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

			$this->publish->move_dir($real_path->waiting_real_path, $dirname, $real_path->running_real_path, $running_dirname, $real_path->log_real_path);

			try {

				/* トランザクションを開始する。オートコミットがオフになる */
				$this->dbh->beginTransaction();


				//============================================================
				// バックアップテーブルの登録処理
				//============================================================
				
		 		$this->common->debug_echo('　□ -----バックアップテーブルの登録処理-----');
				
				$this->tsBackup->insert_ts_backup($this->dbh, $this->options, $backup_datetime, $insert_id);


				//============================================================
				// 本番ソースを「backup」ディレクトリへコピー
				//============================================================

		 		$this->common->debug_echo('　□ -----本番ソースを「backup」ディレクトリへコピー-----');
				
				// GMTの現在日時
				$backup_datetime = $this->common->get_current_datetime_of_gmt();
				$backup_dirname = $this->common->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);

				$this->common->debug_echo('　□ バックアップ日時：' . $backup_datetime);

				// バックアップファイル作成
				$this->publish->create_backup($backup_dirname, $real_path);


		 		/* 変更をコミットする */
				$this->dbh->commit();
				/* データベース接続はオートコミットモードに戻る */

		    } catch (\Exception $e) {
		    
		      /* 変更をロールバックする */
		      $this->dbh->rollBack();
		 
		      throw $e;
		    }

			try {

				/* トランザクションを開始する。オートコミットがオフになる */
				$this->dbh->beginTransaction();


				//============================================================
				// 公開処理結果テーブルの更新処理（成功）
				//============================================================

		 		$this->common->debug_echo('　□ -----公開処理結果テーブルの更新処理（成功）-----');

				// GMTの現在日時
				$end_datetime = $this->common->get_current_datetime_of_gmt();

				$dataArray = array(
					tsOutput::TS_OUTPUT_STATUS => define::PUBLISH_STATUS_SUCCESS,
					tsOutput::TS_OUTPUT_DIFF_FLG1 => "0",
					tsOutput::TS_OUTPUT_DIFF_FLG2 => "0",
					tsOutput::TS_OUTPUT_DIFF_FLG3 => "0",
					tsOutput::TS_OUTPUT_END => $end_datetime,
					tsOutput::TS_OUTPUT_UPDATE_USER_ID => $this->options->user_id
				);

		 		$this->tsOutput->update_ts_output($this->dbh, $insert_id, $dataArray);

				//============================================================
				// ※公開処理※
				//============================================================

		 		$this->common->debug_echo('　□ -----公開処理-----');

				$this->publish->do_publish($real_path->running_real_path, $this->options);

		 		/* 変更をコミットする */
				$this->dbh->commit();
				/* データベース接続はオートコミットモードに戻る */

		    } catch (\Exception $e) {
		    
		      /* 変更をロールバックする */
		      $this->dbh->rollBack();
		 
		      throw $e;
		    }

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = 'Immediate publish faild. ' . $e->getMessage();

			//============================================================
			// 公開処理結果テーブルの更新処理（失敗）
			//============================================================

	 		$this->common->debug_echo('　□ -----公開処理結果テーブルの更新処理（失敗）-----');
			// GMTの現在日時
			$end_datetime = $this->common->get_current_datetime_of_gmt();

			$dataArray = array(
				tsOutput::TS_OUTPUT_STATUS => define::PUBLISH_STATUS_FAILED,
				tsOutput::TS_OUTPUT_DIFF_FLG1 => "0",
				tsOutput::TS_OUTPUT_DIFF_FLG2 => "0",
				tsOutput::TS_OUTPUT_DIFF_FLG3 => "0",
				tsOutput::TS_OUTPUT_END => $end_datetime,
				tsOutput::TS_OUTPUT_UPDATE_USER_ID => $this->options->user_id
			);

	 		$this->tsOutput->update_ts_output($this->dbh, $insert_id, $dataArray);

			$this->common->debug_echo('■ immediate_publish error end');

			return json_encode($result);
		}

		$result['status'] = true;

		$this->common->debug_echo('■ immediate_publish end');

		return json_encode($result);
	}
}
