<?php

namespace indigo\db;

use indigo\define as define;

class tsBackup
{

	private $main;

	
	/**
	 * バックアップテーブルのカラム定義
	 */
	const TS_BACKUP_ID_SEQ		 		= 'backup_id_seq';			// バックアップID
	const TS_BACKUP_OUTPUT_ID 			= 'output_id';				// 公開処理結果ID
	const TS_BACKUP_DATETIME 			= 'backup_datetime';		// バックアップ日時
	const TS_BACKUP_GEN_DELETE_FLG 		= 'gen_delete_flg';			// 世代削除フラグ
	const TS_BACKUP_GEN_DELETE_DATETIME = 'gen_delete_datetime';	// 世代削除日時
	const TS_BACKUP_INSERT_DATETIME 	= 'insert_datetime';		// 登録日時
	const TS_BACKUP_INSERT_USER_ID 		= 'insert_user_id';			// 登録ユーザID
	const TS_BACKUP_UPDATE_DATETIME 	= 'update_datetime';		// 更新日時
	const TS_BACKUP_UPDATE_USER_ID 		= 'update_user_id';			// 更新ユーザID
	


	/**
	 * バックアップエンティティのカラム定義
	 */
	const BACKUP_ENTITY_ID_SEQ 			= 'backup_id_seq';			// ID
	const BACKUP_ENTITY_DATETIME_GMT 	= 'backup_datetime_gmt';	// バックアップ日時（GMT日時）
	const BACKUP_ENTITY_DATETIME 		= 'backup_datetime';		// バックアップ日時（タイムゾーン日時）
	const BACKUP_ENTITY_DATETIME_DISP	= 'backup_datetime_disp';	// バックアップ日時（表示用フォーマット）
	const BACKUP_ENTITY_INSERT_DATETIME = 'insert_datetime';		// 登録日時
	const BACKUP_ENTITY_INSERT_USER_ID 	= 'insert_user_id';			// 登録ユーザID
	const BACKUP_ENTITY_UPDATE_DATETIME = 'update_datetime';		// 更新日時
	const BACKUP_ENTITY_UPDATE_USER_ID 	= 'update_user_id';			// 更新ユーザID

	const BACKUP_ENTITY_RESERVE 		= 'reserve_datetime';		// 公開予約日時
	const BACKUP_ENTITY_RESERVE_DISP 	= 'reserve_datetime_disp';	// 公開予約日時（表示用フォーマット）
	const BACKUP_ENTITY_BRANCH 			= 'branch_name';			// ブランチ名
	const BACKUP_ENTITY_COMMIT_HASH 	= 'commit_hash';			// コミットハッシュ値（短縮）
	const BACKUP_ENTITY_COMMENT 		= 'comment';				// コメント
	const BACKUP_ENTITY_STATUS 			= 'status';					// 状態
	const BACKUP_ENTITY_PUBLISH_TYPE 	= 'publish_type';			// 公開種別

