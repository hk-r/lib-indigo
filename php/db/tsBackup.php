<?php

namespace indigo\db;

use indigo\define as define;

/**
 * ts_backupテーブルのデータベース処理クラス
 *
 * ts_backupテーブルに関する処理をまとめたクラス。
 *
 */
class tsBackup
{

	private $main;

	/**
	 * バックアップテーブルのカラム定義
	 */
	const TS_BACKUP_ID_SEQ		 		= 'backup_id';				// バックアップID
	const TS_BACKUP_OUTPUT_ID 			= 'output_id';				// 公開処理結果ID
	const TS_BACKUP_DATETIME 			= 'backup_datetime';		// バックアップ日時
	const TS_BACKUP_GEN_DELETE_FLG 		= 'gen_delete_flg';			// 世代削除フラグ
	const TS_BACKUP_GEN_DELETE_DATETIME = 'gen_delete_datetime';	// 世代削除日時
	const TS_BACKUP_INSERT_DATETIME 	= 'insert_datetime';		// 登録日時
	const TS_BACKUP_INSERT_USER_ID 		= 'insert_user_id';			// 登録ユーザID
	const TS_BACKUP_SPACE_NAME	 		= 'space_name';				// 空間名
	const TS_BACKUP_UPDATE_DATETIME 	= 'update_datetime';		// 更新日時
	const TS_BACKUP_UPDATE_USER_ID 		= 'update_user_id';			// 更新ユーザID
	
	/**
	 * バックアップエンティティのカラム定義
	 */
	const BACKUP_ENTITY_ID_SEQ 			= 'backup_id';				// ID
	const BACKUP_ENTITY_DATETIME_GMT 	= 'backup_datetime_gmt';	// バックアップ日時（GMT日時）
	const BACKUP_ENTITY_DATETIME 		= 'backup_datetime';		// バックアップ日時（タイムゾーン日時）
	const BACKUP_ENTITY_DATETIME_DISP	= 'backup_datetime_disp';	// バックアップ日時（表示用フォーマット）
	const BACKUP_ENTITY_INSERT_DATETIME = 'insert_datetime';		// 登録日時
	const BACKUP_ENTITY_INSERT_USER_ID 	= 'insert_user_id';			// 登録ユーザID
	const BACKUP_ENTITY_UPDATE_DATETIME = 'update_datetime';		// 更新日時
	const BACKUP_ENTITY_UPDATE_USER_ID 	= 'update_user_id';			// 更新ユーザID

	const BACKUP_ENTITY_RESERVE 		= 'reserve_datetime';		// 公開予定日時
	const BACKUP_ENTITY_RESERVE_DISP 	= 'reserve_datetime_disp';	// 公開予定日時（表示用フォーマット）
	const BACKUP_ENTITY_BRANCH 			= 'branch_name';			// ブランチ名
	const BACKUP_ENTITY_COMMIT_HASH 	= 'commit_hash';			// コミットハッシュ値（短縮）
	const BACKUP_ENTITY_COMMENT 		= 'comment';				// コメント
	const BACKUP_ENTITY_STATUS 			= 'status';					// 状態
	const BACKUP_ENTITY_PUBLISH_TYPE 	= 'publish_type';			// 公開種別

	/**
	 * Constructor
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
	}


	/**
	 * バックアップ一覧リストの取得メソッド
	 *
	 * バックアップテーブルから未削除データをリストで取得します。
	 * バックアップ一覧画面表示用に使用しており、フォーマット変換を行い配列を返却します。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * 公開処理結果テーブルと公開処理結果IDをキーに外部結合して情報を取得しています。
	 * 
	 * ページング処理が実装されていないため、暫定処理として最大1,000件の取得としている。
	 *
	 * @return array[] $conv_ret_array
	 * 				バックアップリスト
	 */
	public function get_ts_backup_list() {

		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '■ get_ts_backup_list start');

