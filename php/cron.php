<?php

namespace indigo;

class cron
{
	public $publish;

	private $pdoManager;

	// 開発環境
	const DEVELOP_ENV = '1';
	
	/**
	 * 削除フラグ
	 */
	const DELETE_FLG_ON = 1;	// 削除済み
	const DELETE_FLG_OFF = 0;	// 未削除
	
	/**
	 * 公開種別
	 */
	// 予約公開
	const PUBLISH_TYPE_RESERVE = 1;

	/**
	 * 公開ステータス
	 */
	// 処理中
	const PUBLISH_STATUS_RUNNING = 0;
	// 成功
	const PUBLISH_STATUS_SUCCESS = 1;
	// 成功（警告あり）
	const PUBLISH_STATUS_ALERT = 2;
	// 失敗
	const PUBLISH_STATUS_FAILED = 3;
	// スキップ
	const PUBLISH_STATUS_SKIP = 4;
	
	/**
	 * PDOインスタンス
	 */
	private $dbh;

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {

		$this->options = json_decode(json_encode($options));
		$this->fileManager = new fileManager($this);
		$this->pdoManager = new pdoManager($this);
		$this->publish = new Publish($this);
	}

	/**
	 * 
	 */
    public function run(){

	
		$this->debug_echo('■ [cron] run start');

		// $this->debug_echo('　□カレントパス：' . realpath('.'));
		// $this->debug_echo('　□__DIR__：' . __DIR__);

		// $path = self::PATH_CREATE_DIR . self::PATH_WAITING;
		// $this->debug_echo('　□相対パス' . $path);
		// $real_path = $this->file_control->normalize_path($this->file_control->get_realpath($path));

		// $this->debug_echo('　□絶対パス' . $real_path);

		// 画面表示
		$disp = '';  

		// エラーダイアログ表示
		$alert_message = '';

		// ダイアログの表示
		$dialog_disp = '';
		
		// 画面ロック用
		$disp_lock = '';

		// 処理実行結果格納
		$ret = '';

		// 入力画面へ表示させるエラーメッセージ
		// $error_message = '';

		//timezoneテスト ここから
		date_default_timezone_set('Asia/Tokyo');

		echo "--------------------------------</br>";
	
		$this->debug_echo('　□GMTの現在時刻：');
		$this->debug_echo(gmdate(DATE_ATOM, time()));

		$this->debug_echo('　□Asiaの現在時刻：');
		$this->debug_echo(date(DATE_ATOM, time()));


		$t = new \DateTime(gmdate(DATE_ATOM, time()));
		$t->setTimeZone(new \DateTimeZone('Asia/Tokyo'));
		$this->debug_echo('　□GMTから変換したAsiaの現在時刻：');
		$this->debug_echo($t->format(DATE_ATOM));


		$t = new \DateTime($t->format(DATE_ATOM));
		$t->setTimeZone(new \DateTimeZone('GMT'));

		$this->debug_echo('　□日本時間から変換したGMTの現在時刻：');
		$this->debug_echo($t->format(DATE_ATOM));

		// タイムゾーンが取得できる！！！！
		echo "タイムゾーン取得 ：" . date("e", date(DATE_ATOM, time())). "</br>";
		
		echo "--------------------------------</br>";
		//timezoneテスト ここまで

		try {

			// データベース接続
			$this->dbh = $this->pdoManager->connect();

			// テーブル作成（存在している場合は処理しない）
			$this->pdoManager->create_table($this->dbh);

		$this->debug_echo('■ [cron] create_table_終了');

			// 即時公開処理
			$ret = json_decode($this->publish->immediate_release());

			if ( !$ret->status ) {

				$alert_message = 'jigen_release faild';
			}

			if ( !$ret->status ) {
				// 処理失敗の場合

				// エラーメッセージ表示
				$dialog_disp = '
				<script type="text/javascript">
					console.error("' . $ret->message . '");
					alert("' . $alert_message .'");
				</script>';
				
			}
	
		} catch (\Exception $e) {

			// データベース接続を閉じる
			$this->pdoManager->close($this->dbh);

			echo $e->getMessage();

			$this->debug_echo('■ [cron] run error end');

			return;
		}

		// データベース接続を閉じる
		$this->pdo->close();

		$this->debug_echo('■ [cron] run end');

    }

	/**
	 * ディレクトリの存在有無にかかわらず、ディレクトリを再作成する（存在しているものは削除する）
	 *	 
	 * @param $dirpath = ディレクトリパス
	 *	 
	 * @return true:成功、false：失敗
	 */
	private function is_exists_remkdir($dirpath) {
		
		$this->debug_echo('■ is_exists_remkdir start');
		$this->debug_echo('　■ $dirpath：' . $dirpath);

		if ( file_exists($dirpath) ) {
			$this->debug_echo('　■ $dirpath2：' . $dirpath);

			// 削除
			$command = 'rm -rf --preserve-root '. $dirpath;
			$ret = $this->command_execute($command, true);

			if ( $ret['return'] !== 0 ) {
				$this->debug_echo('[既存ディレクトリ削除失敗]');
				return false;
			}
		}

		// デプロイ先のディレクトリを作成
		if ( !file_exists($dirpath)) {
			if ( !mkdir($dirpath, self::DIR_PERMISSION_0757) ) {
				$this->debug_echo('　□ [再作成失敗]$dirpath：' . $dirpath);
				return false;
			}
		} else {
			$this->debug_echo('　□ [既存ディレクトリが残っている]$dirpath：' . $dirpath);
			return false;
		}
	
		$this->debug_echo('■ is_exists_remkdir end');

		return true;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_echo($text) {

		echo strval($text);
		echo "</br>";

		return;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_var_dump($text) {

		var_dump($text);
		echo "</br>";

		return;
	}

}
