<?php

namespace indigo;

class common
{

	private $main;


	/**
	 * Constructor
	 *
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
	}

	/**
	 * GMTの現在時刻を取得
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	public function get_current_datetime_of_gmt() {

		// return gmdate(DATE_ATOM, time());
		return gmdate(define::DATETIME_FORMAT, time());
	}

	/**
	 * コマンド実行処理
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	public function command_execute($command, $captureStderr) {
	
		$this->debug_echo('■ execute start');

	    $output = array();
	    $return = 0;

	    // 標準出力とエラー出力を両方とも出力する
	    if ($captureStderr === true) {
	        $command .= ' 2>&1';
	    }

	    exec($command, $output, $return);

		$this->debug_echo('■ execute end');

	    return array('output' => $output, 'return' => $return);
	}


	/**
	 * 日付のフォーマット変換（※設定タイムゾーン用）
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	public function format_datetime($datetime, $format) {
	
		// $this->debug_echo('■ format_datetime start');

		$ret = '';

		if ($datetime) {
			$ret = date($format, strtotime($datetime));
		}
		
		// $this->debug_echo('　★変換前の時刻：' . $datetime);
		// $this->debug_echo('　★変換後の時刻：'. $ret);

		// $this->debug_echo('■ format_datetime end');

	    return $ret;
	}

	/**
	 * 日付のフォーマット変換（※GMT用）
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	public function format_gmt_datetime($datetime, $format) {
	
		$this->debug_echo('■ format_datetime start');

		$ret = '';

		if ($datetime) {
			$ret = gmdate($format, strtotime($datetime));
		}
		
		$this->debug_echo('　★変換前の時刻：' . $datetime);
		$this->debug_echo('　★変換後の時刻：'. $ret);

		$this->debug_echo('■ format_datetime end');

	    return $ret;
	}


	/**
	 * 引数日時を引数タイムゾーンの日時へ変換する（画面表示時の変換用）
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	public function convert_to_timezone_datetime($datetime) {
	
		// $this->debug_echo('■ convert_to_timezone_datetime start');

		$ret = '';

		if ($datetime) {

			$timezone = date_default_timezone_get();
			$t = new \DateTime($datetime, new \DateTimeZone('GMT'));
			$t->setTimeZone(new \DateTimeZone($timezone));
			// $ret = $t->format(DATE_ATOM);
			$ret = $t->format(define::DATETIME_FORMAT);
		}

		// $this->debug_echo('　□変換前の時刻（GMT）：' . $datetime);
		// $this->debug_echo('　□変換後の時刻：'. $ret);
		
		// $this->debug_echo('■ convert_to_timezone_datetime end');

	    return $ret;
	}
	
	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	public function debug_echo($text) {
	
		echo strval($text);
		echo "<br>";

		return;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	public function debug_var_dump($text) {
	
		var_dump($text);
		echo "<br>";

		return;
	}
}