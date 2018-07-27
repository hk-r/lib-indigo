<?php

namespace indigo;

class cron
{
	public $options;

	/**
	 * オブジェクト
	 * @access private
	 */
	private $pdoMgr, $fs, $tsReserve, $tsOutput, $tsBackup, $publish, $common;


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

		$this->fs = new \tomk79\filesystem(array(
		  'file_default_permission' => define::FILE_DEFAULT_PERMISSION,
		  'dir_default_pefrmission' => define::DIR_DEFAULT_PERMISSION,
		  'filesystem_encoding' 	=> define::FILESYSTEM_ENCODING
		));
		
		$this->common = new common($this);

		$this->publish = new publish($this);

		$this->pdoMgr = new pdoManager($this);
		$this->tsReserve = new tsReserve($this);
		$this->tsOutput = new tsOutput($this);
		$this->tsBackup = new tsBackup($this);
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
			$this->dbh = $this->pdoMgr->connect();


			//============================================================
			// 公開処理実施
			//============================================================
			$result = json_decode(json_encode($this->publish->exec_publish(define::PUBLISH_TYPE_RESERVE)));
	
			if ( !$result->status ) {
				// 処理失敗の場合

				// TODO:エラーログ出力
				echo '例外キャッチ：' . $result->message;
			}

		} catch (\Exception $e) {

			// データベース接続を閉じる
			$this->pdoMgr->close($this->dbh);

			echo '例外キャッチ：' . $e->getMessage() . "<br>";

			return;
		}

		// データベース接続を閉じる
		$this->pdoMgr->close();

		$this->common->debug_echo('■ [cron] run end');

		return;
    }



	// /**
	//  * 予約公開処理
	//  */
	// private function do_cron_publish() {

	// 	$this->common->debug_echo('■ do_cron_publish start');

	// 	$output = "";
	// 	$result = array('status' => true,
	// 					'message' => '');

	// 	$output_id;

	// 	$publish_data = array();

	// 	try {

	// 		// GMTの現在日時
	// 		$start_datetime = $this->common->get_current_datetime_of_gmt();

	// 		$this->common->debug_echo('　□ 公開処理開始日時：' . $start_datetime);

	// 		// 作業用ディレクトリの絶対パスを取得
	// 		$real_path = json_decode($this->common->get_workdir_real_path($this->options));


	// 		//============================================================
	// 		// 公開予約テーブルより、公開対象データの取得
	// 		//============================================================
	// 		// 公開予約の一覧を取得
	// 		$data_list = $this->tsReserve->get_ts_reserve_publish_list($this->dbh, $start_datetime);

	// 		if (!$data_list) {
	// 			$this->common->debug_echo('Target data does not exist.');
	// 			return $result;
	// 		}

	// 		$cnt = 1;
	// 		$status = define::PUBLISH_STATUS_RUNNING;
	// 		$set_start_datetime = $start_datetime;

	// 		try {

	// 			/* トランザクションを開始する。オートコミットがオフになる */
	// 			$this->dbh->beginTransaction();

	// 			// 複数件取れてきた場合は、最新データ以外はスキップデータとして公開処理結果テーブルへ登録する
	// 			foreach ( (array) $data_list as $data ) {

	// 				$this->common->debug_echo('　□ 公開取得データ[配列]');
	// 				$this->common->debug_var_dump($data);

	// 				//============================================================
	// 				// 公開処理結果テーブルの登録処理
	// 				//============================================================

	// 			 	$this->common->debug_echo('　□ -----[時限公開]公開処理結果テーブルの登録処理-----');

	// 				// 現在時刻
	// 				$now = $this->common->get_current_datetime_of_gmt();

	// 				$dataArray = array(
	// 					tsOutput::TS_OUTPUT_RESERVE_ID 		=> $data[tsReserve::RESERVE_ENTITY_ID_SEQ],
	// 					tsOutput::TS_OUTPUT_BACKUP_ID 		=> null,
	// 					tsOutput::TS_OUTPUT_RESERVE 		=> $data[tsReserve::RESERVE_ENTITY_RESERVE_GMT],
	// 					tsOutput::TS_OUTPUT_BRANCH 			=> $data[tsReserve::RESERVE_ENTITY_BRANCH],
	// 					tsOutput::TS_OUTPUT_COMMIT_HASH 	=> $data[tsReserve::RESERVE_ENTITY_COMMIT_HASH],
	// 					tsOutput::TS_OUTPUT_COMMENT 		=> $data[tsReserve::RESERVE_ENTITY_COMMENT],
	// 					tsOutput::TS_OUTPUT_PUBLISH_TYPE 	=> define::PUBLISH_TYPE_RESERVE,
	// 					tsOutput::TS_OUTPUT_STATUS 			=> $status,
	// 					tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => null,
	// 					tsOutput::TS_OUTPUT_START 			=> $set_start_datetime,
	// 					tsOutput::TS_OUTPUT_END 			=> null,
	// 					tsOutput::TS_OUTPUT_GEN_DELETE_FLG 	=> define::DELETE_FLG_OFF,
	// 					tsOutput::TS_OUTPUT_GEN_DELETE 		=> null,
	// 					tsOutput::TS_OUTPUT_INSERT_DATETIME => $now,
	// 					tsOutput::TS_OUTPUT_INSERT_USER_ID 	=> $this->options->user_id,
	// 					tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
	// 					tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> null
	// 				);

	// 				// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
	// 				$insert_id = $this->tsOutput->insert_ts_output($this->dbh, $dataArray);

	// 				if ($cnt == 1) {

	// 					$dirname = $this->common->format_gmt_datetime($data[tsReserve::RESERVE_ENTITY_RESERVE_GMT], define::DATETIME_FORMAT_SAVE);

	// 					if (!$dirname) {
	// 						// エラー処理
	// 						throw new \Exception('Dirname create failed.');
	// 					} else {
	// 						$dirname .= define::DIR_NAME_RESERVE;
	// 					}

	// 					$output_id = $insert_id;

	// 					$this->common->debug_echo('　□ 公開ディレクトリ名');
	// 					$this->common->debug_var_dump($dirname);

	// 					if (!$dirname) {
	// 						// エラー処理
	// 						throw new \Exception('Publish dirname create failed.');
	// 					}

	// 					// 以降のループはスキップデータなので値を変更
	// 					$status = define::PUBLISH_STATUS_SKIP;
	// 					$set_start_datetime = null;
	// 				}

	// 				//============================================================
	// 				// 公開予約テーブルのステータス更新処理
	// 				//============================================================
					
	// 				// 公開予約テーブルのステータス更新処理
	// 				$this->tsReserve->update_ts_reserve_status($this->dbh, $data[tsReserve::RESERVE_ENTITY_ID_SEQ]);
				
	// 				$cnt++;
	// 			}

	// 	 		/* 変更をコミットする */
	// 			$this->dbh->commit();
	// 			/* データベース接続はオートコミットモードに戻る */

	// 	    } catch (\Exception $e) {
		    
	// 	      /* 変更をロールバックする */
	// 	      $this->dbh->rollBack();
		 
	// 	      throw $e;
	// 	    }

	// 		//============================================================
	// 		// 公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動
	// 		//============================================================

	//  		$this->common->debug_echo('　□ -----公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動-----');

	// 		// runningディレクトリの絶対パスを取得。
	// 		$running_dirname = $this->common->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

	// 		$this->publish->move_dir($real_path->waiting_real_path, $dirname, $real_path->running_real_path, $running_dirname, $real_path->log_real_path);

	// 		try {

	// 			/* トランザクションを開始する。オートコミットがオフになる */
	// 			$this->dbh->beginTransaction();


	// 			//============================================================
	// 			// バックアップテーブルの登録処理
	// 			//============================================================
				
	// 	 		$this->common->debug_echo('　□ -----バックアップテーブルの登録処理-----');
				
	// 			// GMTの現在日時
	// 			$backup_datetime = $this->common->get_current_datetime_of_gmt();

	// 			$this->tsBackup->insert_ts_backup($this->dbh, $this->options, $backup_datetime, $output_id);


	// 			//============================================================
	// 			// 本番ソースを「backup」ディレクトリへコピー
	// 			//============================================================

	// 	 		$this->common->debug_echo('　□ -----本番ソースを「backup」ディレクトリへコピー-----');
				
	// 			// バックアップのディレクトリ名
	// 			$backup_dirname = $this->common->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);

	// 			$this->common->debug_echo('　□ バックアップ日時：' . $backup_datetime);

	// 			// バックアップファイル作成
	// 			$this->publish->create_backup($backup_dirname, $real_path);


	// 	 		/* 変更をコミットする */
	// 			$this->dbh->commit();
	// 			/* データベース接続はオートコミットモードに戻る */

	// 	    } catch (\Exception $e) {
		    
	// 	      /* 変更をロールバックする */
	// 	      $this->dbh->rollBack();
		 
	// 	      throw $e;
	// 	    }

	// 		try {

	// 			/* トランザクションを開始する。オートコミットがオフになる */
	// 			$this->dbh->beginTransaction();


	// 			//============================================================
	// 			// 公開処理結果テーブルの更新処理（成功）
	// 			//============================================================

	// 	 		$this->common->debug_echo('　□ -----公開処理結果テーブルの更新処理（成功）-----');

	// 			// GMTの現在日時
	// 			$end_datetime = $this->common->get_current_datetime_of_gmt();

	// 			$dataArray = array(
	// 				tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_SUCCESS,
	// 				tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
	// 				tsOutput::TS_OUTPUT_END 			=> $end_datetime,
	// 				tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> $this->options->user_id
	// 			);

	// 	 		$this->tsOutput->update_ts_output($this->dbh, $output_id, $dataArray);

	// 			//============================================================
	// 			// ※公開処理※
	// 			//============================================================

	// 	 		$this->common->debug_echo('　□ -----公開処理-----');

	// 			$this->publish->do_publish($running_dirname, $this->options);

	// 	 		/* 変更をコミットする */
	// 			$this->dbh->commit();
	// 			/* データベース接続はオートコミットモードに戻る */

	// 	    } catch (\Exception $e) {
		    
	// 	      /* 変更をロールバックする */
	// 	      $this->dbh->rollBack();
		 
	// 	      throw $e;
	// 	    }

	// 	} catch (\Exception $e) {

	// 		$result['status'] = false;
	// 		$result['message'] = 'Immediate publish faild. ' . $e->getMessage();

	// 		//============================================================
	// 		// 公開処理結果テーブルの更新処理（失敗）
	// 		//============================================================

	//  		$this->common->debug_echo('　□ -----公開処理結果テーブルの更新処理（失敗）-----');
	// 		// GMTの現在日時
	// 		$end_datetime = $this->common->get_current_datetime_of_gmt();

	// 		$dataArray = array(
	// 			tsOutput::TS_OUTPUT_STATUS			=> define::PUBLISH_STATUS_FAILED,
	// 			tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
	// 			tsOutput::TS_OUTPUT_END 			=> $end_datetime,
	// 			tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> $this->options->user_id
	// 		);

	//  		$this->tsOutput->update_ts_output($this->dbh, $output_id, $dataArray);

	// 		$this->common->debug_echo('■ immediate_publish error end');

	// 		return json_encode($result);
	// 	}

	// 	$result['status'] = true;

	// 	$this->common->debug_echo('■ immediate_publish end');

	// 	return json_encode($result);
	// }


	/**
	 * `$fs` オブジェクトを取得する。
	 *
	 * `$fs`(class [tomk79\filesystem](tomk79.filesystem.html))のインスタンスを返します。
	 *
	 * @see https://github.com/tomk79/filesystem
	 * @return object $fs オブジェクト
	 */
	public function fs(){
		return $this->fs;
	}

	/**
	 * `$common` オブジェクトを取得する。
	 *
	 * @return object $common オブジェクト
	 */
	public function common(){
		return $this->common;
	}

	/**
	 * response status code を取得する。
	 *
	 * `$px->set_status()` で登録した情報を取り出します。
	 *
	 * @return int ステータスコード (100〜599の間の数値)
	 */
	public function get_dbh(){
		return $this->dbh;
	}

}
