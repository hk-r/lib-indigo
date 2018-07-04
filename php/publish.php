<?php

namespace indigo;

class publish
{
	public $options;

	private $file_control;

	private $pdo;

	private $main;

	/**
	 * PDOインスタンス
	 */
	private $dbh;

	// サーバのタイムゾーン
	const PARAM_TIME_ZONE = 'Asia/Tokyo';
	// サーバのタイムゾーン
	const GMT = 'GMT';

	// 日時フォーマット（Y-m-d H:i:s）
	const DATETIME_FORMAT = "Y-m-d H:i:s";
	// 時間フォーマット（Y-m-d）
	const DATE_FORMAT_YMD = "Y-m-d";
	// 時間フォーマット（H:i）
	const TIME_FORMAT_HI = "H:i";
	// 時間フォーマット（H:i:s）
	const TIME_FORMAT_HIS = "H:i:s";

	// 日時フォーマット_表示用（Y-m-d H:i）
	const DATETIME_FORMAT_DISPLAY = "Y-m-d H:i";
	// 日時フォーマット_保存用（YmdHis）
	const DATETIME_FORMAT_SAVE = "YmdHis";

	/**
	 * 画像パス定義
	 */
	// 右矢印
	const IMG_ARROW_RIGHT = '/images/arrow_right.png';
	// エラーアイコン
	const IMG_ERROR_ICON = '/images/error_icon.png';


	/**
	 * 入力モード
	 */
	// 追加モード
	const INPUT_MODE_ADD = 1;
	// 追加戻り表示モード
	const INPUT_MODE_ADD_BACK = 2;
	// 更新モード
	const INPUT_MODE_UPDATE = 3;
	// 更新戻り表示モード
	const INPUT_MODE_UPDATE_BACK = 4;
	// 即時公開モード
	const INPUT_MODE_SOKUJI = 5;
	// 即時公開戻り表示モード
	const INPUT_MODE_SOKUJI_BACK = 6;

	/**
	 * 公開種別
	 */
	// 予約公開
	const PUBLISH_TYPE_RESERVE = 1;
	// 復元公開
	const PUBLISH_TYPE_RESTORE = 2;
	// 即時公開
	const PUBLISH_TYPE_SOKUJI = 3;


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
	 * 公開用の操作ディレクトリパス定義
	 */
	// backupディレクトリパス
	const PATH_BACKUP = '/backup/';
	// waitingディレクトリパス
	const PATH_WAITING = '/waiting/';
	// runnningディレクトリパス
	const PATH_RUNNING = '/running/';
	// releasedディレクトリパス
	const PATH_RELEASED = '/released/';
	// logディレクトリパス
	const PATH_LOG = '/log/';


	// 生成ディレクトリパス（後々パラメタ化する）
	const PATH_CREATE_DIR = './../indigo_dir/';
	// 本番パス（後々パラメタ化する）
	const PATH_PROJECT_DIR = './../../indigo-test-project/';

	/**
	 * 公開予約管理CSVの列番号定義
	 */
	const TS_RESERVE_COLUMN_ID = 'reserve_id_seq';		// ID
	const TS_RESERVE_COLUMN_RESERVE = 'reserve_datetime';	// 公開予約日時
	const TS_RESERVE_COLUMN_BRANCH = 'branch_name';	// ブランチ名
	const TS_RESERVE_COLUMN_COMMIT = 'commit_hash';	// コミットハッシュ値（短縮）
	const TS_RESERVE_COLUMN_COMMENT = 'comment';	// コメント
	const TS_RESERVE_COLUMN_INSERT_DATETIME = 'insert_datetime';	// 設定日時

	/**
	 * 公開予約管理CSVの列番号定義
	 */
	const RESERVE_ENTITY_ID = 'reserve_id_seq';		// ID
	const RESERVE_ENTITY_RESERVE = 'reserve_datetime';	// 公開予約日時
	const RESERVE_ENTITY_RESERVE_DISPLAY = 'reserve_display';	// 公開予約日時
	const RESERVE_ENTITY_RESERVE_DATE = 'reserve_date';	// 公開予約日時
	const RESERVE_ENTITY_RESERVE_TIME = 'reserve_time';	// 公開予約日時
	const RESERVE_ENTITY_BRANCH = 'branch_name';	// ブランチ名
	const RESERVE_ENTITY_COMMIT = 'commit_hash';	// コミットハッシュ値（短縮）
	const RESERVE_ENTITY_COMMENT = 'comment';	// コメント
	const RESERVE_ENTITY_INSERT_DATETIME = 'insert_datetime';	// 設定日時




	/**
	 * 公開実施管理CSVの列番号定義
	 */
	const TS_RESULT_COLUMN_ID = 'result_id_seq';			// ID
	const TS_RESULT_COLUMN_RESERVE = 'reserve_datetime';		// 公開予約日時
	const TS_RESULT_COLUMN_BRANCH = 'branch_name';		// ブランチ名
	const TS_RESULT_COLUMN_COMMIT = 'commit_hash';		// コミットハッシュ値（短縮）
	const TS_RESULT_COLUMN_COMMENT = 'comment';		// コメント
	// const TS_RESULT_COLUMN_SETTING = '';		// 設定日時

