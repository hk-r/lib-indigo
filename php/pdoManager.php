<?php

namespace indigo;

class pdo
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
						PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.SELF::UTF
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
	 * 公開処理結果一覧テーブルの登録処理
	 *
	 * @return なし
	 */
	public function insert_ts_output($start_datetime, $options) {

		$this->debug_echo('■ insert_ts_output start');

		$result = array('status' => true,
						'message' => '');

		try {

		$this->debug_echo('　□1');

			// INSERT文作成
			$insert_sql = "INSERT INTO TS_OUTPUT ("
			. self::TS_OUTPUT_RESERVE_ID . ",
			" .	self::TS_OUTPUT_BACKUP_ID . ",
			" .	self::TS_OUTPUT_RESERVE . ",
			" .	self::TS_OUTPUT_BRANCH . ",
			" .	self::TS_OUTPUT_COMMIT . ",
			" .	self::TS_OUTPUT_COMMENT . ",
			" .	self::TS_OUTPUT_PUBLISH_TYPE . ",
			" .	self::TS_OUTPUT_STATUS . ",
			" .	self::TS_OUTPUT_DIFF_FLG1 . ",
			" .	self::TS_OUTPUT_DIFF_FLG2 . ",
			" .	self::TS_OUTPUT_DIFF_FLG3 . ",
			" .	self::TS_OUTPUT_START . ",
			" .	self::TS_OUTPUT_END . ",
			" .	self::TS_OUTPUT_DELETE_FLG . ",
			" .	self::TS_OUTPUT_DELETE . ",
			" .	self::TS_OUTPUT_INSERT_DATETIME . ",
			" .	self::TS_OUTPUT_INSERT_USER_ID . ",
			" .	self::TS_OUTPUT_UPDATE_DATETIME . ",
			" .	self::TS_OUTPUT_UPDATE_USER_ID

			. ") VALUES (

			 :" . self::TS_OUTPUT_RESULT_ID . ",
			 :" . self::TS_OUTPUT_RESERVE_ID . ",
			 :" . self::TS_OUTPUT_BACKUP_ID . ",
			 :" . self::TS_OUTPUT_RESERVE . ",
			 :" . self::TS_OUTPUT_BRANCH . ",
			 :" . self::TS_OUTPUT_COMMIT . ",
			 :" . self::TS_OUTPUT_COMMENT . ",
			 :" . self::TS_OUTPUT_PUBLISH_TYPE . ",
			 :" . self::TS_OUTPUT_STATUS . ",
			 :" . self::TS_OUTPUT_DIFF_FLG1 . ",
			 :" . self::TS_OUTPUT_DIFF_FLG2 . ",
			 :" . self::TS_OUTPUT_DIFF_FLG3 . ",
			 :" . self::TS_OUTPUT_START . ",
			 :" . self::TS_OUTPUT_END . ",
			 :" . self::TS_OUTPUT_DELETE_FLG . ",
			 :" . self::TS_OUTPUT_DELETE . ",
			 :" . self::TS_OUTPUT_INSERT_DATETIME . ",
			 :" . self::TS_OUTPUT_INSERT_USER_ID . ",
			 :" . self::TS_OUTPUT_UPDATE_DATETIME . ",
			 :" . self::TS_OUTPUT_UPDATE_USER_ID . "
			)";

		$this->debug_echo('　□2');
		$this->debug_echo($insert_sql);
			// 現在時刻
			// $now = date(self::DATETIME_FORMAT);
			$now = $this->get_current_datetime_of_gmt();
		$this->debug_echo('　□3');
			// パラメータ作成
			$params = array(
				':reserve_id' => null,
				':backup_id' => null,
				':reserve_datetime' => null,
				':branch_name' => "ブランチ名",
				':commit_hash' => "dummy_commit_hash",
				':comment' => "コメント",
				':publish_type' => self::PUBLISH_TYPE_RESERVE,
				':status' => self::PUBLISH_STATUS_RUNNING,
				':change_check_flg' => null,
				':publish_honban_diff_flg' => null,
				':publish_pre_diff_flg' => null,
				':start_datetime' => $start_datetime,
				':end_datetime' => null,
				':gen_delete_flg' => self::DELETE_FLG_OFF,
				':gen_delete_datetime' => null,
				':insert_datetime' => $now,
				':insert_user_id' => "dummy_insert_user",
				':update_datetime' => null,
				':update_user_id' => null
			);
				$this->debug_echo('　□4');
			// INSERT実行
			$stmt = $this->execute($dbh, $insert_sql, $params);
		$this->debug_echo('　□5');
		} catch (Exception $e) {

	  		echo '公開処理結果テーブル登録処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->debug_echo('■ insert_ts_output end');

		return json_encode($result);
	}

	/**
	 * 公開処理結果一覧テーブルの更新処理
	 *
	 * @return なし
	 */
	private function update_ts_output($id) {

		$this->debug_echo('■ update_ts_output start');

		$result = array('status' => true,
						'message' => '');

		try {

			$this->debug_echo('id：' . $id);

			if (!$id) {
				$this->debug_echo('公開処理結果テーブルの更新IDが取得できませんでした。');
			} else {

				// UPDATE文作成
				$update_sql = "UPDATE TS_OUTPUT SET 
					status = :status,
					change_check_flg = :change_check_flg,
					publish_honban_diff_flg = :publish_honban_diff_flg,
					publish_pre_diff_flg = :publish_pre_diff_flg,
					end_datetime = :end_datetime,
					update_datetime = :update_datetime,
					update_user_id = :update_user_id 		

					WHERE result_id_seq = :result_id_seq";

				// 現在時刻
				$now = $this->get_current_datetime_of_gmt();

				// パラメータ作成
				$params = array(
					':status' => self::PUBLISH_STATUS_SUCCESS,
					':change_check_flg' => "0",
					':publish_honban_diff_flg' => "1",
					':publish_pre_diff_flg' => "1",
					':end_datetime' => $now,
					':update_datetime' => $now,
					':update_user_id' => "dummy_update_user",

					':result_id_seq' => $id
				);

				// UPDATE実行
				$stmt = $this->pdo->execute($this->dbh, $update_sql, $params);
			}

		} catch (Exception $e) {

	  		echo '公開処理結果テーブルの更新処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->debug_echo('■ update_ts_output end');

		return json_encode($result);
	}


	/**
	 * 公開予約一覧テーブルからリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	private function get_ts_reserve_list($now) {

		$this->debug_echo('■ get_ts_reserve_list start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

			// // テーブル作成
			// $this->pdo->create_table();

			// SELECT文作成（削除フラグ = 0、ソート順：公開予約日時の昇順）
			$select_sql = "
					SELECT * FROM TS_RESERVE WHERE delete_flg = " . self::DELETE_FLG_OFF . " ORDER BY reserve_datetime";
			// SELECT実行
			$ret_array = $this->pdo->select($this->dbh, $select_sql);

			foreach ((array)$ret_array as $array) {

				$conv_ret_array[] = $this->convert_ts_reserve_entity($array);
			}

			// $this->debug_echo('　□ conv_ret_array：');
			// $this->debug_var_dump($conv_ret_array);

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->debug_echo('■ get_ts_reserve_list end');

		return $conv_ret_array;
	}

	/**
	 * 公開処理結果一覧テーブルからリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	private function get_ts_output_list($now) {

		$this->debug_echo('■ get_ts_reserve_list start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

			// SELECT文作成（世代削除フラグ = 0、ソート順：IDの降順）
			$select_sql = "
					SELECT * FROM TS_OUTPUT WHERE gen_delete_flg = " . self::DELETE_FLG_OFF . " ORDER BY result_id_seq DESC";
			// SELECT実行
			$ret_array = $this->pdo->select($this->dbh, $select_sql);

			foreach ((array)$ret_array as $array) {

				$conv_ret_array[] = $this->convert_ts_output_entity($array);
			}

			// $this->debug_echo('　□ SELECTリストデータ：');
			// $this->debug_var_dump($ret_array);

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->debug_echo('■ get_ts_reserve_list end');

		return $conv_ret_array;
	}

	/**
	 * 公開予約テーブルから、選択された公開予約情報を取得する
	 *
	 * @return 選択行の情報
	 */
	private function get_selected_reserve_data() {


		$this->debug_echo('■ get_selected_reserve_data start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

			$selected_id =  $this->options->_POST->selected_id;

			$this->debug_echo('　□ selected_id：' . $selected_id);

			if (!$selected_id) {
				$this->debug_echo('選択値が取得できませんでした。');
			} else {

				// SELECT文作成
				$select_sql = "SELECT * from TS_RESERVE
					WHERE reserve_id_seq = ". $selected_id;

				// // パラメータ作成
				// $params = array(
				// 	':id' => $selected_id
				// );

				// SELECT実行
				$ret_array = array_shift($this->pdo->select($this->dbh, $select_sql));

				$conv_ret_array = $this->convert_ts_reserve_entity($ret_array);

				// $this->debug_echo('　□ SELECTデータ：');
				// $this->debug_var_dump($ret_array);
			}

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->debug_echo('■ get_selected_reserve_data end');

		return $conv_ret_array;
	}

	/**
	 * 公開予約テーブル登録処理
	 *
	 * @return なし
	 */
	private function insert_ts_reserve($combine_reserve_time) {

		$this->debug_echo('■ insert_ts_reserve start');

		$result = array('status' => true,
						'message' => '');

		try {

			// INSERT文作成
			$insert_sql = "INSERT INTO TS_RESERVE (
				reserve_datetime,
				branch_name,
				commit_hash,
				comment,
				delete_flg,
				insert_datetime,
				insert_user_id,
				update_datetime,
				update_user_id
			)VALUES(
				:reserve_datetime,
				:branch_name,
				:commit_hash,
				:comment,
				:delete_flg,
				:insert_datetime,
				:insert_user_id,
				:update_datetime,
				:update_user_id
			)";

			// 現在時刻
			// $now = date(self::DATETIME_FORMAT);
			$now = $this->get_current_datetime_of_gmt();

			// パラメータ作成
			$params = array(
				':reserve_datetime' => $this->convert_to_gmt_datetime($combine_reserve_time),
				':branch_name' => $this->options->_POST->branch_select_value,
				':commit_hash' => $this->commit_hash,
				':comment' => $this->options->_POST->comment,
				':delete_flg' => self::DELETE_FLG_OFF,
				':insert_datetime' => $now,
				':insert_user_id' => "dummy_insert_user",
				':update_datetime' => null,
				':update_user_id' => null
			);
		
			// INSERT実行
			$stmt = $this->pdo->execute($this->dbh, $insert_sql, $params);

		} catch (Exception $e) {

	  		echo '公開予約テーブルの登録処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->debug_echo('■ insert_ts_reserve end');

		return json_encode($result);
	}

	/**
	 * 公開予約テーブル更新処理
	 *
	 * @return なし
	 */
	private function update_reserve_table($combine_reserve_time) {

		$this->debug_echo('■ update_reserve_table start');

		$result = array('status' => true,
						'message' => '');

		try {

			$selected_id =  $this->options->_POST->selected_id;

			$this->debug_echo('　□ selected_id：' . $selected_id);

			if (!$selected_id) {
				$this->debug_echo('選択IDが取得できませんでした。');
			} else {

				// UPDATE文作成
				$update_sql = "UPDATE TS_RESERVE SET 
					reserve_datetime = :reserve_datetime,
					branch_name = :branch_name,
					commit_hash = :commit_hash,
					comment = :comment,
					update_datetime = :update_datetime,
					update_user_id = :update_user_id 
					WHERE reserve_id_seq = :reserve_id_seq";

				// 現在時刻
				// $now = date(self::DATETIME_FORMAT);
				$now = $this->get_current_datetime_of_gmt();

				// パラメータ作成
				$params = array(
					':reserve_datetime' => $this->convert_to_gmt_datetime($combine_reserve_time),
					':branch_name' => $this->options->_POST->branch_select_value,
					':commit_hash' => $this->commit_hash,
					':comment' => $this->options->_POST->comment,
					':update_datetime' => $now,
					':update_user_id' => "dummy_update_user",
					':reserve_id_seq' => $selected_id
				);

				// UPDATE実行
				$stmt = $this->pdo->execute($this->dbh, $update_sql, $params);
			}

		} catch (Exception $e) {

	  		echo '公開予約テーブルの更新処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->debug_echo('■ update_reserve_table end');

		return json_encode($result);
	}

	/**
	 * 公開予約テーブル論理削除処理
	 *
	 * @return なし
	 */
	private function delete_reserve_table() {

		$this->debug_echo('■ delete_reserve_table start');

		$result = array('status' => true,
						'message' => '');

		try {

			$selected_id =  $this->options->_POST->selected_id;

			$this->debug_echo('　□ selected_id：' . $selected_id);

			if (!$selected_id) {
				$this->debug_echo('選択IDが取得できませんでした。');
			} else {

				// UPDATE文作成（論理削除）
				$update_sql = "UPDATE TS_RESERVE SET 
					delete_flg = :delete_flg,
					update_datetime = :update_datetime,
					update_user_id = :update_user_id 
					WHERE reserve_id_seq = :reserve_id_seq";

				// 現在時刻
				// $now = date(self::DATETIME_FORMAT);
				$now = $this->get_current_datetime_of_gmt();

				// パラメータ作成
				$params = array(
					':delete_flg' => self::DELETE_FLG_ON,
					':update_datetime' => $now,
					':update_user_id' => "dummy_delete_user",
					':reserve_id_seq' => $selected_id
				);

				// UPDATE実行
				$stmt = $this->pdo->execute($this->dbh, $update_sql, $params);
			}

		} catch (Exception $e) {

	  		echo '公開予約テーブルの論理削除処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->debug_echo('■ delete_reserve_table end');

		return json_encode($result);
	}

	/**
	 * 選択された公開処理結果情報を取得する
	 *
	 * @return 選択行の情報
	 */
	private function get_selected_result_data() {


		$this->debug_echo('■ get_selected_result_data start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

			$selected_id =  $this->options->_POST->selected_id;

			$this->debug_echo('　□ selected_id：' . $selected_id);

			if (!$selected_id) {
				$this->debug_echo('選択値が取得できませんでした。');
			} else {

				// SELECT文作成
				$select_sql = "SELECT * from TS_OUTPUT 
					WHERE result_id_seq = ". $selected_id;

				// // パラメータ作成
				// $params = array(
				// 	':id' => $selected_id
				// );

				// SELECT実行
				$ret_array = array_shift($this->pdo->select($this->dbh, $select_sql));

				$conv_ret_array = $this->convert_ts_output_entity($ret_array);


				// $this->debug_echo('　□ SELECTデータ：');
				// $this->debug_var_dump($ret_array);
			}

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->debug_echo('■ get_selected_result_data end');

		return $conv_ret_array;
	}


	/**
	 * GMTの現在時刻を取得
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	private function get_current_datetime_of_gmt() {

		return gmdate(DATE_ATOM, time());
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