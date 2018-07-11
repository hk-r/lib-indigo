<?php

namespace indigo;

class tsReserve
{

	private $main;

	private $pdoManager;
	private $common;

	// 時間フォーマット（Y-m-d）
	const DATE_FORMAT_YMD = "Y-m-d";
	// 時間フォーマット（H:i）
	const TIME_FORMAT_HI = "H:i";
	
	// 日時フォーマット_表示用（Y-m-d H:i）
	const DATETIME_FORMAT_DISPLAY = "Y-m-d H:i";

	/**
	 * 削除フラグ
	 */
	// 削除済み
	const DELETE_FLG_ON = 1;
	// 未削除
	const DELETE_FLG_OFF = 0;

	/**
	 * 公開予約テーブルのカラム定義
	 */
	const TS_RESERVE_RESERVE_ID_SEQ = 'reserve_id_seq';		// ID
	const TS_RESERVE_RESERVE = 'reserve_datetime';	// 公開予約日時
	const TS_RESERVE_BRANCH = 'branch_name';	// ブランチ名
	const TS_RESERVE_COMMIT = 'commit_hash';	// コミットハッシュ値（短縮）
	const TS_RESERVE_COMMENT = 'comment';	// コメント
	const TS_RESERVE_DELETE_FLG = 'delete_flg';	// 削除フラグ
	const TS_RESERVE_INSERT_DATETIME = 'insert_datetime';	// 登録日時
	const TS_RESERVE_INSERT_USER_ID = 'insert_user_id';	// 登録ユーザID
	const TS_RESERVE_UPDATE_DATETIME = 'update_datetime';	// 更新日時
	const TS_RESERVE_UPDATE_USER_ID = 'update_user_id';	// 更新ユーザID
	
