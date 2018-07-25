<?php

namespace indigo;

class tsReserve
{

	private $main;

	private $pdoMgr;
	private $common;


	// 日付フォーマット（Y-m-d）
	const DATE_FORMAT_YMD = "Y-m-d";
	// 時刻フォーマット（H:i）
	const TIME_FORMAT_HI = "H:i";


	/**
	 * 公開予約テーブルのカラム定義
	 */
	const TS_RESERVE_ID_SEQ 		= 'reserve_id_seq';			// ID
	const TS_RESERVE_RESERVE 		= 'reserve_datetime';		// 公開予約日時
	const TS_RESERVE_BRANCH 		= 'branch_name';			// ブランチ名
	const TS_RESERVE_COMMIT_HASH	= 'commit_hash';			// コミットハッシュ値（短縮）
	const TS_RESERVE_COMMENT		= 'comment';				// コメント
	const TS_RESERVE_STATUS			= 'status';					// 状態（0：未処理、1：処理済）
	const TS_RESERVE_DELETE_FLG		= 'delete_flg';				// 削除フラグ（0：未削除、1：削除済）
	const TS_RESERVE_INSERT_DATETIME 	= 'insert_datetime';	// 登録日時
	const TS_RESERVE_INSERT_USER_ID 	= 'insert_user_id';		// 登録ユーザID
	const TS_RESERVE_UPDATE_DATETIME 	= 'update_datetime';	// 更新日時
	const TS_RESERVE_UPDATE_USER_ID 	= 'update_user_id';		// 更新ユーザID
	
	/**
	 * 公開予約エンティティのカラム定義
	 */
	const RESERVE_ENTITY_ID_SEQ 		= 'reserve_id_seq';			// ID
	const RESERVE_ENTITY_RESERVE_GMT	= 'reserve_datetime_gmt';	// 公開予約日時（GMT日時）
	const RESERVE_ENTITY_RESERVE 		= 'reserve_datetime';		// 公開予約日時（タイムゾーン日時）
	const RESERVE_ENTITY_RESERVE_DISP 	= 'reserve_datetime_disp';	// 公開予約日時（表示用フォーマット）
	const RESERVE_ENTITY_RESERVE_DATE 	= 'reserve_date';			// 公開予約日時（タイムゾーン日付）
	const RESERVE_ENTITY_RESERVE_TIME	= 'reserve_time';			// 公開予約日時（タイムゾーン時刻）
	const RESERVE_ENTITY_BRANCH 		= 'branch_name';			// ブランチ名
	const RESERVE_ENTITY_COMMIT_HASH 	= 'commit_hash';			// コミットハッシュ値（短縮）
	const RESERVE_ENTITY_COMMENT 		= 'comment';				// コメント
	const RESERVE_ENTITY_INSERT_DATETIME 	= 'insert_datetime';	// 登録日時
	const RESERVE_ENTITY_INSERT_USER_ID 	= 'insert_user_id';		// 登録ユーザID
	const RESERVE_ENTITY_UPDATE_DATETIME 	= 'update_datetime';	// 更新日時
	const RESERVE_ENTITY_UPDATE_USER_ID		= 'update_user_id';		// 更新ユーザID


	/**
	 * Constructor
	 *
	 * @param object
	 */
	public function __construct ($main){

		$this->main = $main;
		$this->pdoMgr = new pdoManager($this);
		$this->common = new common($this);
	}

	/**
	 * 公開予約一覧テーブルからリストを取得する
	 *
	 * @param $dbh = dbオブジェクト
	 * @return データリスト
	 */
	public function get_ts_reserve_list($dbh) {

		$this->common->debug_echo('■ get_ts_reserve_list start');

		$ret_array = array();
		$conv_ret_array = array();

		// 公開予約テーブルから未処理、未削除データを取得
		$select_sql = "
				SELECT * FROM TS_RESERVE 
				WHERE " . self::TS_RESERVE_STATUS . " = '0'
				  AND " . self::TS_RESERVE_DELETE_FLG . " = '0'
				ORDER BY " . self::TS_RESERVE_RESERVE . " ASC;";

		// SELECT実行
		$ret_array = $this->pdoMgr->select($dbh, $select_sql);

		foreach ((array)$ret_array as $array) {
			$conv_ret_array[] = $this->convert_ts_reserve_entity($array);
		}
		
		$this->common->debug_echo('■ get_ts_reserve_list end');

		return $conv_ret_array;
	}