	/**
	 * Constructor
	 *
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
	}

	/**
	 * バックアップ一覧テーブルからリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	public function get_ts_backup_list() {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_ts_backup_list start');

		$ret_array = null;
		$conv_ret_array = null;

		// バックアップテーブル 外部結合 公開処理結果テーブル
		$select_sql = "
				SELECT 
				  TS_BACKUP." . self::TS_BACKUP_ID_SEQ 				. " as " . self::BACKUP_ENTITY_ID_SEQ . ",
				  TS_BACKUP." . self::TS_BACKUP_DATETIME 			. "	as " . self::BACKUP_ENTITY_DATETIME . ",
				  TS_BACKUP." . self::TS_BACKUP_INSERT_USER_ID 		. "	as " . self::BACKUP_ENTITY_INSERT_USER_ID . ",
				  TS_OUTPUT." . tsOutput::TS_OUTPUT_RESERVE 		. "	as " . self::BACKUP_ENTITY_RESERVE . ",
				  TS_OUTPUT." . tsOutput::TS_OUTPUT_BRANCH 			. "	as " . self::BACKUP_ENTITY_BRANCH . ",
				  TS_OUTPUT." . tsOutput::TS_OUTPUT_COMMIT_HASH 	. "	as " . self::BACKUP_ENTITY_COMMIT_HASH . ",
				  TS_OUTPUT." . tsOutput::TS_OUTPUT_COMMENT 		. "	as " . self::BACKUP_ENTITY_COMMENT . ",
				  TS_OUTPUT." . tsOutput::TS_OUTPUT_PUBLISH_TYPE 	. " as " . self::BACKUP_ENTITY_PUBLISH_TYPE . ",
				  TS_OUTPUT." . tsOutput::TS_OUTPUT_STATUS			. "	as " . self::BACKUP_ENTITY_STATUS .  
				" FROM TS_BACKUP 
				LEFT OUTER JOIN TS_OUTPUT
					ON TS_BACKUP." 	. self::TS_BACKUP_OUTPUT_ID . " = TS_OUTPUT." . tsOutput::TS_OUTPUT_ID_SEQ .
				" WHERE TS_BACKUP." . self::TS_BACKUP_GEN_DELETE_FLG . " = '0' " .
				" ORDER BY TS_BACKUP." . self::TS_BACKUP_DATETIME . " DESC";

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->select($this->main->get_dbh(), $select_sql);

		foreach ((array)$ret_array as $array) {
			$conv_ret_array[] = $this->convert_ts_backup_entity($array);
		}
	
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_ts_backup_list end');

		return $conv_ret_array;
	}


	/**
	 * バックアップテーブルから、選択されたバックアップ情報を取得する
	 *
	 * @return 選択行の情報
	 */
	public function get_selected_ts_backup($selected_id) {


		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_backup start');

		$ret_array = null;
		$conv_ret_array = null;

		if (!$selected_id) {
			throw new \Exception('選択されたIDが取得できませんでした。 ');
		}

		// SELECT文作成
		$select_sql = "SELECT * from TS_BACKUP 
		WHERE " . self::TS_BACKUP_ID_SEQ . " = " . $selected_id . ";";

		// SELECT実行
		// $get_array = array_shift($this->main->pdoMgr()->select($dbh, $select_sql));
		$ret_array = $this->main->pdoMgr()->selectOne($this->main->get_dbh(), $select_sql);

		// foreach ( (array) $get_array as $data) {
			// $ret_array = array_shift($data);
			$conv_ret_array = $this->convert_ts_backup_entity($ret_array);
		// }

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_backup end');

		return $conv_ret_array;
	}

	/**
	 * バックアップテーブルから公開処理結果IDを条件に情報を取得する
	 *
	 * @return 選択行の情報
	 */
	public function get_selected_ts_backup_by_output_id($output_id) {


		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_backup_by_output_id start');

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '　□ output_id：' . $output_id);

		$ret_array = null;
		$conv_ret_array = null;

		if (!$output_id) {
			throw new \Exception('復元対象の公開処理結果IDが取得できませんでした。 ');
		}

		// SELECT文作成
		$select_sql = "SELECT * from TS_BACKUP 
		WHERE " . self::TS_BACKUP_OUTPUT_ID . " = " . $output_id . ";";

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->selectOne($this->main->get_dbh(), $select_sql);

		// foreach ( (array) $get_array as $data) {
			// $ret_array = array_shift($data);
			$conv_ret_array = $this->convert_ts_backup_entity($ret_array);
		// }

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_backup_by_output_id end');

