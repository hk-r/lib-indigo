<?php

namespace indigo;

use indigo\db\tsReserve as tsReserve;
use indigo\db\tsOutput as tsOutput;
use indigo\db\tsBackup as tsBackup;

class publish
{
	private $main;

	private $tsReserve, $tsOutput, $tsBackup;

	/** ロックファイルの格納パス */
	private $path_lockfile;

	/** パス設定 */
	private $path_lockdir;

	/** ログ絶対パス（本番同期処理用） */
	private $realpath_copylog;
	/** ログ絶対パス（公開処理全体用） */
	private $realpath_tracelog;

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->tsReserve = new tsReserve($this->main);
		$this->tsOutput = new tsOutput($this->main);
		$this->tsBackup = new tsBackup($this->main);
		
		$this->path_lockdir = $main->fs()->get_realpath( $this->main->options->workdir_realpath . 'applock/' );
		$this->path_lockfile = $this->path_lockdir .'applock.txt';

		// $this->main->common()->put_process_log(__METHOD__, __LINE__, '　□ path_lockdir：' . $this->path_lockdir);
		// $this->main->common()->put_process_log(__METHOD__, __LINE__, '　□ path_lockdir：' . $this->path_lockfile);
	}


	/**
	 * 公開処理
	 */
	public function exec_publish($publish_type, $output_id) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish start');

		$logstr = "===============================================" . "\r\n";
		$logstr .= "公開処理開始" . "\r\n";
		$logstr .= "===============================================";
		$this->main->common()->put_process_log_block($logstr);

		$output = "";
		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '',
						'output_id' => '',
						'backup_id' => '');

		$backup_dirname;

		$result['output_id'] = $output_id;


		//============================================================
		// ログ生成
		//============================================================
		// 作業用ディレクトリの絶対パスを取得
		$realpath_array = $this->main->realpath_array;
		
		// GMT現在日時を取得し、ディレクトリ名用にフォーマット変換
		$start_datetime = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		$running_dirname = $this->main->common()->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

		// 同期ログ
		$this->realpath_copylog = $this->main->fs()->normalize_path($this->main->fs()->get_realpath(
			$realpath_array->realpath_log . $running_dirname . "/")) . 'pub_copy_' . $running_dirname . '.log';
		// 公開処理実行ログ
		$this->realpath_tracelog = $this->main->fs()->normalize_path($this->main->fs()->get_realpath(
			$realpath_array->realpath_log . $running_dirname . "/")) . 'pub_trace_' . $running_dirname . '.log';

		// ログファイルの上位ディレクトリを作成
		if( !@is_dir( dirname( $this->realpath_copylog ) ) ){
			$this->main->fs()->mkdir_r( dirname( $this->realpath_copylog ) );
		}
		// ログファイルの上位ディレクトリを作成
		if( !@is_dir( dirname( $this->realpath_tracelog ) ) ){
			$this->main->fs()->mkdir_r( dirname( $this->realpath_tracelog ) );
		}

		//============================================================
		// ロック処理
		//============================================================
		if( !$this->lock() ){//ロック

			// $logstr = 'publish is now locked.'."\n";
			// $logstr .= '  (lockfile updated: '.@date('Y-m-d H:i:s', filemtime($this->path_lockfile)).')'."\n";
			// $logstr .= 'Try again later...'."\n";
			// $logstr .= 'exit.'."\n";
			// $this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			// エラー処理
			throw new \Exception('公開ロック中となっております。しばらくお待ちいただいてもロックが解除されない場合は、管理者にお問い合わせください。');
		}

		// $logstr = "5秒スリープ" . "\r\n";
		// $this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		// sleep(3);


		try {

			set_time_limit(12*60*60);

			$logstr = "公開種別：" . $publish_type . "\r\n";
			$logstr .= "公開処理開始日時：" . $start_datetime . "\r\n";
			$logstr .= "公開日時ディレクトリ名：" . $running_dirname . "\r\n";
			$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			try {

				// 予約公開の場合
				if ($publish_type == define::PUBLISH_TYPE_RESERVE) {

					$logstr = "===============================================" . "\r\n";
					$logstr .= "公開予約処理実施" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$this->main->common()->put_process_log_block($logstr);

					//============================================================
					// 公開予約テーブルより、公開対象データの取得
					//============================================================
					$data_list = $this->tsReserve->get_ts_reserve_publish_list($start_datetime);
					
					if (!$data_list) {
						$this->main->common()->put_process_log(__METHOD__, __LINE__, 'Target data does not exist.');

						$logstr = "===============================================" . "\r\n";
						$logstr .= "ロック解除" . "\r\n";
						$logstr .= "===============================================" . "\r\n";
						$this->main->common()->put_process_log_block($logstr);

						$this->unlock();//ロック解除

						$logstr = "===============================================" . "\r\n";
						$logstr .= "公開処理完了" . "\r\n";
						$logstr .= "===============================================" . "\r\n";
						$this->main->common()->put_process_log_block($logstr);

						return $result;
					}

					$cnt = 1;
					$status = define::PUBLISH_STATUS_RUNNING;
					$set_start_datetime = $start_datetime;

					/* トランザクションを開始する。オートコミットがオフになる */
					$this->main->get_dbh()->beginTransaction();

					// 複数件取れてきた場合は、最新データ以外はスキップデータとして公開処理結果テーブルへ登録する
					foreach ( (array) $data_list as $data ) {

						$logstr = "-----------------------------------------------" . "\r\n";
						$logstr .= "公開予約取得データ" . "\r\n";
						$logstr .= "-----------------------------------------------" . "\r\n";
						$this->main->common()->put_process_log_block($logstr);

						//============================================================
						// 公開処理結果テーブルの登録処理
						//============================================================
						$logstr = "===============================================" . "\r\n";
						$logstr .= "[予約公開]公開処理結果テーブルの登録処理" . "\r\n";
						$logstr .= "===============================================" . "\r\n";

						if ($cnt != 1) {
							$logstr .= "-----------------------------------------------" . "\r\n";
							$logstr .= "スキップ処理" . "\r\n";
							$logstr .= "-----------------------------------------------" . "\r\n";
						}

						$logstr .= "公開予約ID" . $data[tsReserve::RESERVE_ENTITY_ID_SEQ] . "\r\n";
						$logstr .= "公開予約日時(GMT)：" . $data[tsReserve::RESERVE_ENTITY_RESERVE_GMT] . "\r\n";
						$logstr .= "ブランチ名：" . $data[tsReserve::RESERVE_ENTITY_BRANCH] . "\r\n";
						$logstr .= "コミット：" . $data[tsReserve::RESERVE_ENTITY_COMMIT_HASH] . "\r\n";
						$logstr .= "コメント：" . $data[tsReserve::RESERVE_ENTITY_COMMENT] . "\r\n";
						$logstr .= "ユーザID：" . $this->main->options->user_id . "\r\n";
						$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

						// 現在時刻
						$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

						$dataArray = array(
							tsOutput::TS_OUTPUT_RESERVE_ID 		=> $data[tsReserve::RESERVE_ENTITY_ID_SEQ],
							tsOutput::TS_OUTPUT_BACKUP_ID 		=> null,
							tsOutput::TS_OUTPUT_RESERVE 		=> $data[tsReserve::RESERVE_ENTITY_RESERVE_GMT],
							tsOutput::TS_OUTPUT_BRANCH 			=> $data[tsReserve::RESERVE_ENTITY_BRANCH],
							tsOutput::TS_OUTPUT_COMMIT_HASH 	=> $data[tsReserve::RESERVE_ENTITY_COMMIT_HASH],
							tsOutput::TS_OUTPUT_COMMENT 		=> $data[tsReserve::RESERVE_ENTITY_COMMENT],
							tsOutput::TS_OUTPUT_PUBLISH_TYPE 	=> define::PUBLISH_TYPE_RESERVE,
							tsOutput::TS_OUTPUT_STATUS 			=> $status,
							tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => null,
							tsOutput::TS_OUTPUT_START 			=> $set_start_datetime,
							tsOutput::TS_OUTPUT_END 			=> null,
							tsOutput::TS_OUTPUT_GEN_DELETE_FLG 	=> define::DELETE_FLG_OFF,
							tsOutput::TS_OUTPUT_GEN_DELETE 		=> null,
							tsOutput::TS_OUTPUT_INSERT_DATETIME => $now,
							tsOutput::TS_OUTPUT_INSERT_USER_ID 	=> $this->main->options->user_id,
							tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
							tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> null
						);

						// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
						$insert_id = $this->tsOutput->insert_ts_output($dataArray);

						if ($cnt == 1) {

							$result['output_id'] = $insert_id;


							$logstr = "-----------------------------------------------" . "\r\n";
							$logstr .= "予約対象" . "\r\n";
							$logstr .= "-----------------------------------------------" . "\r\n";
							$logstr .= "公開処理結果テーブル登録ID：" . $result['output_id'] . "\r\n";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

							$reserve_dirname = $this->main->common()->format_gmt_datetime($data[tsReserve::RESERVE_ENTITY_RESERVE_GMT], define::DATETIME_FORMAT_SAVE);

							if (!$reserve_dirname) {
								// エラー処理
								throw new \Exception('Dirname create failed.');
							} else {
								$reserve_dirname .= define::DIR_NAME_RESERVE;
							}

							$logstr = "公開対象のwaitingディレクトリ名'" . $reserve_dirname . "\r\n";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

							// 以降のループはスキップデータなので値を変更
							$status = define::PUBLISH_STATUS_SKIP;
							$set_start_datetime = null;
						}

						//============================================================
						// 公開予約テーブルのステータス更新処理
						//============================================================
						$logstr = "===============================================" . "\r\n";
						$logstr .= "公開予約テーブルのステータス更新処理（処理済みへ）" . "\r\n";
						$logstr .= "===============================================" . "\r\n";
						$this->main->common()->put_process_log_block($logstr);

						$this->tsReserve->update_ts_reserve_status($data[tsReserve::RESERVE_ENTITY_ID_SEQ], $data[tsReserve::RESERVE_ENTITY_VER_NO]);
						
						$cnt++;
					}

			 		/* 変更をコミットする */
					$this->main->get_dbh()->commit();
					/* データベース接続はオートコミットモードに戻る */

					//============================================================
					// 公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動
					//============================================================
					$logstr = "===============================================" . "\r\n";
					$logstr .= "waitingディレクトリからrunningディレクトリへ移動" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					
					$from_realpath = $realpath_array->realpath_waiting . $reserve_dirname . '/';
					$to_realpath = $realpath_array->realpath_running . $running_dirname . '/';

					$this->exec_sync_move($from_realpath, $to_realpath);

				// 即時公開、手動復元公開、自動復元公開の場合
				} else {

					$backup_id = null;
					$backup_dirname = '';

					// 公開処理結果データ
					$output_dataArray = null;

 					if (($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) ||
						($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE)) {

						//============================================================
						// バックアップテーブルより、公開対象データの取得
						//============================================================
						$backup_data = null;

						if ($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) {

							$logstr = "==========[手動復元公開]バックアップ対象データの取得==========";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

							$selected_id =  $this->main->options->_POST->selected_id;

							$logstr = "選択バックアップID --> " . $selected_id . "\r\n";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

							$backup_data = $this->tsBackup->get_selected_ts_backup($selected_id);
						
						} else {

							$logstr = "==========[自動復元公開]バックアップ対象データの取得==========";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

							$logstr = "公開処理結果ID --> " . $output_id . "\r\n";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
							
							// 処理結果IDからバックアップ情報を取得
							$backup_data = $this->tsBackup->get_selected_ts_backup_by_output_id($output_id);
						}

						$logstr = "backup_data : " . implode("|" , $backup_data) . "\r\n";
						$this->main->common()->put_process_log_block($logstr);

						if (!$backup_data) {
							throw new \Exception('バックアップデータが取得できませんでした。.');
						}

						$backup_id = $backup_data[tsBackup::TS_BACKUP_ID_SEQ];

						if (!$backup_id) {
							// エラー処理
							throw new \Exception('バックアップIDが存在しないため復元処理は実施されませんでした。');
						}

						$logstr = "バックアップID：" . $backup_id . "\r\n";
						$logstr .= "バックアップ日時：" . $backup_data[tsBackup::TS_BACKUP_DATETIME] . "\r\n";
						$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

						$backup_dirname = $this->main->common()->format_gmt_datetime($backup_data[tsBackup::TS_BACKUP_DATETIME], define::DATETIME_FORMAT_SAVE);
					
						$logstr = "バックアップディレクトリ：" . $backup_dirname . "\r\n";
						$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

						if (!$backup_dirname) {
							// エラー処理
							throw new \Exception('Backup dirname not found.');
						}


						$output_dataArray = array(

							tsOutput::TS_OUTPUT_RESERVE_ID 		=> null,
							tsOutput::TS_OUTPUT_BACKUP_ID		=> $backup_id,
							tsOutput::TS_OUTPUT_RESERVE 		=> null,
							tsOutput::TS_OUTPUT_BRANCH 			=> null,
							tsOutput::TS_OUTPUT_COMMIT_HASH 	=> null,
							tsOutput::TS_OUTPUT_COMMENT 		=> null,
							tsOutput::TS_OUTPUT_PUBLISH_TYPE 	=> $publish_type,
							tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_RUNNING,
							tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => null,
							tsOutput::TS_OUTPUT_START 			=> $start_datetime,
							tsOutput::TS_OUTPUT_END 			=> null,
							tsOutput::TS_OUTPUT_GEN_DELETE_FLG 	=> define::DELETE_FLG_OFF,
							tsOutput::TS_OUTPUT_GEN_DELETE 		=> null,
							tsOutput::TS_OUTPUT_INSERT_DATETIME => $now,
							tsOutput::TS_OUTPUT_INSERT_USER_ID 	=> $this->main->options->user_id,
							tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
							tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> null
						);

					} elseif ($publish_type == define::PUBLISH_TYPE_IMMEDIATE) {

						$output_dataArray = array(

							tsOutput::TS_OUTPUT_RESERVE_ID 		=> null,
							tsOutput::TS_OUTPUT_BACKUP_ID		=> null,
							tsOutput::TS_OUTPUT_RESERVE 		=> null,
							tsOutput::TS_OUTPUT_BRANCH 			=> $this->main->options->_POST->branch_select_value,
							tsOutput::TS_OUTPUT_COMMIT_HASH 	=> $this->main->options->_POST->commit_hash,
							tsOutput::TS_OUTPUT_COMMENT 		=> $this->main->options->_POST->comment,
							tsOutput::TS_OUTPUT_PUBLISH_TYPE 	=> $publish_type,
							tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_RUNNING,
							tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => null,
							tsOutput::TS_OUTPUT_START 			=> $start_datetime,
							tsOutput::TS_OUTPUT_END 			=> null,
							tsOutput::TS_OUTPUT_GEN_DELETE_FLG 	=> define::DELETE_FLG_OFF,
							tsOutput::TS_OUTPUT_GEN_DELETE 		=> null,
							tsOutput::TS_OUTPUT_INSERT_DATETIME => $now,
							tsOutput::TS_OUTPUT_INSERT_USER_ID 	=> $this->main->options->user_id,
							tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
							tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> null
						);
					}

					/* トランザクションを開始する。オートコミットがオフになる */
					$this->main->get_dbh()->beginTransaction();

					$logstr = "==========トランザクション開始==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

					//============================================================
					// 公開処理結果テーブルの登録処理
					//============================================================
					$logstr = "==========公開処理結果テーブルのINSERT実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

					// 現在時刻
					$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

					// $reserve_id = null;

					// $dataArray = array(

					// 	tsOutput::TS_OUTPUT_RESERVE_ID 		=> null,
					// 	tsOutput::TS_OUTPUT_BACKUP_ID		=> $backup_id,
					// 	tsOutput::TS_OUTPUT_RESERVE 		=> null,
					// 	tsOutput::TS_OUTPUT_BRANCH 			=> $this->main->options->_POST->branch_select_value,
					// 	tsOutput::TS_OUTPUT_COMMIT_HASH 	=> $this->main->options->_POST->commit_hash,
					// 	tsOutput::TS_OUTPUT_COMMENT 		=> $this->main->options->_POST->comment,
					// 	tsOutput::TS_OUTPUT_PUBLISH_TYPE 	=> $publish_type,
					// 	tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_RUNNING,
					// 	tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => null,
					// 	tsOutput::TS_OUTPUT_START 			=> $start_datetime,
					// 	tsOutput::TS_OUTPUT_END 			=> null,
					// 	tsOutput::TS_OUTPUT_GEN_DELETE_FLG 	=> define::DELETE_FLG_OFF,
					// 	tsOutput::TS_OUTPUT_GEN_DELETE 		=> null,
					// 	tsOutput::TS_OUTPUT_INSERT_DATETIME => $now,
					// 	tsOutput::TS_OUTPUT_INSERT_USER_ID 	=> $this->main->options->user_id,
					// 	tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
					// 	tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> null
					// );

					// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
					$result['output_id'] = $this->tsOutput->insert_ts_output($output_dataArray);


			 		/* 変更をコミットする */
					$this->main->get_dbh()->commit();

					$logstr = "==========コミット処理実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

					$logstr = "公開処理結果テーブル登録ID：" . $result['output_id'];
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);



					if ($publish_type == define::PUBLISH_TYPE_IMMEDIATE) {

						// ============================================================
						// 選択されたブランチのGit情報を「running」ディレクトリへコピー
						// ============================================================
						$logstr = "==========[即時公開]Git情報をrunningへコピー==========";
						$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

						// Git情報のコピー処理
						$this->main->gitMgr()->git_file_copy($this->main->options, $realpath_array->realpath_running, $running_dirname);

					} elseif (($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) || 
							  ($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE)) {

						//============================================================
						// バックアップディレクトリを「backup」から「running」ディレクトリへコピー
						//============================================================
						$logstr = "==========[復元公開]backupからrunningへディレクトリのコピー==========";
						$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

						$from_realpath = $realpath_array->realpath_backup . $backup_dirname . '/';
						$to_realpath = $realpath_array->realpath_running . $running_dirname . '/';
						
						$logstr = "backupディレクトリ --> " . $from_realpath . "\r\n";
						$logstr .= "runningディレクトリ --> " . $to_realpath;
						$this->main->common()->put_process_log_block($logstr);

						$this->exec_sync_copy($from_realpath, $to_realpath);
					}
				}

		    } catch (\Exception $e) {
		    		    
				// $logstr = "===============================================" . "\r\n";
				// $logstr .= "公開処理の事前準備ロールバック" . "\r\n";
				// $logstr .= "===============================================" . "\r\n";
				// // $logstr .= $e.getMessage() . "\r\n";
				// $this->main->common()->put_process_log(__METHOD__, __LINE__, $realpath_tracelog, $logstr);

			 //    /* 変更をロールバックする */
			 //    $this->main->get_dbh()->rollBack();
		 
		     	throw $e;
		    }

		    // 自動復元公開の場合は、本番環境からバックアップは取得しない
		    if ($publish_type != define::PUBLISH_TYPE_AUTO_RESTORE) {

				try {

					/* トランザクションを開始する。オートコミットがオフになる */
					$this->main->get_dbh()->beginTransaction();
						
					$logstr = "==========トランザクション開始==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

					//============================================================
					// バックアップテーブルの登録処理
					//============================================================
					$logstr = "==========バックアップテーブルのINSERT実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

					// GMTの現在日時
					$backup_datetime = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
					$backup_dirname = $this->main->common()->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);

					$logstr = "バックアップ日時 --> " . $backup_datetime . "\r\n";
					$logstr .= "バックアップディレクトリ名 --> " . $backup_dirname;
					$this->main->common()->put_process_log_block($logstr);

					$result['backup_id'] = $this->tsBackup->insert_ts_backup($this->main->options, $backup_datetime, $result['output_id']);


					//============================================================
					// 本番ソースを「backup」ディレクトリへコピー
					//============================================================
					$logstr = "==========本番ソースをbackupディレクトリへコピー==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
					$from_realpath = $realpath_array->realpath_server;
					$to_realpath = $realpath_array->realpath_backup . $backup_dirname . '/';

					$logstr = "本番環境ディレクトリ --> " . $from_realpath . "\r\n";
					$logstr .= "backupディレクトリ --> " . $to_realpath;
					$this->main->common()->put_process_log_block($logstr);

					// rsyncによるディレクトリのコピー処理
					$this->exec_sync_copy($from_realpath, $to_realpath);

			 		// 変更をコミットする
					$this->main->get_dbh()->commit();
					/* データベース接続はオートコミットモードに戻る */

					$logstr = "==========コミット処理実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

					$logstr = "バックアップテーブル登録ID：" . $result['backup_id'];
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);


			    } catch (\Exception $e) {
		
					$logstr = "==========バックアップテーブルのロールバック処理実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

				    /* 変更をロールバックする */
				    $this->main->get_dbh()->rollBack();
			 
			     	throw $e;
			    }
		    }

			try {
			
				/* トランザクションを開始する。オートコミットがオフになる */
				$this->main->get_dbh()->beginTransaction();

				$logstr = "==========トランザクション開始==========";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
				
				//============================================================
				// 公開処理結果テーブルの更新処理（成功）
				//============================================================
				$logstr = "===============================================" . "\r\n";
				$logstr .= "公開処理結果テーブルの更新処理（ステータス：成功）" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
				
				// GMTの現在日時
				$end_datetime = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

				$dataArray = array(
					tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_SUCCESS,
					tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
					tsOutput::TS_OUTPUT_END 			=> $end_datetime,
					tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> $this->main->options->user_id
				);

		 		$this->tsOutput->update_ts_output($result['output_id'], $dataArray);


				//============================================================
				// runningディレクトリを本番環境へ同期
				//============================================================
				$logstr = "===============================================" . "\r\n";
				$logstr .= "※runningディレクトリを本番環境へ同期※" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

				$from_realpath = $realpath_array->realpath_running . $running_dirname . '/';
				$to_realpath = $realpath_array->realpath_server;

				$logstr = "runningディレクトリ --> " . $from_realpath . "\r\n";
				$logstr .= "本番環境ディレクトリ --> " . $to_realpath;
				$this->main->common()->put_process_log_block($logstr);

				$this->exec_sync($this->main->options->ignore, $from_realpath, $to_realpath);

				//============================================================
				// 公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動
				//============================================================
				$logstr = "==========runningディレクトリからreleasedディレクトリへ移動==========";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

				$from_realpath = $realpath_array->realpath_running . $running_dirname . '/';
				$to_realpath = $realpath_array->realpath_released . $running_dirname . '/';

				$logstr = "runningディレクトリ --> " . $from_realpath . "\r\n";
				$logstr .= "releasedディレクトリ： --> " . $to_realpath;
				$this->main->common()->put_process_log_block($logstr);

				// rsyncによるディレクトリの移動処理
				$this->exec_sync_move($from_realpath, $to_realpath);

		 		/* 変更をコミットする */
				$this->main->get_dbh()->commit();
				/* データベース接続はオートコミットモードに戻る */

				$logstr = "==========コミット処理実行==========";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

				// $logstr = "バックアップテーブル登録ID：" . $result['backup_id'];
				// $this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		    } catch (\Exception $e) {
		    
				$logstr = "===============================================" . "\r\n";
				$logstr .= "公開処理結果テーブルのロールバック処理実行" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$logstr .= $e->getMessage() . "\r\n";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		    	/* 変更をロールバックする */
		    	$this->main->get_dbh()->rollBack();
		 
		    	throw $e;
		    }

		} catch (\Exception $e) {

			// $logstr = "** exec_publish 例外キャッチ **" . "\r\n";
			// $logstr .= $e->getMessage() . "\r\n";
			// $this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);


			$logstr =  "***** exec_publish 例外キャッチ *****" . "\r\n";
			$logstr .= "[ERROR]" . "\r\n";
			$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
			$logstr .= "Error message:" . $e->getMessage() . "\r\n";
			$this->main->common()->put_error_log($logstr);

			$result['status'] = false;
			$result['message'] = '公開処理が失敗しました。';

			//============================================================
			// 公開処理結果テーブルの更新処理（失敗）
			//============================================================
			$logstr = "==========公開処理結果テーブルの更新処理（ステータス：失敗）==========";
			$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			if ($result['output_id']) {

				// GMTの現在日時
				$end_datetime = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
				$dataArray = array(
					tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_FAILED,
					tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
					tsOutput::TS_OUTPUT_END 			=> $end_datetime,
					tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> $this->main->options->user_id
				);

				// テーブル更新
		 		$this->tsOutput->update_ts_output($result['output_id'], $dataArray);
			}

			// ロック解除処理
			$this->unlock();

			$logstr = "===============================================" . "\r\n";
			$logstr .= "公開処理中止" . "\r\n";
			$logstr .= "===============================================" . "\r\n";
			$this->main->common()->put_process_log_block($logstr);

			return $result;
		}

		$result['status'] = true;

		// ロック解除処理
		$this->unlock();

		$logstr = "===============================================" . "\r\n";
		$logstr .= "公開処理完了" . "\r\n";
		$logstr .= "===============================================";
		$this->main->common()->put_process_log_block($logstr);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish end');

		return $result;
	}

	/**
	 * rsyncコマンド実行（公開処理用）
	 */
	public function exec_sync($ignore, $from_realpath, $to_realpath) {

		// ※runningディレクトリパスの後ろにはスラッシュは付けない（スラッシュを付けると日付ディレクトリも含めて同期してしまう）
			
		// 同期除外コマンドの作成
		$exclude_command = '';
		foreach ($ignore as $key => $value) {
		 	$exclude_command .= "--exclude='" . $value . "' ";
		}

		$command = 'rsync --checksum -rvzP --delete ' . $exclude_command . $from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $this->realpath_tracelog;

		$ret = $this->main->common()->command_execute($command, true);
		if ($ret['return']) {
			// 戻り値が0以外の場合
			throw new \Exception('Command error. [command]' . $command);
		}

		// rsyncコマンド実行ログ配列を、履歴一覧画面のダイアログ表示用にファイル保持しておく
		file_put_contents($this->realpath_copylog, $ret['output'], FILE_APPEND);
	}

	/**
	 * rsyncコマンド実行（ディレクトリコピー用）
	 */
	public function exec_sync_copy($from_realpath, $to_realpath) {

		$logstr = "==========rsyncコマンドによるディレクトリのコピー実行==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		
		$command = 'rsync -rtvzP' . ' ' . $from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $this->realpath_tracelog;
		
		// $logstr = "コマンド --> " . $command;
		// $this->main->common()->put_process_log_block($logstr);

		$ret = $this->main->common()->command_execute($command, true);
		if ($ret['return']) {
			// 戻り値が0以外の場合
					
			$logstr = "**コマンド実行エラー**" . "\r\n";
			$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			throw new \Exception('Command error.');
		}

		$logstr = "**コマンド実行成功**";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
	}

	/**
	 * rsyncコマンド実行（ディレクトリ移動用）
	 */
	public function exec_sync_move($from_realpath, $to_realpath) {

		$logstr = "==========rsyncコマンドによるディレクトリの移動実行==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		$command = 'rsync -rtvzP --remove-source-files ' . $from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $this->realpath_tracelog;

		$ret = $this->main->common()->command_execute($command, true);
		if ($ret['return']) {
			// 戻り値が0以外の場合

			$logstr = "**移動コマンド実行エラー**" . "\r\n";
			$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			throw new \Exception('Command error.');
		}

		$logstr = "**移動コマンド実行成功**";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);


		$logstr = "==========rsyncコマンドによる移動元の空ディレクトリ削除==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);


		$logstr .= "移動元ディレクトリ --> " . $from_realpath;
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		$command = 'find ' .  $from_realpath . ' -type d -empty -delete' ;

		$ret = $this->main->common()->command_execute($command, true);
		if ($ret['return']) {
			// 戻り値が0以外の場合

			$logstr = "**削除コマンド実行エラー**" . "\r\n";
			$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			throw new \Exception('Command error.');
		}

		$logstr = "**削除コマンド実行成功**";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
	}


	/**
	 * パブリッシュをロックする。
	 *
	 * @return bool ロック成功時に `true`、失敗時に `false` を返します。
	 */
	private function lock(){

		$logstr = "==========パブリッシュのロック処理 START==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
	
		$lockfilepath = $this->path_lockfile;
		$timeout_limit = 5;

		// 親ディレクトリのチェック生成
		if( !@is_dir( dirname( $lockfilepath ) ) ){
			$this->main->fs()->mkdir_r( dirname( $lockfilepath ) );
		}

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		$i = 0;
		while( $this->is_locked() ){
			$i ++;
			if( $i >= $timeout_limit ){

				$logstr = "==========パブリッシュロック中のため処理中断==========";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

				return false;
				// break;
			}
			sleep(1);

			#	PHPのFileStatusCacheをクリア
			clearstatcache();
		}

		$logstr = "==========パブリッシュのロック作成 START==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
	
		$src = '';
		$src .= 'ProcessID='.getmypid()."\r\n";
		// $src .= @date( 'Y-m-d H:i:s' , time() )."\r\n";
		$src .= 'Date='.@gmdate( 'Y-m-d H:i:s' , time() );
		$rtn = $this->main->fs()->save_file( $lockfilepath , $src );

		$logstr = "ロックファイル作成結果：rtn=" . $rtn . "\r\n";
		$logstr .= $src;
		$this->main->common()->put_process_log_block($logstr);

		$logstr = "==========パブリッシュのロック作成 END==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		return	$rtn;

	}//lock()

	/**
	 * パブリッシュがロックされているか確認する。
	 *
	 * @return bool ロック中の場合に `true`、それ以外の場合に `false` を返します。
	 */
	private function is_locked(){
	
		$lockfilepath = $this->path_lockfile;
		$lockfile_expire = 12*60*60;//有効期限は12時間（過ぎた場合はロック解除してもよいこととする）

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		if( $this->main->fs()->is_file($lockfilepath) ){
			if( ( time() - filemtime($lockfilepath) ) > $lockfile_expire ){
				#	有効期限を過ぎていたら、ロックは成立する。

				$logstr = "※パブリッシュのロック確認 --->>> ロック無し（有効期限の超過）";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
				return false;
			}

			$logstr = "※パブリッシュのロック確認 --->>> ロック中";
			$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			return true;
		}

		$logstr = "※パブリッシュのロック確認 --->>> ロック無し";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		return false;

	}//is_locked()

	/**
	 * パブリッシュロックを解除する。
	 *
	 * @return bool ロック解除成功時に `true`、失敗時に `false` を返します。
	 */
	private function unlock(){

		$logstr = "==========パブリッシュのロック解除==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		$lockfilepath = $this->path_lockfile;

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		return @unlink( $lockfilepath );
	}//unlock()

}