		$select_sql = "SELECT " .
				  $this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')."." . self::TS_BACKUP_ID_SEQ 				. " as " . self::BACKUP_ENTITY_ID_SEQ . "," .
				  $this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')."." . self::TS_BACKUP_DATETIME 			. "	as " . self::BACKUP_ENTITY_DATETIME . "," .
				  $this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')."." . self::TS_BACKUP_INSERT_USER_ID 		. "	as " . self::BACKUP_ENTITY_INSERT_USER_ID . "," .
				  $this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')."." . tsOutput::TS_OUTPUT_RESERVE 		. "	as " . self::BACKUP_ENTITY_RESERVE . "," .
				  $this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')."." . tsOutput::TS_OUTPUT_BRANCH 			. "	as " . self::BACKUP_ENTITY_BRANCH . "," .
				  $this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')."." . tsOutput::TS_OUTPUT_COMMIT_HASH 	. "	as " . self::BACKUP_ENTITY_COMMIT_HASH . "," .
				  $this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')."." . tsOutput::TS_OUTPUT_COMMENT 		. "	as " . self::BACKUP_ENTITY_COMMENT . "," .
				  $this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')."." . tsOutput::TS_OUTPUT_PUBLISH_TYPE 	. " as " . self::BACKUP_ENTITY_PUBLISH_TYPE . "," .
				  $this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')."." . tsOutput::TS_OUTPUT_STATUS			. "	as " . self::BACKUP_ENTITY_STATUS .
				" FROM ".$this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')." " .
				"LEFT OUTER JOIN ".$this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')."" . // 外部結合：公開処理結果テーブル
				" 	ON ".$this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')."." 	. self::TS_BACKUP_OUTPUT_ID . " = ".$this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')."." . tsOutput::TS_OUTPUT_ID_SEQ .
				" WHERE ".$this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')."." . self::TS_BACKUP_GEN_DELETE_FLG . " = '0' " .	// 0:未削除
				"   AND ".$this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')."." . self::TS_BACKUP_SPACE_NAME . " = ".json_encode($this->main->space_name)." " .
				" ORDER BY ".$this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')."." . self::TS_BACKUP_DATETIME . " DESC " .		// バックアップ日時 降順
				" LIMIT " . define::LIMIT_LIST_RECORD;								// 最大1,000件までの取得

		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select($this->main->dbh(), $stmt);
		
		$conv_ret_array = null;
		foreach ((array)$ret_array as $array) {
			$conv_ret_array[] = $this->convert_ts_backup_entity($array);
		}
	
		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '■ get_ts_backup_list end');

		return $conv_ret_array;
	}


	/**
	 * バックアップ情報取得メソッド
	 *
	 * 引数のバックアップIDを条件に、バックアップ情報を1件取得します。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @param  string  $selected_id バックアップID
	 * @return array $ret_array バックアップ情報
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 */
	public function get_selected_ts_backup($selected_id) {


		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_backup start');

		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '[パラメタ]selected_id：' . $selected_id);

		if (!$selected_id) {
			throw new \Exception('対象のバックアップIDが正しく取得できませんでした。 ');
		}

		// SELECT文作成
		$select_sql = "SELECT * from ".$this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')." WHERE " . self::TS_BACKUP_ID_SEQ . " = ? AND ".self::TS_BACKUP_SPACE_NAME." = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// バインド引数設定
		$stmt->bindParam(1, $selected_id, \PDO::PARAM_STR);
		$stmt->bindParam(2, $this->main->space_name, \PDO::PARAM_STR);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select_one($this->main->dbh(), $stmt);

		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_backup end');

		return $ret_array;
	}


	/**
	 * バックアップ情報取得メソッド
	 *
	 * 引数の公開処理結果IDに紐づくバックアップ情報を1件取得します。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @param  string  $output_id 公開処理結果ID
	 * @return array $ret_array バックアップ情報
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 */
	public function get_selected_ts_backup_by_output_id($output_id) {


		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_backup_by_output_id start');

		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '　[パラメタ]output_id：' . $output_id);

		if (!$output_id) {
			throw new \Exception('対象の公開処理結果IDが取得できませんでした。 ');
		}

		// SELECT文作成
		$select_sql = "SELECT * from ".$this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')." 
		WHERE " . self::TS_BACKUP_OUTPUT_ID . " = ? AND ".self::TS_BACKUP_SPACE_NAME." = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// バインド引数設定
		$stmt->bindParam(1, $output_id, \PDO::PARAM_STR);
		$stmt->bindParam(2, $this->main->space_name, \PDO::PARAM_STR);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select_one($this->main->dbh(), $stmt);

		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_backup_by_output_id end');

		return $ret_array;
	}

	/**
	 * バックアップテーブル登録処理メソッド
	 *
	 * バックアップ情報を1件登録します。
	 *
	 * @param  string  $user_id ユーザID
	 * @param  string  $backup_datetime バックアップ日時
	 * @param  int     $output_id 公開処理結果ID
	 * @return int   $insert_id 登録発行されたシーケンスID
	 */
	public function insert_ts_backup($user_id, $backup_datetime, $output_id) {

		// INSERT文作成
		$insert_sql = "INSERT INTO ".$this->main->pdoMgr()->get_physical_table_name('TS_BACKUP')." ("
		. self::TS_BACKUP_ID_SEQ . ","
		. self::TS_BACKUP_OUTPUT_ID . ","
		. self::TS_BACKUP_DATETIME . ","
		. self::TS_BACKUP_GEN_DELETE_FLG . ","
		. self::TS_BACKUP_GEN_DELETE_DATETIME . ","
		. self::TS_BACKUP_INSERT_DATETIME . ","
		. self::TS_BACKUP_INSERT_USER_ID . ","
		. self::TS_BACKUP_SPACE_NAME . ","
		. self::TS_BACKUP_UPDATE_DATETIME . ","
		. self::TS_BACKUP_UPDATE_USER_ID

		. ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

		// 前処理
		$stmt = $this->main->dbh()->prepare($insert_sql);

		// 現在時刻
		$now = $this->main->utils()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		$uuid = \Ramsey\Uuid\Uuid::uuid1()->toString();

		// バインド引数設定
		$stmt->bindParam(1, $uuid, \PDO::PARAM_STR);
		$stmt->bindParam(2, $output_id, \PDO::PARAM_STR);
		$stmt->bindParam(3, $backup_datetime, \PDO::PARAM_STR);
		$stmt->bindValue(4, define::DELETE_FLG_OFF, \PDO::PARAM_STR);
		$stmt->bindValue(5, null, \PDO::PARAM_STR);
		$stmt->bindParam(6, $now, \PDO::PARAM_STR);
		$stmt->bindParam(7, $user_id, \PDO::PARAM_STR);
		$stmt->bindParam(8, $this->main->space_name, \PDO::PARAM_STR);
		$stmt->bindValue(9, null, \PDO::PARAM_STR);
		$stmt->bindValue(10, null, \PDO::PARAM_STR);

		// INSERT実行
		$this->main->pdoMgr()->execute($this->main->dbh(), $stmt);

		// 登録したシーケンスIDを取得
		// $insert_id = $this->main->dbh()->lastInsertId();
		$insert_id = $uuid;
		
		return $insert_id;
	}


	/**
	 * バックアップテーブルの情報を変換する
	 *
	 * @param  array $array バックアップテーブル情報
	 * @return array $conv_array 変換後のバックアップテーブル情報
	 */
	private function convert_ts_backup_entity($array) {
	
		// ID
		$conv_array[self::BACKUP_ENTITY_ID_SEQ] = $array[self::BACKUP_ENTITY_ID_SEQ];

		// バックアップ日時（GMT日時）
		$conv_array[self::BACKUP_ENTITY_DATETIME_GMT] = $array[self::BACKUP_ENTITY_DATETIME];
		// バックアップ日時（タイムゾーン日時）
		$tz_datetime = $this->main->utils()->convert_to_timezone_datetime($array[self::BACKUP_ENTITY_DATETIME]);
		$conv_array[self::BACKUP_ENTITY_DATETIME] = $tz_datetime;
		$conv_array[self::BACKUP_ENTITY_DATETIME_DISP] = $this->main->utils()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);

		// 公開予定日時（タイムゾーン日時）
		$tz_datetime = $this->main->utils()->convert_to_timezone_datetime($array[self::BACKUP_ENTITY_RESERVE]);
		$conv_array[self::BACKUP_ENTITY_RESERVE] = $tz_datetime;
		$conv_array[self::BACKUP_ENTITY_RESERVE_DISP] = $this->main->utils()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);

		// ブランチ名
		$conv_array[self::BACKUP_ENTITY_BRANCH] = $array[self::BACKUP_ENTITY_BRANCH];
		// コミット
		$conv_array[self::BACKUP_ENTITY_COMMIT_HASH] = $array[self::BACKUP_ENTITY_COMMIT_HASH];
		// コメント
		$conv_array[self::BACKUP_ENTITY_COMMENT] = $array[self::BACKUP_ENTITY_COMMENT];
		// 公開種別
		$conv_array[self::BACKUP_ENTITY_PUBLISH_TYPE] = $this->main->utils()->convert_publish_type($array[self::BACKUP_ENTITY_PUBLISH_TYPE]);	
		// 登録ユーザ
		$conv_array[self::BACKUP_ENTITY_INSERT_USER_ID] = $array[self::BACKUP_ENTITY_INSERT_USER_ID];

	    return $conv_array;
	}



}