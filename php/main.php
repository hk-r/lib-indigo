<?php

namespace indigo;

class main
{
	public $options;

	/**
	 * オブジェクト
	 * @access private
	 */
	private $gitMgr, $fs, $pdoMgr, $initScn, $historyScn, $backupScn, $publish, $common;

	/**
	 * PDOインスタンス
	 */
	public $dbh;

	/**
	 * 作業ディレクトリパス配列
	 */
	public $realpath_array = array('realpath_server' => '',
									'realpath_backup' => '',
									'realpath_waiting' => '',
									'realpath_running' => '',
									'realpath_released' => '',
									'realpath_log' => '');

	/**
	 * logパス
	 */
	public $process_log_path, $error_log_path;


	/**
	 * コンストラクタ
	 * @param $this->options = オプション
	 */
	public function __construct($options) {

		//============================================================
		// オブジェクト生成
		//============================================================	
		$this->options = json_decode(json_encode($options));

		$this->fs = new \tomk79\filesystem(array(
		  'file_default_permission' => define::FILE_DEFAULT_PERMISSION,
		  'dir_default_pefrmission' => define::DIR_DEFAULT_PERMISSION,
		  'filesystem_encoding' 	=> define::FILESYSTEM_ENCODING
		));

		$this->common = new common($this);

		$this->gitMgr = new gitManager($this);
		$this->pdoMgr = new pdoManager($this);
		$this->publish = new publish($this);

		$this->initScn = new \indigo\screen\initScreen($this);
		$this->historyScn = new \indigo\screen\historyScreen($this);
		$this->backupScn = new \indigo\screen\backupScreen($this);


		//============================================================
		// エラーログ出力登録
		//============================================================	
		// ログパス
		$this->error_log_path = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_LOG . 'error.log'));

		// 致命的なエラーのエラーハンドラ登録
		register_shutdown_function(
		    function(){
		        $e = error_get_last();
		        // if ($e === null) {
		        // 	return;
		        // }
		        if( $e['type'] == E_ERROR ||
		        	$e['type'] == E_WARNING ||
		            $e['type'] == E_PARSE ||
		            $e['type'] == E_CORE_ERROR ||
		            $e['type'] == E_COMPILE_ERROR ||
		            $e['type'] == E_USER_ERROR ){
		            
		            echo "エラーが発生しました。管理者にお問い合わせください。";

					if (file_exists($this->error_log_path)) {
					
						$logstr =  "***** エラー発生 *****" . "\r\n";
						$logstr .= "[ERROR]" . "\r\n";
						$logstr .= $e['file'] . " in " . $e['line'] . "\r\n";
						$logstr .= "Error message:" . $e['message'] . "\r\n";
						$this->common()->put_error_log($logstr);
					
					} else {
						echo $e['file'] . " in " . $e['line'] . "\r\n";
						echo "Error message:" . $e['message'] . "\r\n";
					}

		        }
		    }
		);


		// 致命的なエラー以外のエラーハンドラ登録
		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
		});

		//============================================================
		// 作業ディレクトリ絶対パス格納
		//============================================================
		// 本番環境ディレクトリの絶対パスを取得。（配列1番目のサーバを設定）
		foreach ( (array)$this->options->server as $server ) {
			$realpath_array['realpath_server'] = $this->fs()->normalize_path($this->fs()->get_realpath($server->real_path . "/"));
			break; // 現時点では最初の1つのみ有効なのでブレイク
		}

		// backupディレクトリの絶対パスを取得。
		$realpath_array['realpath_backup'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_BACKUP));

		// waitingディレクトリの絶対パスを取得。
		$realpath_array['realpath_waiting'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_WAITING));

		// runningディレクトリの絶対パスを取得。
		$realpath_array['realpath_running'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_RUNNING));

		// releasedディレクトリの絶対パスを取得。
		$realpath_array['realpath_released'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_RELEASED));

		// logディレクトリの絶対パスを取得。
		$realpath_array['realpath_log'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_LOG));

		// 変数へログ情報を格納
		$this->realpath_array = json_decode(json_encode($realpath_array));


		//============================================================
		// 作業ディレクトリ作成
		//============================================================
		$current_dir = realpath('.');
		if (chdir($this->options->realpath_workdir)) {

			// logファイルディレクトリが存在しない場合は作成
			$this->fs()->mkdir($this->realpath_array->realpath_log);
			// backupディレクトリが存在しない場合は作成
			$this->fs()->mkdir($this->realpath_array->realpath_backup);
			// waitingディレクトリが存在しない場合は作成
			$this->fs()->mkdir($this->realpath_array->realpath_waiting);
			// runningディレクトリが存在しない場合は作成
			$this->fs()->mkdir($this->realpath_array->realpath_running);
			// releasedディレクトリが存在しない場合は作成
			$this->fs()->mkdir($this->realpath_array->realpath_released);

		} else {
			// ディレクトリ移動に失敗
			chdir($current_dir);
			throw new \Exception('Move to indigo work directory failed.');
		}
		chdir($current_dir);


		//============================================================
		// 通常ログ出力登録
		//============================================================	
		// ログファイル名
		$log_dirname = $this->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT_YMD);

		// ログパス
		$this->process_log_path = $this->realpath_array->realpath_log . 'log_process_' . $log_dirname . '.log';

		$logstr = "realpath_server：" . $this->realpath_array->realpath_server . "\r\n";
		$logstr .= "realpath_backup：" . $this->realpath_array->realpath_backup . "\r\n";
		$logstr .= "realpath_waiting：" . $this->realpath_array->realpath_waiting . "\r\n";
		$logstr .= "realpath_running：" . $this->realpath_array->realpath_running . "\r\n";
		$logstr .= "realpath_released：" . $this->realpath_array->realpath_released . "\r\n";
		$logstr .= "realpath_log：" . $this->realpath_array->realpath_log;
		$this->common()->put_process_log_block($logstr);
	}

	/**
	 * 
	 */
	public function run() {
		
		$logstr = "run() start";
		$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		// 画面表示
		$disp = '';  

		// エラーメッセージ表示
		$error_message = '';

		// ダイアログの表示
		$dialog_disp = '';
		
		// 画面ロック用
		$disp_lock = '';

		// 処理実行結果格納
		$result = json_decode(json_encode(
					array('status' => true,
					      'message' => '',
					  	  'dialog_disp' => '')
				  ));

		try {

			// エラーデバッグ用
			// $i = 5/0;
			// asdf();
			// $status = define::PUBLISH_STATUS_RUNNNG;

			//============================================================
			// タイムゾーンの設定
			//============================================================
			$time_zone = $this->options->time_zone;
			if (!$time_zone) {
				throw new \Exception('Parameter of timezone not found.');
			}
			date_default_timezone_set($time_zone);

			$logstr = "設定タイムゾーン：" . $time_zone;
			$this->common()->put_process_log_block($logstr);

			//============================================================
			// データベース接続
			//============================================================
			$this->dbh = $this->pdoMgr->connect();


			//============================================================
			// テーブル作成（作成済みの場合はスキップ）
			//============================================================
			$this->pdoMgr->create_table();
			

			//============================================================
			// Gitのmaster情報取得
			//============================================================
			$this->gitMgr->get_git_master($this->options);
			

			//============================================================
			// 新規関連処理
			//============================================================
			if (isset($this->options->_POST->add)) {
				// 初期表示画面の「新規」ボタン押下

				$logstr = "==========初期表示画面の「新規」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		
				$result = json_decode($this->initScn->do_disp_add_dialog());
				$error_message = $result->message;

			} elseif (isset($this->options->_POST->add_check)) {
				// 新規ダイアログの「確認」ボタン押下
				
				$logstr = "==========新規ダイアログの「確認」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_check_add());
				$error_message = $result->message;

			} elseif (isset($this->options->_POST->add_confirm)) {
				// 新規確認ダイアログの「確定」ボタン押下
				
				$logstr = "==========新規ダイアログの「確定」ボタン押下==========";;
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		
				$result = json_decode($this->initScn->do_confirm_add());	
				$error_message = $result->message;

			} elseif (isset($this->options->_POST->add_back)) {
				// 新規確認ダイアログの「戻る」ボタン押下
				
				$logstr = "==========新規ダイアログの「戻る」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		
				$result = json_decode($this->initScn->do_back_add_dialog());
				$error_message = $result->message;

			//============================================================
			// 変更関連処理
			//============================================================
			} elseif (isset($this->options->_POST->update)) {
				// 初期表示画面の「変更」ボタン押下
				
				$logstr = "==========初期表示画面の「変更」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_disp_update_dialog());
				$error_message = $result->message;

			} elseif (isset($this->options->_POST->update_check)) {
				// 変更ダイアログの「確認」ボタン押下
				
				$logstr = "==========変更ダイアログの「確認」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_check_update());
				$error_message = $result->message;

			} elseif (isset($this->options->_POST->update_confirm)) {
				// 変更確認ダイアログの「確定」ボタン押下
				
				$logstr = "==========変更ダイアログの「確定」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_confirm_update());	
				$error_message = $result->message;

			} elseif (isset($this->options->_POST->update_back)) {
				// 変更確認ダイアログの「戻る」ボタン押下	
				
				$logstr = "==========変更ダイアログの「戻る」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_back_update_dialog());
				$error_message = $result->message;

			//============================================================
			// 削除処理
			//============================================================
			} elseif (isset($this->options->_POST->delete)) {
				// 初期表示画面の「削除」ボタン押下				
				
				$logstr = "==========初期表示画面の「削除」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				// Gitファイルの削除
				$result = json_decode($this->initScn->do_delete());
				$error_message = $result->message;

			//============================================================
			// 復元処理
			//============================================================
			} elseif (isset($this->options->_POST->restore)) {
				// バックアップ一覧画面の「復元ボタン押下				
				
				$logstr = "==========バックアップ一覧画面の「復元」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				// Gitファイルの削除
				$result = json_decode($this->backupScn->do_restore_publish());
				$error_message = $result->message;

			//============================================================
			// 即時公開処理
			//============================================================
			} elseif (isset($this->options->_POST->immediate)) {
				// 初期表示画面の「即時公開」ボタン押下				
				
				$logstr = "==========初期表示画面の「即時公開」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_disp_immediate_dialog());
				$error_message = $result->message;
								$error_message = $result->message;
			} elseif (isset($this->options->_POST->immediate_check)) {
				// 即時公開ダイアログの「確認」ボタン押下
				
				$logstr = "==========即時公開ダイアログの「確認」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_check_immediate());
				$error_message = $result->message;

			} elseif (isset($this->options->_POST->immediate_confirm)) {
				// 即時公開確認ダイアログの「確定」ボタン押下	
				
				$logstr = "==========即時公開ダイアログの「確定」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_immediate_publish());
				$error_message = $result->message;

				if ( !$result->status ) {
					// 処理失敗の場合、復元処理

					$logstr = "※即時公開処理エラー終了";
					$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

					// 画面アラート用のメッセージ			
					$error_message = "[即時公開処理]" . $result->message . " ";

					$result = json_decode($this->initScn->do_restore_publish_failure($result->output_id));

					if ( !$result->status ) {
						// 処理失敗の場合、復元処理
						
						$logstr = "※復元公開処理エラー終了";
						$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

						// 画面アラート用のメッセージ
						$error_message .= $error_message . "[復元公開処理]" . $result->message;
					}

				}

			} elseif (isset($this->options->_POST->immediate_back)) {
				// 即時公開確認ダイアログの「戻る」ボタン押下			
				
				$logstr = "==========即時公開ダイアログの「戻る」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_back_immediate_dialog());
				$error_message = $result->message;

			//============================================================
			// ログ表示処理
			//============================================================
			} elseif (isset($this->options->_POST->log)) {
				// 履歴表示画面の「新規」ボタン押下
				
				$logstr = "==========履歴表示画面の「新規」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->historyScn->do_disp_log_dialog());
				$error_message = $result->message;
			}


			if ( $error_message ) {
				// 処理失敗の場合

				// $error_message .=  $result->message;

				$logstr = "**********************************************************************************" . "\r\n";
				$logstr .= " ステータスエラー " . "\r\n";
				$logstr .= "**********************************************************************************" . "\r\n";
				$this->common()->put_process_log_block($logstr);

				$logstr = $error_message . "\r\n";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

				$msg = nl2br(error_message);


				// エラーメッセージ表示
				$dialog_disp = '
				<script type="text/javascript">
					console.error(' . "'" . $error_message . "'" . ');
					alert(' . "'" . $msg . "'" . ');
				</script>';

			} else {

				if ($result->dialog_disp) {
					$dialog_disp = $result->dialog_disp;	
				}
			}

			if (isset($this->options->_POST->history) ||
				isset($this->options->_POST->log)) {
				// 初期表示画面の「履歴」ボタン押下
				
				$logstr = "==========履歴画面の表示==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$disp = $this->historyScn->disp_history_screen();

			} elseif (isset($this->options->_POST->backup)) {
				// 初期表示画面の「バックアップ一覧」ボタン押下

				$logstr = "==========バックアップ一覧画面の表示==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$disp = $this->backupScn->disp_backup_screen();
				
			} else {
				// 初期表示画面の表示

				$logstr = "==========初期表示画面の表示==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$disp = $this->initScn->do_disp_init_screen();

			}

			// 画面ロック用
			$disp_lock = '<div id="loader-bg"><div id="loading"></div></div>';

		} catch (\ErrorException $e) {

		    echo "例外エラーが発生しました。管理者にお問い合わせください。". "\r\n";
		    // echo $this->error_log_path. "\r\n";
		    // echo file_exists($this->error_log_path). "\r\n";
			if (file_exists($this->error_log_path)) {
				$logstr =  "***** エラー発生 *****" . "\r\n";
				$logstr .= "[ERROR]" . "\r\n";
				$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
				$logstr .= "Error message:" . $e->getMessage() . "\r\n";
				$this->common()->put_error_log($logstr);
			} else {
				echo $e->getFile() . " in " . $e->getLine() . "\r\n";
				echo "Error message:" . $e->getMessage() . "\r\n";
			}


		} catch (\Exception $e) {

			$logstr = "** run() 例外キャッチ **" . "\r\n";
			$logstr .= $e->getMessage() . "\r\n";
			$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			echo $e->getMessage();

			// // エラーメッセージ表示
			// $dialog_disp = '
			// <script type="text/javascript">
			// 	console.error(' . "'" . $e->getMessage() . "'" . ');
			// 	alert(' . "'" . $e->getMessage() . "'" . ');
			// </script>';

			$logstr =  "***** 例外エラー発生 *****" . "\r\n";
			$logstr .= "[ERROR]" . "\r\n";
			$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
			$logstr .= "Error message:" . $e->getMessage() . "\r\n";
			$this->common()->put_error_log($logstr);

			// データベース接続を閉じる
			$this->pdoMgr->close($this->dbh);

			$this->common()->put_process_log(__METHOD__, __LINE__, '■ run error end');

			// return $dialog_disp;
			return;
		}
		
		// データベース接続を閉じる
		$this->pdoMgr->close();

		$logstr = "run() end";
		$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		// 画面表示
		return $disp . $disp_lock . $dialog_disp;
	}

	/**
	 * 
	 */
    public function cron_run(){
	
		$this->common()->put_process_log(__METHOD__, __LINE__, '■ [cron] run start');

		// 処理実行結果格納
		$result = json_decode(json_encode(
					array('status' => true,
					      'message' => '')
				  ));

		try {

			$logstr = "\r\n";
			$logstr .= "===============================================" . "\r\n";
			$logstr .= "予約公開処理開始" . "\r\n";
			$logstr .= "===============================================" . "\r\n";
			$logstr .= "日時：" . $this->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT) . "\r\n";
			$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			//============================================================
			// データベース接続
			//============================================================
			$this->dbh = $this->pdoMgr->connect();


			//============================================================
			// 公開処理実施
			//============================================================
			$result = json_decode(json_encode($this->publish->exec_publish(define::PUBLISH_TYPE_RESERVE, null)));
	
			if ( !$result->status ) {
				// 処理失敗の場合、復元処理

				$logstr = "** 予約公開処理失敗 **" . "\r\n";
				$logstr .= $result->message . "\r\n";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

				// $error_message = $result->message . " ";
				$result = json_decode(json_encode($this->publish->exec_publish(define::PUBLISH_TYPE_AUTO_RESTORE, $result->output_id)));

				// $result = $this->publish->exec_publish(define::PUBLISH_TYPE_AUTO_RESTORE, $output_id);

				if ( !$result->status ) {
					// 処理失敗の場合

					$logstr = "** 復元処理失敗 **" . "\r\n";
					$logstr .= $result->message . "\r\n";
					$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
				}
			}

		} catch (\Exception $e) {

			// データベース接続を閉じる
			$this->pdoMgr->close($this->dbh);
			
			$logstr = "\r\n";
			$logstr .= "===============================================" . "\r\n";
			$logstr .= "予約公開処理異常終了（例外キャッチ）" . "\r\n";
			$logstr .= "===============================================" . "\r\n";
			$logstr .= "日時：" . $this->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT) . "\r\n";
			$logstr .= $e->getMessage();
			$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			return;
		}

		// データベース接続を閉じる
		$this->pdoMgr->close();

		$logstr = "\r\n";
		$logstr .= "===============================================" . "\r\n";
		$logstr .= "予約公開処理終了" . "\r\n";
		$logstr .= "===============================================" . "\r\n";
		$logstr .= "日時：" . $this->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT) . "\r\n";
		$logstr .= $result->message;
		$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		$this->common()->put_process_log(__METHOD__, __LINE__, '■ [cron] run end');

		return;
    }

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
	 * `$gitMgr` オブジェクトを取得する。
	 *
	 * @return object $gitMgr オブジェクト
	 */
	public function gitMgr(){
		return $this->gitMgr;
	}

	/**
	 * `$pdoMgr` オブジェクトを取得する。
	 *
	 * @return object $pdoMgr オブジェクト
	 */
	public function pdoMgr(){
		return $this->pdoMgr;
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
	 * `$dbh` オブジェクトを取得する。
	 *
	 * @return object $dbh オブジェクト
	 */
	public function get_dbh(){
		return $this->dbh;
	}

}
