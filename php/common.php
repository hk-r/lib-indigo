<?php

namespace indigo;

/**
 * 共通クラス
 *
 * 各クラスで共通化できる処理をまとめたクラス。
 *
 */
class common
{

	private $main;

	/**
	 * Constructor
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
	}

	/**
	 * GMTの現在日時取得メソッド
	 *
	 * GMTの現在日時を引数で受け取ったフォーマットに変換して返却します
	 *
	 * @param  string $format フォーマット形式
	 *
	 * @return string gmdate  GMTの現在日時
	 */
	public function get_current_datetime_of_gmt($format) {

		return gmdate($format, time());
	}

	/**
	 * コマンド実行処理
	 *	 
	 * @param string $command 		実行コマンド文字列
	 * @param string $captureStderr true:標準出力とエラー出力を両方受け取る、false：標準出力のみ受け取る
	 *	 
	 * @return array 
	 * 			string ['output'] コマンド実行時の出力情報
	 * 			int    ['return'] 実行結果（0:正常終了、0以外:異常終了）
	 * 
	 * @throws Exception コマンド実行が異常終了した場合
	 */
	public function command_execute($command, $captureStderr) {
	
		$ret = array(
					'output' => array(),
					'return' => 0
			  	  );


		$this->main->common()->put_process_log_block('[command]');
		$this->main->common()->put_process_log_block($command);

	    // 標準出力とエラー出力を両方とも出力する
	    if ($captureStderr === true) {
	        $command .= ' 2>&1';
	    }

	    exec($command, $output, $return);

		if ($return !== 0 ) {
			// 異常終了の場合

			$logstr = "** コマンド実行エラーが発生しました。詳細はエラーログを確認してください。 **";
			$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			$msg = 'Command error. ' . "\r\n" .
					   '<command>' . "\r\n" . $command . "\r\n" .
					   '<message>' . "\r\n" . implode(" " , $output);

			throw new \Exception($msg);
		}

		$ret['output'] = $output;
		$ret['return'] = $return;

	    return $ret;
	}


	/**
	 * 日付のフォーマット変換（※パラメタ設定タイムゾーン用）
	 *	 
	 * @param string $datetime 日時
	 * @param string $format   フォーマット形式
	 *	 
	 * @return string $ret 変換後の日時
	 */
	public function format_datetime($datetime, $format) {
	
		$ret = '';

		if ($datetime) {
			$ret = date($format, strtotime($datetime));
		}
		
	    return $ret;
	}

	/**
	 * 引数日時のフォーマット変換（※GMT用）
	 *	 
	 * @param string $datetime 日時
	 * @param string $format   フォーマット形式
	 *	 
	 * @return string $ret 変換後の日時
	 */
	public function format_gmt_datetime($datetime, $format) {
	
		$ret = '';

		if ($datetime) {
			
			$t = new \DateTime($datetime, new \DateTimeZone('GMT'));
			$ret = $t->format($format);
		}
		
	    return $ret;
	}


	/**
	 * 引数日時を引数タイムゾーンの日時へ変換する（画面表示時の変換用）
	 *	 
	 * @param string $datetime 日時
	 *	 
	 * @return string $ret 変換後の日時
	 */
	public function convert_to_timezone_datetime($datetime) {
	
		$ret = '';

		if ($datetime) {

			$timezone = date_default_timezone_get();
			$t = new \DateTime($datetime, new \DateTimeZone('GMT'));
			$t->setTimeZone(new \DateTimeZone($timezone));
			$ret = $t->format(define::DATETIME_FORMAT);
		}

	    return $ret;
	}
	
	/**
	 * 公開種別を画面表示用に変換し返却する
	 *	 
	 * @param string $publish_type 公開種別のコード値
	 *	 
	 * @return string $ret コード変換後の公開種別
	 */
	public function convert_publish_type($publish_type) {

		$ret =  '';

		if ($publish_type == define::PUBLISH_TYPE_RESERVE) {
		
			$ret =  '予定';
		
		} else if ($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) {
			
			$ret =  '手動復元';

		} else if ($publish_type == define::PUBLISH_TYPE_IMMEDIATE) {
			
			$ret =  '即時';

 		} else if ($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE) {
			
			$ret =  '自動復元';

		}

		return $ret;
	}

	/**
	 * 通常ログを出力する。
	 *
	 * @param string $method クラス名::メソッド名
	 * @param string $line   行数
	 * @param string $text   出力文字列
	 *
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function put_process_log($method, $line, $text){
		
		$datetime = $this->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		$str = "[" . $datetime . "]" . " " .
			   "[pid:" . getmypid() . "]" . " " .
			   "[userid:" . $this->main->user_id . "]" . " " .
			   "[" . $method . "]" . " " .
			   "[line:" . $line . "]" . " " .
			   $text . "\r\n";

		return error_log( $str, 3, $this->main->process_log_path );
	}

	/**
	 * エラーログを出力する。
	 *
	 * @param string $text 出力文字列
	 *
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function put_error_log($text){
		
		$datetime = $this->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		$str = "[" . $datetime . "]" . " " .
			   $text . "\r\n";

		return error_log( $str, 3, $this->main->error_log_path );
	}

	/**
	 * ブロックログを出力する。（日時などの詳細を出力しない）
	 *
	 * @param string $text 出力文字列
	 *
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function put_process_log_block($text){
		
		$str = $text . "\r\n";

		return error_log( $str, 3, $this->main->process_log_path );
	}

	/**
	 * 公開確認用のログを出力する。
	 *
	 * @param string $method クラス名::メソッド名
	 * @param string $line 行数
	 * @param string $text 出力文字列
	 * @param string $path 出力先のパス
	 *
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function put_publish_log($method, $line, $text, $path){
		
		$datetime = $this->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		$str = "[" . $datetime . "]" . " " .
			   "[pid:" . getmypid() . "]" . " " .
			   "[userid:" . $this->main->user_id . "]" . " " .
			   "[" . $method . "]" . " " .
			   "[line:" . $line . "]" . " " .
			   $text . "\r\n";

		return error_log( $str, 3, $path );
	}

	/**
	 * ステータスを画面表示用に変換し返却する
	 *	 
	 * @param string $status ステータスのコード値
	 *	 
	 * @return string $ret コード変換後のステータス
	 */
	public function convert_status($status) {

		$ret = '';

		if ($status == define::PUBLISH_STATUS_RUNNING) {
			$ret =  '★(処理中)';
		} else if ($status == define::PUBLISH_STATUS_SUCCESS) {
			$ret =  '〇(成功)';
		} else if ($status == define::PUBLISH_STATUS_ALERT) {
			$ret =  '△(警告あり)';
		} else if ($status == define::PUBLISH_STATUS_FAILED) {
			$ret =  '×(失敗)';
		} else if ($status == define::PUBLISH_STATUS_SKIP) {
			$ret =  '-(スキップ)';
		}

		return $ret;
	}


	/**
	 * 予定ディレクトリを命名し返却する
	 *	 
	 * @param string $status ステータスのコード値
	 *	 
	 * @return string $ret コード変換後のステータス
	 */
	public function get_reserve_dirname($datetime) {

		$ret = '';

		$conv_reserve_datetime = $this->main->common()->format_gmt_datetime($datetime, define::DATETIME_FORMAT_SAVE);

		if (!$conv_reserve_datetime) {
			throw new \Exception('Dirname create failed.');
		} else {
			$ret = $conv_reserve_datetime . define::DIR_NAME_RESERVE;
		}

		return $ret;
	}
}