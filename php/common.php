<?php

namespace indigo;

class common
{

	private $main;
	// private $fs;

	const DIR_PERMISSION_0757 = 0757;

	/**
	 * Constructor
	 *
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;

		// $this->fs = new \tomk79\filesystem(array(
		//   'file_default_permission' => define::FILE_DEFAULT_PERMISSION,
		//   'dir_default_pefrmission' => define::DIR_DEFAULT_PERMISSION,
		//   'filesystem_encoding' => define::FILESYSTEM_ENCODING
		// ));


	}

	/**
	 * GMTの現在時刻を取得
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	public function get_current_datetime_of_gmt($format) {

		// return gmdate(DATE_ATOM, time());
		return gmdate($format, time());
		
	}

	/**
	 * コマンド実行処理
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	public function command_execute($command, $captureStderr) {
	
		// $this->put_process_log(__METHOD__, __LINE__, '■ execute start');

	    $output = array();
	    $return = 0;

	    // 標準出力とエラー出力を両方とも出力する
	    if ($captureStderr === true) {
	        $command .= ' 2>&1';
	    }

	    exec($command, $output, $return);

		// $this->put_process_log(__METHOD__, __LINE__, '■ execute end');

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
	
		// $this->put_process_log(__METHOD__, __LINE__, '■ format_datetime start');

		$ret = '';

		if ($datetime) {
			$ret = date($format, strtotime($datetime));
		}
		
		// $this->put_process_log(__METHOD__, __LINE__, '　★変換前の時刻：' . $datetime);
		// $this->put_process_log(__METHOD__, __LINE__, '　★変換後の時刻：'. $ret);

		// $this->put_process_log(__METHOD__, __LINE__, '■ format_datetime end');

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
	
		// $this->put_process_log(__METHOD__, __LINE__, '■ format_gmt_datetime start');

		$ret = '';

		if ($datetime) {
			
			$t = new \DateTime($datetime, new \DateTimeZone('GMT'));

			$ret = $t->format($format);
		}
		
		// $this->put_process_log(__METHOD__, __LINE__, '■ format_gmt_datetime end');

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
	
		// $this->put_process_log(__METHOD__, __LINE__, '■ convert_to_timezone_datetime start');

		$ret = '';

		if ($datetime) {

			$timezone = date_default_timezone_get();
			$t = new \DateTime($datetime, new \DateTimeZone('GMT'));
			$t->setTimeZone(new \DateTimeZone($timezone));
			// $ret = $t->format(DATE_ATOM);
			$ret = $t->format(define::DATETIME_FORMAT);
		}

		// $this->put_process_log(__METHOD__, __LINE__, '　□変換前の時刻（GMT）：' . $datetime);
		// $this->put_process_log(__METHOD__, __LINE__, '　□変換後の時刻：'. $ret);
		
		// $this->put_process_log(__METHOD__, __LINE__, '■ convert_to_timezone_datetime end');

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
		
			$ret =  '予約公開';
		
		} else if ($publish_type == define::PUBLISH_TYPE_MANUAL_RESTORE) {
			
			$ret =  '手動復元公開';

		} else if ($publish_type == define::PUBLISH_TYPE_IMMEDIATE) {
			
			$ret =  '即時公開';

 		} else if ($publish_type == define::PUBLISH_TYPE_AUTO_RESTORE) {
			
			$ret =  '自動復元公開';

		}

		return $ret;
	}


	/**
	 * ディレクトリが存在しない場合はディレクトリを作成する
	 *	 
	 * @param $dirpath = ディレクトリパス
	 *	 
	 * @return true:成功、false：失敗
	 */
	public function is_exists_mkdir($dirpath) {

		// $this->put_process_log(__METHOD__, __LINE__, '■ is_exists_mkdir start');

		$ret = true;

		if ($dirpath) {
			if ( !file_exists($dirpath) ) {
				// ディレクトリ作成
				if ( !mkdir($dirpath, self::DIR_PERMISSION_0757)) {
					$ret = false;
				}
			}
		} else {
			$ret = false;
		}

		// $this->put_process_log(__METHOD__, __LINE__, '　□ return：' . $ret);
		// $this->put_process_log(__METHOD__, __LINE__, '■ is_exists_mkdir end');

		return $ret;
	}