	const TS_RESULT_COLUMN_STATUS = 'status';		// 状態
	const TS_RESULT_COLUMN_TYPE = 'publish_type';		// 公開種別

	const TS_RESULT_COLUMN_START = 'start_datetime';		// 公開処理開始日時
	const TS_RESULT_COLUMN_END = 'end_datetime';			// 公開処理終了日時

	const TS_RESULT_COLUMN_RELEASED = 8;		// 公開完了日時
	const TS_RESULT_COLUMN_RESTORE = 9;		// 復元完了日時

	const TS_RESULT_COLUMN_DIFF_FLG1 = 10;	// 差分フラグ1（本番環境と前回分の差分）
	const TS_RESULT_COLUMN_DIFF_FLG2 = 11;	// 差分フラグ2（本番環境と今回分の差分）
	const TS_RESULT_COLUMN_DIFF_FLG3 = 12;	// 差分フラグ3（前回分と今回分の差分）

	/**
	 * 公開実施管理CSVの列番号定義
	 */
	const RESULT_ENTITY_ID = 'result_id_seq';			// ID
	const RESULT_ENTITY_RESERVE = 'reserve_datetime';		// 公開予約日時
	const RESULT_ENTITY_RESERVE_DISPLAY = 'reserve_display';	// 公開予約日時
	const RESULT_ENTITY_BRANCH = 'branch_name';		// ブランチ名
	const RESULT_ENTITY_COMMIT = 'commit_hash';		// コミットハッシュ値（短縮）
	const RESULT_ENTITY_COMMENT = 'comment';		// コメント
	// const RESULT_ENTITY_SETTING = '';		// 設定日時

	const RESULT_ENTITY_STATUS = 'status';		// 状態
	const RESULT_ENTITY_TYPE = 'publish_type';		// 公開種別

	const RESULT_ENTITY_START = 'start_datetime';		// 公開処理開始日時
	const RESULT_ENTITY_START_DISPLAY = 'start_display';	// 公開処理開始日時
	const RESULT_ENTITY_END = 'end_datetime';			// 公開処理終了日時
	const RESULT_ENTITY_END_DISPLAY = 'end_display';	// 公開処理終了日時

	const RESULT_ENTITY_RELEASED = 8;		// 公開完了日時
	const RESULT_ENTITY_RESTORE = 9;		// 復元完了日時

	const RESULT_ENTITY_DIFF_FLG1 = 10;	// 差分フラグ1（本番環境と前回分の差分）
	const RESULT_ENTITY_DIFF_FLG2 = 11;	// 差分フラグ2（本番環境と今回分の差分）
	const RESULT_ENTITY_DIFF_FLG3 = 12;	// 差分フラグ3（前回分と今回分の差分）

	// const HONBAN_REALPATH = '/var/www/html/indigo-test-project/';
	const HONBAN_REALPATH = '/var/www/html/test';
	
	// 削除済み
	const DELETE_FLG_ON = 1;
	// 未削除
	const DELETE_FLG_OFF = 0;

	// const DEFINE_MAX_RECORD = 10;		// 公開予約できる最大件数
	// const DEFINE_MAX_GENERATION = 10;	// 差分フラグ2（本番環境と今回分の差分）
	// const DEFINE_MAX_RECORD = 12;		// 差分フラグ3（前回分と今回分の差分）

	/**
	 * コミットハッシュ値
	 */
	private $commit_hash = '';

	/**
	 * 入力画面のエラーメッセージ
	 */
	private $input_error_message = '';

	/**
	 * 本番環境ディレクトリパス（仮）
	 */
	private $honban_path = './../honban/';

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;
		$this->file_control = new file_control($this);
		$this->pdo = new pdo($this);

		$this->debug_echo('★publishクラスのコンストラクタ起動！');
	}


	/**
	 * GMTの現在時刻を取得
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	private function get_current_datetime_of_gmt() {

		return gmdate(DATE_ATOM, time());
	}


	/**
	 * タイムゾーンの現在時刻を取得
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	private function get_current_datetime_of_timezone() {

		return date(DATE_ATOM, time());
	}

	

	/**
	 * 時限公開処理
	 */
	public function jigen_release() {

		$this->debug_echo('■ jigen_release start');

		$is_windows = true;

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$project_real_path = '';

		// GMTの現在日時
		$start_datetime = gmdate(self::DATETIME_FORMAT);
		$start_datetime_dir = gmdate(self::DATETIME_FORMAT_SAVE);

		try {

		error_log(print_r($start_datetime, TRUE), 3, '/var/www/html/sample-lib-indigo/indigo_dir/log/output.log');

		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			$this->debug_echo('■ sokuji_release error end');

			chdir($current_dir);
			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->debug_echo('■ jigen_release end');

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

