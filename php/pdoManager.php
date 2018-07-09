<?php

namespace indigo;

class pdoManager
{

	private $main;

	private $fileManager;
	
	// DBディレクトリパス
	const SQLITE_DB_PATH = '/sqlite/';
	// DBディレクトリパス
	const SQLITE_DB_NAME = 'indigo.db';


	/**
	 * 公開処理結果テーブルのカラム定義
	 */
	const TS_OUTPUT_RESULT_ID = 'result_id_seq';		// 公開処理結果ID
	const TS_OUTPUT_RESERVE_ID = 'reserve_id';			// 公開予約ID
	const TS_OUTPUT_BACKUP_ID = 'backup_id';			// バックアップID
	const TS_OUTPUT_RESERVE = 'reserve_datetime';		// 公開予約日時
	const TS_OUTPUT_BRANCH = 'branch_name';		// ブランチ名
	const TS_OUTPUT_COMMIT = 'commit_hash';		// コミットハッシュ値（短縮）
	const TS_OUTPUT_COMMENT = 'comment';		// コメント
	const TS_OUTPUT_PUBLISH_TYPE = 'publish_type';	// 公開種別
	const TS_OUTPUT_STATUS = 'status';				// 状態
	const TS_OUTPUT_DIFF_FLG1 = 'change_check_flg';
	const TS_OUTPUT_DIFF_FLG2 = 'publish_honban_diff_flg';
	const TS_OUTPUT_DIFF_FLG3 = 'publish_pre_diff_flg';
	const TS_OUTPUT_START = 'start_datetime';		// 公開処理開始日時
	const TS_OUTPUT_END = 'end_datetime';			// 公開処理終了日時
	const TS_OUTPUT_DELETE_FLG = 'gen_delete_flg';		// 世代削除フラグ
	const TS_OUTPUT_DELETE = 'gen_delete_datetime';		// 世代削除日時
	const TS_OUTPUT_INSERT_DATETIME = 'insert_datetime';	// 登録日時
	const TS_OUTPUT_INSERT_USER_ID = 'insert_user_id';		// 登録ユーザID
	const TS_OUTPUT_UPDATE_DATETIME = 'update_datetime';	// 更新日時
	const TS_OUTPUT_UPDATE_USER_ID = 'update_user_id';		// 更新ユーザID

	/**
	 * 公開種別
	 */
	// 予約公開
	const PUBLISH_TYPE_RESERVE = 1;
	
	// 削除済み
	const DELETE_FLG_ON = 1;
	// 未削除
	const DELETE_FLG_OFF = 0;


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
	 * Constructor
	 *
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
		$this->fileManager = new fileManager($this);


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

		$dsn;
		$db_user;
		$db_pass;
		$option;

		$db_type = $this->main->options->db_type;

		$this->debug_echo('　□ db_type');
		$this->debug_echo($db_type);

		if ($db_type && $db_type == 'mysql') {

			$this->debug_echo('　□ mysql');

			/**
			 * mysqlの場合
			 */
			$db_name = $this->main->options->mysql_db_name;		// データベース名
			$db_host = $this->main->options->mysql_db_host;		// ホスト名

			$dsn = "mysql:dbname=" . $db_name . ";host=" . $db_host. ";charset=utf8";

			$db_user = $this->main->options->mysql_db_user;
			$db_pass = $this->main->options->mysql_db_pass;

			$option = array(
						\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.SELF::UTF
					);

	
		} else {

			$this->debug_echo('　□ sqlite');

			$this->debug_echo($this->main->options->indigo_workdir_path . self::SQLITE_DB_PATH);

			/**
			 * sqliteの場合 
			 */
			// dbディレクトリの絶対パス
			$db_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->indigo_workdir_path . self::SQLITE_DB_PATH));

			$this->debug_echo('　□ db_real_path：' . $db_real_path);

			// DBディレクトリが存在しない場合は作成
			if ( !$this->fileManager->is_exists_mkdir($db_real_path) ) {

					// エラー処理
					throw new \Exception('Creation of sqlite directory failed.');
			}

			$dsn = "sqlite:" . $db_real_path . self::SQLITE_DB_NAME;

			$db_user = null;
			$db_pass = null;

			$option = array(
						\PDO::ATTR_PERSISTENT => false, // ←これをtrueにすると、"持続的な接続" になる
						\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,	// エラー表示の設定
						\PDO::ATTR_EMULATE_PREPARES => false　	// prepareを利用する
					);
		}
			
		try {

	  		$dbh = new \PDO(
	  			$dsn,
	  			$db_user,
	  			$db_pass,
	  			$option
	  		);

		} catch (\PDOException $e) {
	  		echo 'Connection failed: ' . $e->getMessage();
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


		} catch (\PDOException $e) {
	  		echo 'Connection failed: ' . $e->getMessage();
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

		//============================================================
		// 公開予約テーブル作成
		//============================================================
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

		// SQL実行
		$stmt = $dbh->query($create_sql);

		if (!$stmt) {
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		//============================================================
		// 公開処理結果テーブル作成
		//============================================================
		$create_sql = 'CREATE TABLE IF NOT EXISTS TS_OUTPUT ('
			. self::TS_OUTPUT_RESULT_ID . ' INTEGER PRIMARY KEY AUTOINCREMENT,
			' . self::TS_OUTPUT_RESERVE_ID . ' INTEGER,
			' . self::TS_OUTPUT_BACKUP_ID . ' INTEGER,
			' . self::TS_OUTPUT_RESERVE . ' TEXT,
			' . self::TS_OUTPUT_BRANCH . ' TEXT,
			' . self::TS_OUTPUT_COMMIT . ' TEXT,
			' . self::TS_OUTPUT_COMMENT . ' TEXT,
			' . self::TS_OUTPUT_PUBLISH_TYPE . ' TEXT,
			' . self::TS_OUTPUT_STATUS . ' TEXT,
			' . self::TS_OUTPUT_DIFF_FLG1 . ' TEXT,
			' . self::TS_OUTPUT_DIFF_FLG2 . ' TEXT,
			' . self::TS_OUTPUT_DIFF_FLG3 . ' TEXT,
			' . self::TS_OUTPUT_START . ' TEXT,
			' . self::TS_OUTPUT_END . ' TEXT,
			' . self::TS_OUTPUT_DELETE_FLG . ' TEXT,
			' . self::TS_OUTPUT_DELETE . ' TEXT,
			' . self::TS_OUTPUT_INSERT_DATETIME . ' TEXT,
			' . self::TS_OUTPUT_INSERT_USER_ID . ' TEXT,
			' . self::TS_OUTPUT_UPDATE_DATETIME . ' TEXT,
			' . self::TS_OUTPUT_UPDATE_USER_ID . ' TEXT
		)';

		// SQL実行
		$stmt = $dbh->query($create_sql);

		if (!$stmt) {
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		//============================================================
		// バックアップテーブル作成
		//============================================================
		$create_sql = 'CREATE TABLE IF NOT EXISTS TS_BACKUP (
			backup_id_seq INTEGER PRIMARY KEY AUTOINCREMENT,
			result_id INTEGER,
			backup_datetime TEXT,
			gen_delete_flg TEXT,
			gen_delete_datetime TEXT,
			insert_datetime TEXT,
			insert_user_id TEXT,
			update_datetime TEXT,
			update_user_id TEXT
		)';

		// SQL実行
		$stmt = $dbh->query($create_sql);

		if (!$stmt) {
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

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

		// 前処理
		$stmt = $dbh->prepare($sql);

		// 実行
		$stmt->execute($params);

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