<?php

namespace indigo;

class cron
{
	public $publish;

	private $pdo;

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
		// $this->file_control = new file_control($this);
		$this->pdo = new pdo($this);
		$this->publish = new publish($this);
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
			$this->dbh = $this->pdo->connect();

			// テーブル作成（存在している場合は処理しない）
			$this->pdo->create_table($this->dbh);

		$this->debug_echo('■ [cron] create_table_終了');

			// 即時公開処理
			$ret = json_decode($this->publish->jigen_release());

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
			$this->pdo->close($this->dbh);

			echo $e->getMessage();

			$this->debug_echo('■ [cron] run error end');

			return;
		}

		// データベース接続を閉じる
		$this->pdo->close();

		$this->debug_echo('■ [cron] run end');

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






// // require_once("./../.px_execute.php");
// require __DIR__ . '/pdo.php';

// debug_echo('------------------------');
// debug_echo('------------------------');
// debug_echo(__DIR__);
// debug_echo('Hello World!');

// // $arr = array( "tokyo"  => "東京",
// //             "osaka"  => "大阪",
// //             "nagoya" => "名古屋"
// //           );
// // $log = $arr;
// error_log(print_r($log, TRUE), 3, 'C:\workspace\sample-lib-indigo\vendor\pickles2\lib-indigo\php\output.log');


// connect();


// /**
//  * ※デバッグ関数（エラー調査用）
//  *	 
//  */
// function debug_echo($text) {

// 	echo strval($text);
// 	echo "\n";

// 	return;
// }

// /**
//  * ※デバッグ関数（エラー調査用）
//  *	 
//  */
// function debug_var_dump($text) {

// 	var_dump($text);
// 	echo "\n";

// 	return;
// }




