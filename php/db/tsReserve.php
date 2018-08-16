<?php

namespace indigo\db;

use indigo\define as define;

class tsReserve
{

	private $main;

	/**
	 * 公開予約テーブルのカラム定義
	 */
	const TS_RESERVE_ID_SEQ 		= 'reserve_id_seq';			// ID
	const TS_RESERVE_DATETIME 		= 'reserve_datetime';		// 公開予約日時
	const TS_RESERVE_BRANCH 		= 'branch_name';			// ブランチ名
	const TS_RESERVE_COMMIT_HASH	= 'commit_hash';			// コミットハッシュ値（短縮）
	const TS_RESERVE_COMMENT		= 'comment';				// コメント
	const TS_RESERVE_STATUS			= 'status';					// 状態（0：未処理、1：処理済）
	const TS_RESERVE_DELETE_FLG		= 'delete_flg';				// 削除フラグ（0：未削除、1：削除済）
	const TS_RESERVE_INSERT_DATETIME 	= 'insert_datetime';	// 登録日時
	const TS_RESERVE_INSERT_USER_ID 	= 'insert_user_id';		// 登録ユーザID
	const TS_RESERVE_UPDATE_DATETIME 	= 'update_datetime';	// 更新日時
	const TS_RESERVE_UPDATE_USER_ID 	= 'update_user_id';		// 更新ユーザID
	const TS_RESERVE_VER_NO 			= 'ver_no';				// バージョンNO

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
	const RESERVE_ENTITY_VER_NO				= 'ver_no';				// バージョンNO


	/**
	 * Constructor
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
	}

	/**
	 * 公開予約一覧リストの取得メソッド
	 *
	 * 公開予約テーブルから未処理、未削除データをリストで取得します。
	 * 初期表示画面表示用に使用しており、フォーマット変換を行い配列を返却します。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @return array[] $conv_ret_array
	 * 				公開予約リスト
	 */
	public function get_ts_reserve_list() {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_ts_reserve_list start');

		$select_sql = "
				SELECT * FROM TS_RESERVE 
				WHERE " . self::TS_RESERVE_STATUS . " = '0' " . 		// 0:未処理
				"  AND " . self::TS_RESERVE_DELETE_FLG . " = '0' " .	// 0:未削除
				"ORDER BY " . self::TS_RESERVE_DATETIME . " ASC;";		// 公開予約日時 昇順

		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select($this->main->dbh(), $stmt);

		$conv_ret_array = null;
		foreach ((array)$ret_array as $array) {
			$conv_ret_array[] = $this->convert_ts_reserve_entity($array);
		}
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_ts_reserve_list end');

		return $conv_ret_array;
	}

	/**
	 * 公開対象の公開予約一覧リストの取得メソッド
	 *
	 * 公開予約一覧テーブルから未処理、未削除、かつ、公開日時に達したデータをリストで取得します。
	 * クーロン処理用に使用しています。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @param  string  $now 日時
	 * @return array[] $ret_array
	 * 				公開予約リスト
	 */
	public function get_ts_reserve_publish_list($now) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_ts_reserve_publish_list start');

		// SELECT文作成（削除フラグ = 0、公開予約日時>=現在日時、ソート順：公開予約日時の降順）
		$select_sql = "
				SELECT * FROM TS_RESERVE
				WHERE " . self::TS_RESERVE_STATUS . " = '0' " . 		// 0:未処理
				" AND " . self::TS_RESERVE_DATETIME . " <= ? " .		// 引数日時と同時刻、または過去日時
				" AND " . self::TS_RESERVE_DELETE_FLG . " = '0' " . 	// 0:未削除
				"ORDER BY " . self::TS_RESERVE_DATETIME . " DESC;";		// 公開予約日時 降順

		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// バインド引数設定
		$stmt->bindParam(1, $now, \PDO::PARAM_STR);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select($this->main->dbh(), $stmt);
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_ts_reserve_publish_list end');

