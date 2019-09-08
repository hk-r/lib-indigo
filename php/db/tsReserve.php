<?php

namespace indigo\db;

use indigo\define as define;

/**
 * ts_reserveテーブルのデータベース処理クラス
 *
 * ts_reserveテーブルに関する処理をまとめたクラス。
 *
 */
class tsReserve
{

	private $main;

	/**
	 * 公開予定テーブルのカラム定義
	 */
	const TS_RESERVE_ID_SEQ 			= 'reserve_id';			// ID
	const TS_RESERVE_DATETIME 			= 'reserve_datetime';	// 公開予定日時
	const TS_RESERVE_BRANCH 			= 'branch_name';		// ブランチ名
	const TS_RESERVE_COMMIT_HASH		= 'commit_hash';		// コミットハッシュ値（短縮）
	const TS_RESERVE_COMMENT			= 'comment';			// コメント
	const TS_RESERVE_STATUS				= 'status';				// 状態（0：未処理、1：処理済）
	const TS_RESERVE_DELETE_FLG			= 'delete_flg';			// 削除フラグ（0：未削除、1：削除済）
	const TS_RESERVE_INSERT_DATETIME 	= 'insert_datetime';	// 登録日時
	const TS_RESERVE_INSERT_USER_ID 	= 'insert_user_id';		// 登録ユーザID
	const TS_RESERVE_SPACE_NAME		 	= 'space_name';			// 空間名
	const TS_RESERVE_UPDATE_DATETIME 	= 'update_datetime';	// 更新日時
	const TS_RESERVE_UPDATE_USER_ID 	= 'update_user_id';		// 更新ユーザID
	const TS_RESERVE_VER_NO 			= 'ver_no';				// バージョンNO

	/**
	 * 公開予定エンティティのカラム定義
	 */
	const RESERVE_ENTITY_ID_SEQ 		= 'reserve_id';				// ID
	const RESERVE_ENTITY_RESERVE_GMT	= 'reserve_datetime_gmt';	// 公開予定日時（GMT日時）
	const RESERVE_ENTITY_RESERVE 		= 'reserve_datetime';		// 公開予定日時（タイムゾーン日時）
	const RESERVE_ENTITY_RESERVE_DISP 	= 'reserve_datetime_disp';	// 公開予定日時（表示用フォーマット）
	const RESERVE_ENTITY_RESERVE_DATE 	= 'reserve_date';			// 公開予定日時（タイムゾーン日付）
	const RESERVE_ENTITY_RESERVE_TIME	= 'reserve_time';			// 公開予定日時（タイムゾーン時刻）
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
	 * 公開予定一覧リストの取得メソッド
	 *
	 * 公開予定テーブルから未処理、未削除データをリストで取得します。
	 * 初期表示画面表示用に使用しており、フォーマット変換を行い配列を返却します。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @return array[] $conv_ret_array
	 * 				公開予定リスト
	 */
	public function get_ts_reserve_list() {

		$select_sql = "SELECT * FROM ".$this->main->pdoMgr()->get_physical_table_name('TS_RESERVE')." " . 
				"WHERE " . self::TS_RESERVE_STATUS . " = '0' " . 		// 0:未処理
				"  AND " . self::TS_RESERVE_DELETE_FLG . " = '0' " .	// 0:未削除
				"  AND " . self::TS_RESERVE_SPACE_NAME . " = ".json_encode($this->main->space_name)." " .
				"ORDER BY " . self::TS_RESERVE_DATETIME . " ASC;";		// 公開予定日時 昇順


		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select($this->main->dbh(), $stmt);

		$conv_ret_array = null;
		foreach ((array)$ret_array as $array) {
			$conv_ret_array[] = $this->convert_ts_reserve_entity($array);
		}
		
		return $conv_ret_array;
	}

