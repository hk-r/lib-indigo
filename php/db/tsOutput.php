<?php

namespace indigo\db;

use indigo\define as define;

class tsOutput
{

	private $main;


	/**
	 * 公開処理結果テーブルのカラム定義
	 */
	const TS_OUTPUT_ID_SEQ 			= 'output_id_seq';			// 公開処理結果ID
	const TS_OUTPUT_RESERVE_ID 		= 'reserve_id';				// 公開予約ID
	const TS_OUTPUT_BACKUP_ID 		= 'backup_id';				// バックアップID
	const TS_OUTPUT_RESERVE 		= 'reserve_datetime';		// 公開予約日時
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
	const TS_OUTPUT_UPDATE_DATETIME = 'update_datetime';		// 更新日時
	const TS_OUTPUT_UPDATE_USER_ID 	= 'update_user_id';			// 更新ユーザID


	/**
	 * 公開処理結果エンティティのカラム定義
	 */
	const OUTPUT_ENTITY_ID_SEQ 			= 'output_id_seq';			// ID
	const OUTPUT_ENTITY_RESERVE 		= 'reserve_datetime';		// 公開予約日時（タイムゾーン日時）
	const OUTPUT_ENTITY_RESERVE_DISP 	= 'reserve_datetime_disp';	// 公開予約日時（表示用フォーマット）
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
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
	}

	/**
	 * 公開処理結果一覧テーブルからリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	public function get_ts_output_list($now) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_ts_output_list start');

		$ret_array = array();
		$conv_ret_array = array();

		// 公開処理結果テーブルから未世代削除のデータを取得
		$select_sql = "
				SELECT * FROM TS_OUTPUT
				WHERE " . self::TS_OUTPUT_GEN_DELETE_FLG . " = '0' 
				ORDER BY " . self::TS_OUTPUT_ID_SEQ . " DESC 
				LIMIT " . define::LIMIT_LIST_RECORD;

		// SELECT実行
		$ret_array = $this->main->pdoMgr()->select($this->main->get_dbh(), $select_sql);

		foreach ((array)$ret_array as $array) {
			$conv_ret_array[] = $this->convert_ts_output_entity($array);
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_ts_output_list end');

		return $conv_ret_array;
	}
	
	/**
	 * 公開処理結果テーブルから、選択された処理結果情報を取得する
	 *
	 * @return 選択行の情報
	 */
	public function get_selected_ts_output($selected_id) {


		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_output start');

		$ret_array = null;

		$conv_ret_array = null;

		if (!$selected_id) {
			throw new \Exception('選択されたIDが取得できませんでした。 ');
		} else {

			// SELECT文作成
			$select_sql = "SELECT * from TS_OUTPUT 
			WHERE " . self::TS_OUTPUT_ID_SEQ . " = " . $selected_id . ";";

			// SELECT実行
			$ret_array = $this->main->pdoMgr()->selectOne($this->main->get_dbh(), $select_sql);

			$conv_ret_array = $this->convert_ts_output_entity($ret_array);
		}
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ get_selected_ts_output end');

		return $conv_ret_array;
	}

	/**
	 * 公開処理結果一覧テーブルの登録処理
	 *
	 * @return なし
	 */
	public function insert_ts_output($dataArray) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ insert_ts_output start');

		// INSERT文作成
		$insert_sql = "INSERT INTO TS_OUTPUT ("
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
		. self::TS_OUTPUT_UPDATE_DATETIME . ","
		. self::TS_OUTPUT_UPDATE_USER_ID

		. ") VALUES (" .

		 ":" . self::TS_OUTPUT_RESERVE_ID . "," .
		 ":" . self::TS_OUTPUT_BACKUP_ID . "," .
		 ":" . self::TS_OUTPUT_RESERVE . "," .
		 ":" . self::TS_OUTPUT_BRANCH . "," .
		 ":" . self::TS_OUTPUT_COMMIT_HASH . "," .
		 ":" . self::TS_OUTPUT_COMMENT . "," .
		 ":" . self::TS_OUTPUT_PUBLISH_TYPE . "," .
		 ":" . self::TS_OUTPUT_STATUS . "," .
		 ":" . self::TS_OUTPUT_SRV_BK_DIFF_FLG . "," .
		 ":" . self::TS_OUTPUT_START . "," .
		 ":" . self::TS_OUTPUT_END . "," .
		 ":" . self::TS_OUTPUT_GEN_DELETE_FLG . "," .
		 ":" . self::TS_OUTPUT_GEN_DELETE . "," .
		 ":" . self::TS_OUTPUT_INSERT_DATETIME . "," .
		 ":" . self::TS_OUTPUT_INSERT_USER_ID . "," .
		 ":" . self::TS_OUTPUT_UPDATE_DATETIME . "," .
		 ":" . self::TS_OUTPUT_UPDATE_USER_ID

		. ");";


		// 現在時刻
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $now);
		// パラメータ作成
		$params = array(
			":" . self::TS_OUTPUT_RESERVE_ID		=> $dataArray[self::TS_OUTPUT_RESERVE_ID],
			":" . self::TS_OUTPUT_BACKUP_ID	 		=> $dataArray[self::TS_OUTPUT_BACKUP_ID],
			":" . self::TS_OUTPUT_RESERVE 			=> $dataArray[self::TS_OUTPUT_RESERVE],
			":" . self::TS_OUTPUT_BRANCH 			=> $dataArray[self::TS_OUTPUT_BRANCH],
			":" . self::TS_OUTPUT_COMMIT_HASH 		=> $dataArray[self::TS_OUTPUT_COMMIT_HASH],
			":" . self::TS_OUTPUT_COMMENT 			=> $dataArray[self::TS_OUTPUT_COMMENT],
			":" . self::TS_OUTPUT_PUBLISH_TYPE 		=> $dataArray[self::TS_OUTPUT_PUBLISH_TYPE],
			":" . self::TS_OUTPUT_STATUS 			=> $dataArray[self::TS_OUTPUT_STATUS],
			":" . self::TS_OUTPUT_SRV_BK_DIFF_FLG 	=> $dataArray[self::TS_OUTPUT_SRV_BK_DIFF_FLG],
			":" . self::TS_OUTPUT_START 			=> $dataArray[self::TS_OUTPUT_START],
			":" . self::TS_OUTPUT_END 				=> $dataArray[self::TS_OUTPUT_END],
			":" . self::TS_OUTPUT_GEN_DELETE_FLG 	=> $dataArray[self::TS_OUTPUT_GEN_DELETE_FLG],
			":" . self::TS_OUTPUT_GEN_DELETE 		=> $dataArray[self::TS_OUTPUT_GEN_DELETE],
			":" . self::TS_OUTPUT_INSERT_DATETIME	=> $now,
			":" . self::TS_OUTPUT_INSERT_USER_ID	=> $dataArray[self::TS_OUTPUT_INSERT_USER_ID],
			":" . self::TS_OUTPUT_UPDATE_DATETIME	=> null,
			":" . self::TS_OUTPUT_UPDATE_USER_ID	=> null
		);

		// INSERT実行
		$this->main->pdoMgr()->execute($this->main->get_dbh(), $insert_sql, $params);

		// 登録したシーケンスIDを取得
		$insert_id = $this->main->get_dbh()->lastInsertId();
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '　□ insert_id：' . $insert_id);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ insert_ts_output end');

		return $insert_id;
	}

	/**
	 * 公開処理結果一覧テーブルの更新処理
	 *
	 * @return なし
	 */
	public function update_ts_output($id, $dataArray) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ update_ts_output start');

		if (!$id) {
			throw new \Exception('更新対象のIDが取得できませんでした。 ');
		}

		// UPDATE文作成
		$update_sql = "UPDATE TS_OUTPUT SET " .
			self::TS_OUTPUT_STATUS 				. "= :" . self::TS_OUTPUT_STATUS . "," .
			self::TS_OUTPUT_SRV_BK_DIFF_FLG 	. "= :" . self::TS_OUTPUT_SRV_BK_DIFF_FLG . "," .
			self::TS_OUTPUT_END 				. "= :" . self::TS_OUTPUT_END . "," .
			self::TS_OUTPUT_UPDATE_DATETIME 	. "= :" . self::TS_OUTPUT_UPDATE_DATETIME . "," .
			self::TS_OUTPUT_UPDATE_USER_ID 		. "= :" . self::TS_OUTPUT_UPDATE_USER_ID .
			" WHERE " . self::TS_OUTPUT_ID_SEQ 	. "= :" . self::TS_OUTPUT_ID_SEQ . ";";

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '　□ update_sql');
		$this->main->common()->put_process_log(__METHOD__, __LINE__, $update_sql);

		// 現在時刻
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		// パラメータ作成
		$params = array(
			":" . self::TS_OUTPUT_STATUS 			=> $dataArray[self::TS_OUTPUT_STATUS],
			":" . self::TS_OUTPUT_SRV_BK_DIFF_FLG 	=> $dataArray[self::TS_OUTPUT_SRV_BK_DIFF_FLG],
			":" . self::TS_OUTPUT_END 				=> $dataArray[self::TS_OUTPUT_END],
			":" . self::TS_OUTPUT_UPDATE_DATETIME	=> $now,
			":" . self::TS_OUTPUT_UPDATE_USER_ID	=> $dataArray[self::TS_OUTPUT_UPDATE_USER_ID],
			":" . self::TS_OUTPUT_ID_SEQ			=> $id
		);

		// UPDATE実行
		$this->main->pdoMgr()->execute($this->main->get_dbh(), $update_sql, $params);

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ update_ts_output end');
	}

	/**
	 * 公開処理結果テーブルの情報を変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	private function convert_ts_output_entity($array) {
	
		// $this->main->common()->put_process_log(__METHOD__, __LINE__, '■ convert_ts_output_entity start');

		$entity = array();

		// ID
		$entity[self::OUTPUT_ENTITY_ID_SEQ] = $array[self::TS_OUTPUT_ID_SEQ];

		// 公開予約日時（タイムゾーン日時）
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::TS_OUTPUT_RESERVE]);
		$entity[self::OUTPUT_ENTITY_RESERVE] 		 = $tz_datetime;
		$entity[self::OUTPUT_ENTITY_RESERVE_DISP] = $this->main->common()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);
		// 処理開始日時（GMT日時）
		$entity[self::OUTPUT_ENTITY_START_GMT] 	= $array[self::TS_OUTPUT_START];
		// 処理開始日時（タイムゾーン日時）
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::TS_OUTPUT_START]);
		$entity[self::OUTPUT_ENTITY_START]		   = $tz_datetime;
		$entity[self::OUTPUT_ENTITY_START_DISP] = $this->main->common()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);
		// 処理終了日時（タイムゾーン日時）
		$tz_datetime = $this->main->common()->convert_to_timezone_datetime($array[self::TS_OUTPUT_END]);
		$entity[self::OUTPUT_ENTITY_END]	     = $tz_datetime;
		$entity[self::OUTPUT_ENTITY_END_DISP] = $this->main->common()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);
		// ブランチ名
		$entity[self::OUTPUT_ENTITY_BRANCH] = $array[self::TS_OUTPUT_BRANCH];
		// コミット
		$entity[self::OUTPUT_ENTITY_COMMIT_HASH] = $array[self::TS_OUTPUT_COMMIT_HASH];
		// コメント
		$entity[self::OUTPUT_ENTITY_COMMENT] = $array[self::TS_OUTPUT_COMMENT];
		// 状態
		$entity[self::OUTPUT_ENTITY_STATUS] = $array[self::TS_OUTPUT_STATUS];
		// 状態
		$entity[self::OUTPUT_ENTITY_STATUS_DISP] = $this->convert_status($array[self::TS_OUTPUT_STATUS]);
		// 公開種別
		$entity[self::OUTPUT_ENTITY_PUBLISH_TYPE] = $this->main->common()->convert_publish_type($array[self::TS_OUTPUT_PUBLISH_TYPE]);
		// 登録ユーザID
		$entity[self::OUTPUT_ENTITY_INSERT_USER_ID] = $array[self::TS_OUTPUT_INSERT_USER_ID];

		// $this->main->common()->put_process_log(__METHOD__, __LINE__, '■ convert_ts_output_entity end');

	    return $entity;
	}


	/**
	 * ステータスを画面表示用に変換し返却する
	 *	 
	 * @param $status = ステータスのコード値
	 *	 
	 * @return 画面表示用のステータス情報
	 */
	private function convert_status($status) {

		$ret = '';

		// if ($status == define::PUBLISH_STATUS_RUNNING) {
		// 	$ret =  '★（処理中）';
		// } else if ($status == define::PUBLISH_STATUS_SUCCESS) {
		// 	$ret =  '〇（成功）';
		// } else if ($status == define::PUBLISH_STATUS_ALERT) {
		// 	$ret =  '△（警告あり）';
		// } else if ($status == define::PUBLISH_STATUS_FAILED) {
		// 	$ret =  '×（失敗）';
		// } else if ($status == define::PUBLISH_STATUS_SKIP) {
		// 	$ret =  '-（スキップ）';
		// }

		if ($status == define::PUBLISH_STATUS_RUNNING) {
			$ret =  '処理中';
		} else if ($status == define::PUBLISH_STATUS_SUCCESS) {
			$ret =  '成功';
		} else if ($status == define::PUBLISH_STATUS_ALERT) {
			$ret =  '警告あり';
		} else if ($status == define::PUBLISH_STATUS_FAILED) {
			$ret =  '失敗';
		} else if ($status == define::PUBLISH_STATUS_SKIP) {
			$ret =  'スキップ';
		}

		return $ret;
	}


}