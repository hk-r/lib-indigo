<?php

namespace indigo;

class publish
{
	private $main;

	private $tsOutput, $tsBackup;

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
	public function do_publish($running_dirname, $options, $log_datetime_dir_path) {

		$this->main->common()->debug_echo('■ do_publish start');

		$current_dir = realpath('.');

		$this->main->common()->debug_echo('　□ 公開ファイル日時：');
		$this->main->common()->debug_echo($running_dirname);

		// 作業用ディレクトリの絶対パスを取得
		$result = json_decode($this->main->common()->get_workdir_real_path($options));


		//============================================================
		// 「running」ディレクトリのソースを本番環境へ同期
		//============================================================

 		$this->main->common()->debug_echo('　□ -----「running」ディレクトリのソースを本番環境へ同期ー-----');
		
		if ( file_exists($result->running_real_path) ) {

			if ( file_exists($result->server_real_path) ) {

			// 以下のコマンド（-a）だと、パーミッションまで変えようとするためエラーが発生する。
			// $command = 'rsync -avzP ' . $running_real_path . $dirname . '/' . ' ' . $server_real_path . ' --log-file=' . $log_real_path . $dirname . '/rsync_' . $dirname . '.log' ;

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
			
			// 同期除外コマンドの作成
			$exclude_command = '';
			foreach ($options->ignore as $key => $value) {
			 	$exclude_command .= "--exclude='" . $value . "' ";
			}

			$command = 'rsync --checksum -rvzP --delete ' . $exclude_command . $result->running_real_path . $running_dirname . '/' . ' ' . $result->server_real_path . ' ' . '--log-file=' . $log_datetime_dir_path . 'rsync_' . $running_dirname . '.log' ;

			$this->main->common()->debug_echo('　□ $command：');
			$this->main->common()->debug_echo($command);

			$ret = $this->main->common()->command_execute($command, true);
			
			// ファイルのパスを変数に格納
			$filename = $log_datetime_dir_path . 'rsync_copy_' . $running_dirname . '.log';
 
			// ファイルに書き込む
			file_put_contents($filename, $ret['output']);
// // ファイルを出力する
// readfile($filename);

				// foreach ( (array) $ret['output'] as $element ) {
				// 	$this->main->common()->debug_echo($element);
				// }

			// $this->main->common()->debug_echo('　▼本番反映の公開処理結果');

			// foreach ( (array)$ret['output'] as $element ) {
			// 	$this->main->common()->debug_echo($element);
			// }

			} else {
				// エラー処理
				throw new \Exception('Project directory not found. ' . $result->server_real_path);
			}

		} else {
			// エラー処理
			throw new \Exception('Running directory not found .' . $result->running_real_path);
		}
		//============================================================
		// 公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動
		//============================================================

 		$this->main->common()->debug_echo('　□ -----公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動-----');

		$this->move_dir($result->running_real_path, $running_dirname, $result->released_real_path, $running_dirname, $result->log_real_path);

		$this->main->common()->debug_echo('■ do_publish end');
	}

	/**
	 * バックアップファイルの作成（コマンド実行）
	 */
	public function create_backup($backup_dirname, $real_path) {

		$this->main->common()->debug_echo('■ create_backup start');

				$this->main->common()->debug_echo('　□ $backup_dirname' . $backup_dirname);

		// // 作業用ディレクトリの絶対パスを取得
		// $result = json_decode($this->main->common()->get_workdir_real_path($options));

		if ( file_exists($real_path->backup_real_path) ) {

			if ( file_exists($real_path->server_real_path) ) {

				$this->main->common()->debug_echo('　□ 1');

				$command = 'rsync -rtvzP' . ' ' . $real_path->server_real_path . ' ' . $real_path->backup_real_path . $backup_dirname . '/' . ' --log-file=' . $real_path->log_real_path . '/rsync_' . $backup_dirname . '.log' ;

				$this->main->common()->debug_echo('　□ $command：' . $command);
$this->main->common()->debug_echo('　□ 2');
				$ret = $this->main->common()->command_execute($command, true);
$this->main->common()->debug_echo('　□ 3');
				$this->main->common()->debug_echo('　★ バックアップ作成の処理結果');
				
				foreach ( (array) $ret['output'] as $element ) {
					$this->main->common()->debug_echo($element);
				}

			} else {
				// エラー処理
				throw new \Exception('Project directory not found. ' . $real_path->server_real_path);
			}
		} else {
			// エラー処理
			throw new \Exception('Backup directory not found. ' . $real_path->backup_real_path);
		}

		$this->main->common()->debug_echo('■ create_backup end');

	}

