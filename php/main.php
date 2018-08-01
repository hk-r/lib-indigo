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
	public $process_log_path;


	/**
	 * コンストラクタ
	 * @param $options = オプション
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
		// ログ出力用の日付ディレクトリ作成
		//============================================================	
		
		if (!$this->process_log_path) {
			// ログファイル名
			$log_dirname = $this->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT_YMD);

			// ログパス
			$this->process_log_path = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->workdir_relativepath . define::PATH_LOG)) . 'log_process_' . $log_dirname . '.log';
		}

		// $logstr = "起動パラメタ：" . $this->options;
		// $this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		// 作業用ディレクトリの絶対パスを取得
		$this->realpath_array = json_decode($this->common()->get_realpath_workdir($this->options, $this->realpath_array));

		$logstr = "realpath_server：" . $this->realpath_array->realpath_server . "\r\n";
		$logstr .= "realpath_backup：" . $this->realpath_array->realpath_backup . "\r\n";
		$logstr .= "realpath_waiting：" . $this->realpath_array->realpath_waiting . "\r\n";
		$logstr .= "realpath_running：" . $this->realpath_array->realpath_running . "\r\n";
		$logstr .= "realpath_released：" . $this->realpath_array->realpath_released . "\r\n";
		$logstr .= "realpath_log：" . $this->realpath_array->realpath_log;
		$this->common()->put_process_log_block($logstr);

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
		// 作業用ディレクトリの作成（既にある場合は作成しない）
		//============================================================
		$this->create_indigo_work_dir();

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

			} elseif (isset($this->options->_POST->add_check)) {
				// 新規ダイアログの「確認」ボタン押下
				
				$logstr = "==========新規ダイアログの「確認」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_check_add());
				
			} elseif (isset($this->options->_POST->add_confirm)) {
				// 新規確認ダイアログの「確定」ボタン押下
				
				$logstr = "==========新規ダイアログの「確定」ボタン押下==========";;
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		
				$result = json_decode($this->initScn->do_confirm_add());	

			} elseif (isset($this->options->_POST->add_back)) {
				// 新規確認ダイアログの「戻る」ボタン押下
				
				$logstr = "==========新規ダイアログの「戻る」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
		
				$result = json_decode($this->initScn->do_back_add_dialog());

			//============================================================
			// 変更関連処理
			//============================================================
			} elseif (isset($this->options->_POST->update)) {
				// 初期表示画面の「変更」ボタン押下
				
				$logstr = "==========初期表示画面の「変更」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_disp_update_dialog());


			} elseif (isset($this->options->_POST->update_check)) {
				// 変更ダイアログの「確認」ボタン押下
				
				$logstr = "==========変更ダイアログの「確認」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_check_update());

			} elseif (isset($this->options->_POST->update_confirm)) {
				// 変更確認ダイアログの「確定」ボタン押下
				
				$logstr = "==========変更ダイアログの「確定」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_confirm_update());	

			} elseif (isset($this->options->_POST->update_back)) {
				// 変更確認ダイアログの「戻る」ボタン押下	
				
				$logstr = "==========変更ダイアログの「戻る」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_back_update_dialog());


			//============================================================
			// 削除処理
			//============================================================
			} elseif (isset($this->options->_POST->delete)) {
				// 初期表示画面の「削除」ボタン押下				
				
				$logstr = "==========初期表示画面の「削除」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				// Gitファイルの削除
				$result = json_decode($this->initScn->do_delete());


			//============================================================
			// 復元処理
			//============================================================
			} elseif (isset($this->options->_POST->restore)) {
				// バックアップ一覧画面の「復元ボタン押下				
				
				$logstr = "==========バックアップ一覧画面の「復元」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				// Gitファイルの削除
				$result = json_decode($this->backupScn->do_restore_publish());


			//============================================================
			// 即時公開処理
			//============================================================
			} elseif (isset($this->options->_POST->immediate)) {
				// 初期表示画面の「即時公開」ボタン押下				
				
				$logstr = "==========初期表示画面の「即時公開」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_disp_immediate_dialog());

			} elseif (isset($this->options->_POST->immediate_check)) {
				// 即時公開ダイアログの「確認」ボタン押下
				
				$logstr = "==========即時公開ダイアログの「確認」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_check_immediate());

			} elseif (isset($this->options->_POST->immediate_confirm)) {
				// 即時公開確認ダイアログの「確定」ボタン押下	
				
				$logstr = "==========即時公開ダイアログの「確定」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_immediate_publish());

				if ( !$result->status ) {
					// 処理失敗の場合、復元処理

					$logstr = "==========即時公開失敗==========";
					$logstr .= $result->message . "\r\n";
					$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			

					$error_message = $result->message . " ";
					$result = json_decode($this->initScn->do_restore_publish_failure($result->output_id));

					if ( !$result->status ) {
						// 処理失敗の場合、復元処理
						
						$logstr = "==========即時公開の復元処理失敗==========";
						$logstr .= $result->message . "\r\n";
						$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
					}

				}

			} elseif (isset($this->options->_POST->immediate_back)) {
				// 即時公開確認ダイアログの「戻る」ボタン押下			
				
				$logstr = "==========即時公開ダイアログの「戻る」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->initScn->do_back_immediate_dialog());
			
			//============================================================
			// ログ表示処理
			//============================================================
			} elseif (isset($this->options->_POST->log)) {
				// 履歴表示画面の「新規」ボタン押下
				
				$logstr = "==========履歴表示画面の「新規」ボタン押下==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$result = json_decode($this->historyScn->do_disp_log_dialog());
			}


			if ( !$result->status ) {
				// 処理失敗の場合

				$error_message .=  $result->message;

				$logstr = "**********************************************************************************" . "\r\n";
				$logstr .= " ステータスエラー " . "\r\n";
				$logstr .= "**********************************************************************************" . "\r\n";
				$this->common()->put_process_log_block($logstr);

				$logstr = $error_message . "\r\n";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);


				// エラーメッセージ表示
				$dialog_disp = '
				<script type="text/javascript">
					console.error(' . "'" . $error_message . "'" . ');
					alert(' . "'" . $error_message . "'" . ');
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

		} catch (\Exception $e) {

			$logstr = "** run() 例外キャッチ **" . "\r\n";
			$logstr .= $e->getMessage() . "\r\n";
			$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			// エラーメッセージ表示
			$dialog_disp = '
			<script type="text/javascript">
				console.error(' . "'" . $e->getMessage() . "'" . ');
				alert(' . "'" . $e->getMessage() . "'" . ');
			</script>';

			// データベース接続を閉じる
			$this->pdoMgr->close($this->dbh);

			$this->common()->put_process_log('■ run error end');

			return $dialog_disp;
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
			$logstr .= $e->getMessage(). "\r\n";
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
		$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		$this->common()->put_process_log(__METHOD__, __LINE__, '■ [cron] run end');

		return;
    }

	/**
	 * 作業用ディレクトリの作成（既にある場合は作成しない）
	 *	
	 * @return ソート後の配列
	 */
	function create_indigo_work_dir() {
	
		$logstr = "create_indigo_work_dir() start";
		$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		$ret = true;

		// logファイルディレクトリが存在しない場合は作成
		if ( !$this->common()->is_exists_mkdir($this->realpath_array->realpath_log) ) {
			$ret = false;
		}

		// backupディレクトリが存在しない場合は作成
		if ( !$this->common()->is_exists_mkdir($this->realpath_array->realpath_backup) ) {
			$ret = false;
		}

		// waitingディレクトリが存在しない場合は作成
		if ( !$this->common()->is_exists_mkdir($this->realpath_array->realpath_waiting) ) {
			$ret = false;
		}

		// runningディレクトリが存在しない場合は作成
		if ( !$this->common()->is_exists_mkdir($this->realpath_array->realpath_running) ) {
			$ret = false;
		}

		// releasedディレクトリが存在しない場合は作成
		if ( !$this->common()->is_exists_mkdir($this->realpath_array->realpath_released) ) {
			$ret = false;
		}

		$logstr = "$ret = " . $ret;
		$logstr = "create_indigo_work_dir() end";
		$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		return $ret;
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
	 * response status code を取得する。
	 *
	 * `$px->set_status()` で登録した情報を取り出します。
	 *
	 * @return int ステータスコード (100〜599の間の数値)
	 */
	public function get_dbh(){
		return $this->dbh;
	}


	/**
	 * response status code を取得する。
	 *
	 * `$px->set_status()` で登録した情報を取り出します。
	 *
	 * @return int ステータスコード (100〜599の間の数値)
	 */
	// public function put_process_log($path, $text){
		
	// 	file_put_contents($path, $text, FILE_APPEND);
	// }

	// /**
	//  * response status code を取得する。
	//  *
	//  * `$px->set_status()` で登録した情報を取り出します。
	//  *
	//  * @return int ステータスコード (100〜599の間の数値)
	//  */
	// public function put_process_log($text){
		
	// 	$datetime = $this->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

	// 	$str = "[" . $datetime . "]" . " " . $text . "\r\n";

	// 	// file_put_contents($path, $str, FILE_APPEND);

	// 	return error_log( $str, 3, $this->process_log_path );
	// }


}
