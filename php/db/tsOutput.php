<?php

namespace indigo\db;

use indigo\define as define;

/**
 * ts_outputテーブルのデータベース処理クラス
 *
 * ts_outputテーブルに関する処理をまとめたクラス。
 *
 */
class tsOutput
{

	private $main;


	/**
	 * 公開処理結果テーブルのカラム定義
	 */
	const TS_OUTPUT_ID_SEQ 			= 'output_id_seq';			// 公開処理結果ID
	const TS_OUTPUT_RESERVE_ID 		= 'reserve_id';				// 公開予定ID
	const TS_OUTPUT_BACKUP_ID 		= 'backup_id';				// バックアップID
	const TS_OUTPUT_RESERVE 		= 'reserve_datetime';		// 公開予定日時
	const TS_OUTPUT_BRANCH 			= 'branch_name';			// ブランチ名
	const TS_OUTPUT_COMMIT_HASH 	= 'commit_hash';			// コミットハッシュ値（短縮）
	const TS_OUTPUT_COMMENT 		= 'comment';				// コメント
	const TS_OUTPUT_PUBLISH_TYPE	= 'publish_type';			// 公開種別
	const TS_OUTPUT_STATUS 			= 'status';					// 状態
	const TS_OUTPUT_SRV_BK_DIFF_FLG = 'srv_bk_diff_flg';		// 本番と最新バックアップの差分有無
	const TS_OUTPUT_START		 	= 'start_datetime';			// 公開処理開始日時
	const TS_OUTPUT_END 			= 'end_datetime';			// 公開処理終了日時
	const TS_OUTPUT_GEN_DELETE_FLG 	= 'gen_delete_flg';			// 世代削除フラグ
	const TS_OUTPUT_GEN_DELETE 		= 'gen_delete_datetime';	// 世代削除日時
	const TS_OUTPUT_INSERT_DATETIME = 'insert_datetime';		// 登録日時
	const TS_OUTPUT_INSERT_USER_ID 	= 'insert_user_id';			// 登録ユーザID
	const TS_OUTPUT_SPACE_NAME	 	= 'space_name';				// 空間名
	const TS_OUTPUT_UPDATE_DATETIME = 'update_datetime';		// 更新日時
	const TS_OUTPUT_UPDATE_USER_ID 	= 'update_user_id';			// 更新ユーザID


	/**
	 * 公開処理結果エンティティのカラム定義
	 */
	const OUTPUT_ENTITY_ID_SEQ 			= 'output_id_seq';			// ID
	const OUTPUT_ENTITY_BACKUP_ID 		= 'backup_id';				// バックアップID
	const OUTPUT_ENTITY_RESERVE 		= 'reserve_datetime';		// 公開予定日時（タイムゾーン日時）
	const OUTPUT_ENTITY_RESERVE_DISP 	= 'reserve_datetime_disp';	// 公開予定日時（表示用フォーマット）
	const OUTPUT_ENTITY_BRANCH 			= 'branch_name';			// ブランチ名
	const OUTPUT_ENTITY_COMMIT_HASH 	= 'commit_hash';			// コミットハッシュ値（短縮）
	const OUTPUT_ENTITY_PUBLISH_TYPE 	= 'publish_type';			// 公開種別
	const OUTPUT_ENTITY_COMMENT 		= 'comment';				// コメント
	const OUTPUT_ENTITY_STATUS 			= 'status';					// 状態
	const OUTPUT_ENTITY_STATUS_DISP		= 'status_disp';			// 状態（表示用）
	const OUTPUT_ENTITY_SRV_BK_DIFF_FLG = 'srv_bk_diff_flg';		// 本番と最新バックアップの差分有無
	const OUTPUT_ENTITY_START_GMT 		= 'start_datetime_gmt';		// 公開処理開始日時（GMT日時）
	const OUTPUT_ENTITY_START 			= 'start_datetime';			// 公開処理開始日時（タイムゾーン日時）
	const OUTPUT_ENTITY_START_DISP 		= 'start_datetime_disp';	// 公開処理開始日時（表示用フォーマット）
	const OUTPUT_ENTITY_END 			= 'end_datetime';			// 公開処理終了日時
	const OUTPUT_ENTITY_END_DISP 		= 'end_datetime_disp';		// 公開処理終了日時（表示用フォーマット）
	const OUTPUT_ENTITY_INSERT_DATETIME = 'insert_datetime';		// 登録日時
	const OUTPUT_ENTITY_INSERT_USER_ID 	= 'insert_user_id';			// 登録ユーザID
	const OUTPUT_ENTITY_UPDATE_DATETIME = 'update_datetime';		// 更新日時
	const OUTPUT_ENTITY_UPDATE_USER_ID 	= 'update_user_id';			// 更新ユーザID
	
