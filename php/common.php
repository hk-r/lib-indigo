<?php

namespace indigo;

class common
{

	private $main;

	/**
	 * Constructor
	 *
	 * @param object $mainオブジェクト
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
	 * @return string gmdate
	 *			GMTの現在日時
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
	 * 			['output'] コマンド実行時の出力情報
	 * 			['return'] 実行結果（0:正常終了、0以外:異常終了）
	 */
	public function command_execute($command, $captureStderr) {
	
		$ret = array(
					'output' => array(),
					'return' => 0
			  	  );

		$this->put_process_log(__METHOD__, __LINE__, "■ command_execute start");
		$this->put_process_log(__METHOD__, __LINE__, "command --> " . $command);

	    // 標準出力とエラー出力を両方とも出力する
	    if ($captureStderr === true) {
	        $command .= ' 2>&1';
	    }

	    exec($command, $output, $return);
	    // exec('export LANG=ja_JP.UTF-8;' . $command, $output, $return);

		if ($return !== 0 ) {
			// 異常終了の場合

			$logstr = "**コマンド実行エラー**";
			// $logstr = implode("\r\n" , $output_str);
			$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			var_dump($output);
			foreach ( $output as $value ) {
				// 「*」の付いてるブランチを現在のブランチと判定
				echo $value;
			}

			echo implode(" " , $output);
			$message = 'Command error. ' . "\r\n" .
					   '<command>' . "\r\n" .
					   $command . "\r\n" .
					   '<message>' . "\r\n" .
					   implode(" " , $output);


			throw new \Exception($message);
		}

		$ret['output'] = $output;
		$ret['return'] = $return;

		$this->put_process_log(__METHOD__, __LINE__, "■ command_execute end");

	    return $ret;
	}


	/**
	 * 日付のフォーマット変換（※パラメタ設定タイムゾーン用）
	 *	 
	 * @param string $datetime = 日時
	 * @param $format = フォーマット形式
	 *	 
	 * @return 変換後の日付
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
	 * @param $datetime = 日時
	 * @param $format = フォーマット形式
	 *	 
	 * @return 変換後の日付
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
	 * @param $datetime = 日時
	 *	 
	 * @return 変換後の日時
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
	 * @param $publish_type = 公開種別のコード値
	 *	 
	 * @return 画面表示用のステータス情報
	 */
	public function convert_publish_type($publish_type) {

		$ret =  '';

		if ($publish_type == define::PUBLISH_TYPE_RESERVE) {
		
			$ret =  '予約';
		
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
	 * @param $method = クラス名::メソッド名
	 * @param $line = 行数
	 * @param $text = 出力文字列
	 *
	 * @return 通常ログ出力
	 */
	public function put_process_log($method, $line, $text){
		
		$datetime = $this->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		$str = "[" . $datetime . "]" . " " .
			   "[pid:" . getmypid() . "]" . " " .
			   "[userid:" . $this->main->options->user_id . "]" . " " .
			   "[" . $method . "]" . " " .
			   "[line:" . $line . "]" . " " .
			   $text . "\r\n";

		return error_log( $str, 3, $this->main->process_log_path );
	}

	/**
	 * エラーログを出力する。
	 *
	 * @param $text = 出力文字列
	 *
	 * @return エラーログ出力
	 */
	public function put_error_log($text){
		
		$datetime = $this->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		$str = "[" . $datetime . "]" . " " .
			   $text . "\r\n";

		return error_log( $str, 3, $this->main->error_log_path );
	}

	/**
	 * 区切り用のログを出力する。（日時などの詳細を出力しない）
	 *
	 * @param $text = 出力文字列
	 *
	 * @return 区切り用のログ出力
	 */
	public function put_process_log_block($text){
		
		$str = $text . "\r\n";

		return error_log( $str, 3, $this->main->process_log_path );
	}

	/**
	 * 公開確認用のログを出力する。
	 *
	 * @param $method = クラス名::メソッド名
	 * @param $line = 行数
	 * @param $text = 出力文字列
	 * @param $path = 出力先のパス
	 *
	 * @return 公開確認用ログ出力
	 */
	public function put_publish_log($method, $line, $text, $path){
		
		$datetime = $this->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		$str = "[" . $datetime . "]" . " " .
			   "[pid:" . getmypid() . "]" . " " .
			   "[userid:" . $this->main->options->user_id . "]" . " " .
			   "[" . $method . "]" . " " .
			   "[line:" . $line . "]" . " " .
			   $text . "\r\n";

		return error_log( $str, 3, $path );
	}
}