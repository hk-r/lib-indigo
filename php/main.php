<?php

namespace indigo;

class main
{
	public $options;

	/**
	 * オブジェクト
	 * @access private
	 */
	private $gitMgr, $pdoMgr, $initScn, $historyScn, $backupScn, $common;

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
		$this->gitMgr = new gitManager($this);
		$this->pdoMgr = new pdoManager($this);
		$this->initScn = new initScreen($this);
		$this->historyScn = new historyScreen($this);
		$this->backupScn = new backupScreen($this);
		$this->common = new common($this);
	}

	/**
	 * 
	 */
	public function run() {
	
		$this->common->debug_echo('■ run start');

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
			// タイムゾーンの設定
			//============================================================
			$time_zone = $this->options->time_zone;
			if (!$time_zone) {
				throw new \Exception('Parameter of timezone not found.');
			}
			date_default_timezone_set($time_zone);


			//============================================================
			// データベース接続
			//============================================================
			$this->dbh = $this->pdoMgr->connect();


			//============================================================
			// テーブル作成（既にある場合は作成しない）
			//============================================================
			$this->pdoMgr->create_table();


			//============================================================
			// 作業用ディレクトリの作成（既にある場合は作成しない）
			//============================================================
			$this->create_indigo_work_dir();


			//============================================================
			// Gitのmaster情報取得
			//============================================================
			$this->gitMgr->get_git_master($this->options);
			

			//============================================================
			// 新規関連処理
			//============================================================
			if (isset($this->options->_POST->add)) {
				// 初期表示画面の「新規」ボタン押下
				
				$result = json_decode($this->initScn->do_disp_add_dialog());

			} elseif (isset($this->options->_POST->add_check)) {
				// 新規ダイアログの「確認」ボタン押下
				
				$result = json_decode($this->initScn->do_check_add());
				
			} elseif (isset($this->options->_POST->add_confirm)) {
				// 新規確認ダイアログの「確定」ボタン押下
				
				$result = json_decode($this->initScn->do_confirm_add());	

			} elseif (isset($this->options->_POST->add_back)) {
				// 新規確認ダイアログの「戻る」ボタン押下

				$result = json_decode($this->initScn->do_back_add_dialog());

			//============================================================
			// 変更関連処理
			//============================================================
			} elseif (isset($this->options->_POST->update)) {
				// 初期表示画面の「変更」ボタン押下
				
				$result = json_decode($this->initScn->do_disp_update_dialog());


			} elseif (isset($this->options->_POST->update_check)) {
			// 変更ダイアログの「確認」ボタン押下
				
				$result = json_decode($this->initScn->do_check_update());

			} elseif (isset($this->options->_POST->update_confirm)) {
				// 変更確認ダイアログの「確定」ボタン押下
				
				$result = json_decode($this->initScn->do_confirm_update());	

			} elseif (isset($this->options->_POST->update_back)) {
				// 変更確認ダイアログの「戻る」ボタン押下	

				$result = json_decode($this->initScn->do_back_update_dialog());


			//============================================================
			// 削除処理
			//============================================================
			} elseif (isset($this->options->_POST->delete)) {
				// 初期表示画面の「削除」ボタン押下				

				// Gitファイルの削除
				$result = json_decode($this->initScn->do_delete());


			//============================================================
			// 復元処理
			//============================================================
			} elseif (isset($this->options->_POST->restore)) {
				// バックアップ一覧画面の「復元ボタン押下				

				// Gitファイルの削除
				$result = json_decode($this->backupScn->do_restore_publish());


			//============================================================
			// 即時公開処理
			//============================================================
			} elseif (isset($this->options->_POST->immediate)) {
				// 初期表示画面の「即時公開」ボタン押下				

				$result = json_decode($this->initScn->do_disp_immediate_dialog());

			} elseif (isset($this->options->_POST->immediate_check)) {
				// 即時公開ダイアログの「確認」ボタン押下

				$result = json_decode($this->initScn->do_check_immediate());

			} elseif (isset($this->options->_POST->immediate_confirm)) {
				// 即時公開確認ダイアログの「確定」ボタン押下	
				
				$result = json_decode($this->initScn->do_immediate_publish());

				if ( !$result->status ) {
					// 処理失敗の場合、復元処理
					$error_message = $result->message . " ";
					$result = json_decode($this->initScn->do_restore_publish_failure($result->output_id));
				}

			} elseif (isset($this->options->_POST->immediate_back)) {
				// 即時公開確認ダイアログの「戻る」ボタン押下			

				$result = json_decode($this->initScn->do_back_immediate_dialog());
			
			//============================================================
			// ログ表示処理
			//============================================================
			} elseif (isset($this->options->_POST->log)) {
				// 初期表示画面の「新規」ボタン押下
				
				$result = json_decode($this->historyScn->do_disp_log_dialog());
			}


			if ( !$result->status ) {
				// 処理失敗の場合

				$error_message .=  $result->message;

			$this->common->debug_echo('　□error_message');
			$this->common->debug_echo($error_message);

				// エラーメッセージ表示
				$dialog_disp = '
				<script type="text/javascript">
					console.error(' . "'" . $error_message. "'" . ');
					alert(' . "'" . $error_message. "'" . ');
				</script>';

			} else {

				if ($result->dialog_disp) {
					$dialog_disp = $result->dialog_disp;	
				}
			}

			if (isset($this->options->_POST->history) || isset($this->options->_POST->log)) {
				// 初期表示画面の「履歴」ボタン押下

				$disp = $this->historyScn->disp_history_screen();

			} elseif (isset($this->options->_POST->backup)) {
				// 初期表示画面の「バックアップ一覧」ボタン押下

				$disp = $this->backupScn->disp_backup_screen();
				
			} else {
				// 初期表示画面の表示

				$disp = $this->initScn->do_disp_init_screen();

			}

			// 画面ロック用
			$disp_lock = '<div id="loader-bg"><div id="loading"></div></div>';

		} catch (\Exception $e) {

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
		$result = json_decode($this->common->get_workdir_real_path($this->options));


		// logファイルディレクトリが存在しない場合は作成
		if ( !$this->common->is_exists_mkdir($result->log_real_path) ) {
			$ret = false;
		}

		// backupディレクトリが存在しない場合は作成
		if ( !$this->common->is_exists_mkdir($result->backup_real_path) ) {
			$ret = false;
		}

		// waitingディレクトリが存在しない場合は作成
		if ( !$this->common->is_exists_mkdir($result->waiting_real_path) ) {
			$ret = false;
		}

		// runningディレクトリが存在しない場合は作成
		if ( !$this->common->is_exists_mkdir($result->running_real_path) ) {
			$ret = false;
		}

		// releasedディレクトリが存在しない場合は作成
		if ( !$this->common->is_exists_mkdir($result->released_real_path) ) {
			$ret = false;
		}

		$this->common->debug_echo('■ create_indigo_work_dir end');

		return $ret;
	}

}
