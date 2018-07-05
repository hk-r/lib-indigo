<?php

namespace indigo;

class TsOutput
{

	private $main;

	private $pdo;

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
	 * 削除フラグ
	 */
	// 削除済み
	const DELETE_FLG_ON = 1;
	// 未削除
	const DELETE_FLG_OFF = 0;

	/**
	 * 公開種別
	 */
	// 予約公開
	const PUBLISH_TYPE_RESERVE = 1;
	// 復元公開
	const PUBLISH_TYPE_RESTORE = 2;
	// 即時公開
	const PUBLISH_TYPE_IMMEDIATE = 3;
	
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
		$this->pdo = new Pdo($this);
	}

	/**
	 * 公開処理結果一覧テーブルからリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	private function get_ts_output_list($dbh, $now) {

		$this->debug_echo('■ get_ts_reserve_list start');

		$ret_array = array();
		$conv_ret_array = array();

		try {

			// SELECT文作成（世代削除フラグ = 0、ソート順：IDの降順）
			$select_sql = "
					SELECT * FROM TS_OUTPUT WHERE gen_delete_flg = " . self::DELETE_FLG_OFF . " ORDER BY result_id_seq DESC";
			// SELECT実行
			$ret_array = $this->pdo->select($dbh, $select_sql);

			foreach ((array)$ret_array as $array) {
				$conv_ret_array[] = $this->main->convert_ts_output_entity($array);
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
	 * 選択された公開処理結果情報を取得する
	 *
	 * @return 選択行の情報
	 */
	private function get_selected_ts_output($dbh, $selected_id) {


		$this->debug_echo('■ get_selected_ts_output start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

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
				$ret_array = array_shift($this->pdo->select($dbh, $select_sql));

				$conv_ret_array = $this->main->convert_ts_output_entity($ret_array);


				// $this->debug_echo('　□ SELECTデータ：');
				// $this->debug_var_dump($ret_array);
			}

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->debug_echo('■ get_selected_ts_output end');

		return $conv_ret_array;
	}
	
	/**
	 * 公開処理結果一覧テーブルの登録処理
	 *
	 * @return なし
	 */
	public function insert_ts_output($dbh, $options, $start_datetime) {

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
			$now = $this->main->get_current_datetime_of_gmt();
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
			$stmt = $this->pdo->execute($dbh, $insert_sql, $params);
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
	private function update_ts_output($dbh, $id) {

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
				$now = $this->main->get_current_datetime_of_gmt();

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
				$stmt = $this->pdo->execute($dbh, $update_sql, $params);
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