	/**
	 * ディレクトリの移動（コマンド実行）
	 */
	public function move_dir($from_real_path, $from_dirname, $to_real_path, $to_dirname, $log_real_path) {

		$this->main->common()->debug_echo('■ move_dir start');

			$this->main->common()->debug_echo($from_real_path);
			$this->main->common()->debug_echo($to_real_path);

		if ( file_exists($from_real_path)  ) {

			if ( file_exists($to_real_path) ) {

				//============================================================
				// runningディレクトリへファイルを移動する
				//============================================================
				$command = 'rsync -rtvzP --remove-source-files ' . $from_real_path . $from_dirname . '/ ' . $to_real_path . $to_dirname . '/' . ' --log-file=' . $log_real_path . '/rsync_' . $to_dirname . '.log' ;

				$ret = $this->main->common()->command_execute($command, true);
				if ($ret['return']) {
					// 戻り値が0以外の場合
					throw new \Exception('Command error. command:' . $command);
				}
				$this->main->common()->debug_echo('　★ ファイル移動結果');

				// foreach ( (array)$ret['output'] as $element ) {
				// 	$this->main->common()->debug_echo($element);
				// }

				//============================================================
				// 移動元のディレクトリを削除する
				//============================================================
				$command = 'find ' .  $from_real_path . $from_dirname . '/ -type d -empty -delete' ;

				$ret = $this->main->common()->command_execute($command, true);
				if ($ret['return']) {
					// 戻り値が0以外の場合
					throw new \Exception('Command error. command:' . $command);
				}
				$this->main->common()->debug_echo('　★ 移動元のディレクトリ削除結果');

				// foreach ( (array)$ret['output'] as $element ) {
				// 	$this->main->common()->debug_echo($element);
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

		$this->main->common()->debug_echo('■ move_dir end');
	}


	/**
	 * ディレクトリのコピー（コマンド実行）
	 */
	public function copy_dir($from_real_path, $from_dirname, $to_real_path, $to_dirname, $log_real_path) {

		$this->main->common()->debug_echo('■ copy_dir start');

		if ( file_exists($from_real_path)  ) {

			if ( file_exists($to_real_path) ) {

				//============================================================
				// runningディレクトリへファイルを移動する
				//============================================================
				$command = 'rsync -rtvzP ' . $from_real_path . $from_dirname . '/ ' . $to_real_path . $to_dirname . '/' . ' --log-file=' . $log_real_path . '/rsync_' . $to_dirname . '.log' ;

				$ret = $this->main->common()->command_execute($command, true);
				if ($ret['return']) {
					// 戻り値が0以外の場合
					throw new \Exception('Command error. command:' . $command);
				}
				
				// foreach ( (array)$ret['output'] as $element ) {
				// 	$this->main->common()->debug_echo($element);
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

		$this->main->common()->debug_echo('■ copy_dir end');
	}


	/**
	 * 即時公開処理
	 */
	public function exec_immediate_publish($publish_type) {

		$this->main->common()->debug_echo('■ exec_immediate_publish start');

		$output = "";
		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '',
						'output_id' => '',
						'backup_id' => '');

		$backup_dirname;

		try {

			if( !$this->lock() ){//ロック
				// print '------'."\n";
				// print 'publish is now locked.'."\n";
				// print '  (lockfile updated: '.@date('Y-m-d H:i:s', filemtime($this->path_lockfile)).')'."\n";
				// print 'Try again later...'."\n";
				// print 'exit.'."\n";
				// print $this->cli_footer();
				// exit;
			}

			// GMTの現在日時
			$start_datetime = $this->main->common()->get_current_datetime_of_gmt();

			$this->main->common()->debug_echo('　□ 公開処理開始日時：' . $start_datetime);

			// 作業用ディレクトリの絶対パスを取得
			$real_path = json_decode($this->main->common()->get_workdir_real_path($this->main->options));

			// 公開日時ディレクトリ名の取得
			$running_dirname = $this->main->common()->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

			$this->main->common()->debug_echo('　□ 公開日時ディレクトリ名：' . $running_dirname);

			//============================================================
			// ログ出力用の日付ディレクトリ作成
			//============================================================

			// logの日付ディレクトリを作成
			$copy_logpath = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($real_path->log_real_path . $running_dirname . "/")) . 'rsync_copy_' . $running_dirname . '.log';
			$backup_logpath = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($real_path->log_real_path . $running_dirname . "/")) . 'rsync_backup_' . $running_dirname . '.log';

			// 親ディレクトリの作成
			if( !@is_dir( dirname( $copy_logpath ) ) ){
				$this->main->fs()->mkdir_r( dirname( $copy_logpath ) );
			}
			// 親ディレクトリの作成
			if( !@is_dir( dirname( $backup_logpath ) ) ){
				$this->main->fs()->mkdir_r( dirname( $backup_logpath ) );
			}

			$src = '';

			if (!$this->main->fs()->save_file( $copy_logpath , $src )) {
				throw new \Exception('Create copy logfile is failed. ' . $copy_logpath);
			}
			if (!$this->main->fs()->save_file( $backup_logpath , $src )) {
				throw new \Exception('Create backup logfile is failed. ' . $backup_logpath);
			}

			// if ( !$this->main->common()->is_exists_mkdir($copylogpath) ) {
			// 	// エラー処理
			// 	throw new \Exception('Create copy logfi directory is failed. ' . $result->server_real_path);
			// }
			// if ( !$this->main->common()->is_exists_mkdir($copylogpath) ) {
			// 	// エラー処理
			// 	throw new \Exception('Create log directory is failed. ' . $result->server_real_path);
			// }

			try {

				/* トランザクションを開始する。オートコミットがオフになる */
				$this->dbh->beginTransaction();

				// 予約公開の場合
				if ($publish_type == define::PUBLISH_TYPE_RESERVE) {
					
					//============================================================
					// 公開予約テーブルより、公開対象データの取得
					//============================================================
					// 公開予約の一覧を取得
					$data_list = $this->tsReserve->get_ts_reserve_publish_list($this->dbh, $start_datetime);

					if (!$data_list) {
						$this->common->debug_echo('Target data does not exist.');
						return $result;
					}

					$cnt = 1;
					$status = define::PUBLISH_STATUS_RUNNING;
					$set_start_datetime = $start_datetime;

					// 複数件取れてきた場合は、最新データ以外はスキップデータとして公開処理結果テーブルへ登録する
					foreach ( (array) $data_list as $data ) {

						$this->common->debug_echo('　□ 公開取得データ[配列]');
						$this->common->debug_var_dump($data);

						//============================================================
						// 公開処理結果テーブルの登録処理
						//============================================================

					 	$this->common->debug_echo('　□ -----[時限公開]公開処理結果テーブルの登録処理-----');

						// 現在時刻
						$now = $this->common->get_current_datetime_of_gmt();

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
							tsOutput::TS_OUTPUT_INSERT_USER_ID 	=> $this->options->user_id,
							tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
							tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> null
						);

						// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
						$insert_id = $this->tsOutput->insert_ts_output($this->dbh, $dataArray);

						if ($cnt == 1) {

							$dirname = $this->common->format_gmt_datetime($data[tsReserve::RESERVE_ENTITY_RESERVE_GMT], define::DATETIME_FORMAT_SAVE);

							if (!$dirname) {
								// エラー処理
								throw new \Exception('Dirname create failed.');
							} else {
								$dirname .= define::DIR_NAME_RESERVE;
							}

							$output_id = $insert_id;

							$this->common->debug_echo('　□ 公開ディレクトリ名');
							$this->common->debug_var_dump($dirname);

							if (!$dirname) {
								// エラー処理
								throw new \Exception('Publish dirname create failed.');
							}

							// 以降のループはスキップデータなので値を変更
							$status = define::PUBLISH_STATUS_SKIP;
							$set_start_datetime = null;
						}

						//============================================================
						// 公開予約テーブルのステータス更新処理
						//============================================================
						
						// 公開予約テーブルのステータス更新処理
						$this->tsReserve->update_ts_reserve_status($this->dbh, $data[tsReserve::RESERVE_ENTITY_ID_SEQ]);
					
						$cnt++;
					}

					//============================================================
					// 公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動
					//============================================================

			 		$this->common->debug_echo('　□ -----公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動-----');

					// // runningディレクトリの絶対パスを取得。
					// $running_dirname = $this->common->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

					$this->move_dir($real_path->waiting_real_path, $dirname, $real_path->running_real_path, $running_dirname, $real_path->log_real_path);

				} else {

					//============================================================
					// 公開処理結果テーブルの登録処理
					//============================================================

			 		$this->main->common()->debug_echo('　□ -----公開処理結果テーブルの登録処理-----');

					// 現在時刻
					$now = $this->main->common()->get_current_datetime_of_gmt();

					$reserve_id = null;

					$dataArray = array(
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

					// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
					$result['output_id'] = $this->tsOutput->insert_ts_output($this->main->get_dbh(), $dataArray);

					if ($publish_type == define::PUBLISH_TYPE_IMMEDIATE) {

						// ============================================================
						// 選択されたブランチのGit情報を「running」ディレクトリへコピー
						// ============================================================

				 		$this->main->common()->debug_echo('　□ -----指定ブランチのGit情報を「running」ディレクトリへコピー-----');
						
						// Git情報のコピー処理
						$this->main->gitMgr()->git_file_copy($this->main->options, $real_path->running_real_path, $dirname);
						

					} elseif ($publish_type == define::PUBLISH_TYPE_RESTORE) {

						//============================================================
						// バックアップテーブルより、公開対象データの取得
						//============================================================

				 		$this->main->common()->debug_echo('　□ -----[復元公開]バックアップテーブルより、公開対象データの取得-----');

						$selected_id =  $this->main->options->_POST->selected_id;

						$selected_data = $this->tsBackup->get_selected_ts_backup($this->main->dbh, $selected_id);
					
						if (!$selected_data) {
							throw new \Exception('Target data not found.');
						}

						$dirname = $this->main->common()->format_gmt_datetime($selected_data[tsBackup::BACKUP_ENTITY_DATETIME_GMT], define::DATETIME_FORMAT_SAVE);
					
						if (!$dirname) {
							// エラー処理
							throw new \Exception('Publish dirname create failed.');
						}

						//============================================================
						// バックアップディレクトリを「backup」から「running」ディレクトリへ移動
						//============================================================

				 		$this->main->common()->debug_echo('　□ -----バックアップディレクトリを「backup」から「running」ディレクトリへコピー-----');

						// runningディレクトリの絶対パスを取得。
						$running_dirname = $this->main->common()->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

						$this->copy_dir($real_path->backup_real_path, $dirname, $real_path->running_real_path, $running_dirname, $real_path->log_real_path);
					}
				}

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
				$this->main->get_dbh()->beginTransaction();

				//============================================================
				// バックアップテーブルの登録処理
				//============================================================

		 		$this->main->common()->debug_echo('　□ -----バックアップテーブルの登録処理-----');
				
				// GMTの現在日時
				$backup_datetime = $this->main->common()->get_current_datetime_of_gmt();

				$this->main->common()->debug_echo('　□ バックアップ日時：' . $backup_datetime);

				$result['backup_id'] = $this->tsBackup->insert_ts_backup($this->main->get_dbh(), $this->main->options, $backup_datetime, $result['output_id']);


				//============================================================
				// 本番ソースを「backup」ディレクトリへコピー
				//============================================================

		 		$this->main->common()->debug_echo('　□ -----本番ソースを「backup」ディレクトリへコピー-----');
				
				$backup_dirname = $this->main->common()->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);
				
				// バックアップファイル作成
				$this->create_backup($backup_dirname, $real_path);

		 		/* 変更をコミットする */
				$this->main->get_dbh()->commit();
				/* データベース接続はオートコミットモードに戻る */

		    } catch (\Exception $e) {
		    
		      /* 変更をロールバックする */
		      $this->main->get_dbh()->rollBack();
		 
		      throw $e;
		    }


			try {

				/* トランザクションを開始する。オートコミットがオフになる */
				$this->main->get_dbh()->beginTransaction();

				//============================================================
				// 公開処理結果テーブルの更新処理（成功）
				//============================================================

		 		$this->main->common()->debug_echo('　□ -----公開処理結果テーブルの更新処理（成功）-----');
				
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
				// ※公開処理※
				//============================================================

		 		$this->main->common()->debug_echo('　□ -----公開処理-----');
				
				$this->do_publish($dirname, $this->main->options, $copylogpath);


		 		/* 変更をコミットする */
				$this->main->get_dbh()->commit();
				/* データベース接続はオートコミットモードに戻る */

		    } catch (\Exception $e) {
		    
		      /* 変更をロールバックする */
		      $this->main->get_dbh()->rollBack();
		 
		      throw $e;
		    }

		} catch (\Exception $e) {

		$this->main->common()->debug_echo('■ 3');

			$result['status'] = false;
			$result['message'] = '【Immediate publish faild.】' . $e->getMessage();

			//============================================================
			// 公開処理結果テーブルの更新処理（失敗）
			//============================================================

	 		$this->main->common()->debug_echo('　□ -----公開処理結果テーブルの更新処理（失敗）-----');
			
			// GMTの現在日時
			$end_datetime = $this->main->common()->get_current_datetime_of_gmt();
			$dataArray = array(
				tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_FAILED,
				tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
				tsOutput::TS_OUTPUT_END 			=> $end_datetime,
				tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> $this->main->options->user_id
			);
	 		$this->tsOutput->update_ts_output($this->main->get_dbh(), $result['output_id'], $dataArray);

			$this->main->common()->debug_echo('■ exec_immediate_publish error end');

			return $result;
		}

		$result['status'] = true;

		$this->main->common()->debug_echo('■ exec_immediate_publish end');

		return $result;
	}