	/**
	 * 公開予約一覧テーブルからリストを取得する（公開処理用：現在日時以前の公開日時であること）
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	public function get_ts_reserve_publish_list($dbh, $now) {

		$this->common->debug_echo('■ get_ts_reserve_publish_list start');

		$ret_array = array();

		$conv_ret_array = array();

		$option_param = '';

		// if ($now) {
		// 	$option_param = " and reserve_datetime <= '" . $now . "'";
		// }

		// SELECT文作成（削除フラグ = 0、公開予約日時>=現在日時、ソート順：公開予約日時の降順）
		$select_sql = "
				SELECT * FROM TS_RESERVE
				WHERE " . self::TS_RESERVE_STATUS . " = '0'
				  AND " . self::TS_RESERVE_DELETE_FLG . " = '0' "
				. $option_param .
				" ORDER BY " . self::TS_RESERVE_RESERVE . " DESC;";

		// SELECT実行
		$ret_array = $this->pdoMgr->select($dbh, $select_sql);

		foreach ((array)$ret_array as $array) {
			$conv_ret_array[] = $this->convert_ts_reserve_entity($array);
		}
		
		$this->common->debug_echo('■ get_ts_reserve_publish_list end');

		return $conv_ret_array;
	}


	/**
	 * 公開予約テーブルから、選択された公開予約情報を取得する
	 *
	 * @return 選択行の情報
	 */
	public function get_selected_ts_reserve($dbh, $selected_id) {


		$this->common->debug_echo('■ get_selected_ts_reserve start');

		$ret_array = array();

		$conv_ret_array = array();

		if (!$selected_id) {
			throw new \Exception('選択されたIDが取得できませんでした。 ');
		} else {

			// SELECT文作成
			$select_sql = "SELECT * from TS_RESERVE 
			WHERE " . self::TS_RESERVE_ID_SEQ . " = " . $selected_id . ";";

			// SELECT実行
			$ret_array = array_shift($this->pdoMgr->select($dbh, $select_sql));

			$conv_ret_array = $this->convert_ts_reserve_entity($ret_array);
		}
		
		$this->common->debug_echo('■ get_selected_ts_reserve end');

		return $conv_ret_array;
	}

	/**
	 * 公開予約テーブル登録処理
	 *
	 * @return なし
	 */
	public function insert_ts_reserve($dbh, $options) {

		$this->common->debug_echo('■ insert_ts_reserve start');

		// INSERT文作成
		$insert_sql = "INSERT INTO TS_RESERVE ("
		. self::TS_RESERVE_RESERVE . ","
		. self::TS_RESERVE_BRANCH . ","
		. self::TS_RESERVE_COMMIT_HASH . ","
		. self::TS_RESERVE_COMMENT . ","
		. self::TS_RESERVE_STATUS . ","
		. self::TS_RESERVE_DELETE_FLG . ","
		. self::TS_RESERVE_INSERT_DATETIME . ","
		. self::TS_RESERVE_INSERT_USER_ID . ","
		. self::TS_RESERVE_UPDATE_DATETIME . ","
		. self::TS_RESERVE_UPDATE_USER_ID

		. ") VALUES (" .

		 ":" . self::TS_RESERVE_RESERVE . "," .
		 ":" . self::TS_RESERVE_BRANCH . "," .
		 ":" . self::TS_RESERVE_COMMIT_HASH . "," .
		 ":" . self::TS_RESERVE_COMMENT . "," .
		 ":" . self::TS_RESERVE_STATUS . "," .
		 ":" . self::TS_RESERVE_DELETE_FLG . "," .
		 ":" . self::TS_RESERVE_INSERT_DATETIME . "," .
		 ":" . self::TS_RESERVE_INSERT_USER_ID . "," .
		 ":" . self::TS_RESERVE_UPDATE_DATETIME . "," .
		 ":" . self::TS_RESERVE_UPDATE_USER_ID

		. ");";

		$this->common->debug_echo('　□ insert_sql');
		$this->common->debug_echo($insert_sql);

		// 現在時刻
		$now = $this->common->get_current_datetime_of_gmt();
		
		// パラメータ作成
		$params = array(
			":" . self::TS_RESERVE_RESERVE 			=> $options->_POST->gmt_reserve_datetime,
			":" . self::TS_RESERVE_BRANCH			=> $options->_POST->branch_select_value,
			":" . self::TS_RESERVE_COMMIT_HASH 		=> $options->_POST->commit_hash,
			":" . self::TS_RESERVE_COMMENT 			=> $options->_POST->comment,
			":" . self::TS_RESERVE_STATUS 			=> '0',
			":" . self::TS_RESERVE_DELETE_FLG 		=> define::DELETE_FLG_OFF,
			":" . self::TS_RESERVE_INSERT_DATETIME 	=> $now,
			":" . self::TS_RESERVE_INSERT_USER_ID 	=> $options->user_id,
			":" . self::TS_RESERVE_UPDATE_DATETIME 	=> null,
			":" . self::TS_RESERVE_UPDATE_USER_ID 	=> null
		);

		// INSERT実行
		$stmt = $this->pdoMgr->execute($dbh, $insert_sql, $params);

		$this->common->debug_echo('■ insert_ts_reserve end');

		return;
	}