	/**
	 * Constructor
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
	}


	/**
	 * 公開処理結果一覧リストの取得メソッド
	 *
	 * 公開処理結果テーブルから未削除データをリストで取得します。
	 * 履歴表示画面表示用に使用しており、フォーマット変換を行い配列を返却します。
	 * 該当データが存在しない場合はnullを返却します。
	 * 
	 * ページング処理が実装されていないため、暫定処理として最大1,000件の取得としている。
	 *
	 * @return array[] $conv_ret_array
	 * 				公開処理結果リスト
	 */
	public function get_ts_output_list() {

		$select_sql = "SELECT * FROM ".$this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')." " .
				"WHERE " . self::TS_OUTPUT_GEN_DELETE_FLG . " = '0' " .	// 0:未削除
				" AND " . self::TS_OUTPUT_SPACE_NAME . " = ".json_encode($this->main->space_name)." " .
				"ORDER BY " . self::TS_OUTPUT_ID_SEQ . " DESC "	.		// 処理結果ID 降順
				"LIMIT " . define::LIMIT_LIST_RECORD;					// 最大1,000件までの取得

		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select($this->main->dbh(), $stmt);

		$conv_ret_array = null;
		foreach ((array)$ret_array as $array) {
			$conv_ret_array[] = $this->convert_ts_output_entity($array);
		}

		return $conv_ret_array;
	}