	/**
	 * ディレクトリの存在有無にかかわらず、ディレクトリを再作成する（存在しているものは削除する）
	 *	 
	 * @param $dirpath = ディレクトリパス
	 *	 
	 * @return true:成功、false：失敗
	 */
	public function is_exists_remkdir($dirpath) {
		
		$this->put_process_log(__METHOD__, __LINE__, '■ is_exists_remkdir start');

		if ( file_exists($dirpath) ) {
			// 削除
			$command = 'rm -rf --preserve-root '. $dirpath;
			$ret = $this->command_execute($command, true);

			if ( $ret['return'] !== 0 ) {
				return false;
			}
		}

		// デプロイ先のディレクトリを作成
		if ( !file_exists($dirpath)) {
			if ( !mkdir($dirpath, self::DIR_PERMISSION_0757) ) {
				return false;
			}
		} else {
			return false;
		}
	
		$this->put_process_log(__METHOD__, __LINE__, '■ is_exists_remkdir end');

		return true;
	}



	/**
	 * 本番サーバの絶対パス取得（複数サーバ対応（※作成中））
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	public function get_server_real_path($options) {
	
		$this->put_process_log(__METHOD__, __LINE__, '■ get_server_real_path start');


		$server_list = $options->server;

		$server_real_path = array();

		foreach ( (array)$server_list as $server ) {
			
			// 本番環境ディレクトリの絶対パスを取得。
			$server_real_path[] = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($server . "/"));
		}

		$this->put_process_log(__METHOD__, __LINE__, '　□ server_real_path');
		$this->debug_var_dump($server_real_path);

		$this->put_process_log(__METHOD__, __LINE__, '■ get_server_real_path end');

	    return json_encode($server_real_path);
	}


	/**
	 * 作業用ディレクトリの絶対パス取得
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	public function get_realpath_workdir($options, $realpath_array) {
	
		$logstr = "get_realpath_workdir() start";
		$this->put_process_log(__METHOD__, __LINE__, $logstr);

		// 本番環境ディレクトリの絶対パスを取得。
		$realpath_array['realpath_server'] = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($options->server_real_path . "/"));

		// backupディレクトリの絶対パスを取得。
		$realpath_array['realpath_backup'] = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($options->workdir_relativepath . define::PATH_BACKUP));

		// waitingディレクトリの絶対パスを取得。
		$realpath_array['realpath_waiting'] = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($options->workdir_relativepath . define::PATH_WAITING));

		// runningディレクトリの絶対パスを取得。
		$realpath_array['realpath_running'] = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($options->workdir_relativepath . define::PATH_RUNNING));

		// releasedディレクトリの絶対パスを取得。
		$realpath_array['realpath_released'] = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($options->workdir_relativepath . define::PATH_RELEASED));

		// logディレクトリの絶対パスを取得。
		$realpath_array['realpath_log'] = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($options->workdir_relativepath . define::PATH_LOG));

		$logstr = "get_realpath_workdir() end";
		$this->put_process_log(__METHOD__, __LINE__, $logstr);

	    return json_encode($realpath_array);
	}


	/**
	 * response status code を取得する。
	 *
	 * `$px->set_status()` で登録した情報を取り出します。
	 *
	 * @return int ステータスコード (100〜599の間の数値)
	 */
	public function put_process_log($method, $line, $text){
		
		$datetime = $this->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		$str = "[" . $datetime . "]" . " " .
			   "[pid:" . getmypid() . "]" . " " .
			   "[userid:" . $this->main->options->user_id . "]" . " " .
			   "[" . $method . "]" . " " .
			   "[line:" . $line . "]" . " " .
			   $text . "\r\n";

		// file_put_contents($path, $str, FILE_APPEND);

		return error_log( $str, 3, $this->main->process_log_path );
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