		return $conv_ret_array;
	}

	/**
	 * バックアップテーブル登録処理
	 *
	 * @return なし
	 */
	public function insert_ts_backup($options, $backup_datetime, $output_id) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ insert_ts_backup start');

		// INSERT文作成
		$insert_sql = "INSERT INTO TS_BACKUP ("
		. self::TS_BACKUP_OUTPUT_ID . ","
		. self::TS_BACKUP_DATETIME . ","
		. self::TS_BACKUP_GEN_DELETE_FLG . ","
		. self::TS_BACKUP_GEN_DELETE_DATETIME . ","
		. self::TS_BACKUP_INSERT_DATETIME . ","
		. self::TS_BACKUP_INSERT_USER_ID . ","
		. self::TS_BACKUP_UPDATE_DATETIME . ","
		. self::TS_BACKUP_UPDATE_USER_ID

		. ") VALUES (" .

		 ":" . self::TS_BACKUP_OUTPUT_ID . "," .
		 ":" . self::TS_BACKUP_DATETIME . "," .
		 ":" . self::TS_BACKUP_GEN_DELETE_FLG . "," .
		 ":" . self::TS_BACKUP_GEN_DELETE_DATETIME . "," .
		 ":" . self::TS_BACKUP_INSERT_DATETIME . "," .
		 ":" . self::TS_BACKUP_INSERT_USER_ID . "," .
		 ":" . self::TS_BACKUP_UPDATE_DATETIME . "," .
		 ":" . self::TS_BACKUP_UPDATE_USER_ID

		. ");";

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '　□ insert_sql');
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $insert_sql);

		// 現在時刻
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		
		// パラメータ作成
		$params = array(
			":" . self::TS_BACKUP_OUTPUT_ID 			=> $output_id,
			":" . self::TS_BACKUP_DATETIME 				=> $backup_datetime,
			":" . self::TS_BACKUP_GEN_DELETE_FLG 		=> define::DELETE_FLG_OFF,
			":" . self::TS_BACKUP_GEN_DELETE_DATETIME 	=> null, 
			":" . self::TS_BACKUP_INSERT_DATETIME 		=> $now,
			":" . self::TS_BACKUP_INSERT_USER_ID 		=> $options->user_id,
			":" . self::TS_BACKUP_UPDATE_DATETIME 		=> null,
			":" . self::TS_BACKUP_UPDATE_USER_ID		=> null
		);
	
		// INSERT実行
		$this->main->pdoMgr()->execute($this->main->get_dbh(), $insert_sql, $params);

		// 登録したシーケンスIDを取得
		$insert_id = $this->main->get_dbh()->lastInsertId();
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '　□ insert_id：' . $insert_id);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ insert_ts_backup end');

		return $insert_id;
	}

	/**
	 * バックアップ一覧テーブルの情報を変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	private function convert_ts_backup_entity($array) {
	
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ convert_ts_backup_entity start');

		$entity = array();

		// ID
		$entity[self::BACKUP_ENTITY_ID_SEQ] = $array[self::BACKUP_ENTITY_ID_SEQ];
		// バックアップ日時（GMT日時）
		$entity[self::BACKUP_ENTITY_DATETIME_GMT] = $array[self::BACKUP_ENTITY_DATETIME];
		// バックアップ日時（タイムゾーン日時）
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::BACKUP_ENTITY_DATETIME]);
		$entity[self::BACKUP_ENTITY_DATETIME] = $tz_datetime;
		$entity[self::BACKUP_ENTITY_DATETIME_DISP] = $this->main->common()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);

		// 公開予約日時（タイムゾーン日時）
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::BACKUP_ENTITY_RESERVE]);
		$entity[self::BACKUP_ENTITY_RESERVE] = $tz_datetime;
		$entity[self::BACKUP_ENTITY_RESERVE_DISP] = $this->main->common()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);

		// ブランチ名
		$entity[self::BACKUP_ENTITY_BRANCH] = $array[self::BACKUP_ENTITY_BRANCH];
		// コミット
		$entity[self::BACKUP_ENTITY_COMMIT_HASH] = $array[self::BACKUP_ENTITY_COMMIT_HASH];
		// コメント
		$entity[self::BACKUP_ENTITY_COMMENT] = $array[self::BACKUP_ENTITY_COMMENT];
		// 公開種別
		$entity[self::BACKUP_ENTITY_PUBLISH_TYPE] = $this->main->common()->convert_publish_type($array[self::BACKUP_ENTITY_PUBLISH_TYPE]);	
		// 登録ユーザ
		$entity[self::BACKUP_ENTITY_INSERT_USER_ID] = $array[self::BACKUP_ENTITY_INSERT_USER_ID];

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ convert_ts_backup_entity end');

	    return $entity;
	}



}