	/**
	 * 公開予約テーブル更新処理
	 *
	 * @return なし
	 */
	public function update_ts_reserve($dbh, $options, $selected_id) {

		$this->common->debug_echo('■ update_ts_reserve start');

		if (!$selected_id) {
			throw new \Exception('更新対象の公開予約IDが取得できませんでした。 ');
		}

		// UPDATE文作成
		$update_sql = "UPDATE TS_RESERVE SET " .
			self::TS_RESERVE_RESERVE 		 . "= :" . self::TS_RESERVE_RESERVE . "," .
			self::TS_RESERVE_BRANCH 		 . "= :" . self::TS_RESERVE_BRANCH . "," .
			self::TS_RESERVE_COMMIT_HASH 	 . "= :" . self::TS_RESERVE_COMMIT_HASH . "," .
			self::TS_RESERVE_COMMENT 		 . "= :" . self::TS_RESERVE_COMMENT . "," .
			self::TS_RESERVE_UPDATE_DATETIME . "= :" . self::TS_RESERVE_UPDATE_DATETIME . "," .
			self::TS_RESERVE_UPDATE_USER_ID  . "= :" . self::TS_RESERVE_UPDATE_USER_ID .
			" WHERE " . self::TS_RESERVE_ID_SEQ . "= :" . self::TS_RESERVE_ID_SEQ . ";";

		// 現在時刻
		$now = $this->common->get_current_datetime_of_gmt();

		// パラメータ作成
		$params = array(
			":" . self::TS_RESERVE_RESERVE 			=> $options->_POST->gmt_reserve_datetime,
			":" . self::TS_RESERVE_BRANCH 			=> $options->_POST->branch_select_value,
			":" . self::TS_RESERVE_COMMIT_HASH 		=> $options->_POST->commit_hash,
			":" . self::TS_RESERVE_COMMENT	 		=> $options->_POST->comment,
			":" . self::TS_RESERVE_UPDATE_DATETIME 	=> $now,
			":" . self::TS_RESERVE_UPDATE_USER_ID	=> $options->user_id,
			":" . self::TS_RESERVE_ID_SEQ			=> $selected_id
		);

		// UPDATE実行
		$this->pdoMgr->execute($dbh, $update_sql, $params);

		$this->common->debug_echo('■ update_ts_reserve end');
	}

	/**
	 * 公開予約テーブル更新処理
	 *
	 * @return なし
	 */
	public function update_ts_reserve_status($dbh, $selected_id) {

		$this->common->debug_echo('■ update_ts_reserve_status start');

		if (!$selected_id) {
			throw new \Exception('更新対象の公開予約IDが取得できませんでした。 ');
		}

		// UPDATE文作成
		$update_sql = "UPDATE TS_RESERVE SET " .
			self::TS_RESERVE_STATUS .	"= :" . self::TS_RESERVE_STATUS .
			" WHERE " . self::TS_RESERVE_ID_SEQ . "= :" . self::TS_RESERVE_ID_SEQ . ";";

		// パラメータ作成
		$params = array(
			":" . self::TS_RESERVE_STATUS 	=> '1',
			":" . self::TS_RESERVE_ID_SEQ	=> $selected_id
		);

		// UPDATE実行
		$this->pdoMgr->execute($dbh, $update_sql, $params);

		$this->common->debug_echo('■ update_ts_reserve_status end');
	}