	/**
	 * 公開処理結果情報取得メソッド
	 *
	 * 引数の公開処理結果IDを条件に、公開処理結果情報を1件取得します。
	 * フォーマット変換を行い返却します。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @param  string  $selected_id 公開処理結果ID
	 * @return array $conv_ret_array 変換後の公開処理結果情報
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 */
	public function get_selected_ts_output($selected_id) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_output start');

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '[パラメタ]selected_id：' . $selected_id);

		if (!$selected_id) {
			throw new \Exception('対象の公開処理結果IDが正しく取得できませんでした。');
		}

		// SELECT文作成
		$select_sql = "SELECT * from ".$this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')." 
		WHERE " . self::TS_OUTPUT_ID_SEQ  . " = ? AND " . self::TS_OUTPUT_SPACE_NAME  . " = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($select_sql);

		// バインド引数設定
		$stmt->bindParam(1, $selected_id, \PDO::PARAM_INT);
		$stmt->bindParam(2, $this->main->space_name, \PDO::PARAM_STR);

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->execute_select_one($this->main->dbh(), $stmt);

		$conv_ret_array = $this->convert_ts_output_entity($ret_array);
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_output end');

		return $conv_ret_array;
	}

	/**
	 * 公開処理結果テーブル登録処理メソッド
	 *
	 * 公開処理結果情報を1件登録します。
	 *
	 * @param  array $dataArray 公開処理結果情報
	 * @return int   $insert_id 登録発行されたシーケンスID
	 */
	public function insert_ts_output($dataArray) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ insert_ts_output start');

		// INSERT文作成
		$insert_sql = "INSERT INTO ".$this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')." ("
		. self::TS_OUTPUT_RESERVE_ID . ","
		. self::TS_OUTPUT_BACKUP_ID . ","
		. self::TS_OUTPUT_RESERVE . ","
		. self::TS_OUTPUT_BRANCH . ","
		. self::TS_OUTPUT_COMMIT_HASH . ","
		. self::TS_OUTPUT_COMMENT . ","
		. self::TS_OUTPUT_PUBLISH_TYPE . ","
		. self::TS_OUTPUT_STATUS . ","
		. self::TS_OUTPUT_SRV_BK_DIFF_FLG . ","
		. self::TS_OUTPUT_START . ","
		. self::TS_OUTPUT_END . ","
		. self::TS_OUTPUT_GEN_DELETE_FLG . ","
		. self::TS_OUTPUT_GEN_DELETE . ","
		. self::TS_OUTPUT_INSERT_DATETIME . ","
		. self::TS_OUTPUT_INSERT_USER_ID . ","
		. self::TS_OUTPUT_SPACE_NAME . ","
		. self::TS_OUTPUT_UPDATE_DATETIME . ","
		. self::TS_OUTPUT_UPDATE_USER_ID

		. ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

		// 前処理
		$stmt = $this->main->dbh()->prepare($insert_sql);

		// 現在日時
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		// バインド引数設定
		$stmt->bindParam(1, $dataArray[self::TS_OUTPUT_RESERVE_ID], \PDO::PARAM_STR);
		$stmt->bindParam(2, $dataArray[self::TS_OUTPUT_BACKUP_ID], \PDO::PARAM_STR);
		$stmt->bindParam(3, $dataArray[self::TS_OUTPUT_RESERVE], \PDO::PARAM_STR);
		$stmt->bindParam(4, $dataArray[self::TS_OUTPUT_BRANCH], \PDO::PARAM_STR);
		$stmt->bindParam(5, $dataArray[self::TS_OUTPUT_COMMIT_HASH], \PDO::PARAM_STR);
		$stmt->bindParam(6, $dataArray[self::TS_OUTPUT_COMMENT], \PDO::PARAM_STR);
		$stmt->bindParam(7, $dataArray[self::TS_OUTPUT_PUBLISH_TYPE], \PDO::PARAM_STR);
		$stmt->bindParam(8, $dataArray[self::TS_OUTPUT_STATUS], \PDO::PARAM_STR);
		$stmt->bindParam(9, $dataArray[self::TS_OUTPUT_SRV_BK_DIFF_FLG], \PDO::PARAM_STR);
		$stmt->bindParam(10, $dataArray[self::TS_OUTPUT_START], \PDO::PARAM_STR);
		$stmt->bindParam(11, $dataArray[self::TS_OUTPUT_END], \PDO::PARAM_STR);
		$stmt->bindParam(12, $dataArray[self::TS_OUTPUT_GEN_DELETE_FLG], \PDO::PARAM_STR);
		$stmt->bindParam(13, $dataArray[self::TS_OUTPUT_GEN_DELETE], \PDO::PARAM_STR);
		$stmt->bindParam(14, $now, \PDO::PARAM_STR);
		$stmt->bindParam(15, $dataArray[self::TS_OUTPUT_INSERT_USER_ID], \PDO::PARAM_STR);
		$stmt->bindParam(16, $dataArray[self::TS_OUTPUT_SPACE_NAME], \PDO::PARAM_STR);
		$stmt->bindValue(17, null, \PDO::PARAM_STR);
		$stmt->bindValue(18, null, \PDO::PARAM_STR);

		// INSERT実行
		$stmt = $this->main->pdoMgr()->execute($this->main->dbh(), $stmt);

		// 登録したシーケンスIDを取得
		$insert_id = $this->main->dbh()->lastInsertId();
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ insert_ts_output end');

		return $insert_id;
	}

	/**
	 * 公開処理結果テーブル更新処理メソッド
	 *
	 * 引数の公開予定IDを条件に、公開処理結果情報を1件更新します。
	 *
	 * @param  int   $output_id 公開処理結果ID
	 * @param  array $dataArray 公開処理結果情報
	 * @return null
	 * 
	 * @throws Exception パラメタの値が正しく設定されていない場合
	 */
	public function update_ts_output($output_id, $dataArray) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ update_ts_output start');

		if (!$output_id) {
			throw new \Exception('更新対象の公開処理結果IDが取得できませんでした。 ');
		}

		// UPDATE文作成
		$update_sql = "UPDATE ".$this->main->pdoMgr()->get_physical_table_name('TS_OUTPUT')." SET " .
			self::TS_OUTPUT_STATUS 				. " = ?, " .
			self::TS_OUTPUT_SRV_BK_DIFF_FLG 	. " = ?, " .
			self::TS_OUTPUT_END 				. " = ?, " .
			self::TS_OUTPUT_UPDATE_DATETIME 	. " = ?, " .
			self::TS_OUTPUT_UPDATE_USER_ID 		. " = ? " .
			" WHERE " . self::TS_OUTPUT_ID_SEQ 	. " = ? ".
			"   AND " . self::TS_OUTPUT_SPACE_NAME . " = ?;";

		// 前処理
		$stmt = $this->main->dbh()->prepare($update_sql);

		// 現在日時
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		
		// バインド引数設定
		$stmt->bindParam(1, $dataArray[self::TS_OUTPUT_STATUS], \PDO::PARAM_STR);
		$stmt->bindParam(2, $dataArray[self::TS_OUTPUT_SRV_BK_DIFF_FLG], \PDO::PARAM_STR);
		$stmt->bindParam(3, $dataArray[self::TS_OUTPUT_END], \PDO::PARAM_STR);
		$stmt->bindParam(4, $now, \PDO::PARAM_STR);
		$stmt->bindParam(5, $dataArray[self::TS_OUTPUT_UPDATE_USER_ID], \PDO::PARAM_STR);
		$stmt->bindParam(6, $output_id, \PDO::PARAM_INT);
		$stmt->bindParam(7, $this->main->space_name, \PDO::PARAM_STR);

		// UPDATE実行
		$stmt = $this->main->pdoMgr()->execute($this->main->dbh(), $stmt);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ update_ts_output end');
	}


	/**
	 * 公開処理結果テーブルの情報を変換する
	 *
	 * @param  array $array 公開処理結果テーブル情報
	 * @return array $conv_array 変換後の公開処理結果テーブル情報
	 */
	private function convert_ts_output_entity($array) {
	
		$conv_array = array();
		
		// ID
		$conv_array[self::OUTPUT_ENTITY_ID_SEQ] = $array[self::TS_OUTPUT_ID_SEQ];

		// バックアップID
		$conv_array[self::OUTPUT_ENTITY_BACKUP_ID] = $array[self::TS_OUTPUT_BACKUP_ID];

		// 公開予定日時（タイムゾーン日時）
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::TS_OUTPUT_RESERVE]);
		$conv_array[self::OUTPUT_ENTITY_RESERVE] 		 = $tz_datetime;
		$conv_array[self::OUTPUT_ENTITY_RESERVE_DISP] = $this->main->common()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);

		// 処理開始日時（GMT日時）
		$conv_array[self::OUTPUT_ENTITY_START_GMT] 	= $array[self::TS_OUTPUT_START];
		// 処理開始日時（タイムゾーン日時）
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::TS_OUTPUT_START]);
		$conv_array[self::OUTPUT_ENTITY_START]		   = $tz_datetime;
		$conv_array[self::OUTPUT_ENTITY_START_DISP] = $this->main->common()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);

		// 処理終了日時（タイムゾーン日時）
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::TS_OUTPUT_END]);
		$conv_array[self::OUTPUT_ENTITY_END]	     = $tz_datetime;
		$conv_array[self::OUTPUT_ENTITY_END_DISP] = $this->main->common()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);

		// ブランチ名
		$conv_array[self::OUTPUT_ENTITY_BRANCH] = $array[self::TS_OUTPUT_BRANCH];
		// コミット
		$conv_array[self::OUTPUT_ENTITY_COMMIT_HASH] = $array[self::TS_OUTPUT_COMMIT_HASH];
		// コメント
		$conv_array[self::OUTPUT_ENTITY_COMMENT] = $array[self::TS_OUTPUT_COMMENT];
		// 状態
		$conv_array[self::OUTPUT_ENTITY_STATUS] = $array[self::TS_OUTPUT_STATUS];
		// 状態
		$conv_array[self::OUTPUT_ENTITY_STATUS_DISP] = $this->main->common()->convert_status($array[self::TS_OUTPUT_STATUS]);
		// 公開種別
		$conv_array[self::OUTPUT_ENTITY_PUBLISH_TYPE] = $this->main->common()->convert_publish_type($array[self::TS_OUTPUT_PUBLISH_TYPE]);
		// 登録ユーザID
		$conv_array[self::OUTPUT_ENTITY_INSERT_USER_ID] = $array[self::TS_OUTPUT_INSERT_USER_ID];

	    return $conv_array;
	}

}