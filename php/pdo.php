<?php

namespace indigo;

class pdo
{

	private $main;

	/**
	 * Constructor
	 *
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;

		// // DELETE文作成
		// $delete_sql = "DELETE FROM list WHERE id = :id";
		// // パラメータ作成
		// $params = array(
		// 	':id' => '1'
		// );
		// // DELETE実行
		// $stmt = $this->select($delete_sql, $params);

		// // デバック用（直前の操作件数取得）
		// // $count = $stmt->rowCount();
	}


	/**
	 * データベースへ接続する
	 *	 
	 */
	public function connect() {
	
		$this->debug_echo('■ connect start');

		$dbh = null; // 初期化

		/**
		 * mysqlの場合（一旦コメント。後々パラメタで接続種類を判別し、切り替えられるようにする。）
		 */
		// $db_name = 'db_name';		// データベース名
		// $db_host = '.localhost';		// ホスト名

		// $dsn = "mysql:dbname=" . $db_name . ";host=" . $db_host. ";charset=utf8";

		// $db_user = self::USER;
		// $db_pass = self::PASS;

		// $option = array(
		// 			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.SELF::UTF
		// 		);

	
		/**
		 * sqliteの場合 
		 */
		$db_name = 'test.db';	// データベース名
		$db_path = './sqlite/';	// データベースパス

		$dsn = "sqlite:" . $db_path . $db_name;

		$db_user = null;
		$db_pass = null;
	
		$option = array(
					\PDO::ATTR_PERSISTENT => false, // ←これをtrueにすると、"持続的な接続" になる
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,	// エラー表示の設定
					\PDO::ATTR_EMULATE_PREPARES => false　	// prepareを利用する
				);

		$pdo = null;

		try {

	  		$dbh = new \PDO(
	  			$dsn,
	  			$db_user,
	  			$db_pass,
	  			$option
	  		);

		} catch (\Exception $e) {
	  		echo 'データベースにアクセスできません。' . $e->getMesseage;
	  		// // 強制終了
	  		// die();
		}
			
		$this->debug_echo('■ connect end');

		return $dbh;

	}

	/**
	 * データベースの接続を閉じる
	 *	 
	 */
	public function close($dbh) {
	
		$this->debug_echo('■ close start');

		try {

			// データベースの接続を閉じる
			$dbh = null;


		} catch (\Exception $e) {
	  		echo 'データベースの接続が閉じれません。' . $e->getMesseage;
	  		// // 強制終了
	  		// die();
		}
		
		$this->debug_echo('■ close end');

	}


	/**
	 * CREATE処理関数
	 *	 
	 */
	public function create_table($dbh) {

		$this->debug_echo('■ create_table start');

		// // データベース接続
		// $this->connect();

		// 公開予約テーブル作成
		$create_sql = 'CREATE TABLE IF NOT EXISTS TS_RESERVE (
			reserve_id_seq INTEGER PRIMARY KEY AUTOINCREMENT,
			reserve_datetime TEXT,
			branch_name TEXT,
			commit_hash TEXT,
			comment TEXT,
			delete_flg TEXT,
			insert_datetime TEXT,
			insert_user_id TEXT,
			update_datetime TEXT,
			update_user_id TEXT
		)';

		// 実行
		$stmt = $dbh->query($create_sql);

		if (!$stmt) {

			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		// 公開処理結果テーブル作成
		$create_sql = 'CREATE TABLE IF NOT EXISTS TS_RESULT (
			result_id_seq INTEGER PRIMARY KEY AUTOINCREMENT,
			reserve_id INTEGER,
			reserve_datetime TEXT,
			branch_name TEXT,
			commit_hash TEXT,
			comment TEXT,
			publish_type TEXT,
			status TEXT,
			change_check_flg TEXT,
			publish_honban_diff_flg TEXT,
			publish_pre_diff_flg TEXT,
			start_datetime TEXT,
			end_datetime TEXT,
			batch_user_id TEXT,
			gen_delete_flg TEXT,
			gen_delete_datetime TEXT,
			insert_datetime TEXT
		)';

		// SQL実行
		$stmt = $dbh->query($create_sql);

		if (!$stmt) {
			
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		// // データベースの接続を閉じる
		// $this->dbh = null;


		$this->debug_echo('■ create_table end');

		return;
	}


	/**
	 * SELECT処理関数
	 *	 
	 * @param $sql = SQL文
	 *	 
	 * @return 取得データ配列
	 */
	public function select($dbh, $sql) {

		$this->debug_echo('■ select start');

		$ret_array = array();
		$stmt = null;

		// $this->debug_echo('　□sql：');
		// $this->debug_var_dump($sql);

		// 実行
		if ($stmt = $dbh->query($sql)) {

			// 取得したデータを配列に格納して返す
			while ($row = $stmt->fetch(\PDO::FETCH_BOTH)) {
				$ret_array[] = $row;
			}
		}

		if (!$stmt) {
			
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}
		
		// $this->debug_echo('　□返却リストデータ：');
		// $this->debug_var_dump($ret_array);

		$this->debug_echo('■ select end');

		return $ret_array;
	}


	/**
	 * INSERT、UPDATE、DELETE処理関数
	 *	 
	 * @param $sql    = SQL文
	 * @param $params = パラメータ
	 *	 
	 * @return 画面表示用のステータス情報
	 */
	public function execute ($dbh, $sql, $params) {

		$this->debug_echo('■ execute start');

		$this->debug_echo('　□sql：');
		$this->debug_var_dump($sql);
		
		$this->debug_echo('　□$dbh：');
		$this->debug_var_dump($dbh);

		// 前処理
		$stmt = $dbh->prepare($sql);
		
		$this->debug_echo('　□1：');
		// 実行
		$stmt->execute($params);
		
		$this->debug_echo('　□2：');
		if (!$stmt) {
			
			$this->debug_echo('　□ execute error');

			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		$this->debug_echo('■ execute end');

		return $stmt;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_echo($text) {
	
		echo strval($text);
		echo "<br>";

		return;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_var_dump($text) {
	
		var_dump($text);
		echo "<br>";

		return;
	}

}