	/**
	 * 公開予約テーブル論理削除処理
	 *
	 * @return なし
	 */
	public function delete_reserve_table($dbh, $options, $selected_id) {

		$this->common->debug_echo('■ delete_reserve_table start');

		if (!$selected_id) {
			throw new \Exception('選択情報のIDが取得できませんでした。 ');
		}

		// UPDATE文作成
		$update_sql = "UPDATE TS_RESERVE SET " .
			self::TS_RESERVE_DELETE_FLG 		. "= :" . self::TS_RESERVE_DELETE_FLG . "," .
			self::TS_RESERVE_UPDATE_DATETIME	. "= :" . self::TS_RESERVE_UPDATE_DATETIME . "," .
			self::TS_RESERVE_UPDATE_USER_ID		. "= :" . self::TS_RESERVE_UPDATE_USER_ID .
			" WHERE " . self::TS_RESERVE_ID_SEQ . "= :" . self::TS_RESERVE_ID_SEQ . ";";

		// 現在時刻
		// $now = date(self::DATETIME_FORMAT);
		$now = $this->common->get_current_datetime_of_gmt();

		// パラメータ作成
		$params = array(
			":" . self::TS_RESERVE_DELETE_FLG 		=> define::DELETE_FLG_ON,
			":" . self::TS_RESERVE_UPDATE_DATETIME 	=> $now,
			":" . self::TS_RESERVE_UPDATE_USER_ID	=> $options->user_id,
			":" . self::TS_RESERVE_ID_SEQ			=> $selected_id
		);

		// UPDATE実行
		$this->pdoMgr->execute($dbh, $update_sql, $params);

		$this->common->debug_echo('■ delete_reserve_table end');
	}

	/**
	 * 公開予約テーブルの情報を変換する
	 *	 
	 * @param $array = テーブル情報
	 * @return 変換後
	 */
	private function convert_ts_reserve_entity($array) {
	
		// $this->common->debug_echo('■ convert_ts_reserve_entity start');

		$entity = array();

		// ID
		$entity[self::RESERVE_ENTITY_ID_SEQ] 		= $array[self::TS_RESERVE_ID_SEQ];
		// 公開予約日時（GMT日時）
		$entity[self::RESERVE_ENTITY_RESERVE_GMT] 	= $array[self::TS_RESERVE_RESERVE];
		// 公開予約日時（タイムゾーン日時）
		$tz_datetime = $this->common->convert_to_timezone_datetime($array[self::TS_RESERVE_RESERVE]);
		$entity[self::RESERVE_ENTITY_RESERVE] = $tz_datetime;
		$entity[self::RESERVE_ENTITY_RESERVE_DISP] = $this->common->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);
		$entity[self::RESERVE_ENTITY_RESERVE_DATE] = $this->common->format_datetime($tz_datetime, self::DATE_FORMAT_YMD);
		$entity[self::RESERVE_ENTITY_RESERVE_TIME] = $this->common->format_datetime($tz_datetime, self::TIME_FORMAT_HI);
		// ブランチ名
		$entity[self::RESERVE_ENTITY_BRANCH] = $array[self::TS_RESERVE_BRANCH];
		// コミットハッシュ値
		$entity[self::RESERVE_ENTITY_COMMIT_HASH] = $array[self::TS_RESERVE_COMMIT_HASH];
		// コメント
		$entity[self::RESERVE_ENTITY_COMMENT] = $array[self::TS_RESERVE_COMMENT];
		// 登録ユーザID
		$entity[self::RESERVE_ENTITY_INSERT_USER_ID] = $array[self::TS_RESERVE_INSERT_USER_ID];
		// 登録日時
		$tz_datetime = $this->common->convert_to_timezone_datetime($array[self::TS_RESERVE_INSERT_DATETIME]);
		$entity[self::RESERVE_ENTITY_INSERT_DATETIME] = $tz_datetime;
		// 更新ユーザID
		$entity[self::RESERVE_ENTITY_UPDATE_USER_ID] = $array[self::TS_RESERVE_UPDATE_USER_ID];
		// 更新日時
		$tz_datetime = $this->common->convert_to_timezone_datetime($array[self::TS_RESERVE_UPDATE_DATETIME]);
		$entity[self::RESERVE_ENTITY_UPDATE_DATETIME] = $tz_datetime;

		// $this->common->debug_echo('■ convert_ts_reserve_entity end');

	    return $entity;
	}


}