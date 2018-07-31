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


		//============================================================
		// 作業用ディレクトリのパス取得
		//============================================================
		// 作業用ディレクトリの絶対パスを取得
		$this->realpath_array = json_decode($this->common->get_realpath_workdir($this->options, $this->realpath_array));


		//============================================================
		// ログ出力用の日付ディレクトリ作成
		//============================================================
		// // 作業用ディレクトリの絶対パスを取得
		// $realpath_array = $this->realpath_array;
		
		// GMT現在日時を取得し、ディレクトリ名用にフォーマット変換
		$start_datetime = $this->common()->get_current_datetime_of_gmt();

		$log_dirname = $this->common()->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_YMD) . "_cron";

		// logの日付ディレクトリを作成
		$this->process_log = $this->fs()->normalize_path($this->fs()->get_realpath(
			$this->realpath_array->realpath_log)) . 'log_' . $log_dirname . '.log';
			
		$this->common()->debug_echo('　□ process_log：' . $this->process_log);
	}

	// /**
	//  * 
	//  */
 //    public function run(){
	
	// 	$this->common->debug_echo('■ [cron] run start');

	// 	// 処理実行結果格納
	// 	$result = json_decode(json_encode(
	// 				array('status' => true,
	// 				      'message' => '')
	// 			  ));

	// 	try {

	// 		$logstr = "\r\n";
	// 		$logstr .= "===============================================" . "\r\n";
	// 		$logstr .= "予約公開処理開始" . "\r\n";
	// 		$logstr .= "===============================================" . "\r\n";
	// 		$logstr .= "日時：" . $this->common()->get_current_datetime_of_gmt() . "\r\n";
	// 		$this->put_log($this->process_log, $logstr);

	// 		//============================================================
	// 		// データベース接続
	// 		//============================================================
	// 		$this->dbh = $this->pdoMgr->connect();


	// 		//============================================================
	// 		// 公開処理実施
	// 		//============================================================
	// 		$result = json_decode(json_encode($this->publish->exec_publish(define::PUBLISH_TYPE_RESERVE, null)));
	
	// 		if ( !$result->status ) {
	// 			// 処理失敗の場合、復元処理

	// 			$logstr = "** 予約公開処理失敗 **" . "\r\n";
	// 			$logstr .= $result->message . "\r\n";
	// 			$this->put_log($this->process_log, $logstr);

	// 			// $error_message = $result->message . " ";
	// 			$result = json_decode(json_encode($this->publish->exec_publish(define::PUBLISH_TYPE_AUTO_RESTORE, $result->output_id)));

	// 			// $result = $this->publish->exec_publish(define::PUBLISH_TYPE_AUTO_RESTORE, $output_id);

	// 			if ( !$result->status ) {
	// 				// 処理失敗の場合

	// 				$logstr = "** 復元処理失敗 **" . "\r\n";
	// 				$logstr .= $result->message . "\r\n";
	// 				$this->put_log($this->process_log, $logstr);
	// 			}
	// 		}

	// 	} catch (\Exception $e) {

	// 		// データベース接続を閉じる
	// 		$this->pdoMgr->close($this->dbh);
			
	// 		$logstr = "\r\n";
	// 		$logstr .= "===============================================" . "\r\n";
	// 		$logstr .= "予約公開処理異常終了（例外キャッチ）" . "\r\n";
	// 		$logstr .= "===============================================" . "\r\n";
	// 		$logstr .= "日時：" . $this->common()->get_current_datetime_of_gmt() . "\r\n";
	// 		$logstr .= $e.getMessage() . "\r\n";
	// 		$this->put_log($this->process_log, $logstr);

	// 		return;
	// 	}

	// 	// データベース接続を閉じる
	// 	$this->pdoMgr->close();

	// 	$logstr = "\r\n";
	// 	$logstr .= "===============================================" . "\r\n";
	// 	$logstr .= "予約公開処理終了" . "\r\n";
	// 	$logstr .= "===============================================" . "\r\n";
	// 	$logstr .= "日時：" . $this->common()->get_current_datetime_of_gmt() . "\r\n";
	// 	$this->put_log($this->process_log, $logstr);

	// 	$this->common->debug_echo('■ [cron] run end');

	// 	return;
 //    }

	// /**
	//  * `$fs` オブジェクトを取得する。
	//  *
	//  * `$fs`(class [tomk79\filesystem](tomk79.filesystem.html))のインスタンスを返します。
	//  *
	//  * @see https://github.com/tomk79/filesystem
	//  * @return object $fs オブジェクト
	//  */
	// public function fs(){
	// 	return $this->fs;
	// }

	// /**
	//  * `$common` オブジェクトを取得する。
	//  *
	//  * @return object $common オブジェクト
	//  */
	// public function common(){
	// 	return $this->common;
	// }

	// /**
	//  * response status code を取得する。
	//  *
	//  * `$px->set_status()` で登録した情報を取り出します。
	//  *
	//  * @return int ステータスコード (100〜599の間の数値)
	//  */
	// public function get_dbh(){
	// 	return $this->dbh;
	// }

	// *
	//  * response status code を取得する。
	//  *
	//  * `$px->set_status()` で登録した情報を取り出します。
	//  *
	//  * @return int ステータスコード (100〜599の間の数値)
	 
	// public function put_log($path, $text){
		
	// 	$datetime = $this->common()->get_current_datetime_of_gmt();

	// 	$str = $datetime . "     " . $text;

	// 	file_put_contents($path, $str, FILE_APPEND);
	// }


	// /**
	//  * response status code を取得する。
	//  *
	//  * `$px->set_status()` で登録した情報を取り出します。
	//  *
	//  * @return int ステータスコード (100〜599の間の数値)
	//  */
	// public function put_log_block($path, $text){
		
	// 	$str = "\r\n" . $text . "\r\n";

	// 	file_put_contents($path, $str, FILE_APPEND);
	// }
}
