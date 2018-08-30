<?php

namespace indigo;

use indigo\db\tsReserve as tsReserve;
use indigo\db\tsOutput as tsOutput;
use indigo\db\tsBackup as tsBackup;

/**
 * 公開処理実行クラス
 *
 * 本番公開の処理を共通化したクラス。
 *
 */
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
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->tsReserve = new tsReserve($this->main);
		$this->tsOutput = new tsOutput($this->main);
		$this->tsBackup = new tsBackup($this->main);
	}


	/**
	 * 公開処理
	 *
	 * 予定公開、即時公開、手動復元公開、また、これらが失敗した場合の自動復元公開を記載しています。	
	 * 引数の公開種別によって処理を分岐しています。
	 *
	 * 処理開始時にロックファイルを作成し、他の公開処理をロックします。
	 * 処理終了時にロックファイルは削除されます。
	 *
	 * 予定公開の場合 -> 公開対象のデータが存在しない場合は処理を終了します。
	 * 
	 * 	 
	 *
	 * @param  string $publish_type 	公開種別
	 * @param  int    $p_output_id		公開処理結果ID（自動復元する際に使用する）
	 *	 
	 * @return array $result
	 * 			bool   $result['status'] 		公開処理成功時に `true`、失敗時に `false` を返します。
	 * 			string $result['message'] 		メッセージを返します。
	 * 			int    $result['output_id'] 	公開処理時にDBに登録した公開処理結果ID
	 * 			int    $result['backup_id'] 	公開処理時にDBに登録したバックアップID
	 * 
	 * @throws Exception コマンド実行が異常終了した場合
	 */
	public function exec_publish($publish_type, $p_output_id) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish start');
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '□ 公開種別：' . $this->main->common()->convert_publish_type($publish_type));

		$current_dir = realpath('.');

		$result = array('status' => true,
						'message' => '',
						'output_id' => '',
						'backup_id' => '');

		$start_datetime = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		
		$reserve_data_list;

		//============================================================
		// 予定公開の場合、公開対象の予定データを取得する。
		// データが存在しない場合は処理を終了する。
		//============================================================
		if ($publish_type == define::PUBLISH_TYPE_RESERVE) {

			$reserve_data_list = $this->tsReserve->get_ts_reserve_publish_list($start_datetime);

			if (!$reserve_data_list) {
				$result['message'] = 'Cron Publish data does not exist.';
				$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish end');
				return $result;
			}
		}

		$backup_dirname;

		// 作業用ディレクトリの絶対パスを取得
		$realpath_array = $this->main->realpath_array;
		
		// 公開作業用のディレクトリ名
		$running_dirname = $this->main->common()->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

		//============================================================
		// 公開処理用のログ生成
		//============================================================
		$this->create_publish_log($realpath_array, $running_dirname);

		//============================================================
		// ロック処理
		//============================================================
		if( !$this->lock() ){//ロック

			$result['message'] = '公開ロック中となっております。しばらくお待ちいただいてもロックが解除されない場合は、管理者にお問い合わせください。';

			$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish end');

			return $result;
		}

		try {

			set_time_limit(12*60*60);

			$logstr = "[公開種別]" . $this->main->common()->convert_publish_type($publish_type) . "\r\n";
			$logstr .= "[公開処理開始日時]" . $start_datetime . "\r\n";
			$logstr .= "[公開作業用ディレクトリ名]" . $running_dirname;
			$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

			try {

				// 予定公開の場合
				if ($publish_type == define::PUBLISH_TYPE_RESERVE) {

					$cnt = 1;
					$status = define::PUBLISH_STATUS_RUNNING;
					$set_start_datetime = $start_datetime;

					/* トランザクションを開始する。オートコミットがオフになる */
					$this->main->dbh()->beginTransaction();
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========トランザクション開始==========", $this->realpath_tracelog);

					// 複数件取れてきた場合、最新（先頭）データ以降はスキップデータとして公開処理結果テーブルへ登録する
					foreach ( (array) $reserve_data_list as $data ) {

						//============================================================
						// 公開処理結果テーブルの登録処理
						//============================================================
						$insert_id = $this->insert_output_data($publish_type, $status, $set_start_datetime, $data, null);

						if ($cnt == 1) {
							// 先頭データは公開予定対象

							$result['output_id'] = $insert_id;

							$reserve_dirname = $this->main->common()->get_reserve_dirname($data[tsReserve::TS_RESERVE_DATETIME]);
							$this->main->common()->put_publish_log(__METHOD__, __LINE__, "★予定公開対象", $this->realpath_tracelog);

							// 以降のループはスキップデータなので値を変更
							$status = define::PUBLISH_STATUS_SKIP;
							$set_start_datetime = null;

						} else {

							$logstr = "※スキップ対象";
							$this->main->common()->put_process_log_block($logstr);
						}

						$this->main->common()->put_publish_log(__METHOD__, __LINE__, "公開予定ID" . $data[tsReserve::TS_RESERVE_ID_SEQ], $this->realpath_tracelog);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, "公開予定日時(GMT)：" . $data[tsReserve::TS_RESERVE_DATETIME], $this->realpath_tracelog);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, "ブランチ名：" . $data[tsReserve::TS_RESERVE_BRANCH], $this->realpath_tracelog);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, "コミット：" . $data[tsReserve::TS_RESERVE_COMMIT_HASH], $this->realpath_tracelog);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, "コメント：" . $data[tsReserve::TS_RESERVE_COMMENT], $this->realpath_tracelog);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, "ユーザID：" . $this->main->user_id, $this->realpath_tracelog);

						//============================================================
						// 公開予定テーブルのステータス更新処理
						//============================================================
						$this->tsReserve->update_ts_reserve_status($data[tsReserve::TS_RESERVE_ID_SEQ], $data[tsReserve::TS_RESERVE_VER_NO]);
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, "----------公開予定テーブルのステータス更新処理----------", $this->realpath_tracelog);						
						$cnt++;
					}

			 		// 変更をコミットする。データベース接続はオートコミットモードに戻る。
					$this->main->dbh()->commit();
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========コミット処理実行==========", $this->realpath_tracelog);

					//============================================================
					// 公開予定ディレクトリを「waiting」から「running」ディレクトリへ移動
					//============================================================
					$from_realpath = $realpath_array['realpath_waiting'] . $reserve_dirname . '/';
					$to_realpath = $realpath_array['realpath_running'] . $running_dirname . '/';

					$this->exec_sync_move($from_realpath, $to_realpath);

				} else {
				// 即時公開、手動復元公開、自動復元公開の場合

					$backup_id = null;
					$backup_dirname = '';

 					if (($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) ||
						($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE)) {

						//============================================================
						// バックアップ情報の取得
						//============================================================
						$backup_data = $this->get_backup_data($publish_type, $p_output_id);

						$backup_id = $backup_data[tsBackup::TS_BACKUP_ID_SEQ];
						$backup_dirname = $this->main->common()->format_gmt_datetime($backup_data[tsBackup::TS_BACKUP_DATETIME], define::DATETIME_FORMAT_SAVE);
						
						if (!$backup_dirname) {
							// エラー処理
							throw new \Exception('Backup dirname not found.');
						}

						$logstr = "バックアップID --> " 	. $backup_data[tsBackup::TS_BACKUP_ID_SEQ] . "\r\n";
						$logstr .= "バックアップ日時 --> " 	. $backup_data[tsBackup::TS_BACKUP_DATETIME];
						$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);
					}

					/* トランザクションを開始する。オートコミットがオフになる */
					$this->main->dbh()->beginTransaction();
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========トランザクション開始==========", $this->realpath_tracelog);

					//============================================================
					// 公開処理結果テーブルの登録処理
					//============================================================
					$result['output_id'] = $this->insert_output_data($publish_type, define::PUBLISH_STATUS_RUNNING, $start_datetime, null, $backup_id);

			 		// 変更をコミットする。データベース接続はオートコミットモードに戻る。
					$this->main->dbh()->commit();
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========コミット処理実行==========", $this->realpath_tracelog);

					if ($publish_type == define::PUBLISH_TYPE_IMMEDIATE) {

						// ============================================================
						// 選択されたブランチのGit情報を「running」ディレクトリへコピー
						// ============================================================
						$this->main->common()->put_publish_log(__METHOD__, __LINE__,"==========Git情報をrunningへコピー==========", $this->realpath_tracelog);

						// Git情報のコピー処理
						$this->main->gitMgr()->git_file_copy($this->main->options, $realpath_array['realpath_running'], $running_dirname);

					} elseif (($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) || 
							  ($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE)) {

						//============================================================
						// バックアップディレクトリを「backup」から「running」ディレクトリへコピー
						//============================================================
						$from_realpath = $realpath_array['realpath_backup'] . $backup_dirname . '/';
						$to_realpath = $realpath_array['realpath_running'] . $running_dirname . '/';

						$this->exec_sync_copy($from_realpath, $to_realpath);
					}
				}

		    } catch (\Exception $e) {
		    	
		     	throw $e;
		    }

			//============================================================
			// バックアップの作成処理
			//============================================================
		    if ($publish_type != define::PUBLISH_TYPE_AUTO_RESTORE) {
		    	// 自動復元公開の場合は、本番環境からバックアップは取得しない

				try {

					// トランザクションを開始する。オートコミットがオフになる
					$this->main->dbh()->beginTransaction();				
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========トランザクション開始==========", $this->realpath_tracelog);

					$result['backup_id'] = $this->exec_backup($realpath_array, $result['output_id']);
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, "----------バックアップ処理実施----------", $this->realpath_tracelog);

			 		// 変更をコミットする。オートコミットモードに戻る
					$this->main->dbh()->commit();
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========コミット処理実行==========", $this->realpath_tracelog);


			    } catch (\Exception $e) {
		
				    /* 変更をロールバックする */
				    $this->main->dbh()->rollBack();
			 
					$logstr = "==========バックアップテーブルのロールバック処理実行==========" . "\r\n";
					$logstr .= $e->getMessage() . "\r\n";
					$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

			     	throw $e;
			    }
		    }

			try {
			
				/* トランザクションを開始する。オートコミットがオフになる */
				$this->main->dbh()->beginTransaction();
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========トランザクション開始==========", $this->realpath_tracelog);	

				// GMTの現在日時
				$end_datetime = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

				//============================================================
				// 公開処理結果テーブルの更新処理（ステータス：成功）
				// ※ 同期後にテーブル更新でエラーが発生すると不整合となるので、同期前に成功ステータスで更新しておく。
				// 　 コミット処理までは実際にDBへ反映されない。
				//============================================================
				$this->update_output_data(define::PUBLISH_STATUS_SUCCESS, $result['output_id'], $end_datetime);

				//============================================================
				// runningディレクトリを本番環境へ同期
				//============================================================
				$from_realpath = $realpath_array['realpath_running'] . $running_dirname . '/';
				$to_realpath = $realpath_array['realpath_server'];

				$this->exec_sync($this->main->options->ignore, $from_realpath, $to_realpath);

				//============================================================
				// 公開済みのソースを「running」ディレクトリから「released」ディレクトリへ移動
				//============================================================

				// 公開作業用のディレクトリ名
				$released_dirname = $this->main->common()->format_gmt_datetime($end_datetime, define::DATETIME_FORMAT_SAVE);

				$from_realpath = $realpath_array['realpath_running'] . $running_dirname . '/';
				$to_realpath = $realpath_array['realpath_released'] . $released_dirname . '/';

				// rsyncによるディレクトリの移動処理
				$this->exec_sync_move($from_realpath, $to_realpath);

		 		// 変更をコミットする。データベース接続はオートコミットモードに戻る。
				$this->main->dbh()->commit();
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========コミット処理実行==========", $this->realpath_tracelog);

		    } catch (\Exception $e) {

		    	/* 変更をロールバックする */
		    	$this->main->dbh()->rollBack();
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========公開処理結果テーブルのロールバック処理実行==========", $this->realpath_tracelog);
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $e->getMessage(), $this->realpath_tracelog);

		    	throw $e;
		    }

		} catch (\Exception $e) {

			$logstr =  "***** exec_publish 例外キャッチ *****" . "\r\n";
			$logstr .= "[ERROR]" . "\r\n" . $e->getFile() . " in " . $e->getLine() . "\r\n" . "Error message:" . $e->getMessage() . "\r\n";
			$this->main->common()->put_error_log($logstr);

			$result['status'] = false;
			$result['message'] = '公開処理が失敗しました。';

			// GMTの現在日時
			$end_datetime = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

			//============================================================
			// 公開処理結果テーブルの更新処理（失敗）
			//============================================================
			if ($result['output_id']) {
				$this->update_output_data(define::PUBLISH_STATUS_FAILED, $result['output_id'], $end_datetime);
			}

			// ロック解除処理
			$this->unlock();

			$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========公開処理 異常終了==========", $this->realpath_tracelog);

			$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish end');

			chdir($current_dir);
			
			return $result;
		}

		$result['status'] = true;
		$result['message'] = '公開処理が成功しました。';

		// ロック解除処理
		$this->unlock();

		$this->main->common()->put_publish_log(__METHOD__, __LINE__, "==========公開処理 正常終了==========", $this->realpath_tracelog);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ exec_publish end');

		chdir($current_dir);
			
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

		$logstr = "==========rsyncコマンドによるディレクトリの同期実行==========" . "\r\n";
		$logstr .= "【同期元パス】" . $from_realpath . "\r\n";
		$logstr .= "【同期先パス】" . $to_realpath;
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

		$logstr = "==========rsyncコマンドによるディレクトリのコピー実行==========" . "\r\n";
		$logstr .= "【コピー元パス】" . $from_realpath . "\r\n";
		$logstr .= "【コピー先パス】" . $to_realpath;
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$command = 'rsync -rhtvz' . ' ' .
					$from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $this->realpath_tracelog;
		
		$this->main->common()->command_execute($command, true);

	}

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
	 * @param  string $from_realpath 	移動元の絶対パス
	 * @param  string $to_realpath		移動先の絶対パス
	 * 
	 * @throws Exception ディレクトリの削除が失敗した場合
	 */
	public function exec_sync_move($from_realpath, $to_realpath) {

		$logstr = "==========rsyncコマンドによるディレクトリの移動実行==========" . "\r\n";
		$logstr .= "【移動元パス】 " . $from_realpath . "\r\n";
		$logstr .= "【移動先パス】 " . $to_realpath;
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$command = 'rsync -rhtvz --remove-source-files ' .
					$from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $this->realpath_tracelog;

		$this->main->common()->command_execute($command, true);

		$logstr = "==========移動元の空ディレクトリ削除実行==========" . "\r\n";
		$logstr .= "【削除パス】：" . $from_realpath;
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		// 空のディレクトリを再帰的に削除する
		if (!$this->main->fs()->rmdir_r($from_realpath)) {
			throw new \Exception("fs->rmdir_r() is failed.");
		}
	}

	/**
	 * パブリッシュをロックする。
	 *
	 * @return bool ロック成功時に `true`、失敗時に `false` を返します。
	 */
	private function lock(){

		$logstr = "==========パブリッシュのロック処理 START==========";
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);


		$path_lockdir = $this->main->fs()->get_realpath( $this->main->options->realpath_workdir . 'applock/' );
		$this->path_lockfile = $path_lockdir .'applock.txt';

		$timeout_limit = 5;

		// 親ディレクトリのチェック生成
		if( !@is_dir( dirname( $this->path_lockfile ) ) ){
			$this->main->fs()->mkdir_r( dirname( $this->path_lockfile ) );
		}

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		$i = 0;
		while( $this->is_locked() ){
			$i ++;
			if( $i >= $timeout_limit ){

				$logstr = "==========パブリッシュロック中のため処理中断==========";
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);
				return false;
				// break;
			}
			sleep(1);

			#	PHPのFileStatusCacheをクリア
			clearstatcache();
		}

		$logstr = "==========パブリッシュのロック作成 START==========";
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$src = 'ProcessID='.getmypid()."\r\n";
		$src .= 'Date='. $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		$rtn = $this->main->fs()->save_file( $this->path_lockfile , $src );

		$this->main->common()->put_publish_log(__METHOD__, __LINE__, "ロックファイル作成結果：rtn=" . $rtn, $this->realpath_tracelog);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, "ProcessID=" . getmypid(), $this->realpath_tracelog);

		$logstr = "==========パブリッシュのロック作成 END==========";
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
				$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);
				return false;
			}

			$logstr = "※パブリッシュのロック確認 --->>> ロック中";
			$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

			return true;
		}

		$logstr = "※パブリッシュのロック確認 --->>> ロック無し";
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
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$lockfilepath = $this->path_lockfile;

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		return @unlink( $lockfilepath );
	}//unlock()

	/**
	 * 公開処理用のログを生成する
	 *
	 * @param array  $realpath_array 	作業用ディレクトリのパス配列
	 * @param string $running_dirname 	公開作業用ディレクトリ名
	 */
	private function create_publish_log($realpath_array, $running_dirname){

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
	}


	/**
	 * バックアップ情報の取得
	 *
	 * @param array  $publish_type 	公開種別
	 * @param string $output_id 	公開処理結果ID
	 * 
	 * @return array $get_backup_data
	 *					バックアップ情報配列
	 * 
	 * @throws Exception バックアップ情報の取得が失敗した場合
	 */
	private function get_backup_data($publish_type, $output_id) {

		$backup_data = null;

		// 手動復元の場合
		if ($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) {

			$selected_id =  $this->main->options->_POST->selected_id;

			$logstr = "==========[手動復元公開]バックアップ対象データの取得==========";
			$logstr .= "選択バックアップID --> " . $selected_id . "\r\n";
			$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

			$backup_data = $this->tsBackup->get_selected_ts_backup($selected_id);
	
		// 自動復元の場合	
		} elseif ($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE) {

			$logstr = "==========[自動復元公開]バックアップ対象データの取得==========";
			$logstr .= "公開処理結果ID --> " . $output_id . "\r\n";
			$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

			// 処理結果IDからバックアップ情報を取得
			$backup_data = $this->tsBackup->get_selected_ts_backup_by_output_id($output_id);
		}

		if (!$backup_data) {
			throw new \Exception('Backup data not found.');
		}

		return $backup_data;
	}

	/**
	 * 公開処理結果情報の登録を行う
	 *
	 * @param string $publish_type    公開種別
	 * @param string $status 		  公開ステータス
	 * @param string $start_datetime  処理開始日時
	 * @param array  $reserve_data	  予定情報配列（予定公開の場合のみ）
	 * @param int    $backup_id  	  バックアップID （復元公開の場合のみ）
	 *
	 * @return int $output_id insertしたシーケンスID
	 */
	private function insert_output_data($publish_type, $status, $start_datetime, $reserve_data, $backup_id) {

		// 現在時刻
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		$output_dataArray = null;

		if ($publish_type == define::PUBLISH_TYPE_RESERVE) {

			// 予定公開時の公開処理結果テーブル設定情報
			$output_dataArray = array(

				tsOutput::TS_OUTPUT_RESERVE_ID 		=> $reserve_data[tsReserve::TS_RESERVE_ID_SEQ],
				tsOutput::TS_OUTPUT_BACKUP_ID 		=> null,
				tsOutput::TS_OUTPUT_RESERVE 		=> $reserve_data[tsReserve::TS_RESERVE_DATETIME],
				tsOutput::TS_OUTPUT_BRANCH 			=> $reserve_data[tsReserve::TS_RESERVE_BRANCH],
				tsOutput::TS_OUTPUT_COMMIT_HASH 	=> $reserve_data[tsReserve::TS_RESERVE_COMMIT_HASH],
				tsOutput::TS_OUTPUT_COMMENT 		=> $reserve_data[tsReserve::TS_RESERVE_COMMENT],
				tsOutput::TS_OUTPUT_PUBLISH_TYPE 	=> define::PUBLISH_TYPE_RESERVE,
				tsOutput::TS_OUTPUT_STATUS 			=> $status,
				tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => null,
				tsOutput::TS_OUTPUT_START 			=> $start_datetime,
				tsOutput::TS_OUTPUT_END 			=> null,
				tsOutput::TS_OUTPUT_GEN_DELETE_FLG 	=> define::DELETE_FLG_OFF,
				tsOutput::TS_OUTPUT_GEN_DELETE 		=> null,
				tsOutput::TS_OUTPUT_INSERT_DATETIME => $now,
				tsOutput::TS_OUTPUT_INSERT_USER_ID 	=> $this->main->user_id,
				tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
				tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> null
			);

		} elseif (($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) ||
					($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE)) {

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
				tsOutput::TS_OUTPUT_INSERT_USER_ID 	=> $this->main->user_id,
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
				tsOutput::TS_OUTPUT_INSERT_USER_ID 	=> $this->main->user_id,
				tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
				tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> null
			);
		}

		// 公開処理結果テーブルの登録
		$output_id = $this->tsOutput->insert_ts_output($output_dataArray);

		$this->main->common()->put_publish_log(__METHOD__, __LINE__, "☆公開処理結果テーブルのINSERT処理実行", $this->realpath_tracelog);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, "公開処理結果ID --> " . $output_id, $this->realpath_tracelog);

		return $output_id;
	}


	/**
	 * 公開処理結果情報の登録を行う
	 *
	 * @param string $status 		  公開ステータス
	 * @param int    $output_id       公開処理結果ID
	 * @param string $end_datetime    公開終了日時
	 */
	private function update_output_data($status, $output_id, $end_datetime) {

		$logstr = "==========公開処理結果テーブルのUPDATE(ステータス更新)実行==========" . "\r\n";
		$logstr = "[ステータス] " . $this->main->common()->convert_status($status);
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		$dataArray = array(
			tsOutput::TS_OUTPUT_STATUS 			=> $status,
			tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
			tsOutput::TS_OUTPUT_END 			=> $end_datetime,
			tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> $this->main->user_id
		);

 		$this->tsOutput->update_ts_output($output_id, $dataArray);
	}

	/**
	 * 公開処理結果情報の登録を行う
	 *
	 * @param array $realpath_array 作業用ディレクトリのパス配列
	 * @param int   $output_id 		公開処理結果ID
	 *
	 * @return int $backup_id insertしたシーケンスID
	 */
	private function exec_backup($realpath_array, $output_id) {

		// GMTの現在日時
		$backup_datetime = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		$backup_dirname = $this->main->common()->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);

		// バックアップテーブルの登録処理
		$backup_id = $this->tsBackup->insert_ts_backup($this->main->user_id, $backup_datetime, $output_id);

		$logstr = "☆バックアップテーブルのINSERT実行" . "\r\n";
		$logstr .= "バックアップID --> " . $backup_id . "\r\n";
		$logstr .= "バックアップ日時 --> " . $backup_datetime . "\r\n";
		$logstr .= "バックアップディレクトリ名 --> " . $backup_dirname;
		$this->main->common()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		//============================================================
		// 本番ソースを「backup」ディレクトリへコピー
		//============================================================
		$from_realpath = $realpath_array['realpath_server'];
		$to_realpath = $realpath_array['realpath_backup'] . $backup_dirname . '/';
		$this->exec_sync_copy($from_realpath, $to_realpath);

		return $backup_id;
	}
}