	/**
	 * 公開復元処理
	 */
	public function exec_restore_publish($output_id) {

		$this->main->common()->debug_echo('■ exec_restore_publish start');

		$output = "";
		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		try {

			// 処理結果IDからバックアップ情報を取得
			$backup_data = $this->tsBackup->get_selected_ts_backup_by_output_id($this->main->get_dbh(), $output_id);

			//============================================================
			// 公開処理結果テーブルの登録処理（復元用）
			//============================================================

	 		$this->main->common()->debug_echo('　□ -----[復元公開]公開処理結果テーブルの登録処理-----');

			// 開始時刻
			$start_datetime = $this->main->common()->get_current_datetime_of_gmt();

			$dataArray = array(
				tsOutput::TS_OUTPUT_RESERVE_ID => $output_id,
				tsOutput::TS_OUTPUT_BACKUP_ID => $backup_data[tsBackup::BACKUP_ENTITY_ID_SEQ],
				tsOutput::TS_OUTPUT_RESERVE => null,
				tsOutput::TS_OUTPUT_BRANCH => null,
				tsOutput::TS_OUTPUT_COMMIT_HASH => null,
				tsOutput::TS_OUTPUT_COMMENT => null,
				tsOutput::TS_OUTPUT_PUBLISH_TYPE => define::PUBLISH_TYPE_RESTORE,
				tsOutput::TS_OUTPUT_STATUS => define::PUBLISH_STATUS_RUNNING,
				tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => null,
				tsOutput::TS_OUTPUT_START => $start_datetime,
				tsOutput::TS_OUTPUT_END => null,
				tsOutput::TS_OUTPUT_GEN_DELETE_FLG => define::DELETE_FLG_OFF,
				tsOutput::TS_OUTPUT_GEN_DELETE => null,
				tsOutput::TS_OUTPUT_INSERT_DATETIME => $start_datetime,
				tsOutput::TS_OUTPUT_INSERT_USER_ID => $this->main->options->user_id,
				tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
				tsOutput::TS_OUTPUT_UPDATE_USER_ID => null
			);

			// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
			$insert_id = $this->tsOutput->insert_ts_output($this->main->get_dbh(), $dataArray);

			$this->main->common()->debug_echo('　□ $insert_id：' . $insert_id);



			//============================================================
			// バックアップディレクトリを「backup」から「running」ディレクトリへ移動
			//============================================================

	 		$this->main->common()->debug_echo('　□ -----バックアップディレクトリを「backup」から「running」ディレクトリへコピー-----');

			$backup_dirname = $this->main->common()->format_gmt_datetime($backup_data[tsBackup::BACKUP_ENTITY_DATETIME_GMT], define::DATETIME_FORMAT_SAVE);
				
			// runningディレクトリの絶対パスを取得。
			$running_dirname = $this->main->common()->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

			$this->copy_dir($real_path->backup_real_path, $backup_dirname, $real_path->running_real_path, $running_dirname, $real_path->log_real_path);

			try {

				/* トランザクションを開始する。オートコミットがオフになる */
				$this->main->get_dbh()->beginTransaction();

				//============================================================
				// 公開処理結果テーブルの更新処理（成功）
				//============================================================

		 		$this->main->common()->debug_echo('　□ -----公開処理結果テーブルの更新処理（成功）-----');
				
				// GMTの現在日時
				$end_datetime = $this->main->common()->get_current_datetime_of_gmt();

				$dataArray = array(
					tsOutput::TS_OUTPUT_STATUS => define::PUBLISH_STATUS_SUCCESS,
					tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
					tsOutput::TS_OUTPUT_END => $end_datetime,
					tsOutput::TS_OUTPUT_UPDATE_USER_ID => $this->main->options->user_id
				);

		 		$this->tsOutput->update_ts_output($this->main->get_dbh(), $insert_id, $dataArray);

				//============================================================
				// ※公開処理※
				//============================================================
				
		 		$this->main->common()->debug_echo('　□ -----公開処理-----');
				
				$this->do_publish($running_dirname, $this->main->options);
			

		 		/* 変更をコミットする */
				$this->main->get_dbh()->commit();
				/* データベース接続はオートコミットモードに戻る */

		    } catch (\Exception $e) {
		    
		      /* 変更をロールバックする */
		      $this->main->get_dbh()->rollBack();
		      
		      // throw $e;
		      throw new \Exception($e->getMessage());
		    }

		} catch (\Exception $e) {

		$this->main->common()->debug_echo('■ 3');

			$result['status'] = false;
			$result['message'] = '【Restore publication failure faild.】' . $e->getMessage();


			//============================================================
			// 公開処理結果テーブルの更新処理（失敗）
			//============================================================

	 		$this->main->common()->debug_echo('　□ -----公開処理結果テーブルの更新処理（失敗）-----');
			// GMTの現在日時
			$end_datetime = $this->main->common()->get_current_datetime_of_gmt();

			$dataArray = array(
				tsOutput::TS_OUTPUT_STATUS => define::PUBLISH_STATUS_FAILED,
				tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
				tsOutput::TS_OUTPUT_END => $end_datetime,
				tsOutput::TS_OUTPUT_UPDATE_USER_ID => $this->main->options->user_id
			);

	 		$this->tsOutput->update_ts_output($this->main->get_dbh(), $insert_id, $dataArray);

			$this->main->common()->debug_echo('■ exec_restore_publish error end');

			return json_encode($result);
		}

		$result['status'] = true;

		$this->main->common()->debug_echo('■ exec_restore_publish end');

		return $result;
	}

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

