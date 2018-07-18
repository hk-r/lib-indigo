<?php

namespace indigo;

class common
{

	private $main;
	private $fileManager;

	const DIR_PERMISSION_0757 = 0757;


	/**
	 * Constructor
	 *
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
		$this->fileManager = new fileManager($this);
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
	
		// $this->debug_echo('■ execute start');

	    $output = array();
	    $return = 0;

	    // 標準出力とエラー出力を両方とも出力する
	    if ($captureStderr === true) {
	        $command .= ' 2>&1';
	    }

	    exec($command, $output, $return);

		// $this->debug_echo('■ execute end');

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
	
		// $this->debug_echo('■ format_gmt_datetime start');

		$ret = '';

		if ($datetime) {
			
			$t = new \DateTime($datetime, new \DateTimeZone('GMT'));

			$ret = $t->format($format);
		}
		
		// $this->debug_echo('　★変換前の時刻：' . $datetime);
		// $this->debug_echo('　★変換後の時刻：'. $ret);

		// $this->debug_echo('■ format_gmt_datetime end');

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
	 * 公開種別を画面表示用に変換し返却する
	 *	 
	 * @param $publish_type = 公開種別のコード値
	 *	 
	 * @return 画面表示用のステータス情報
	 */
	public function convert_publish_type($publish_type) {

		$ret = '';

		if ($publish_type == define::PUBLISH_TYPE_RESERVE) {
		
			$ret =  '予約公開';
		
		} else if ($publish_type == define::PUBLISH_TYPE_RESTORE) {
			
			$ret =  '復元公開';

		} else if ($publish_type == define::PUBLISH_TYPE_IMMEDIATE) {
			
			$ret =  '即時公開';

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

		// $this->debug_echo('■ is_exists_mkdir start');

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

		// $this->debug_echo('　□ return：' . $ret);
		// $this->debug_echo('■ is_exists_mkdir end');

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
		
		$this->debug_echo('■ is_exists_remkdir start');

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
	
		$this->debug_echo('■ is_exists_remkdir end');

		return true;
	}


	/**
	 * 作業用ディレクトリの絶対パス取得
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	public function get_workdir_real_path($options) {
	
		$this->debug_echo('■ get_indigo_work_dir start');

		$result = array('project_real_path' => '',
						'backup_real_path' => '',
						'waiting_real_path' => '',
						'running_real_path' => '',
						'released_real_path' => '',
						'log_real_path' => '');

			// 本番環境ディレクトリの絶対パスを取得。
			$result['project_real_path'] = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->project_real_path . "/"));

			// backupディレクトリの絶対パスを取得。
			$result['backup_real_path'] = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->indigo_workdir_path . define::PATH_BACKUP));

			// waitingディレクトリの絶対パスを取得。
			$result['waiting_real_path'] = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->indigo_workdir_path . define::PATH_WAITING));

			// runningディレクトリの絶対パスを取得。
			$result['running_real_path'] = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->indigo_workdir_path . define::PATH_RUNNING));

			// logディレクトリの絶対パスを取得。
			$result['log_real_path'] = $this->fileManager->normalize_path($this->fileManager->get_realpath($options->indigo_workdir_path . define::PATH_LOG));

		$this->debug_echo('■ get_indigo_work_dir end');

	    return json_encode($result);
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