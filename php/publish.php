<?php

namespace indigo;

use indigo\db\tsReserve as tsReserve;
use indigo\db\tsOutput as tsOutput;
use indigo\db\tsBackup as tsBackup;

class publish
{

	/** indigo\main のインスタンス */
	private $main;

	/** indigo\db\tsReserve のインスタンス */
	private $tsReserve;

	/** indigo\db\tsOutput のインスタンス */
	private $tsOutput;

	/** indigo\db\tsBackup のインスタンス */
	private $tsBackup;

	/** 公開ロックファイルの格納パス */
	private $path_lockfile;

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
		
		$path_lockdir = $main->fs()->get_realpath( $this->main->options->realpath_workdir . 'applock/' );
		$this->path_lockfile = $path_lockdir .'applock.txt';
	}


	/**
	 * 公開処理
	 */
	public function exec_publish($publish_type, $output_id) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish start');

		$output = "";
		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '',
						'output_id' => '',
						'backup_id' => '');

		// 開始日時（GMT）
		$start_datetime = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		// クーロン実行で、予約公開データが存在しない場合は処理を終了する
		if ($publish_type == define::PUBLISH_TYPE_RESERVE) {

			//============================================================
			// 公開予約テーブルより、公開対象データの取得
			//============================================================
			$reserve_data_list = $this->tsReserve->get_ts_reserve_publish_list($start_datetime);
			
			if (!$reserve_data_list) {

				$logstr = 'Cron Publish data does not exist.';
				$result['message'] = $logstr;

				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
				
				$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish end');

				return $result;
			}
		}

		$backup_dirname;

		$result['output_id'] = $output_id;


		//============================================================
		// ログ生成
		//============================================================
		// 作業用ディレクトリの絶対パスを取得
		$realpath_array = $this->main->realpath_array;
		
		// GMT現在日時をディレクトリ名用にフォーマット変換
		$running_dirname = $this->main->common()->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

		// 同期ログ（履歴表示画面のログダイアログに表示）
		$this->realpath_copylog = $this->main->fs()->normalize_path($this->main->fs()->get_realpath(
			$realpath_array['realpath_log'] . $running_dirname . "/")) . 'pub_copy_' . $running_dirname . '.log';
		// 公開処理実行ログ
		$this->realpath_tracelog = $this->main->fs()->normalize_path($this->main->fs()->get_realpath(
			$realpath_array['realpath_log'] . $running_dirname . "/")) . 'pub_trace_' . $running_dirname . '.log';

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

			$result['message'] = '公開ロック中となっております。しばらくお待ちいただいてもロックが解除されない場合は、管理者にお問い合わせください。';

			return $result;
		}

		try {

			set_time_limit(12*60*60);

			$logstr = "公開種別：" . $publish_type . "\r\n";
			$logstr .= "公開処理開始日時：" . $start_datetime . "\r\n";
			$logstr .= "公開日時ディレクトリ名：" . $running_dirname;
			$this->main->common()->put_process_log_block($logstr);
			$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

			try {

				// 予約公開の場合
				if ($publish_type == define::PUBLISH_TYPE_RESERVE) {

					// $logstr = "===============================================" . "\r\n";
					// $logstr .= "[予約公開]公開処理" . "\r\n";
					// $logstr .= "===============================================";
					// $this->main->common()->put_process_log_block($logstr);
					// $this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					$cnt = 1;
					$status = define::PUBLISH_STATUS_RUNNING;
					$set_start_datetime = $start_datetime;

					/* トランザクションを開始する。オートコミットがオフになる */
					$this->main->dbh()->beginTransaction();
					
					$logstr = "==========トランザクション開始==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					// 複数件取れてきた場合、最新（先頭）データ以降はスキップデータとして公開処理結果テーブルへ登録する
					foreach ( (array) $reserve_data_list as $data ) {

						if ($cnt != 1) {
							$logstr = "-----------------------------------------------" . "\r\n";
							$logstr .= "スキップ処理" . "\r\n";
							$logstr .= "-----------------------------------------------" . "\r\n";
							$this->main->common()->put_process_log_block($logstr);
						}

						$logstr = "== 公開予約データ" . $cnt . "件目 ==" . "\r\n";
						$logstr .= "公開予約ID" . $data[tsReserve::TS_RESERVE_ID_SEQ] . "\r\n";
						$logstr .= "公開予約日時(GMT)：" . $data[tsReserve::TS_RESERVE_DATETIME] . "\r\n";
						$logstr .= "ブランチ名：" . $data[tsReserve::TS_RESERVE_BRANCH] . "\r\n";
						$logstr .= "コミット：" . $data[tsReserve::TS_RESERVE_COMMIT_HASH] . "\r\n";
						$logstr .= "コメント：" . $data[tsReserve::TS_RESERVE_COMMENT] . "\r\n";
						$logstr .= "ユーザID：" . $this->main->options->user_id;
						$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

						// 現在時刻
						$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

						$dataArray = array(
							tsOutput::TS_OUTPUT_RESERVE_ID 		=> $data[tsReserve::TS_RESERVE_ID_SEQ],
							tsOutput::TS_OUTPUT_BACKUP_ID 		=> null,
							tsOutput::TS_OUTPUT_RESERVE 		=> $data[tsReserve::TS_RESERVE_DATETIME],
							tsOutput::TS_OUTPUT_BRANCH 			=> $data[tsReserve::TS_RESERVE_BRANCH],
							tsOutput::TS_OUTPUT_COMMIT_HASH 	=> $data[tsReserve::TS_RESERVE_COMMIT_HASH],
							tsOutput::TS_OUTPUT_COMMENT 		=> $data[tsReserve::TS_RESERVE_COMMENT],
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

						$logstr = "-----------------------------------------------" . "\r\n";
						$logstr .= "[予約公開]公開処理結果テーブルの登録処理" . "\r\n";
						$logstr .= "-----------------------------------------------";
						$this->main->common()->put_process_log_block($logstr);

						// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
						$insert_id = $this->tsOutput->insert_ts_output($dataArray);

						if ($cnt == 1) {

							$result['output_id'] = $insert_id;


							$logstr = "-----------------------------------------------" . "\r\n";
							$logstr .= "予約対象" . "\r\n";
							$logstr .= "-----------------------------------------------" . "\r\n";
							$logstr .= "公開処理結果テーブル登録ID：" . $result['output_id'] . "\r\n";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
							$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

							$reserve_dirname = $this->main->common()->format_gmt_datetime($data[tsReserve::TS_RESERVE_DATETIME], define::DATETIME_FORMAT_SAVE);

							if (!$reserve_dirname) {
								// エラー処理
								throw new \Exception('Dirname create failed.');
							} else {
								$reserve_dirname .= define::DIR_NAME_RESERVE;
							}

							$logstr = "公開対象のwaitingディレクトリ名'" . $reserve_dirname . "\r\n";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
							$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

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
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

						$this->tsReserve->update_ts_reserve_status($data[tsReserve::TS_RESERVE_ID_SEQ], $data[tsReserve::TS_RESERVE_VER_NO]);
						
						$cnt++;
					}

			 		/* 変更をコミットする */
					$this->main->dbh()->commit();
					/* データベース接続はオートコミットモードに戻る */

					$logstr = "==========コミット処理実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					//============================================================
					// 公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動
					//============================================================
					$logstr = "===============================================" . "\r\n";
					$logstr .= "waitingディレクトリからrunningディレクトリへ移動" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					$from_realpath = $realpath_array['realpath_waiting'] . $reserve_dirname . '/';
					$to_realpath = $realpath_array['realpath_running'] . $running_dirname . '/';

					$this->exec_sync_move($from_realpath, $to_realpath);

				// 即時公開、手動復元公開、自動復元公開の場合
				} else {

					$backup_id = null;
					$backup_dirname = '';

					// 公開処理結果データ
					$output_dataArray = null;

					// 現在時刻
					$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

 					if (($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) ||
						($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE)) {

						//============================================================
						// バックアップテーブルより、公開対象データの取得
						//============================================================
						$backup_data = null;

						if ($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) {

							$logstr = "==========[手動復元公開]バックアップ対象データの取得==========";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
							$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

							$selected_id =  $this->main->options->_POST->selected_id;

							$logstr = "選択バックアップID --> " . $selected_id . "\r\n";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
							$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

							$backup_data = $this->tsBackup->get_selected_ts_backup($selected_id);
						
						} else {

							$logstr = "==========[自動復元公開]バックアップ対象データの取得==========";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
							$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

							$logstr = "公開処理結果ID --> " . $output_id . "\r\n";
							$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
							$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

							// 処理結果IDからバックアップ情報を取得
							$backup_data = $this->tsBackup->get_selected_ts_backup_by_output_id($output_id);
						}

						if (!$backup_data) {
							throw new \Exception('バックアップデータが取得できませんでした。.');
						}

						$logstr = "backup_data --> " . implode("|" , $backup_data) . "\r\n";
						$this->main->common()->put_process_log_block($logstr);

						$backup_id = $backup_data[tsBackup::TS_BACKUP_ID_SEQ];

						if (!$backup_id) {
							// エラー処理
							throw new \Exception('バックアップIDが存在しないため復元処理は実施されませんでした。');
						}

						$logstr = "バックアップID --> " . $backup_id . "\r\n";
						$logstr .= "バックアップ日時 --> " . $backup_data[tsBackup::TS_BACKUP_DATETIME];
						$this->main->common()->put_process_log_block($logstr);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

						$backup_dirname = $this->main->common()->format_gmt_datetime($backup_data[tsBackup::TS_BACKUP_DATETIME], define::DATETIME_FORMAT_SAVE);
					
						// $logstr = "バックアップディレクトリ：" . $backup_dirname . "\r\n";
						// $this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

						if (!$backup_dirname) {
							// エラー処理
							throw new \Exception('Backup dirname not found.');
						}


						// 復元公開時の公開処理結果テーブル設定情報
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
						// 即時公開時の公開処理結果テーブル設定情報

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
					$this->main->dbh()->beginTransaction();

					$logstr = "==========トランザクション開始==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					//============================================================
					// 公開処理結果テーブルの登録処理
					//============================================================
					$logstr = "==========公開処理結果テーブルのINSERT実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
					$result['output_id'] = $this->tsOutput->insert_ts_output($output_dataArray);


			 		/* 変更をコミットする */
					$this->main->dbh()->commit();

					$logstr = "==========コミット処理実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					$logstr = "公開処理結果テーブル登録ID：" . $result['output_id'];
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);


					if ($publish_type == define::PUBLISH_TYPE_IMMEDIATE) {

						// ============================================================
						// 選択されたブランチのGit情報を「running」ディレクトリへコピー
						// ============================================================
						$logstr = "==========[即時公開]Git情報をrunningへコピー==========";
						$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

						// Git情報のコピー処理
						$this->main->gitMgr()->git_file_copy($this->main->options, $realpath_array['realpath_running'], $running_dirname);

					} elseif (($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) || 
							  ($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE)) {

						//============================================================
						// バックアップディレクトリを「backup」から「running」ディレクトリへコピー
						//============================================================
						$logstr = "==========[復元公開]backupからrunningへディレクトリのコピー==========";
						$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

						$from_realpath = $realpath_array['realpath_backup'] . $backup_dirname . '/';
						$to_realpath = $realpath_array['realpath_running'] . $running_dirname . '/';
						
						$logstr = "backupディレクトリ --> " . $from_realpath . "\r\n";
						$logstr .= "runningディレクトリ --> " . $to_realpath;
						$this->main->common()->put_process_log_block($logstr);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

						$this->exec_sync_copy($from_realpath, $to_realpath);
					}
				}

		    } catch (\Exception $e) {
		    		    
				// $logstr = "===============================================" . "\r\n";
				// $logstr .= "公開処理の事前準備ロールバック" . "\r\n";
				// $logstr .= "===============================================" . "\r\n";
				// // $logstr .= $e.getMessage() . "\r\n";
				// $this->main->common()->put_process_log(__METHOD__, __LINE__, $this->realpath_tracelog, $logstr);

			 //    /* 変更をロールバックする */
			 //    $this->main->dbh()->rollBack();
		 
		     	throw $e;
		    }

		    // 自動復元公開の場合は、本番環境からバックアップは取得しない
		    if ($publish_type != define::PUBLISH_TYPE_AUTO_RESTORE) {

				try {

					/* トランザクションを開始する。オートコミットがオフになる */
					$this->main->dbh()->beginTransaction();
						
					$logstr = "==========トランザクション開始==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					//============================================================
					// バックアップテーブルの登録処理
					//============================================================
					$logstr = "==========バックアップテーブルのINSERT実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					// GMTの現在日時
					$backup_datetime = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
					$backup_dirname = $this->main->common()->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);

					$logstr = "バックアップ日時 --> " . $backup_datetime . "\r\n";
					$logstr .= "バックアップディレクトリ名 --> " . $backup_dirname;
					$this->main->common()->put_process_log_block($logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					$result['backup_id'] = $this->tsBackup->insert_ts_backup($this->main->options, $backup_datetime, $result['output_id']);


					//============================================================
					// 本番ソースを「backup」ディレクトリへコピー
					//============================================================
					$logstr = "==========本番ソースをbackupディレクトリへコピー==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					$from_realpath = $realpath_array['realpath_server'];
					$to_realpath = $realpath_array['realpath_backup'] . $backup_dirname . '/';

					$logstr = "本番環境ディレクトリ --> " . $from_realpath . "\r\n";
					$logstr .= "backupディレクトリ --> " . $to_realpath;
					$this->main->common()->put_process_log_block($logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					// rsyncによるディレクトリのコピー処理
					$this->exec_sync_copy($from_realpath, $to_realpath);

			 		// 変更をコミットする
					$this->main->dbh()->commit();
					/* データベース接続はオートコミットモードに戻る */

					$logstr = "==========コミット処理実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

					$logstr = "バックアップテーブル登録ID：" . $result['backup_id'];
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

			    } catch (\Exception $e) {
		
					$logstr = "==========バックアップテーブルのロールバック処理実行==========";
					$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

				    /* 変更をロールバックする */
				    $this->main->dbh()->rollBack();
			 
			     	throw $e;
			    }
		    }

			try {
			
				/* トランザクションを開始する。オートコミットがオフになる */
				$this->main->dbh()->beginTransaction();

				$logstr = "==========トランザクション開始==========";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

				//============================================================
				// 公開処理結果テーブルの更新処理（成功）
				//============================================================
				$logstr = "===============================================" . "\r\n";
				$logstr .= "公開処理結果テーブルの更新処理（ステータス：成功）" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

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
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

				$from_realpath = $realpath_array['realpath_running'] . $running_dirname . '/';
				$to_realpath = $realpath_array['realpath_server'];

				$logstr = "runningディレクトリ --> " . $from_realpath . "\r\n";
				$logstr .= "本番環境ディレクトリ --> " . $to_realpath;
				$this->main->common()->put_process_log_block($logstr);
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

				$this->exec_sync($this->main->options->ignore, $from_realpath, $to_realpath);

				//============================================================
				// 公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動
				//============================================================
				$logstr = "==========runningディレクトリからreleasedディレクトリへ移動==========";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

				$from_realpath = $realpath_array['realpath_running'] . $running_dirname . '/';
				$to_realpath = $realpath_array['realpath_released'] . $running_dirname . '/';

				$logstr = "runningディレクトリ --> " . $from_realpath . "\r\n";
				$logstr .= "releasedディレクトリ： --> " . $to_realpath;
				$this->main->common()->put_process_log_block($logstr);
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

				// rsyncによるディレクトリの移動処理
				$this->exec_sync_move($from_realpath, $to_realpath);

		 		/* 変更をコミットする */
				$this->main->dbh()->commit();
				/* データベース接続はオートコミットモードに戻る */

				$logstr = "==========コミット処理実行==========";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

				// $logstr = "バックアップテーブル登録ID：" . $result['backup_id'];
				// $this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		    } catch (\Exception $e) {
		    
				$logstr = "===============================================" . "\r\n";
				$logstr .= "公開処理結果テーブルのロールバック処理実行" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$logstr .= $e->getMessage() . "\r\n";
				$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		    	/* 変更をロールバックする */
		    	$this->main->dbh()->rollBack();
		 
		    	throw $e;
		    }

		} catch (\Exception $e) {

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
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

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
			$logstr .= "===============================================";
			$this->main->common()->put_process_log_block($logstr);
			$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);


			$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish end');

			return $result;
		}

		$result['status'] = true;
		$result['message'] = '公開処理が成功しました。';

		// ロック解除処理
		$this->unlock();

		$logstr = "===============================================" . "\r\n";
		$logstr .= "公開処理完了" . "\r\n";
		$logstr .= "===============================================";
		$this->main->common()->put_process_log_block($logstr);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish end');

		return $result;
	}

	/**
	 * rsyncコマンドにて公開処理を実施する
	 *
	 * runningディレクトリパスの最後にはスラッシュは付けない（スラッシュを付けると日付ディレクトリも含めて同期してしまう）
	 * log出力するファイルは、履歴一覧画面のログダイアログ表示にて使用するため、この処理のみ異なるファイルに出力する。
	 *
	 * [使用オプション]
	 *		-r 再帰的にコピー（指定ディレクトリ配下をすべて対象とする）
	 *		-h ファイルサイズのbytesをKやMで出力
	 *		-v 処理の経過を表示
	 *		-z 転送中のデータを圧縮する
	 *		--checksum ファイルの中身に差分があるファイルを対象とする
	 *		--delete   転送元に存在しないファイルは削除
	 *		--exclude  同期から除外する対象を指定
	 *		--log-file ログ出力
	 *
	 * @param  array  $ignore 			同期除外ファイル、ディレクトリ名
	 * @param  string $from_realpath 	同期元の絶対パス
	 * @param  string $to_realpath		同期先の絶対パス
	 */
	public function exec_sync($ignore, $from_realpath, $to_realpath) {

		$logstr = "==========rsyncコマンドによるディレクトリの同期実行==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		// 除外コマンドの作成
		$exclude_command = '';
		foreach ($ignore as $key => $value) {
		 	$exclude_command .= "--exclude='" . $value . "' ";
		}

		$command = 'rsync --checksum -rhvz --delete ' .
					$exclude_command .
					$from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $this->realpath_copylog;

		$this->main->common()->command_execute($command, true);
	}

	/**
	 * rsyncコマンドにてディレクトリのコピーを実施する
	 *
	 * [使用オプション]
	 *		-r 再帰的にコピー（指定ディレクトリ配下をすべて対象とする）
	 *		-h ファイルサイズのbytesをKやMで出力
	 *		-t 	タイムスタンプを維持して転送する
	 *		-v 処理の経過を表示
	 *		-z 転送中のデータを圧縮する
	 *		--log-file ログ出力
	 *
	 * @param  string $from_realpath 	コピー元の絶対パス
	 * @param  string $to_realpath		コピー先の絶対パス
	 */
	public function exec_sync_copy($from_realpath, $to_realpath) {

		$logstr = "==========rsyncコマンドによるディレクトリのコピー実行==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$command = 'rsync -rhtvz' . ' ' .
					$from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $this->realpath_tracelog;
		
		$this->main->common()->command_execute($command, true);

	}

	/**
	 * rsyncコマンド実行（ディレクトリ移動用）
	 */

	/**
	 * rsyncコマンドにてディレクトリの移動を実施する
	 *
	 * [使用オプション]
	 *		-r 再帰的にコピー（指定ディレクトリ配下をすべて対象とする）
	 *		-h ファイルサイズのbytesをKやMで出力
	 *		-t 	タイムスタンプを維持して転送する
	 *		-v 処理の経過を表示
	 *		-z 転送中のデータを圧縮する
	 *		--remove-source-files 転送に成功したファイルは転送元から削除する (ディレクトリは残る)
	 *		--log-file ログ出力
	 *
	 * [使用オプション]
	 *		-find 指定パスを探す
	 *		-type d ディレクトリを検索
	 *		-empty ディレクトリ内が空かどうか調べる
	 *		-delete 削除する
	 *		-z 転送中のデータを圧縮する
	 *		--remove-source-files 転送に成功したファイルは転送元から削除する (ディレクトリは残る)
	 *		--log-file ログ出力
	 *
	 * @param  string $from_realpath 	移動元の絶対パス
	 * @param  string $to_realpath		移動先の絶対パス
	 */
	public function exec_sync_move($from_realpath, $to_realpath) {

		$logstr = "==========rsyncコマンドによるディレクトリの移動実行==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$command = 'rsync -rhtvz --remove-source-files ' .
					$from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $this->realpath_tracelog;

		$this->main->common()->command_execute($command, true);


		$logstr = "==========移動元の空ディレクトリ削除実行==========" . "\r\n";
		$logstr .= "削除ディレクトリ：" . $from_realpath;
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		// TODO:空のディレクトリを再帰的に削除する（エラーとなる）
		// $this->main->fs()->rmdir_r($from_realpath);

		// 空のディレクトリを再帰的に削除する
		$command = 'find ' .  $from_realpath . ' -type d -empty -delete' . ' ' .
				   '--log-file=' . $this->realpath_tracelog;

		$this->main->common()->command_execute($command, true);
	}


	/**
	 * パブリッシュをロックする。
	 *
	 * @return bool ロック成功時に `true`、失敗時に `false` を返します。
	 */
	private function lock(){

		$logstr = "==========パブリッシュのロック処理 START==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

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
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);
				return false;
				// break;
			}
			sleep(1);

			#	PHPのFileStatusCacheをクリア
			clearstatcache();
		}

		$logstr = "==========パブリッシュのロック作成 START==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$src = '';
		$src .= 'ProcessID='.getmypid()."\r\n";
		// $src .= @date( 'Y-m-d H:i:s' , time() )."\r\n";
		$src .= 'Date='. $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		$rtn = $this->main->fs()->save_file( $lockfilepath , $src );

		$logstr = "ロックファイル作成結果：rtn=" . $rtn . "\r\n";
		$logstr .= $src;
		$this->main->common()->put_process_log_block($logstr);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$logstr = "==========パブリッシュのロック作成 END==========";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

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
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);
				return false;
			}

			$logstr = "※パブリッシュのロック確認 --->>> ロック中";
			$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

			return true;
		}

		$logstr = "※パブリッシュのロック確認 --->>> ロック無し";
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

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
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$lockfilepath = $this->path_lockfile;

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		return @unlink( $lockfilepath );
	}//unlock()

}

