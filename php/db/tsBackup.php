<?php

namespace indigo;

class tsBackup
{

	private $main;

	private $pdoManager;
	private $common;

	// 時間フォーマット（Y-m-d）
	const DATE_FORMAT_YMD = "Y-m-d";
	// 時間フォーマット（H:i）
	const TIME_FORMAT_HI = "H:i";


	/**
	 * 公開予約テーブルのカラム定義
	 */
	const TS_BACKUP_BACKUP_ID_SEQ = 'backup_id_seq';		// ID
	const TS_BACKUP_OUTPUT_ID = 'output_id';	// 公開予約日時
	const TS_BACKUP_DATETIME = 'backup_datetime';	// ブランチ名
	const TS_BACKUP_GEN_DELETE_FLG = 'gen_delete_flg';	// コミットハッシュ値（短縮）
	const TS_BACKUP_GEN_DELETE_DATETIME = 'gen_delete_datetime';	// コメント
	const TS_BACKUP_INSERT_DATETIME = 'insert_datetime';	// 登録日時
	const TS_BACKUP_INSERT_USER_ID = 'insert_user_id';	// 登録ユーザID
	const TS_BACKUP_UPDATE_DATETIME = 'update_datetime';	// 更新日時
	const TS_BACKUP_UPDATE_USER_ID = 'update_user_id';	// 更新ユーザID
	
	/**
	 * 公開予約エンティティのカラム定義
	 */
	const BACKUP_ENTITY_ID_SEQ = 'backup_id_seq';		// ID
	const BACKUP_ENTITY_PUBLISH_TYPE = 'publish_type';	// 公開種別
	const BACKUP_ENTITY_DATETIME = 'backup_datetime';	// 公開予約日時
	const BACKUP_ENTITY_DATETIME_DISPLAY = 'backup_datetime_display';	// 公開予約日時
	// const BACKUP_ENTITY_INSERT_DATETIME = 'insert_datetime';	// 設定日時


	/**
	 * Constructor
	 *
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
		$this->pdoManager = new pdoManager($this);
		$this->common = new common($this);
	}

	/**
	 * バックアップ一覧テーブルからリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	public function get_ts_backup_list($dbh) {

		$this->common->debug_echo('■ get_ts_backup_list start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

			// SELECT文作成（削除フラグ = 0、ソート順：公開予約日時の昇順）
			$select_sql = "
					SELECT * FROM TS_BACKUP WHERE " . self::TS_BACKUP_GEN_DELETE_FLG . " = " . define::DELETE_FLG_OFF . " ORDER BY backup_datetime DESC";

			// SELECT実行
			$ret_array = $this->pdoManager->select($dbh, $select_sql);

			foreach ((array)$ret_array as $array) {

				$conv_ret_array[] = $this->convert_ts_backup_entity($array);
			}

			// $this->common->debug_echo('　□ conv_ret_array：');
			// $this->common->debug_var_dump($conv_ret_array);

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->common->debug_echo('■ get_ts_backup_list end');

		return $conv_ret_array;
	}

	/**
	 * バックアップ一覧テーブルの情報を変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	private function convert_ts_backup_entity($array) {
	
		$this->common->debug_echo('■ convert_ts_backup_entity start');

		$entity = array();

		// ID
		$entity[self::BACKUP_ENTITY_ID_SEQ] = $array[self::TS_BACKUP_BACKUP_ID_SEQ];
		
		// バックアップ日時
		// タイムゾーンの時刻へ変換
		$tz_datetime = $this->common->convert_to_timezone_datetime($array[self::TS_BACKUP_DATETIME]);
		
		$entity[self::BACKUP_ENTITY_DATETIME] = $tz_datetime;
		$entity[self::BACKUP_ENTITY_DATETIME_DISPLAY] = $this->common->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISPLAY);

		// 公開種別
		$entity[self::BACKUP_ENTITY_PUBLISH_TYPE] = $this->common->convert_publish_type($array[self::TS_OUTPUT_PUBLISH_TYPE]);


		$this->common->debug_echo('■ convert_ts_backup_entity end');

	    return $entity;
	}


	/**
	 * バックアップテーブル登録処理
	 *
	 * @return なし
	 */
	public function insert_ts_backup($dbh, $options, $backup_datetime, $output_id) {

		$this->common->debug_echo('■ insert_ts_backup start');

		$result = array('status' => true,
						'message' => '');

		try {

			// INSERT文作成
			$insert_sql = "INSERT INTO TS_RESERVE ("
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

			$this->common->debug_echo('　□ insert_sql');
			$this->common->debug_echo($insert_sql);

			// 現在時刻
			$now = $this->common->get_current_datetime_of_gmt();
			
			// パラメータ作成
			$params = array(
				":" . self::TS_BACKUP_OUTPUT_ID => $output_id,
				":" . self::TS_BACKUP_DATETIME => $backup_datetime,
				":" . self::TS_BACKUP_GEN_DELETE_FLG => define::DELETE_FLG_OFF,
				":" . self::TS_BACKUP_GEN_DELETE_DATETIME => null, 
				":" . self::TS_BACKUP_INSERT_DATETIME => $now,
				":" . self::TS_BACKUP_INSERT_USER_ID => "dummy_insert_user",
				":" . self::TS_BACKUP_UPDATE_DATETIME => null,
				":" . self::TS_BACKUP_UPDATE_DATETIME => null
			);
		
			// INSERT実行
			$stmt = $this->pdoManager->execute($dbh, $insert_sql, $params);

		} catch (Exception $e) {

	  		echo 'バックアップテーブルの登録処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->common->debug_echo('■ insert_ts_backup end');

		return json_encode($result);
	}

}