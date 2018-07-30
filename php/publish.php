<?php

namespace indigo;

class publish
{
	private $main;

	private $tsReserve, $tsOutput, $tsBackup;

	/** ロックファイルの格納パス */
	private $path_lockfile;

	/** パス設定 */
	private $path_lockdir;

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->tsReserve = new tsReserve($this);
		$this->tsOutput = new tsOutput($this);
		$this->tsBackup = new tsBackup($this);
		
		$this->path_lockdir = $main->fs()->get_realpath( $this->main->options->workdir_relativepath . 'applock/' );
		$this->path_lockfile = $this->path_lockdir.'applock.txt';

		$this->main->common()->debug_echo('　□ path_lockdir：' . $path_lockdir);
		$this->main->common()->debug_echo('　□ path_lockdir：' . $path_lockfile);
	}


	/**
	 * 公開処理
	 */
	public function exec_publish($publish_type, $output_id) {

		$this->main->common()->debug_echo('■ exec_publish start');

		$output = "";
		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '',
						'output_id' => '',
						'backup_id' => '');

		$backup_dirname;

		try {


			//============================================================
			// ログ出力用の日付ディレクトリ作成
			//============================================================
			// 作業用ディレクトリの絶対パスを取得
			$realpath_array = $this->main->realpath_array;
			
			// GMT現在日時を取得し、ディレクトリ名用にフォーマット変換
			$start_datetime = $this->main->common()->get_current_datetime_of_gmt();
			$running_dirname = $this->main->common()->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

			// logの日付ディレクトリを作成
			$realpath_copylog = $this->main->fs()->normalize_path($this->main->fs()->get_realpath(
				$realpath_array->realpath_log . $running_dirname . "/")) . 'pub_copy_' . $running_dirname . '.log';
			
			$realpath_tracelog = $this->main->fs()->normalize_path($this->main->fs()->get_realpath(
				$realpath_array->realpath_log . $running_dirname . "/")) . 'pub_trace_' . $running_dirname . '.log';

			$this->main->common()->debug_echo('　□ realpath_copylog' . $realpath_copylog);
			$this->main->common()->debug_echo('　□ realpath_tracelog' . $realpath_tracelog);

			// ログファイルの上位ディレクトリを作成
			if( !@is_dir( dirname( $realpath_copylog ) ) ){
				$this->main->fs()->mkdir_r( dirname( $realpath_copylog ) );
			}
			// ログファイルの上位ディレクトリを作成
			if( !@is_dir( dirname( $realpath_tracelog ) ) ){
				$this->main->fs()->mkdir_r( dirname( $realpath_tracelog ) );
			}

			$logstr = "ロック処理\r\n";
			$this->main->put_log($realpath_tracelog, $logstr);

			if( !$this->lock() ){//ロック
				// print '------'."\n";
				// print 'publish is now locked.'."\n";
				// print '  (lockfile updated: '.@date('Y-m-d H:i:s', filemtime($this->path_lockfile)).')'."\n";
				// print 'Try again later...'."\n";
				// print 'exit.'."\n";
				// print $this->cli_footer();
				// exit;
			}

			$logstr = "公開処理開始日時：" . $start_datetime . "\r\n";
			$logstr .= "公開日時ディレクトリ名：" . $running_dirname . "\r\n";
			$this->main->put_log($realpath_tracelog, $logstr);


			$src = '';

			// if (!$this->main->fs()->save_file( $realpath_copylog , $src )) {
			// 	throw new \Exception('Create copy logfile is failed. ' . $realpath_copylog);
			// }

			// if (!$this->main->fs()->save_file( $backup_logpath , $src )) {
			// 	throw new \Exception('Create backup logfile is failed. ' . $backup_logpath);
			// }

			try {

				/* トランザクションを開始する。オートコミットがオフになる */
				$this->main->get_dbh()->beginTransaction();

				// 予約公開の場合
				if ($publish_type == define::PUBLISH_TYPE_RESERVE) {
					
					//============================================================
					// 公開予約テーブルより、公開対象データの取得
					//============================================================
					$logstr = "===============================================" . "\r\n";
					$logstr .= "公開予約テーブルSELECT処理実行" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);

					$data_list = $this->tsReserve->get_ts_reserve_publish_list($this->main->get_dbh(), $start_datetime);
					
					if (!$data_list) {
						$this->main->common()->debug_echo('Target data does not exist.');
						return $result;
					}

					$cnt = 1;
					$status = define::PUBLISH_STATUS_RUNNING;
					$set_start_datetime = $start_datetime;

					// 複数件取れてきた場合は、最新データ以外はスキップデータとして公開処理結果テーブルへ登録する
					foreach ( (array) $data_list as $data ) {

						$logstr = "-----------------------------------------------" . "\r\n";
						$logstr .= "公開予約取得データ" . "\r\n";
						$logstr .= "-----------------------------------------------" . "\r\n";
						$this->main->put_log($realpath_tracelog, $logstr);
						$this->main->put_log($realpath_tracelog, $data);

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
						$this->main->put_log($realpath_tracelog, $logstr);

						// 現在時刻
						$now = $this->main->common()->get_current_datetime_of_gmt();

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
						$insert_id = $this->tsOutput->insert_ts_output($this->main->get_dbh(), $dataArray);

						if ($cnt == 1) {

							$result['output_id'] = $insert_id;


							$logstr = "-----------------------------------------------" . "\r\n";
							$logstr .= "予約対象" . "\r\n";
							$logstr .= "-----------------------------------------------" . "\r\n";
							$logstr .= "公開処理結果テーブル登録ID：" . $result['output_id'] . "\r\n";
							$this->main->put_log($realpath_tracelog, $logstr);

							$reserve_dirname = $this->main->common()->format_gmt_datetime($data[tsReserve::RESERVE_ENTITY_RESERVE_GMT], define::DATETIME_FORMAT_SAVE);

							if (!$reserve_dirname) {
								// エラー処理
								throw new \Exception('Dirname create failed.');
							} else {
								$reserve_dirname .= define::DIR_NAME_RESERVE;
							}

							$logstr = "公開対象のwaitingディレクトリ名'" . $reserve_dirname . "\r\n";
							$this->main->put_log($realpath_tracelog, $logstr);

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
						$this->main->put_log($realpath_tracelog, $logstr);
						
						$this->tsReserve->update_ts_reserve_status($this->main->get_dbh(), $data[tsReserve::RESERVE_ENTITY_ID_SEQ]);
					
						$cnt++;
					}

					//============================================================
					// 公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動
					//============================================================
					$logstr = "===============================================" . "\r\n";
					$logstr .= "waitingディレクトリからrunningディレクトリへ移動" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);
					
					$from_realpath = $realpath_array->realpath_waiting . $reserve_dirname . '/';
					$to_realpath = $realpath_array->realpath_running . $running_dirname . '/';

					$this->exec_sync_move($from_realpath, $to_realpath, $realpath_tracelog);

				} else {

					$backup_id = null;
					$backup_dirname = '';

 					if ($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) {

						//============================================================
						// バックアップテーブルより、公開対象データの取得
						//============================================================
						$logstr = "===============================================" . "\r\n";
						$logstr .= "[復元公開]バックアップ対象データの取得" . "\r\n";
						$logstr .= "===============================================" . "\r\n";
						$this->main->put_log($realpath_tracelog, $logstr);

						$selected_id =  $this->main->options->_POST->selected_id;

						$backup_data = $this->tsBackup->get_selected_ts_backup($this->main->get_dbh(), $selected_id);
					
						if (!$backup_data) {
							throw new \Exception('Target data not found.');
						}

						$backup_id = $backup_data[tsBackup::BACKUP_ENTITY_ID_SEQ];

						$logstr .= "バックアップID：" . $backup_id . "\r\n";
						$this->main->put_log($realpath_tracelog, $logstr);

						$backup_dirname = $this->main->common()->format_gmt_datetime($backup_data[tsBackup::BACKUP_ENTITY_DATETIME_GMT], define::DATETIME_FORMAT_SAVE);
					
						if (!$backup_dirname) {
							// エラー処理
							throw new \Exception('Backup dirname not found.');
						}
					
					} elseif ($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE) {

						//============================================================
						// 公開処理結果ID条件に、バックアップ対象データの取得
						//============================================================
						$logstr = "===============================================" . "\r\n";
						$logstr .= "[自動復元公開]公開処理結果ID条件に、バックアップ対象データの取得" . "\r\n";
						$logstr .= "===============================================" . "\r\n";
						$logstr .= "公開処理結果ID：" . $output_id . "\r\n";
						$this->main->put_log($realpath_tracelog, $logstr);

						// 処理結果IDからバックアップ情報を取得
						$backup_data = $this->tsBackup->get_selected_ts_backup_by_output_id($this->main->get_dbh(), $output_id);

						$backup_id = $backup_data[tsBackup::BACKUP_ENTITY_ID_SEQ];
						
						$logstr .= "バックアップID：" . $backup_id . "\r\n";
						$this->main->put_log($realpath_tracelog, $logstr);

						$backup_dirname = $this->main->common()->format_gmt_datetime($backup_data[tsBackup::BACKUP_ENTITY_DATETIME_GMT], define::DATETIME_FORMAT_SAVE);
					
						if (!$backup_dirname) {
							// エラー処理
							throw new \Exception('Backup dirname not found.');
						}
					}

					//============================================================
					// 公開処理結果テーブルの登録処理
					//============================================================
					$logstr = "===============================================" . "\r\n";
					$logstr .= "公開処理結果テーブルの登録処理" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);

					// 現在時刻
					$now = $this->main->common()->get_current_datetime_of_gmt();

					$reserve_id = null;

					$dataArray = array(
						tsOutput::TS_OUTPUT_RESERVE_ID 		=> null,
						tsOutput::TS_OUTPUT_BACKUP_ID		=> $backup_id,
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

					// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
					$result['output_id'] = $this->tsOutput->insert_ts_output($this->main->get_dbh(), $dataArray);

					$logstr = "公開処理結果テーブル登録ID：" . $result['output_id'] . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);

					if ($publish_type == define::PUBLISH_TYPE_IMMEDIATE) {

						// ============================================================
						// 選択されたブランチのGit情報を「running」ディレクトリへコピー
						// ============================================================
						$logstr = "===============================================" . "\r\n";
						$logstr .= "[即時公開]Git情報をrunningへコピー" . "\r\n";
						$logstr .= "===============================================" . "\r\n";
						$this->main->put_log($realpath_tracelog, $logstr);

						// Git情報のコピー処理
						$this->main->gitMgr()->git_file_copy($this->main->options, $realpath_array->realpath_running, $running_dirname);

					} elseif (($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) || 
							  ($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE)) {

						//============================================================
						// バックアップディレクトリを「backup」から「running」ディレクトリへ移動
						//============================================================
						$logstr = "===============================================" . "\r\n";
						$logstr .= "[復元公開]backupからrunningへディレクトリの移動" . "\r\n";
						$logstr .= "===============================================" . "\r\n";
						$logstr .= "backupディレクトリ：" . $realpath_array->realpath_backup . $backup_dirname . "\r\n";
						$logstr .= "runningディレクトリ：" . $realpath_array->realpath_running . $running_dirname . "\r\n";
						$this->main->put_log($realpath_tracelog, $logstr);

						$this->copy_dir($realpath_array->realpath_backup, $backup_dirname, $realpath_array->realpath_running, $running_dirname, $realpath_array->realpath_log);
					}
				}

		 		/* 変更をコミットする */
				$this->main->get_dbh()->commit();
				/* データベース接続はオートコミットモードに戻る */

		    } catch (\Exception $e) {
		    		    
				$logstr = "===============================================" . "\r\n";
				$logstr .= "公開処理の事前準備ロールバック" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$logstr .= $e.getMessage() . "\r\n";
				$this->main->put_log($realpath_tracelog, $logstr);

		      /* 変更をロールバックする */
		      $this->main->get_dbh()->rollBack();
		 
		      throw $e;
		    }

		    // 自動復元公開の場合は、本番環境からバックアップは取得しない
		    if ($publish_type != define::PUBLISH_TYPE_AUTO_RESTORE) {

				try {

					$logstr = "===============================================" . "\r\n";
					$logstr .= "バックアップテーブルのトランザクション処理開始" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);
								
					/* トランザクションを開始する。オートコミットがオフになる */
					$this->main->get_dbh()->beginTransaction();

					//============================================================
					// バックアップテーブルの登録処理
					//============================================================
					$logstr = "===============================================" . "\r\n";
					$logstr .= "バックアップテーブルの登録処理" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);

					// GMTの現在日時
					$backup_datetime = $this->main->common()->get_current_datetime_of_gmt();
					$backup_dirname = $this->main->common()->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);

					$logstr = "バックアップ日時：" . $backup_datetime . "\r\n";
					$logstr .= "バックアップディレクトリ名：" . $backup_dirname . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);

					$result['backup_id'] = $this->tsBackup->insert_ts_backup($this->main->get_dbh(), $this->main->options, $backup_datetime, $result['output_id']);

					$logstr = "登録バックアップID：" . $result['backup_id'] . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);

					//============================================================
					// 本番ソースを「backup」ディレクトリへコピー
					//============================================================
					$logstr = "===============================================" . "\r\n";
					$logstr .= "本番ソースをbackupディレクトリへコピー" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);
			
					$from_realpath = $realpath_array->realpath_server;
					$to_realpath = $realpath_array->realpath_backup . $backup_dirname . '/';

					$logstr = "本番環境ディレクトリ：" . $from_realpath . "\r\n";
					$logstr .= "backupディレクトリ：" . $to_realpath . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);

					$this->exec_sync_copy($from_realpath, $to_realpath, $realpath_tracelog);


					$logstr = "===============================================" . "\r\n";
					$logstr .= "バックアップテーブルのコミット処理実行" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);

			 		/* 変更をコミットする */
					$this->main->get_dbh()->commit();
					/* データベース接続はオートコミットモードに戻る */

			    } catch (\Exception $e) {
		
					$logstr = "===============================================" . "\r\n";
					$logstr .= "バックアップテーブルのロールバック処理実行" . "\r\n";
					$logstr .= "===============================================" . "\r\n";
					$logstr .= $e.getMessage() . "\r\n";
					$this->main->put_log($realpath_tracelog, $logstr);

				    /* 変更をロールバックする */
				    $this->main->get_dbh()->rollBack();
			 
			      throw $e;
			    }

		    }

			try {

				$logstr = "===============================================" . "\r\n";
				$logstr .= "公開処理結果テーブルのトランザクション処理開始" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->main->put_log($realpath_tracelog, $logstr);
							
				/* トランザクションを開始する。オートコミットがオフになる */
				$this->main->get_dbh()->beginTransaction();

				//============================================================
				// 公開処理結果テーブルの更新処理（成功）
				//============================================================
				$logstr = "===============================================" . "\r\n";
				$logstr .= "公開処理結果テーブルの更新処理（ステータス：成功）" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->main->put_log($realpath_tracelog, $logstr);
				
				// GMTの現在日時
				$end_datetime = $this->main->common()->get_current_datetime_of_gmt();

				$dataArray = array(
					tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_SUCCESS,
					tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
					tsOutput::TS_OUTPUT_END 			=> $end_datetime,
					tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> $this->main->options->user_id
				);

		 		$this->tsOutput->update_ts_output($this->main->get_dbh(), $result['output_id'], $dataArray);


				//============================================================
				// runningディレクトリを本番環境へ同期
				//============================================================
				$logstr = "===============================================" . "\r\n";
				$logstr .= "※runningディレクトリを本番環境へ同期※" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->main->put_log($realpath_tracelog, $logstr);

				$from_realpath = $realpath_array->realpath_running . $running_dirname . '/';
				$to_realpath = $realpath_array->realpath_server;

				$logstr = "runningディレクトリ：" . $from_realpath . "\r\n";
				$logstr .= "本番環境ディレクトリ：" . $to_realpath . "\r\n";
				$this->main->put_log($realpath_tracelog, $logstr);

				$this->exec_sync($this->main->options->ignore, $from_realpath, $to_realpath, $realpath_tracelog);

				//============================================================
				// 公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動
				//============================================================
				$logstr = "===============================================" . "\r\n";
				$logstr .= "runningディレクトリからreleasedディレクトリへコピー" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
		 		$this->main->put_log($realpath_tracelog, $logstr);	

				$from_realpath = $result->realpath_running . $running_dirname . '/';
				$to_realpath = $result->realpath_released . $running_dirname . '/';

				$logstr = "カレントディレクトリ：" . realpath('.') . "\r\n";
				$logstr .= "runningディレクトリ：" . $from_realpath . "\r\n";
				$logstr .= "releasedディレクトリ：" . $to_realpath . "\r\n";
				$this->main->put_log($realpath_tracelog, $logstr);

				$this->exec_sync_move($from_realpath, $to_realpath, $realpath_tracelog);


				$logstr = "===============================================" . "\r\n";
				$logstr .= "公開処理結果テーブルのコミット処理実行" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->main->put_log($realpath_tracelog, $logstr);

		 		/* 変更をコミットする */
				$this->main->get_dbh()->commit();
				/* データベース接続はオートコミットモードに戻る */

		    } catch (\Exception $e) {
		    
				$logstr = "===============================================" . "\r\n";
				$logstr .= "公開処理結果テーブルのロールバック処理実行" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$logstr .= $e.getMessage() . "\r\n";
				$this->main->put_log($realpath_tracelog, $logstr);

		    	/* 変更をロールバックする */
		    	$this->main->get_dbh()->rollBack();
		 
		    	throw $e;
		    }

		} catch (\Exception $e) {

			$logstr = "** exec_publish例外キャッチ **" . "\r\n";
			$logstr .= $e->getMessage() . "\r\n";
			$this->main->put_log($realpath_tracelog, $logstr);

			$result['status'] = false;
			$result['message'] = '【publish faild.】' . $e->getMessage();

			//============================================================
			// 公開処理結果テーブルの更新処理（失敗）
			//============================================================
			$logstr = "===============================================" . "\r\n";
			$logstr .= "公開処理結果テーブルの更新処理（ステータス：失敗）" . "\r\n";
			$logstr .= "===============================================" . "\r\n";
			$this->main->put_log($realpath_tracelog, $logstr);

			// GMTの現在日時
			$end_datetime = $this->main->common()->get_current_datetime_of_gmt();
			$dataArray = array(
				tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_FAILED,
				tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
				tsOutput::TS_OUTPUT_END 			=> $end_datetime,
				tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> $this->main->options->user_id
			);

	 		$this->tsOutput->update_ts_output($this->main->get_dbh(), $result['output_id'], $dataArray);

			return $result;
		}

		$result['status'] = true;

		$logstr = "===============================================" . "\r\n";
		$logstr .= "公開処理完了" . "\r\n";
		$logstr .= "===============================================" . "\r\n";
		$this->main->put_log($realpath_tracelog, $logstr);

		$this->main->common()->debug_echo('■ exec_publish end');

		return $result;
	}

	/**
	 * rsyncコマンド実行（公開処理用）
	 */
	public function exec_sync($ignore, $from_realpath, $to_realpath, $realpath_tracelog) {

		// ※runningディレクトリパスの後ろにはスラッシュは付けない（スラッシュを付けると日付ディレクトリも含めて同期してしまう）
			
		// 同期除外コマンドの作成
		$exclude_command = '';
		foreach ($ignore as $key => $value) {
		 	$exclude_command .= "--exclude='" . $value . "' ";
		}

		$command = 'rsync --checksum -rvzP --delete ' . $exclude_command . $from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $realpath_tracelog;

		$ret = $this->main->common()->command_execute($command, true);
		if ($ret['return']) {
			// 戻り値が0以外の場合
			throw new \Exception('Command error. [command]' . $command);
		}

		// rsyncコマンド実行時のログを格納
		$this->main->put_log($realpath_copylog, $ret['output']);		
	}

	/**
	 * rsyncコマンド実行（ディレクトリコピー用）
	 */
	public function exec_sync_copy($from_realpath, $to_realpath, $realpath_tracelog) {

		$command = 'rsync -rtvzP' . ' ' . $from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $realpath_tracelog;

		$ret = $this->main->common()->command_execute($command, true);
		if ($ret['return']) {
			// 戻り値が0以外の場合
			throw new \Exception('Command error. [command]' . $command);
		}

		// // rsyncコマンド実行時のログを格納
		// $this->main->put_log($realpath_copylog, $ret['output']);		
	}

	/**
	 * rsyncコマンド実行（ディレクトリ移動用）
	 */
	public function exec_sync_move($from_realpath, $to_realpath, $realpath_tracelog) {

		$logstr = "-----------------------------------------------" . "\r\n";
		$logstr .= "ディレクトリ移動" . "\r\n";
		$logstr .= "-----------------------------------------------" . "\r\n";
		$this->main->put_log($realpath_tracelog, $logstr);

		$command = 'rsync -rtvzP --remove-source-files ' . $from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $realpath_tracelog;

		$ret = $this->main->common()->command_execute($command, true);
		if ($ret['return']) {
			// 戻り値が0以外の場合
			throw new \Exception('Command error. [command]' . $command);
		}

		// // rsyncコマンド実行時のログを格納
		// $this->main->put_log($realpath_copylog, $ret['output']);	

		$logstr = "-----------------------------------------------" . "\r\n";
		$logstr .= "移動元の空ディレクトリの削除（サブディレクトリも含む）" . "\r\n";
		$logstr .= "-----------------------------------------------" . "\r\n";
		$this->main->put_log($realpath_tracelog, $logstr);

		$command = 'find ' .  $from_realpath . ' -type d -empty -delete' ;

		$ret = $this->main->common()->command_execute($command, true);
		if ($ret['return']) {
			// 戻り値が0以外の場合
			throw new \Exception('Command error. [command]' . $command);
		}
	}


	// /**
	//  * 公開復元処理
	//  */
	// public function exec_restore_publish($output_id) {

	// 	$this->main->common()->debug_echo('■ exec_restore_publish start');

	//   	$logstr = "===============================================" . "\r\n";
	// 	$logstr .= "復元公開処理の開始" . "\r\n";
	// 	$logstr .= "===============================================" . "\r\n";
	// 	$this->main->put_log($realpath_tracelog, $logstr);

	// 	$output = "";
	// 	$result = array('status' => true,
	// 					'message' => '',
	// 					'dialog_disp' => '');

	// 	try {

	// 		//============================================================
	// 		// 公開処理結果テーブルの登録処理（復元用）
	// 		//============================================================

	// 	  	$logstr = "===============================================" . "\r\n";
	// 		$logstr .= "公開処理結果テーブルの登録処理" . "\r\n";
	// 		$logstr .= "===============================================" . "\r\n";
	// 		$this->main->put_log($realpath_tracelog, $logstr);

	// 		$dataArray = array(
	// 			tsOutput::TS_OUTPUT_RESERVE_ID => $output_id,
	// 			tsOutput::TS_OUTPUT_BACKUP_ID => $backup_data[tsBackup::BACKUP_ENTITY_ID_SEQ],
	// 			tsOutput::TS_OUTPUT_RESERVE => null,
	// 			tsOutput::TS_OUTPUT_BRANCH => null,
	// 			tsOutput::TS_OUTPUT_COMMIT_HASH => null,
	// 			tsOutput::TS_OUTPUT_COMMENT => null,
	// 			tsOutput::TS_OUTPUT_PUBLISH_TYPE => define::PUBLISH_TYPE_RESTORE,
	// 			tsOutput::TS_OUTPUT_STATUS => define::PUBLISH_STATUS_RUNNING,
	// 			tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => null,
	// 			tsOutput::TS_OUTPUT_START => $start_datetime,
	// 			tsOutput::TS_OUTPUT_END => null,
	// 			tsOutput::TS_OUTPUT_GEN_DELETE_FLG => define::DELETE_FLG_OFF,
	// 			tsOutput::TS_OUTPUT_GEN_DELETE => null,
	// 			tsOutput::TS_OUTPUT_INSERT_DATETIME => $start_datetime,
	// 			tsOutput::TS_OUTPUT_INSERT_USER_ID => $this->main->options->user_id,
	// 			tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
	// 			tsOutput::TS_OUTPUT_UPDATE_USER_ID => null
	// 		);

	// 		// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
	// 		$insert_id = $this->tsOutput->insert_ts_output($this->main->get_dbh(), $dataArray);

	// 		$this->main->common()->debug_echo('　□ $insert_id：' . $insert_id);



	// 		//============================================================
	// 		// バックアップディレクトリを「backup」から「running」ディレクトリへ移動
	// 		//============================================================
	// 		$logstr = "===============================================" . "\r\n";
	// 		$logstr .= "backupディレクトリからrunningディレクトリへコピー" . "\r\n";
	// 		$logstr .= "===============================================" . "\r\n";
	// 		$this->main->put_log($realpath_tracelog, $logstr);
	
	// 		$from_realpath = $realpath_array->realpath_backup . $backup_dirname . '/';
	// 		$to_realpath = $realpath_array->realpath_running . $running_dirname . '/';
			
	// 		$logstr = "backupディレクトリ：" . $from_realpath . "\r\n";
	// 		$logstr .= "runningディレクトリ：" . $to_realpath . "\r\n";
	// 		$this->main->put_log($realpath_tracelog, $logstr);

	// 		$this->exec_sync_copy($from_realpath, $to_realpath, $realpath_tracelog);


	// 		try {

	// 			/* トランザクションを開始する。オートコミットがオフになる */
	// 			$this->main->get_dbh()->beginTransaction();

	// 			//============================================================
	// 			// 公開処理結果テーブルの更新処理（成功）
	// 			//============================================================

	// 		  	$logstr = "===============================================" . "\r\n";
	// 			$logstr .= "公開処理結果テーブルの更新処理（成功）" . "\r\n";
	// 			$logstr .= "===============================================" . "\r\n";
	// 			$logstr .= $e.getMessage() . "\r\n";
	// 			$this->main->put_log($realpath_tracelog, $logstr);

	// 			// GMTの現在日時
	// 			$end_datetime = $this->main->common()->get_current_datetime_of_gmt();

	// 			$dataArray = array(
	// 				tsOutput::TS_OUTPUT_STATUS => define::PUBLISH_STATUS_SUCCESS,
	// 				tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
	// 				tsOutput::TS_OUTPUT_END => $end_datetime,
	// 				tsOutput::TS_OUTPUT_UPDATE_USER_ID => $this->main->options->user_id
	// 			);

	// 	 		$this->tsOutput->update_ts_output($this->main->get_dbh(), $insert_id, $dataArray);

	// 			//============================================================
	// 			// runningディレクトリを本番環境へ同期
	// 			//============================================================
	// 			$logstr = "===============================================" . "\r\n";
	// 			$logstr .= "※runningディレクトリを本番環境へ同期※" . "\r\n";
	// 			$logstr .= "===============================================" . "\r\n";
	// 			$this->main->put_log($realpath_tracelog, $logstr);

	// 			$from_realpath = $realpath_array->realpath_running . $running_dirname . '/';
	// 			$to_realpath = $realpath_array->realpath_server;

	// 			$logstr = "runningディレクトリ：" . $from_realpath . "\r\n";
	// 			$logstr .= "本番環境ディレクトリ：" . $to_realpath . "\r\n";
	// 			$this->main->put_log($realpath_tracelog, $logstr);

	// 			$this->exec_sync($this->main->options->ignore, $from_realpath, $to_realpath, $realpath_tracelog);

	// 			//============================================================
	// 			// 公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動
	// 			//============================================================
	// 			$logstr = "===============================================" . "\r\n";
	// 			$logstr .= "runningディレクトリからreleasedディレクトリへコピー" . "\r\n";
	// 			$logstr .= "===============================================" . "\r\n";
	// 	 		$this->main->put_log($realpath_tracelog, $logstr);	

	// 			$from_realpath = $realpath_array->realpath_running .  $running_dirname . '/';
	// 			$to_realpath = $realpath_array->realpath_released . $running_dirname . '/';

	// 			$logstr = "runningディレクトリ：" . $from_realpath . "\r\n";
	// 			$logstr .= "releasedディレクトリ：" . $to_realpath . "\r\n";
	// 			$this->main->put_log($realpath_tracelog, $logstr);

	// 			$this->exec_sync_move($from_realpath, $to_realpath, $realpath_tracelog);


	// 			$logstr = "===============================================" . "\r\n";
	// 			$logstr .= "公開処理結果テーブルのコミット処理実行" . "\r\n";
	// 			$logstr .= "===============================================" . "\r\n";
	// 			$this->main->put_log($realpath_tracelog, $logstr);

	// 	 		/* 変更をコミットする */
	// 			$this->main->get_dbh()->commit();
	// 			/* データベース接続はオートコミットモードに戻る */

	// 	    } catch (\Exception $e) {
		    
	// 			$logstr = "===============================================" . "\r\n";
	// 			$logstr .= "公開後処理のロールバック処理実行" . "\r\n";
	// 			$logstr .= "===============================================" . "\r\n";
	// 			$logstr .= $e.getMessage() . "\r\n";
	// 			$this->main->put_log($realpath_tracelog, $logstr);

	// 	      /* 変更をロールバックする */
	// 	      $this->main->get_dbh()->rollBack();
		      
	// 	      // throw $e;
	// 	      throw new \Exception($e->getMessage());
	// 	    }

	// 	} catch (\Exception $e) {

	// 		$logstr = "** exec_restore_publish 例外キャッチ **" . "\r\n";
	// 		$logstr .= $e->getMessage() . "\r\n";
	// 		$this->main->put_log($realpath_tracelog, $logstr);

	// 		$result['status'] = false;
	// 		$result['message'] = '【Restore publication failure faild.】' . $e->getMessage();


	// 		//============================================================
	// 		// 公開処理結果テーブルの更新処理（失敗）
	// 		//============================================================

	// 	  	$logstr = "===============================================" . "\r\n";
	// 		$logstr .= "公開処理結果テーブルの更新処理（失敗）" . "\r\n";
	// 		$logstr .= "===============================================" . "\r\n";
	// 		$logstr .= $e.getMessage() . "\r\n";
	// 		$this->main->put_log($realpath_tracelog, $logstr);

	// 		// GMTの現在日時
	// 		$end_datetime = $this->main->common()->get_current_datetime_of_gmt();

	// 		$dataArray = array(
	// 			tsOutput::TS_OUTPUT_STATUS => define::PUBLISH_STATUS_FAILED,
	// 			tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
	// 			tsOutput::TS_OUTPUT_END => $end_datetime,
	// 			tsOutput::TS_OUTPUT_UPDATE_USER_ID => $this->main->options->user_id
	// 		);

	//  		$this->tsOutput->update_ts_output($this->main->get_dbh(), $insert_id, $dataArray);

	// 		$this->main->common()->debug_echo('■ exec_restore_publish error end');

	// 		return json_encode($result);
	// 	}

	// 	$result['status'] = true;

	// 	$this->main->common()->debug_echo('■ exec_restore_publish end');

	// 	return $result;
	// }

	/**
	 * パブリッシュをロックする。
	 *
	 * @return bool ロック成功時に `true`、失敗時に `false` を返します。
	 */
	private function lock(){

		$this->main->common()->debug_echo('■ lock start');

		$lockfilepath = $this->path_lockfile;
		$timeout_limit = 5;

		// 親ディレクトリのチェック
		if( !@is_dir( dirname( $lockfilepath ) ) ){
			$this->main->fs()->mkdir_r( dirname( $lockfilepath ) );
		}

		$this->main->common()->debug_echo('★1');

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		$this->main->common()->debug_echo('★2');

		$i = 0;
		while( $this->is_locked() ){
			$i ++;
			if( $i >= $timeout_limit ){
				return false;
				break;
			}
			sleep(1);

			#	PHPのFileStatusCacheをクリア
			clearstatcache();
		}
		$this->main->common()->debug_echo('★3');
		$src = '';
		$src .= 'ProcessID='.getmypid()."\r\n";
		$src .= @date( 'Y-m-d H:i:s' , gmdate() )."\r\n";
		$rtn = $this->main->fs()->save_file( $lockfilepath , $src );
		$this->main->common()->debug_echo('★4');
		$this->main->common()->debug_echo('■ lock end');

		return	$rtn;
	}//lock()

	/**
	 * パブリッシュがロックされているか確認する。
	 *
	 * @return bool ロック中の場合に `true`、それ以外の場合に `false` を返します。
	 */
	private function is_locked(){

		$this->main->common()->debug_echo('■ is_locked start');

		$lockfilepath = $this->path_lockfile;
		$lockfile_expire = 60*60;//有効期限は60分（過ぎた場合はロック解除してもよいこととする）

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		if( $this->main->fs()->is_file($lockfilepath) ){
			if( ( time() - filemtime($lockfilepath) ) > $lockfile_expire ){
				#	有効期限を過ぎていたら、ロックは成立する。
				return false;
			}
			return true;
		}
		return false;

		$this->main->common()->debug_echo('■ is_locked start');

	}//is_locked()

	/**
	 * パブリッシュロックを解除する。
	 *
	 * @return bool ロック解除成功時に `true`、失敗時に `false` を返します。
	 */
	private function unlock(){

		$lockfilepath = $this->path_lockfile;

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		return @unlink( $lockfilepath );
	}//unlock()

}