	/**
	 * 公開対象の公開予定一覧リストの取得メソッド
	 *
	 * 公開予定一覧テーブルから未処理、未削除、かつ、公開日時に達したデータをリストで取得します。
	 * クーロン処理用に使用しています。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @param  string  $now 日時
	 * @return array[] $ret_array
	 * 				公開予定リスト
	 */
	public function get_ts_reserve_publish_list($now) {

		// SELECT文作成（削除フラグ = 0、公開予定日時>=現在日時、ソート順：公開予定日時の降順）
		$select_sql = "SELECT * FROM ".$this->main->pdoMgr()->get_physical_table_name('TS_RESERVE')." " .
				"WHERE " . self::TS_RESERVE_STATUS . " = '0' " . 		// 0:未処理
				" AND " . self::TS_RESERVE_DATETIME . " <= ? " .		// 引数日時と同時刻、または過去日時
				" AND " . self::TS_RESERVE_DELETE_FLG . " = '0' " . 	// 0:未削除
				" AND " . self::TS_RESERVE_SPACE_NAME . " = ".json_encode($this->main->space_name)." " .
				"ORDER BY " . self::TS_RESERVE_DATETIME . " DESC;";		// 公開予定日時 降順

		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// バインド引数設定
		$stmt->bindParam(1, $now, \PDO::PARAM_STR);

		$this->main->common()->put_process_log_block('[Param]');
		$this->main->common()->put_process_log_block(self::TS_RESERVE_DATETIME . " = " . $now);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select($this->main->dbh(), $stmt);	

		return $ret_array;
	}

	/**
	 * 公開予定情報取得メソッド
	 *
	 * 引数の公開予定IDを条件に、公開予定情報を1件取得します。
	 * フォーマット変換を行い返却します。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @param  string  $selected_id 公開予定ID
	 * @return array $conv_ret_array 変換後の公開予定情報
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 */
	public function get_selected_ts_reserve($selected_id) {

		if (!$selected_id) {
			throw new \Exception('対象の公開予定IDが正しく取得できませんでした。 ');
		}

		// SELECT文作成
		$select_sql = "SELECT * from ".$this->main->pdoMgr()->get_physical_table_name('TS_RESERVE')
			." WHERE " . self::TS_RESERVE_ID_SEQ . " = ? "
			."   AND " . self::TS_RESERVE_SPACE_NAME . " = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// バインド引数設定
		$stmt->bindParam(1, $selected_id, \PDO::PARAM_STR);
		$stmt->bindParam(2, $this->main->space_name, \PDO::PARAM_STR);

		$this->main->common()->put_process_log_block('[Param]');
		$this->main->common()->put_process_log_block(self::TS_RESERVE_ID_SEQ . " = " . $selected_id);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select_one($this->main->dbh(), $stmt);

		$conv_ret_array = $this->convert_ts_reserve_entity($ret_array);
		
		return $conv_ret_array;
	}

	/**
	 * 公開予定テーブル登録処理メソッド
	 *
	 * 公開予定情報を1件登録します。
	 *
	 * @param array  $form 		 フォーム格納配列
	 * @param string $gmt_reserve_datetime GMT公開予定日時
	 * @param string $user_id 	 ユーザID
	 */
	public function insert_ts_reserve($form, $gmt_reserve_datetime, $user_id) {

		// INSERT文作成
		$insert_sql = "INSERT INTO ".$this->main->pdoMgr()->get_physical_table_name('TS_RESERVE')." ("
		. self::TS_RESERVE_ID_SEQ . ","
		. self::TS_RESERVE_DATETIME . ","
		. self::TS_RESERVE_BRANCH . ","
		. self::TS_RESERVE_COMMIT_HASH . ","
		. self::TS_RESERVE_COMMENT . ","
		. self::TS_RESERVE_STATUS . ","
		. self::TS_RESERVE_DELETE_FLG . ","
		. self::TS_RESERVE_INSERT_DATETIME . ","
		. self::TS_RESERVE_INSERT_USER_ID . ","
		. self::TS_RESERVE_SPACE_NAME . ","
		. self::TS_RESERVE_UPDATE_DATETIME . ","
		. self::TS_RESERVE_UPDATE_USER_ID . ","
		. self::TS_RESERVE_VER_NO

		. ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

		// 前処理
		$stmt = $this->main->dbh()->prepare($insert_sql);

		// 現在日時
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		
		$uuid = \Ramsey\Uuid\Uuid::uuid1()->toString();

		// バインド引数設定
		$stmt->bindParam(1, $uuid, \PDO::PARAM_STR);
		$stmt->bindParam(2, $gmt_reserve_datetime, \PDO::PARAM_STR);
		$stmt->bindParam(3, $form['branch_select_value'], \PDO::PARAM_STR);
		$stmt->bindParam(4, $form['commit_hash'], \PDO::PARAM_STR);
		$stmt->bindParam(5, $form['comment'], \PDO::PARAM_STR);
		$stmt->bindValue(6, '0', \PDO::PARAM_STR);
		$stmt->bindValue(7, define::DELETE_FLG_OFF, \PDO::PARAM_STR);
		$stmt->bindParam(8, $now, \PDO::PARAM_STR);
		$stmt->bindParam(9, $user_id, \PDO::PARAM_STR);
		$stmt->bindParam(10, $this->main->space_name, \PDO::PARAM_STR);
		$stmt->bindValue(11, null, \PDO::PARAM_STR);
		$stmt->bindValue(12, null, \PDO::PARAM_STR);
		$stmt->bindValue(13, '0', \PDO::PARAM_STR);

		// INSERT実行
		$stmt = $this->main->pdoMgr()->execute($this->main->dbh(), $stmt);
	}

