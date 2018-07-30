<?php

namespace indigo;

class main
{
	public $options;

	/**
	 * オブジェクト
	 * @access private
	 */
	private $gitMgr, $fs, $pdoMgr, $initScn, $historyScn, $backupScn, $common;

	/**
	 * PDOインスタンス
	 */
	public $dbh;

	/**
	 * PDOインスタンス
	 */
	public $realpath_array;

	/**
	 * logパス
	 */
	public $realpath_log;


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
		$this->gitMgr = new gitManager($this);

		$this->pdoMgr = new pdoManager($this);
		$this->initScn = new initScreen($this);
		$this->historyScn = new historyScreen($this);
		$this->backupScn = new backupScreen($this);

		//============================================================
		// タイムゾーンの設定
		//============================================================
		$time_zone = $this->options->time_zone;
		if (!$time_zone) {
			throw new \Exception('Parameter of timezone not found.');
		}
		date_default_timezone_set($time_zone);


		//============================================================
		// 作業用ディレクトリの作成（既にある場合は作成しない）
		//============================================================
		$this->create_indigo_work_dir();


		//============================================================
		// ログ出力用の日付ディレクトリ作成
		//============================================================
		// // 作業用ディレクトリの絶対パスを取得
		// $realpath_array = $this->realpath_array;
		
		// GMT現在日時を取得し、ディレクトリ名用にフォーマット変換
		$start_datetime = $this->common()->get_current_datetime_of_gmt();