		return $ret_array;
	}

	/**
	 * 公開予約情報取得メソッド
	 *
	 * 引数の公開予約IDを条件に、公開予約情報を1件取得します。
	 * フォーマット変換を行い返却します。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @param  string  $selected_id 公開予約ID
	 * @return array $conv_ret_array 変換後の公開予約情報
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 */
	public function get_selected_ts_reserve($selected_id) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_reserve start');

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '[パラメタ]selected_id：' . $selected_id);

		if (!$selected_id) {
			throw new \Exception('対象の公開予約IDが正しく取得できませんでした。 ');
		}

		// SELECT文作成
		$select_sql = "SELECT * from TS_RESERVE 
		WHERE " . self::TS_RESERVE_ID_SEQ . " = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// バインド引数設定
		$stmt->bindParam(1, $selected_id, \PDO::PARAM_INT);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select_one($this->main->dbh(), $stmt);

		$conv_ret_array = $this->convert_ts_reserve_entity($ret_array);
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_reserve end');

		return $conv_ret_array;
	}

	/**
	 * 公開予約テーブル登録処理メソッド
	 *
	 * 公開予約情報を1件登録します。
	 *
	 * @param  array[]  $options mainオプション情報
	 * @return null
	 */
	public function insert_ts_reserve($options) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ insert_ts_reserve start');

		// INSERT文作成
		$insert_sql = "INSERT INTO TS_RESERVE ("
		. self::TS_RESERVE_DATETIME . ","
		. self::TS_RESERVE_BRANCH . ","
		. self::TS_RESERVE_COMMIT_HASH . ","
		. self::TS_RESERVE_COMMENT . ","
		. self::TS_RESERVE_STATUS . ","
		. self::TS_RESERVE_DELETE_FLG . ","
		. self::TS_RESERVE_INSERT_DATETIME . ","
		. self::TS_RESERVE_INSERT_USER_ID . ","
		. self::TS_RESERVE_UPDATE_DATETIME . ","
		. self::TS_RESERVE_UPDATE_USER_ID . ","
		. self::TS_RESERVE_VER_NO

		. ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

		// 前処理
		$stmt = $this->main->dbh()->prepare($insert_sql);

		// 現在日時
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		
		// バインド引数設定
		$stmt->bindParam(1, $options->_POST->gmt_reserve_datetime, \PDO::PARAM_STR);
		$stmt->bindParam(2, $options->_POST->branch_select_value, \PDO::PARAM_STR);
		$stmt->bindParam(3, $options->_POST->commit_hash, \PDO::PARAM_STR);
		$stmt->bindParam(4, $options->_POST->comment, \PDO::PARAM_STR);
		$stmt->bindValue(5, '0', \PDO::PARAM_STR);
		$stmt->bindValue(6, define::DELETE_FLG_OFF, \PDO::PARAM_STR);
		$stmt->bindParam(7, $now, \PDO::PARAM_STR);
		$stmt->bindParam(8, $options->user_id, \PDO::PARAM_STR);
		$stmt->bindValue(9, null, \PDO::PARAM_STR);
		$stmt->bindValue(10, null, \PDO::PARAM_STR);
		$stmt->bindValue(11, '0', \PDO::PARAM_STR);

		// INSERT実行
		$stmt = $this->main->pdoMgr()->execute($this->main->dbh(), $stmt);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ insert_ts_reserve end');
	}

	/**
	 * 公開予約テーブル更新処理メソッド
	 *
	 * 引数の公開予約IDを条件に、公開予約情報を1件更新します。
	 *
	 * @param  array[]  $options mainオプション情報
	 * @param  string  $selected_id 公開予約ID
	 * @return null
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 * @throws Exception バージョンNOを確認し、すでに他ユーザにて情報が更新されている場合
	 */
	public function update_ts_reserve($options, $selected_id) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ update_ts_reserve start');

		if (!$selected_id) {
			throw new \Exception('更新対象の公開予約IDが取得できませんでした。 ');
		}

		// 他ユーザで更新されていないか確認
		$selected_ret = $this->get_selected_ts_reserve($selected_id);

		$logstr = "[排他確認]データ取得時のバージョンNO：" . $options->_POST->ver_no . "\r\n";
		$logstr .= "[排他確認]現時点のバージョンNO：" . $selected_ret[self::RESERVE_ENTITY_VER_NO];
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		if ($selected_ret &&
			$selected_ret[self::RESERVE_ENTITY_VER_NO] != $options->_POST->ver_no) {

			throw new \Exception('ユーザID [' . $selected_ret[self::RESERVE_ENTITY_UPDATE_USER_ID] . '] にて公開予約情報が更新されております。');
		}

		// UPDATE文作成
		$update_sql = "UPDATE TS_RESERVE SET " .
			self::TS_RESERVE_DATETIME 		 . " = ?, " .
			self::TS_RESERVE_BRANCH 		 . " = ?, " .
			self::TS_RESERVE_COMMIT_HASH 	 . " = ?, " .
			self::TS_RESERVE_COMMENT 		 . " = ?, " .
			self::TS_RESERVE_UPDATE_DATETIME . " = ?, " .
			self::TS_RESERVE_UPDATE_USER_ID	 . " = ?, " .
			self::TS_RESERVE_VER_NO 		 . " = ? " .
			" WHERE " . self::TS_RESERVE_ID_SEQ . " = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($update_sql);

		// 現在日時
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		
		// バインド引数設定
		$stmt->bindParam(1, $options->_POST->gmt_reserve_datetime, \PDO::PARAM_STR);
		$stmt->bindParam(2, $options->_POST->branch_select_value, \PDO::PARAM_STR);
		$stmt->bindParam(3, $options->_POST->commit_hash, \PDO::PARAM_STR);
		$stmt->bindParam(4, $options->_POST->comment, \PDO::PARAM_STR);
		$stmt->bindParam(5, $now, \PDO::PARAM_STR);
		$stmt->bindParam(6, $options->user_id, \PDO::PARAM_STR);
		$stmt->bindValue(7, $options->_POST->ver_no + 1, \PDO::PARAM_STR);
		$stmt->bindParam(8, $selected_id, \PDO::PARAM_INT);

		// UPDATE実行
		$stmt = $this->main->pdoMgr()->execute($this->main->dbh(), $stmt);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ update_ts_reserve end');
	}

	/**
	 * 公開予約テーブル更新処理メソッド（ステータス更新用）
	 *
	 * 引数の公開予約IDを条件に、公開予約情報のステータスを"処理済み"へ更新します。
	 *
	 * @param  array[]  $options mainオプション情報
	 * @param  string  $selected_id 公開予約ID
	 * @return null
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 * @throws Exception バージョンNOを確認し、すでに他ユーザにて情報が更新されている場合
	 */
	public function update_ts_reserve_status($selected_id, $ver_no) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ update_ts_reserve_status start');

		if (!$selected_id) {
			throw new \Exception('更新対象の公開予約IDが取得できませんでした。 ');
		}

		// 他ユーザで更新されていないか確認
		$selected_ret = $this->get_selected_ts_reserve($selected_id);

		$logstr = "[排他確認]データ取得時のバージョンNO：" . $ver_no . "\r\n";
		$logstr .= "[排他確認]現時点のバージョンNO：" . $selected_ret[self::RESERVE_ENTITY_VER_NO];
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		if ($selected_ret &&
			$selected_ret[self::RESERVE_ENTITY_VER_NO] != $ver_no) {

			throw new \Exception('ユーザID [' . $selected_ret[self::RESERVE_ENTITY_UPDATE_USER_ID] . '] にて公開予約情報が更新されております。');
		}

		// UPDATE文作成
		$update_sql = "UPDATE TS_RESERVE SET " .
			self::TS_RESERVE_STATUS . " = ? " .
			" WHERE " . self::TS_RESERVE_ID_SEQ . " = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($update_sql);

		// バインド引数設定
		$stmt->bindValue(1, '1', \PDO::PARAM_STR);
		$stmt->bindParam(2, $selected_id, \PDO::PARAM_INT);

		// UPDATE実行
		$stmt = $this->main->pdoMgr()->execute($this->main->dbh(), $stmt);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ update_ts_reserve_status end');
	}

	/**
	 * 公開予約テーブル論理削除処理メソッド
	 *
	 * 引数の公開予約IDを条件に、公開予約情報を削除済みへ更新します。
	 *
	 * @param  array[]  $options mainオプション情報
	 * @param  string  $selected_id 公開予約ID
	 * @return null
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 */
	public function delete_reserve_table($options, $selected_id) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ delete_reserve_table start');

		if (!$selected_id) {
			throw new \Exception('更新対象の公開予約IDが取得できませんでした。 ');
		}

		// UPDATE文作成
		$update_sql = "UPDATE TS_RESERVE SET " .
			self::TS_RESERVE_DELETE_FLG 		. " = ?, " .
			self::TS_RESERVE_UPDATE_DATETIME	. " = ?, " .
			self::TS_RESERVE_UPDATE_USER_ID		. " = ? " .
			" WHERE " . self::TS_RESERVE_ID_SEQ . " = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($update_sql);

		// 現在時刻
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		// バインド引数設定
		$stmt->bindValue(1, define::DELETE_FLG_ON, \PDO::PARAM_STR);
		$stmt->bindParam(2, $now, \PDO::PARAM_STR);
		$stmt->bindParam(3, $options->user_id, \PDO::PARAM_STR);
		$stmt->bindParam(4, $selected_id, \PDO::PARAM_INT);

		// UPDATE実行
		$stmt = $this->main->pdoMgr()->execute($this->main->dbh(), $stmt);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ delete_reserve_table end');
	}

	/**
	 * 公開予約テーブルの情報を変換する
	 *
	 * @param  array $array 公開予約テーブル情報
	 * @return array $conv_array 変換後の公開予約テーブル情報
	 */
	private function convert_ts_reserve_entity($array) {
	
		// $this->main->common()->put_process_log(__METHOD__, __LINE__, '■ convert_ts_reserve_entity start');

		// ID
		$conv_array[self::RESERVE_ENTITY_ID_SEQ] 		= $array[self::TS_RESERVE_ID_SEQ];

		// 公開予約日時（GMT日時）
		$conv_array[self::RESERVE_ENTITY_RESERVE_GMT] 	= $array[self::TS_RESERVE_DATETIME];

		// 公開予約日時（タイムゾーン日時）
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::TS_RESERVE_DATETIME]);
		$conv_array[self::RESERVE_ENTITY_RESERVE] = $tz_datetime;
		$conv_array[self::RESERVE_ENTITY_RESERVE_DISP] = $this->main->common()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);
		$conv_array[self::RESERVE_ENTITY_RESERVE_DATE] = $this->main->common()->format_datetime($tz_datetime, define::DATE_FORMAT_YMD);
		$conv_array[self::RESERVE_ENTITY_RESERVE_TIME] = $this->main->common()->format_datetime($tz_datetime, define::TIME_FORMAT_HI);

		// ブランチ名
		$conv_array[self::RESERVE_ENTITY_BRANCH] = $array[self::TS_RESERVE_BRANCH];
		// コミットハッシュ値
		$conv_array[self::RESERVE_ENTITY_COMMIT_HASH] = $array[self::TS_RESERVE_COMMIT_HASH];
		// コメント
		$conv_array[self::RESERVE_ENTITY_COMMENT] = $array[self::TS_RESERVE_COMMENT];

		// 登録ユーザID
		$conv_array[self::RESERVE_ENTITY_INSERT_USER_ID] = $array[self::TS_RESERVE_INSERT_USER_ID];
		// 登録日時
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::TS_RESERVE_INSERT_DATETIME]);
		$conv_array[self::RESERVE_ENTITY_INSERT_DATETIME] = $tz_datetime;
		// 更新ユーザID
		$conv_array[self::RESERVE_ENTITY_UPDATE_USER_ID] = $array[self::TS_RESERVE_UPDATE_USER_ID];
		// 更新日時
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::TS_RESERVE_UPDATE_DATETIME]);
		$conv_array[self::RESERVE_ENTITY_UPDATE_DATETIME] = $tz_datetime;

		// バージョンNO
		$conv_array[self::RESERVE_ENTITY_VER_NO] 	= $array[self::TS_RESERVE_VER_NO];

		// $this->main->common()->put_process_log(__METHOD__, __LINE__, '■ convert_ts_reserve_entity end');

	    return $conv_array;
	}

}