	/**
	 * 公開予定テーブル更新処理メソッド
	 *
	 * 引数の公開予定IDを条件に、公開予定情報を1件更新します。
	 *
	 * @param  string $selected_id 操作対象の公開予定ID
	 * @param  array $form 入力情報
	 * @param string $gmt_reserve_datetime GMT公開予定日時
	 * @param string $user_id 	 ユーザID
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 * @throws Exception バージョンNOを確認し、すでに他ユーザにて情報が更新されている場合
	 */
	public function update_ts_reserve($selected_id, $form, $gmt_reserve_datetime, $user_id) {

		if (!$selected_id) {
			throw new \Exception('更新対象の公開予定IDが取得できませんでした。 ');
		}

		// 他ユーザで更新されていないか確認
		$selected_ret = $this->get_selected_ts_reserve($selected_id);

		$logstr = "[排他確認]データ取得時のバージョンNO：" . $form['ver_no'] . "\r\n";
		$logstr .= "[排他確認]現時点のバージョンNO：" . $selected_ret[self::RESERVE_ENTITY_VER_NO];
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		if ($selected_ret &&
			$selected_ret[self::RESERVE_ENTITY_VER_NO] != $form['ver_no']) {

			throw new \Exception('ユーザID [' . $selected_ret[self::RESERVE_ENTITY_UPDATE_USER_ID] . '] にて公開予定情報が更新されております。');
		}

		// UPDATE文作成
		$update_sql = "UPDATE ".$this->main->pdoMgr()->get_physical_table_name('TS_RESERVE')." SET " .
			self::TS_RESERVE_DATETIME 		 . " = ?, " .
			self::TS_RESERVE_BRANCH 		 . " = ?, " .
			self::TS_RESERVE_COMMIT_HASH 	 . " = ?, " .
			self::TS_RESERVE_COMMENT 		 . " = ?, " .
			self::TS_RESERVE_UPDATE_DATETIME . " = ?, " .
			self::TS_RESERVE_UPDATE_USER_ID	 . " = ?, " .
			self::TS_RESERVE_VER_NO 		 . " = ? " .
			" WHERE " . self::TS_RESERVE_ID_SEQ . " = ? ".
			"   AND " . self::TS_RESERVE_SPACE_NAME . " = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($update_sql);

		// 現在日時
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		
		// バインド引数設定
		$stmt->bindParam(1, $gmt_reserve_datetime, \PDO::PARAM_STR);
		$stmt->bindParam(2, $form['branch_select_value'], \PDO::PARAM_STR);
		$stmt->bindParam(3, $form['commit_hash'], \PDO::PARAM_STR);
		$stmt->bindParam(4, $form['comment'], \PDO::PARAM_STR);
		$stmt->bindParam(5, $now, \PDO::PARAM_STR);
		$stmt->bindParam(6, $user_id, \PDO::PARAM_STR);
		$stmt->bindValue(7, $form['ver_no'] + 1, \PDO::PARAM_STR);
		$stmt->bindParam(8, $selected_id, \PDO::PARAM_STR);
		$stmt->bindParam(9, $this->main->space_name, \PDO::PARAM_STR);

		// UPDATE実行
		$stmt = $this->main->pdoMgr()->execute($this->main->dbh(), $stmt);

	}