		$log_dirname = $this->common()->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_YMD);

		// logの日付ディレクトリを作成
		$this->realpath_log = $this->fs()->normalize_path($this->fs()->get_realpath(
			$this->realpath_array->realpath_log)) . 'log_' . $log_dirname . '.log';
			
		$this->common()->debug_echo('　□ realpath_log' . $realpath_log);

	}

	/**
	 * 
	 */
	public function run() {
	
		$this->common->debug_echo('■ run start');
		
		$logstr = "===============================================" . "\r\n";
		$logstr .= "run()関数呼び出し" . "\r\n";
		$logstr .= "===============================================" . "\r\n";
		$this->put_log($this->realpath_log, $logstr);

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

				$logstr = "===============================================" . "\r\n";
				$logstr .= "初期表示画面の「新規」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
		
				$result = json_decode($this->initScn->do_disp_add_dialog());

			} elseif (isset($this->options->_POST->add_check)) {
				// 新規ダイアログの「確認」ボタン押下
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "新規ダイアログの「確認」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$result = json_decode($this->initScn->do_check_add());
				
			} elseif (isset($this->options->_POST->add_confirm)) {
				// 新規確認ダイアログの「確定」ボタン押下
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "新規ダイアログの「確定」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
		
				$result = json_decode($this->initScn->do_confirm_add());	

			} elseif (isset($this->options->_POST->add_back)) {
				// 新規確認ダイアログの「戻る」ボタン押下
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "新規ダイアログの「戻る」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
		
				$result = json_decode($this->initScn->do_back_add_dialog());

			//============================================================
			// 変更関連処理
			//============================================================
			} elseif (isset($this->options->_POST->update)) {
				// 初期表示画面の「変更」ボタン押下
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "初期表示画面の「変更」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$result = json_decode($this->initScn->do_disp_update_dialog());


			} elseif (isset($this->options->_POST->update_check)) {
				// 変更ダイアログの「確認」ボタン押下
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "変更ダイアログの「確認」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$result = json_decode($this->initScn->do_check_update());

			} elseif (isset($this->options->_POST->update_confirm)) {
				// 変更確認ダイアログの「確定」ボタン押下
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "変更ダイアログの「確定」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$result = json_decode($this->initScn->do_confirm_update());	

			} elseif (isset($this->options->_POST->update_back)) {
				// 変更確認ダイアログの「戻る」ボタン押下	
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "変更ダイアログの「戻る」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$result = json_decode($this->initScn->do_back_update_dialog());


			//============================================================
			// 削除処理
			//============================================================
			} elseif (isset($this->options->_POST->delete)) {
				// 初期表示画面の「削除」ボタン押下				
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "初期表示画面の「削除」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				// Gitファイルの削除
				$result = json_decode($this->initScn->do_delete());


			//============================================================
			// 復元処理
			//============================================================
			} elseif (isset($this->options->_POST->restore)) {
				// バックアップ一覧画面の「復元ボタン押下				
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "バックアップ一覧画面の「復元」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				// Gitファイルの削除
				$result = json_decode($this->backupScn->do_restore_publish());


			//============================================================
			// 即時公開処理
			//============================================================
			} elseif (isset($this->options->_POST->immediate)) {
				// 初期表示画面の「即時公開」ボタン押下				
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "初期表示画面の「即時公開」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$result = json_decode($this->initScn->do_disp_immediate_dialog());

			} elseif (isset($this->options->_POST->immediate_check)) {
				// 即時公開ダイアログの「確認」ボタン押下
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "即時公開ダイアログの「確認」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$result = json_decode($this->initScn->do_check_immediate());

			} elseif (isset($this->options->_POST->immediate_confirm)) {
				// 即時公開確認ダイアログの「確定」ボタン押下	
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "即時公開ダイアログの「確定」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$result = json_decode($this->initScn->do_immediate_publish());

				if ( !$result->status ) {
					// 処理失敗の場合、復元処理
					$error_message = $result->message . " ";
					$result = json_decode($this->initScn->do_restore_publish_failure($result->output_id));
				}

			} elseif (isset($this->options->_POST->immediate_back)) {
				// 即時公開確認ダイアログの「戻る」ボタン押下			
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "即時公開ダイアログの「戻る」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$result = json_decode($this->initScn->do_back_immediate_dialog());
			
			//============================================================
			// ログ表示処理
			//============================================================
			} elseif (isset($this->options->_POST->log)) {
				// 履歴表示画面の「新規」ボタン押下
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "履歴表示画面の「新規」ボタン押下" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$result = json_decode($this->historyScn->do_disp_log_dialog());
			}


			if ( !$result->status ) {
				// 処理失敗の場合

				$error_message .=  $result->message;

				$logstr = "** status : false **" . "\r\n";
				$logstr = $error_message . "\r\n";
				$this->put_log($this->realpath_log, $logstr);

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
				
				$logstr = "===============================================" . "\r\n";
				$logstr .= "履歴画面の表示" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$disp = $this->historyScn->disp_history_screen();

			} elseif (isset($this->options->_POST->backup)) {
				// 初期表示画面の「バックアップ一覧」ボタン押下

				$logstr = "===============================================" . "\r\n";
				$logstr .= "バックアップ一覧画面の表示" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$disp = $this->backupScn->disp_backup_screen();
				
			} else {
				// 初期表示画面の表示

				$logstr = "===============================================" . "\r\n";
				$logstr .= "初期表示画面の表示" . "\r\n";
				$logstr .= "===============================================" . "\r\n";
				$this->put_log($this->realpath_log, $logstr);
			
				$disp = $this->initScn->do_disp_init_screen();

			}

			// 画面ロック用
			$disp_lock = '<div id="loader-bg"><div id="loading"></div></div>';

		} catch (\Exception $e) {

			$logstr = "** run() 例外キャッチ **" . "\r\n";
			$logstr .= $e->getMessage() . "\r\n";
			$this->put_log($this->realpath_log, $logstr);

			// エラーメッセージ表示
			$dialog_disp = '
			<script type="text/javascript">
				console.error(' . "'" . $e->getMessage() . "'" . ');
				alert(' . "'" . $e->getMessage() . "'" . ');
			</script>';

			// データベース接続を閉じる
			$this->pdoMgr->close($this->dbh);

			$this->common->debug_echo('■ run error end');

			return $dialog_disp;
		}
		
		// データベース接続を閉じる
		$this->pdoMgr->close();

		// $this->common->debug_echo('■ run end');

		// 画面表示
		return $disp . $disp_lock . $dialog_disp;
	}

	/**
	 * 作業用ディレクトリの作成（既にある場合は作成しない）
	 *	
	 * @return ソート後の配列
	 */
	function create_indigo_work_dir() {
	
		$this->common->debug_echo('■ create_indigo_work_dir start');

		$ret = true;

		// 作業用ディレクトリの絶対パスを取得
		$this->realpath_array = json_decode($this->common->get_realpath_workdir($this->options));


		// logファイルディレクトリが存在しない場合は作成
		if ( !$this->common->is_exists_mkdir($this->realpath_array->realpath_log) ) {
			$ret = false;
		}

		// backupディレクトリが存在しない場合は作成
		if ( !$this->common->is_exists_mkdir($this->realpath_array->realpath_backup) ) {
			$ret = false;
		}

		// waitingディレクトリが存在しない場合は作成
		if ( !$this->common->is_exists_mkdir($this->realpath_array->realpath_waiting) ) {
			$ret = false;
		}

		// runningディレクトリが存在しない場合は作成
		if ( !$this->common->is_exists_mkdir($this->realpath_array->realpath_running) ) {
			$ret = false;
		}

		// releasedディレクトリが存在しない場合は作成
		if ( !$this->common->is_exists_mkdir($this->realpath_array->realpath_released) ) {
			$ret = false;
		}

		$this->common->debug_echo("　□作業ディレクトリの作成処理結果：");
		$this->common->debug_echo(($ret == true) ? "成功": "失敗");

		$this->common->debug_echo('■ create_indigo_work_dir end');

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
	public function put_log($path, $text){
		
		file_put_contents($path, $text, FILE_APPEND);
	}
}