	/**
	 * 公開予約エンティティのカラム定義
	 */
	const RESERVE_ENTITY_ID_SEQ = 'reserve_id_seq';		// ID
	const RESERVE_ENTITY_RESERVE = 'reserve_datetime';	// 公開予約日時
	const RESERVE_ENTITY_RESERVE_DISPLAY = 'reserve_display';	// 公開予約日時
	const RESERVE_ENTITY_RESERVE_DATE = 'reserve_date';	// 公開予約日時
	const RESERVE_ENTITY_RESERVE_TIME = 'reserve_time';	// 公開予約日時
	const RESERVE_ENTITY_BRANCH = 'branch_name';	// ブランチ名
	const RESERVE_ENTITY_COMMIT = 'commit_hash';	// コミットハッシュ値（短縮）
	const RESERVE_ENTITY_COMMENT = 'comment';	// コメント
	const RESERVE_ENTITY_INSERT_DATETIME = 'insert_datetime';	// 設定日時


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
	 * 公開予約一覧テーブルからリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	public function get_ts_reserve_list($dbh) {

		$this->common->debug_echo('■ get_ts_reserve_list start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

			// SELECT文作成（削除フラグ = 0、ソート順：公開予約日時の昇順）
			$select_sql = "
					SELECT * FROM TS_RESERVE WHERE delete_flg = " . self::DELETE_FLG_OFF . " ORDER BY reserve_datetime";

			// SELECT実行
			$ret_array = $this->pdoManager->select($dbh, $select_sql);

			foreach ((array)$ret_array as $array) {

				$conv_ret_array[] = $this->convert_ts_reserve_entity($array);
			}

			// $this->common->debug_echo('　□ conv_ret_array：');
			// $this->common->debug_var_dump($conv_ret_array);

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
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

		try {

			// if ($now) {
			// 	$option_param = " and reserve_datetime <= '" . $now . "'";
			// }

			// SELECT文作成（削除フラグ = 0、公開予約日時>=現在日時、ソート順：公開予約日時の降順）
			$select_sql = "
					SELECT * FROM TS_RESERVE WHERE delete_flg = " . self::DELETE_FLG_OFF . $option_param . " ORDER BY reserve_datetime DESC;";

			$this->common->debug_echo('　□ select_sql');
			$this->common->debug_echo($select_sql);

			// SELECT実行
			$ret_array = $this->pdoManager->select($dbh, $select_sql);

			$this->common->debug_echo('　□ ret_array');
			$this->common->debug_var_dump($ret_array);

			foreach ((array)$ret_array as $array) {
				$this->common->debug_echo('　★ループ内');

				$conv_ret_array[] = $this->convert_ts_reserve_entity($array);
			}

			// $this->common->debug_echo('　□ conv_ret_array：');
			// $this->common->debug_var_dump($conv_ret_array);

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
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

		$this->common->debug_echo('　□ selected_id：' . $selected_id);

		$ret_array = array();

		$conv_ret_array = array();

		try {

			if (!$selected_id) {
				$this->common->debug_echo('選択値が取得できませんでした。');
			} else {

				// SELECT文作成
				$select_sql = "SELECT * from TS_RESERVE
					WHERE reserve_id_seq = ". $selected_id;

				// // パラメータ作成
				// $params = array(
				// 	':id' => $selected_id
				// );

				// SELECT実行
				$ret_array = array_shift($this->pdoManager->select($dbh, $select_sql));

				$conv_ret_array = $this->convert_ts_reserve_entity($ret_array);

				// $this->common->debug_echo('　□ SELECTデータ：');
				// $this->common->debug_var_dump($ret_array);
			}

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->common->debug_echo('■ get_selected_ts_reserve end');

		return $conv_ret_array;
	}

	/**
	 * 公開予約テーブル登録処理
	 *
	 * @return なし
	 */
	public function insert_ts_reserve($dbh, $options, $commit_hash) {

		$this->common->debug_echo('■ insert_ts_reserve start');

		$result = array('status' => true,
						'message' => '');

		try {

			// INSERT文作成
			$insert_sql = "INSERT INTO TS_RESERVE ("
			. self::TS_RESERVE_RESERVE . ","
			. self::TS_RESERVE_BRANCH . ","
			. self::TS_RESERVE_COMMIT . ","
			. self::TS_RESERVE_COMMENT . ","
			. self::TS_RESERVE_DELETE_FLG . ","
			. self::TS_RESERVE_INSERT_DATETIME . ","
			. self::TS_RESERVE_INSERT_USER_ID . ","
			. self::TS_RESERVE_UPDATE_DATETIME . ","
			. self::TS_RESERVE_UPDATE_USER_ID

			. ") VALUES (" .

			 ":" . self::TS_RESERVE_RESERVE . "," .
			 ":" . self::TS_RESERVE_BRANCH . "," .
			 ":" . self::TS_RESERVE_COMMIT . "," .
			 ":" . self::TS_RESERVE_COMMENT . "," .
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
				":" . self::TS_RESERVE_RESERVE => $options->_POST->gmt_reserve_datetime,
				":" . self::TS_RESERVE_BRANCH => $options->_POST->branch_select_value,
				":" . self::TS_RESERVE_COMMIT => $commit_hash,
				":" . self::TS_RESERVE_COMMENT => $options->_POST->comment,
				":" . self::TS_RESERVE_DELETE_FLG => self::DELETE_FLG_OFF,
				":" . self::TS_RESERVE_INSERT_DATETIME => $now,
				":" . self::TS_RESERVE_INSERT_USER_ID => "dummy_insert_user",
				":" . self::TS_RESERVE_UPDATE_DATETIME => null,
				":" . self::TS_RESERVE_UPDATE_USER_ID => null
			);
		
			// INSERT実行
			$stmt = $this->pdoManager->execute($dbh, $insert_sql, $params);

		} catch (Exception $e) {

	  		echo '公開予約テーブルの登録処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->common->debug_echo('■ insert_ts_reserve end');

		return json_encode($result);
	}

	/**
	 * 公開予約テーブル更新処理
	 *
	 * @return なし
	 */
	public function update_reserve_table($dbh, $options, $selected_id, $commit_hash) {

		$this->common->debug_echo('■ update_reserve_table start');

		$result = array('status' => true,
						'message' => '');

		try {

			$this->common->debug_echo('　□ selected_id：' . $selected_id);

			if (!$selected_id) {
				$this->common->debug_echo('選択IDが取得できませんでした。');
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
				$now = $this->common->get_current_datetime_of_gmt();

				// パラメータ作成
				$params = array(
					':reserve_datetime' => $options->_POST->gmt_reserve_datetime,
					':branch_name' => $options->_POST->branch_select_value,
					':commit_hash' => $commit_hash,
					':comment' => $options->_POST->comment,
					':update_datetime' => $now,
					':update_user_id' => "dummy_update_user",
					':reserve_id_seq' => $selected_id
				);

				// UPDATE実行
				$stmt = $this->pdoManager->execute($dbh, $update_sql, $params);
			}

		} catch (Exception $e) {

	  		echo '公開予約テーブルの更新処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->common->debug_echo('■ update_reserve_table end');

		return json_encode($result);
	}

	/**
	 * 公開予約テーブル論理削除処理
	 *
	 * @return なし
	 */
	public function delete_reserve_table($dbh, $selected_id) {

		$this->common->debug_echo('■ delete_reserve_table start');

		$result = array('status' => true,
						'message' => '');

		try {

			$this->common->debug_echo('　□ selected_id：' . $selected_id);

			if (!$selected_id) {
				$this->common->debug_echo('選択IDが取得できませんでした。');
			} else {

				// UPDATE文作成（論理削除）
				$update_sql = "UPDATE TS_RESERVE SET 
					delete_flg = :delete_flg,
					update_datetime = :update_datetime,
					update_user_id = :update_user_id 
					WHERE reserve_id_seq = :reserve_id_seq";

				// 現在時刻
				// $now = date(self::DATETIME_FORMAT);
				$now = $this->common->get_current_datetime_of_gmt();

				// パラメータ作成
				$params = array(
					':delete_flg' => self::DELETE_FLG_ON,
					':update_datetime' => $now,
					':update_user_id' => "dummy_delete_user",
					':reserve_id_seq' => $selected_id
				);

				// UPDATE実行
				$stmt = $this->pdoManager->execute($dbh, $update_sql, $params);
			}

		} catch (Exception $e) {

	  		echo '公開予約テーブルの論理削除処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->common->debug_echo('■ delete_reserve_table end');

		return json_encode($result);
	}

	/**
	 * 公開予約テーブルの情報を変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	private function convert_ts_reserve_entity($array) {
	
		$this->common->debug_echo('■ convert_ts_reserve_entity start');

		$entity = array();

		// ID
		$entity[self::RESERVE_ENTITY_ID_SEQ] = $array[self::TS_RESERVE_RESERVE_ID_SEQ];
		// 公開予約日時
		// タイムゾーンの時刻へ変換
		$tz_datetime = $this->common->convert_to_timezone_datetime($array[self::TS_RESERVE_RESERVE]);
		$entity[self::RESERVE_ENTITY_RESERVE] = $tz_datetime;
		$entity[self::RESERVE_ENTITY_RESERVE_DISPLAY] = $this->common->format_datetime($tz_datetime, self::DATETIME_FORMAT_DISPLAY);
		$entity[self::RESERVE_ENTITY_RESERVE_DATE] = $this->common->format_datetime($tz_datetime, self::DATE_FORMAT_YMD);
		$entity[self::RESERVE_ENTITY_RESERVE_TIME] = $this->common->format_datetime($tz_datetime, self::TIME_FORMAT_HI);
		// ブランチ
		$entity[self::RESERVE_ENTITY_BRANCH] = $array[self::TS_RESERVE_BRANCH];
		// コミット
		$entity[self::RESERVE_ENTITY_COMMIT] = $array[self::TS_RESERVE_COMMIT];
		// コメント
		$entity[self::RESERVE_ENTITY_COMMENT] = $array[self::TS_RESERVE_COMMENT];
	
		$this->common->debug_echo('■ convert_ts_reserve_entity end');

	    return $entity;
	}


}