	/**
	 * 公開予定テーブル更新処理メソッド（ステータス更新用）
	 *
	 * 引数の公開予定IDを条件に、公開予定情報のステータスを"処理済み"へ更新します。
	 *
	 * @param  string  $selected_id 公開予定ID
	 * @param  string  $ver_no 対象のバージョン番号
	 * @return null
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 * @throws Exception バージョンNOを確認し、すでに他ユーザにて情報が更新されている場合
	 */
	public function update_ts_reserve_status($selected_id, $ver_no) {

		if (!$selected_id) {
			throw new \Exception('更新対象の公開予定IDが取得できませんでした。 ');
		}

		// 他ユーザで更新されていないか確認
		$selected_ret = $this->get_selected_ts_reserve($selected_id);

		$logstr = "[排他確認]データ取得時のバージョンNO：" . $ver_no . "\r\n";
		$logstr .= "[排他確認]現時点のバージョンNO：" . $selected_ret[self::RESERVE_ENTITY_VER_NO];
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		if ($selected_ret &&
			$selected_ret[self::RESERVE_ENTITY_VER_NO] != $ver_no) {

			throw new \Exception('ユーザID [' . $selected_ret[self::RESERVE_ENTITY_UPDATE_USER_ID] . '] にて公開予定情報が更新されております。');
		}

		// UPDATE文作成
		$update_sql = "UPDATE ".$this->main->pdoMgr()->get_physical_table_name('TS_RESERVE')." SET " .
			self::TS_RESERVE_STATUS . " = ? " .
			" WHERE " . self::TS_RESERVE_ID_SEQ . " = ? ".
			"   AND " . self::TS_RESERVE_SPACE_NAME . " = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($update_sql);

		// バインド引数設定
		$stmt->bindValue(1, '1', \PDO::PARAM_STR);
		$stmt->bindParam(2, $selected_id, \PDO::PARAM_STR);
		$stmt->bindParam(3, $this->main->space_name, \PDO::PARAM_STR);

		// UPDATE実行
		$stmt = $this->main->pdoMgr()->execute($this->main->dbh(), $stmt);
	}

	/**
	 * 公開予定テーブル論理削除処理メソッド
	 *
	 * 引数の公開予定IDを条件に、公開予定情報を削除済みへ更新します。
	 *
	 * @param  string  $user_id     ユーザID
	 * @param  string  $selected_id 公開予定ID
	 * @return null
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 */
	public function delete_reserve_table($user_id, $selected_id) {

		if (!$selected_id) {
			throw new \Exception('更新対象の公開予定IDが取得できませんでした。 ');
		}

		// UPDATE文作成
		$update_sql = "UPDATE ".$this->main->pdoMgr()->get_physical_table_name('TS_RESERVE')." SET " .
			self::TS_RESERVE_DELETE_FLG 		. " = ?, " .
			self::TS_RESERVE_UPDATE_DATETIME	. " = ?, " .
			self::TS_RESERVE_UPDATE_USER_ID		. " = ? " .
			" WHERE " . self::TS_RESERVE_ID_SEQ . " = ? " .
			"   AND " . self::TS_RESERVE_SPACE_NAME . " = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($update_sql);

		// 現在時刻
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		// バインド引数設定
		$stmt->bindValue(1, define::DELETE_FLG_ON, \PDO::PARAM_STR);
		$stmt->bindParam(2, $now, \PDO::PARAM_STR);
		$stmt->bindParam(3, $options->user_id, \PDO::PARAM_STR);
		$stmt->bindParam(4, $selected_id, \PDO::PARAM_STR);
		$stmt->bindParam(5, $this->main->space_name, \PDO::PARAM_STR);

		// UPDATE実行
		$stmt = $this->main->pdoMgr()->execute($this->main->dbh(), $stmt);
	}

	/**
	 * 公開予定テーブルの情報を変換する
	 *
	 * @param  array $array 公開予定テーブル情報
	 * @return array $conv_array 変換後の公開予定テーブル情報
	 */
	private function convert_ts_reserve_entity($array) {
	
		// ID
		$conv_array[self::RESERVE_ENTITY_ID_SEQ] 		= $array[self::TS_RESERVE_ID_SEQ];

		// 公開予定日時（GMT日時）
		$conv_array[self::RESERVE_ENTITY_RESERVE_GMT] 	= $array[self::TS_RESERVE_DATETIME];

		// 公開予定日時（タイムゾーン日時）
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

	    return $conv_array;
	}

}