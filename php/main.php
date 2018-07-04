<?php

namespace indigo;

class main
{
	public $options;

	private $file_control;

	private $pdo;

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
	public function __construct($options) {

		$this->options = json_decode(json_encode($options));
		$this->file_control = new file_control($this);
		$this->pdo = new pdo($this);
	}

	/**
	 * Gitのmaster情報を取得
	 */
	private function init() {

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$master_path = $this->options->git->repository;

		set_time_limit(0);

		try {

			if ( strlen($master_path) ) {

				// デプロイ先のディレクトリが無い場合は作成
				if ( !file_exists( $master_path ) ) {
					// 存在しない場合

					// ディレクトリ作成
					if ( !mkdir( $master_path, 0777, true ) ) {
						// ディレクトリが作成できない場合

						// エラー処理
						throw new \Exception('Creation of master directory failed.');
					}
					
					// コマンドでディレクトリを作成する場合
					// exec('mkdir -p ' . $master_path . '2>&1', $output, $return_var);
				}

				// 「.git」フォルダが存在すれば初期化済みと判定
				if ( !file_exists( $master_path . "/.git") ) {
					// 存在しない場合

					// ディレクトリ移動
					if ( chdir( $master_path ) ) {

						// git セットアップ
						$command = 'git init';
						$this->command_execute($command, false);

						// git urlのセット
						$url = $this->options->git->protocol . "://" . urlencode($this->options->git->username) . ":" . urlencode($this->options->git->password) . "@" . $this->options->git->url;

						$command = 'git remote add origin ' . $url;
						$this->command_execute($command, false);

						// git fetch
						$command = 'git fetch origin';
						$this->command_execute($command, false);

						// git pull
						$command = 'git pull origin master';
						$this->command_execute($command, false);

					} else {
						// ディレクトリが存在しない場合

						// エラー処理
						throw new \Exception('master directory not found.');
					}
				}
			}

		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);
		return json_encode($result);
	}

	/**
	 * ブランチリストを取得
	 *	 
	 * @return 指定リポジトリ内のブランチリストを返す
	 */
	private function get_branch_list() {


		$this->debug_echo('■ get_branch_list start');

		$current_dir = realpath('.');
		// echo $current_dir;

		$output_array = array();
		$result = array('status' => true,
						'message' => '');

		try {

			if ( chdir( $this->options->git->repository )) {

				// fetch
				$command = 'git fetch';
				$this->command_execute($command, false);

				// ブランチの一覧取得
				$command = 'git branch -r';
				$ret = $this->command_execute($command, false);

				foreach ((array)$ret['output'] as $key => $value) {
					if( strpos($value, '/HEAD') !== false ){
						continue;
					}
					$output_array[] = trim($value);
				}

				$result['branch_list'] = $output_array;

			} else {
				// 指定リポジトリのディレクトリが存在しない場合

				// エラー処理
				throw new \Exception('Repository directory not found.');
			}

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		$result['status'] = true;

		chdir($current_dir);


		$this->debug_echo('■ get_branch_list end');
		return json_encode($result);

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

		if ($status == self::PUBLISH_STATUS_RUNNING) {
		
			$ret =  '？(処理中)';
		
		} else if ($status == self::PUBLISH_STATUS_SUCCESS) {
			
			$ret =  '〇(公開成功)';

		} else if ($status == self::PUBLISH_STATUS_ALERT) {
			
			$ret =  '△(警告あり)';

		} else if ($status == self::PUBLISH_STATUS_FAILED) {
			
			$ret =  '×(公開失敗)';
			
		} else if ($status == self::PUBLISH_STATUS_SKIP) {
			
			$ret =  '-(スキップ)';
			
		}

		return $ret;
	}


	/**
	 * 公開種別を画面表示用に変換し返却する
	 *	 
	 * @param $publish_type = 公開種別のコード値
	 *	 
	 * @return 画面表示用のステータス情報
	 */
	private function convert_publish_type($publish_type) {

		$ret = '';

		if ($publish_type == self::PUBLISH_TYPE_RESERVE) {
		
			$ret =  '予約公開';
		
		} else if ($publish_type == self::PUBLISH_TYPE_RESTORE) {
			
			$ret =  '復元公開';

		} else if ($publish_type == self::PUBLISH_TYPE_SOKUJI) {
			
			$ret =  '即時公開';

		}

		return $ret;
	}

	/**
	 * プルダウンで選択状態とさせる値であるか比較する
	 *	 
	 * @param $selected = 選択状態とする値
	 * @param $value    = 比較対象の値
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	private function compare_to_selected_value($selected, $value) {

		$ret = "";

		if (!empty($selected) && $selected == $value) {
			// 選択状態とする
			$ret = "selected";
		}

		return $ret;
	}


	/**
	 * 公開予約の最大件数チェック
	 *	 
	 * @param $data_list       = データリスト
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	private function check_reserve_max_record($data_list) {

		$ret = true;

		// TODO:定数化
		$max = 10;

		if ($max <= count($data_list)) {
			$ret = false;
		}

		return $ret;
	}

	/**
	 * 日付の妥当性チェック
	 *	 
	 * @param $reserve_date  = 公開予約日時
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	private function check_date($reserve_date) {

		$ret = true;

		// 日付の妥当性チェック
		list($Y, $m, $d) = explode('-', $reserve_date);

		if (!checkdate(intval($m), intval($d), intval($Y))) {
			$ret = false;
		}	

		return $ret;
	}

	/**
	 * 日付未来チェック
	 *	 
	 * @param $datetime       = 公開予約日時の日付
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	private function check_future_date($datetime) {

		$ret = true;

		// GMTの現在日時
		$now = $this->get_current_datetime_of_timezone();

		if (strtotime($now) > strtotime($datetime)) {
			$ret = false;
		}	

		return $ret;
	}


	/**
	 * ブランチ重複チェック
	 *	 
	 * @param $data_list       = データリスト
	 * @param $selected_branch = 選択されたブランチ
	 * @param $selected_id   = 変更ID
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	private function check_exist_branch($data_list, $selected_branch, $selected_id) {

		$ret = true;

		foreach ((array)$data_list as $array) {
			
			if (($array[self::RESERVE_ENTITY_ID] != $selected_id) && ($array[self::RESERVE_ENTITY_BRANCH] == $selected_branch)) {
				$ret = false;
				break;
			}
		}		

		return $ret;
	}

	/**
	 * 公開予約日時重複チェック
	 *	 
	 * @param $data_list     = データリスト
	 * @param $input_reserve = 入力された日時
	 * @param $selected_id   = 変更ID
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	private function check_exist_reserve($data_list, $input_reserve, $selected_id) {

		$ret = true;

		foreach ((array)$data_list as $array) {
			if (($array[self::RESERVE_ENTITY_ID] != $selected_id) && ($array[self::RESERVE_ENTITY_RESERVE] == $input_reserve)) {
				$ret = false;
				break;
			}
		}		

		return $ret;
	}


	/**
	 * 日付と時間を結合（Y-m-i H:i:s）
	 *	 
	 * @param $date = 日付
	 * @param $time = 時間
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	private function combine_date_time($date, $time) {

		$ret = '';

		if (isset($date) && isset($time)) {

			$ret = $date . ' ' . $this->format_datetime($time, self::TIME_FORMAT_HIS);
		}

		return $ret;
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
	 * 新規ダイアログの表示
	 *	 
	 * @return 新規ダイアログの出力内容
	 */
	private function disp_add_dialog() {
		
		$this->debug_echo('■ disp_add_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(self::INPUT_MODE_ADD, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_add_dialog end');

		return $ret;
	}


	/**
	 * 新規ダイアログの戻り表示
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	private function disp_back_add_dialog() {
		
		$this->debug_echo('■ disp_back_add_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_select_value)) {
			$branch_select_value = $this->options->_POST->branch_select_value;
		}
		if (isset($this->options->_POST->reserve_date)) {
			$reserve_date = $this->options->_POST->reserve_date;
		}
		if (isset($this->options->_POST->reserve_time)) {
			$reserve_time = $this->options->_POST->reserve_time;
		}
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}

		// 入力ダイアログHTMLの作成
		$ret = $this->create_dialog_html(self::INPUT_MODE_ADD_BACK, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_back_add_dialog end');

		return $ret;
	}

	/**
	 * 変更ダイアログの表示
	 *	 
	 *
	 * @return 変更ダイアログの出力内容
	 */
	private function disp_update_dialog() {
		
		$this->debug_echo('■ disp_update_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// // 選択されたID
		// $selected_id =  $this->options->_POST->selected_id;

		// 画面選択された公開予約情報を取得
		$selected_data = $this->get_selected_reserve_data();
		
		$this->debug_echo('　□ 公開予約データ');
		$this->debug_var_dump($selected_data);
		$this->debug_echo('　');

		if ($selected_data) {

			$branch_select_value = $selected_data[self::RESERVE_ENTITY_BRANCH];

			$reserve_date = $selected_data[self::RESERVE_ENTITY_RESERVE_DATE];
			$reserve_time = $selected_data[self::RESERVE_ENTITY_RESERVE_TIME];

			$comment = $selected_data[self::RESERVE_ENTITY_COMMENT];
		}

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(self::INPUT_MODE_UPDATE, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_update_dialog end');

		return $ret;
	}


	/**
	 * 変更ダイアログの戻り表示
	 *	 
	 * @param $error_message  = エラーメッセージ出力内容
	 *
	 * @return 変更ダイアログの出力内容
	 */
	private function disp_back_update_dialog() {
		
		$this->debug_echo('■ disp_back_update_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_select_value)) {
			$branch_select_value = $this->options->_POST->branch_select_value;
		}
		if (isset($this->options->_POST->reserve_date)) {
			$reserve_date = $this->options->_POST->reserve_date;
		}
		if (isset($this->options->_POST->reserve_time)) {
			$reserve_time = $this->options->_POST->reserve_time;
		}
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}
	
		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(self::INPUT_MODE_UPDATE_BACK, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_back_update_dialog end');

		return $ret;
	}

	/**
	 * 即時公開ダイアログの表示
	 *	 
	 * @return 即時公開ダイアログの出力内容
	 */
	private function disp_sokuji_dialog() {
		
		$this->debug_echo('■ disp_sokuji_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(self::INPUT_MODE_SOKUJI, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_sokuji_dialog end');

		return $ret;
	}


	/**
	 * 即時ダイアログの戻り表示
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	private function disp_back_sokuji_dialog() {
		
		$this->debug_echo('■ disp_back_sokuji_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_select_value)) {
			$branch_select_value = $this->options->_POST->branch_select_value;
		}
		if (isset($this->options->_POST->reserve_date)) {
			$reserve_date = $this->options->_POST->reserve_date;
		}
		if (isset($this->options->_POST->reserve_time)) {
			$reserve_time = $this->options->_POST->reserve_time;
		}
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}

		// 入力ダイアログHTMLの作成
		$ret = $this->create_dialog_html(self::INPUT_MODE_SOKUJI_BACK, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_back_sokuji_dialog end');

		return $ret;
	}


	/**
	 * 新規・変更の入力ダイアログHTMLの作成
	 *	 
	 * @param $add_flg       = 新規フラグ
	 * @param $error_message = エラーメッセージ出力内容
	 * @param $branch_list   = ブランチリスト
	 * @param $branch_select_value = ブランチ選択値
	 * @param $reserve_date = 公開予約日時
	 * @param $reserve_time = 公開予約時間
	 * @param $comment      = コメント
	 * @param $selected_id  = 変更時の選択ID
	 *
	 * @return 
	 *  入力ダイアログ出力内容
	 */
	private function create_dialog_html($input_mode, $branch_select_value, $reserve_date, $reserve_time, $comment) {
		
		$this->debug_echo('■ create_dialog_html start');

		$ret = "";

		$ret .= '<div class="dialog" id="modal_dialog">'
			  . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			  . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			  . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			  . '<div class="dialog_box">';

		 if ($this->input_error_message) {
		 // エラーメッセージの出力
			$ret .= '<div class="alert_box">'
				. $this->input_error_message
				. '</div>';
		 }

		// 入力モードによってタイトル変更
		if ( ($input_mode == self::INPUT_MODE_ADD) || ($input_mode == self::INPUT_MODE_ADD_BACK)) {
			$ret .= '<h4>新規</h4>';

		} elseif ( ($input_mode == self::INPUT_MODE_UPDATE) || ($input_mode == self::INPUT_MODE_UPDATE_BACK) ) {
		  	$ret .= '<h4>変更</h4>';

		} elseif ( ($input_mode == self::INPUT_MODE_SOKUJI) || ($input_mode == self::INPUT_MODE_SOKUJI_BACK) ) {
		  	$ret .= '<h4>即時公開</h4>';

		} else {
			throw new \Exception("Input mode is not found.");
		}

		$ret .= '<form method="post">';

		// // 変更前の値をhidden項目に保持させる
		// if ( $input_mode == self::INPUT_MODE_UPDATE ) {
		//   	$ret .= $this->create_change_before_hidden_html($init_trans_flg)
		// }

		$ret .= '<input type="hidden" name="selected_id" value="' . $this->options->_POST->selected_id . '"/>';

		$ret .= '<table class="table table-striped">'
			  . '<tr>';

		// 「ブランチ」項目
		$ret .= '<td class="dialog_thead">ブランチ</td>'
			  . '<td><select id="branch_list" class="form-control" name="branch_select_value">';

				// ブランチリストを取得
				$get_branch_ret = json_decode($this->get_branch_list());
				$branch_list = $get_branch_ret->branch_list;

				foreach ((array)$branch_list as $branch) {
					$ret .= '<option value="' . htmlspecialchars($branch) . '" ' . $this->compare_to_selected_value($branch_select_value, $branch) . '>' . htmlspecialchars($branch) . '</option>';
				}

		$ret .= '</select></td>'
			  . '</tr>';
		
		// 「コミット」項目
		// $ret .= '<tr>'
			  // . '<td class="dialog_thead">コミット</td>'
			  // . '<td>' . 'dummy' . '</td>'
			  // . '</tr>'

		// 「公開予約日時」項目
		if ( ($input_mode == self::INPUT_MODE_SOKUJI) || ($input_mode == self::INPUT_MODE_SOKUJI_BACK) ) {

			$ret .= '<tr>'
				  . '<td class="dialog_thead">公開予約日時</td>'
				  . '<td scope="row"><span style="margin-right:10px;color:#B61111">即時</span></td>'
				  . '</tr>';
		
		} else {

			$ret .= '<tr>'
				  . '<td class="dialog_thead">公開予約日時</td>'
				  . '<td scope="row"><span style="margin-right:10px;"><input type="text" id="datepicker" name="reserve_date" value="'. $reserve_date . '" autocomplete="off" /></span>'
				  . '<input type="time" id="reserve_time" name="reserve_time" value="'. $reserve_time . '" /></td>'
				  . '</tr>';
		}

		// 「コメント」項目
		$ret .= '<tr>'
			  . '<td class="dialog_thead">コメント</td>'
			  . '<td><input type="text" id="comment" name="comment" size="50" value="' . htmlspecialchars($comment) . '" /></td>'
			  . '</tr>'
			  . '</tbody></table>'

			  . '<div class="button_contents_box">'
			  . '<div class="button_contents">'
			  . '<ul>';
		
		// 「確認」ボタン（入力モードによってidとnameを変更）
		if ( ($input_mode == self::INPUT_MODE_ADD) || ($input_mode == self::INPUT_MODE_ADD_BACK)) {
			$ret .= '<li><input type="submit" id="add_check_btn" name="add_check" class="px2-btn px2-btn--primary" value="確認"/></li>';

		} elseif ( ($input_mode == self::INPUT_MODE_UPDATE) || ($input_mode == self::INPUT_MODE_UPDATE_BACK) ) {
		  	$ret .= '<li><input type="submit" id="update_check_btn" name="update_check" class="px2-btn px2-btn--primary" value="確認"/></li>';

		} elseif ( ($input_mode == self::INPUT_MODE_SOKUJI) ||  ($input_mode == self::INPUT_MODE_SOKUJI_BACK) ) {
		  	$ret .= '<li><input type="submit" id="sokuji_check_btn" name="sokuji_check" class="px2-btn px2-btn--danger" value="確認"/></li>';

		} else {
			throw new \Exception("Input mode is not found.");
		}

		// 「キャンセル」ボタン
		$ret .= '<li><input type="submit" id="close_btn" class="px2-btn" value="キャンセル"/></li>';
		
		$ret .= '</ul>'
			  . '</div>'
			  . '</div>'
			  . '</form>'
			  . '</div>'

			  . '</div>'
			  . '</div>'
			  . '</div></div>';

		$this->debug_echo('■ create_dialog_html end');

		return $ret;
	}

	// /**
	//  * 変更前hidden項目HTMLの作成
	//  *	 
	//  * @param $add_flg       = 新規フラグ
	//  * @param $error_message = エラーメッセージ出力内容
	//  * @param $branch_list   = ブランチリスト
	//  * @param $branch_select_value = ブランチ選択値
	//  * @param $reserve_date = 公開予約日時
	//  * @param $reserve_time = 公開予約時間
	//  * @param $comment      = コメント
	//  * @param $selected_id  = 変更時の選択ID
	//  *
	//  * @return 
	//  *  入力ダイアログ出力内容
	//  */
	// private function create_change_before_hidden_html($init_trans_flg) {
		
	// 	$this->debug_echo('■ create_change_before_hidden_html start');

	// 	$this->debug_echo('　□$init_trans_flg：' . $init_trans_flg);

	// 	$selected_id = '';
	// 	$branch_select_value = '';
	// 	$reserve_date = '';
	// 	$reserve_time = '';
	// 	$comment = '';

	// 	// 初期画面からの遷移の場合、CSVから変更の情報を取得する
	// 	if ($init_trans_flg) {

	// 		// 選択されたID
	// 		$selected_id = $this->options->_POST->selected_id;
	// 		// 選択されたIDに紐づく情報を取得
	// 		$selected_ret = $this->get_selected_reserve_data();
			
	// 		$this->debug_echo('　□selected_ret ：' . $selected_ret);
	// 		$this->debug_echo($selected_ret);

	// 		if ($selected_ret) {
	// 			$branch_select_value = $selected_ret[self::TS_RESERVE_COLUMN_BRANCH];
	// 			$reserve_date = date(self::DATE_FORMAT_YMD,  strtotime($selected_ret[self::TS_RESERVE_COLUMN_RESERVE]));
	// 			$reserve_time = date(self::TIME_FORMAT_HI,  strtotime($selected_ret[self::TS_RESERVE_COLUMN_RESERVE]));
	// 			$comment = $selected_ret[self::TS_RESERVE_COLUMN_COMMENT];
	// 		}
			
	// 	} else {

	// 		$selected_id =  $this->options->_POST->selected_id;		
	// 		$branch_select_value = $this->options->_POST->change_before_branch_select_value;
	// 		$reserve_date = $this->options->_POST->change_before_reserve_date;
	// 		$reserve_time = $this->options->_POST->change_before_reserve_time;
	// 		$comment = $this->options->_POST->change_before_comment;
	// 	}

	// 	$ret = '<input type="hidden" name="selected_id" value="' . $selected_id . '"/>'
 //  			  . '<input type="hidden" name="change_before_branch_select_value" value="'. $branch_select_value . '"/>'
 //  			  . '<input type="hidden" name="change_before_reserve_date" value="'. $reserve_date . '"/>'
 //  			  . '<input type="hidden" name="change_before_reserve_time" value="'. $reserve_time . '"/>'
 //  			  . '<input type="hidden" name="change_before_comment" value="'. $comment . '"/>';

	// 	$this->debug_echo('　□ret ：');
	// 	$this->debug_echo($ret);

	// 	$this->debug_echo('■ create_change_before_hidden_html end');

	// 	return $ret;
	// }


	/**
	 * 新規確認ダイアログの表示
	 *	 
	 * @param $add_flg     = 新規フラグ
	 * @param $branch_select_value = ブランチ選択値
	 * @param $reserve_date = 公開予約日付
	 * @param $reserve_time = 公開予約時間
	 * @param $comment      = コメント
	 *
	 * @return 確認ダイアログ出力内容
	 */
	private function disp_check_add_dialog() {
		
		$this->debug_echo('■ disp_check_add_dialog start');

		$ret = "";

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_select_value)) {
			$branch_select_value = $this->options->_POST->branch_select_value;
		}
		if (isset($this->options->_POST->reserve_date)) {
			$reserve_date = $this->options->_POST->reserve_date;
		}
		if (isset($this->options->_POST->reserve_time)) {
			$reserve_time = $this->options->_POST->reserve_time;
		}
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}

		$ret .= '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>追加確認</h4>';

		$ret .= '<form method="post">'
			. '<table class="table table-striped">';

		// 「ブランチ」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $branch_select_value
			. '<input type="hidden" name="branch_select_value" value="' . $branch_select_value . '"/>'
			. '</td>'
			. '</tr>';

		// 「コミット」項目
		// $ret .= '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'

		// 「公開予約日時」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . '公開予約日時' . '</td>'
			. '<td>' . $reserve_date . ' ' . $reserve_time
			. '<input type="hidden" name="reserve_date" value="' . $reserve_date . '"/>'
			. '<input type="hidden" name="reserve_time" value="' . $reserve_time . '"/>'
			. '</td>'
			. '</tr>';

		// 「コメント」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . htmlspecialchars($comment) . '</td>'
			. '<input type="hidden" name="comment" value="' . htmlspecialchars($comment) . '"/>'
			. '</tr>'

			. '</tbody></table>'
			
			. '<div class="unit">'
			. '<div class="text-center">';

		$ret .= '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>';

		// 「確定」ボタン
		$ret .= '<li><input type="submit" id="confirm_btn" name="add_confirm" class="px2-btn px2-btn--primary" value="確定"/></li>';
		
		// 「キャンセル」ボタン
		$ret .= '<li><input type="submit" id="back_btn" name="add_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'

			. '</div>'
			 . '</div>'

			. '</form>'
			 . '</div>'
			 . '</div></div></div>';

		$this->debug_echo('■ disp_check_add_dialog end');

		return $ret;
	}


	/**
	 * 変更確認ダイアログの表示
	 *
	 * @return 
	 *  確認ダイアログ出力内容
	 */
	private function disp_check_update_dialog() {
		
		$this->debug_echo('■ disp_check_update_dialog start');

		$before_branch_select_value = "";
		$before_reserve_date = "";
		$before_reserve_time = "";
		$before_comment = "";

		$img_filename = self::PATH_CREATE_DIR . self::IMG_ARROW_RIGHT;

		$ret = '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>変更確認</h4>'
			. '<form method="post">'
			. '<div class="colum_3">'
			. '<div class="left_box">';


		// 画面選択された公開予約情報を取得
		$selected_data = $this->get_selected_reserve_data();
		
		$this->debug_echo('　□ 公開予約データ');
		$this->debug_var_dump($selected_data);
		$this->debug_echo('　');

		if ($selected_data) {

			$before_branch_select_value = $selected_data[self::RESERVE_ENTITY_BRANCH];

			$before_reserve_date = $selected_data[self::RESERVE_ENTITY_RESERVE_DATE];
			$before_reserve_time = $selected_data[self::RESERVE_ENTITY_RESERVE_TIME];

			$before_comment = $selected_data[self::RESERVE_ENTITY_COMMENT];
		}

		$ret .= '<input type="hidden" name="selected_id" value="' . $this->options->_POST->selected_id . '"/>'

			// . '<input type="hidden" name="change_before_branch_select_value" value="' . $this->options->_POST->change_before_branch_select_value . '"/>'
			// . '<input type="hidden" name="change_before_reserve_date" value="' . $this->options->_POST->change_before_reserve_date . '"/>'
			// . '<input type="hidden" name="change_before_reserve_time" value="' . $this->options->_POST->change_before_reserve_time . '"/>' 
			// . '<input type="hidden" name="change_before_comment" value="' . htmlspecialchars($this->options->_POST->change_before_comment) . '"/>'

			// hidden＿「ブランチ」項目
			. '<input type="hidden" name="branch_select_value" value="' . $this->options->_POST->branch_select_value . '"/>'
			// hidden＿「公開予約日時」項目（日付）
			. '<input type="hidden" name="reserve_date" value="' . $this->options->_POST->reserve_date . '"/>'
			// hidden＿「公開予約日時」項目（時間）
			. '<input type="hidden" name="reserve_time" value="' . $this->options->_POST->reserve_time . '"/>'	 
			// hidden＿「コメント」項目
			. '<input type="hidden" name="comment" value="' . htmlspecialchars($this->options->_POST->comment) . '"/>'

			. '<table class="table table-striped">';
	
		// 「ブランチ」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $before_branch_select_value
			. '</td>'
			. '</tr>';
		
		// 「コミット」項目
		// $ret .= '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'
		
		// 「公開予約日時」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . '公開予約日時' . '</td>'
			. '<td>' . $before_reserve_date . ' ' . $before_reserve_time
			. '</td>'
			. '</tr>';
		
		// 「コメント」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . htmlspecialchars($before_comment) . '</td>'
			. '</tr>'
			. '</tbody></table>'
			
		    . '</div>'

		    . '<div class="center_box">'
		    . '<img src="'. $img_filename .'"/>'
		    . '</div>'

            . '<div class="right_box">'
			. '<table class="table table-striped" style="width: 100%">'
			. '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $this->options->_POST->branch_select_value
			. '</td>'
			. '</tr>'
			// . '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . '公開予約日時' . '</td>'
			. '<td>' . $this->options->_POST->reserve_date . ' ' . $this->options->_POST->reserve_time
			. '</td>'
			. '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . htmlspecialchars($this->options->_POST->comment) . '</td>'
			. '</tr>'
			. '</tbody></table>'

		    . '</div>'
		 	. '</div>'

			. '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>';

		$ret .= '<li><input type="submit" id="confirm_btn" name="update_confirm" class="px2-btn px2-btn--primary" value="確定"/></li>'
			. '<li><input type="submit" id="back_btn" name="update_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'
			. '</form>'
			. '</div>'

			. '</div>'
			. '</div>'
			. '</div></div>';

		$this->debug_echo('■ disp_check_update_dialog end');

		return $ret;
	}

	/**
	 * 即時公開確認ダイアログの表示
	 *	 
	 * @param $add_flg     = 新規フラグ
	 * @param $branch_select_value = ブランチ選択値
	 * @param $reserve_date = 公開予約日付
	 * @param $reserve_time = 公開予約時間
	 * @param $comment      = コメント
	 *
	 * @return 確認ダイアログ出力内容
	 */
	private function disp_check_sokuji_dialog() {
		
		$this->debug_echo('■ disp_check_sokuji_dialog start');

		$ret = "";

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_select_value)) {
			$branch_select_value = $this->options->_POST->branch_select_value;
		}
		if (isset($this->options->_POST->reserve_date)) {
			$reserve_date = $this->options->_POST->reserve_date;
		}
		if (isset($this->options->_POST->reserve_time)) {
			$reserve_time = $this->options->_POST->reserve_time;
		}
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}

		$ret .= '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>即時公開確認</h4>';

		$ret .= '<form method="post">'
			. '<table class="table table-striped">';

		// 「ブランチ」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $branch_select_value
			. '<input type="hidden" name="branch_select_value" value="' . $branch_select_value . '"/>'
			. '</td>'
			. '</tr>';

		// 「コミット」項目
		// $ret .= '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'

		// 「公開予約日時」項目
		$ret .= '<tr>'
			  . '<td class="dialog_thead">公開予約日時</td>'
			  . '<td scope="row"><span style="margin-right:10px;color:#B61111">即時</span></td>'
			  . '</tr>';

		// 「コメント」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . htmlspecialchars($comment) . '</td>'
			. '<input type="hidden" name="comment" value="' . htmlspecialchars($comment) . '"/>'
			. '</tr>'

			. '</tbody></table>'
			
			. '<div class="unit">'
			. '<div class="text-center">';

		$ret .= '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>';

		// 「確定」ボタン
		$ret .= '<li><input type="submit" id="confirm_btn" name="sokuji_confirm" class="px2-btn px2-btn--danger" value="確定（注意：本番環境への公開処理が開始されます）"/></li>';
		
		// 「キャンセル」ボタン
		$ret .= '<li><input type="submit" id="back_btn" name="sokuji_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'

			. '</div>'
			 . '</div>'

			. '</form>'
			 . '</div>'
			 . '</div></div></div>';

		$this->debug_echo('■ disp_check_sokuji_dialog end');

		return $ret;
	}


	/**
	 * 入力チェック処理
	 *	 
	 * @return 
	 *  エラーメッセージHTML
	 */
	private function do_validation_check($input_mode) {
				
		$this->debug_echo('■ do_validation_check start');

		$ret = "";

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";
		$selected_id = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_select_value)) {
			$branch_select_value = $this->options->_POST->branch_select_value;
		}

		if (isset($this->options->_POST->reserve_date)) {
			$reserve_date = $this->options->_POST->reserve_date;
		}

		if (isset($this->options->_POST->reserve_time)) {
			$reserve_time = $this->options->_POST->reserve_time;
		}
		
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}

		if (isset($this->options->_POST->selected_id)) {
			$selected_id = $this->options->_POST->selected_id;
		}

		
		/**
 		* 公開予約一覧を取得
		*/ 
		$data_list = $this->get_ts_reserve_list(null);
	
		// 日時結合（画面表示日時）
		$combine_datetime = $this->combine_date_time($reserve_date, $reserve_time);


		if ($input_mode == self::INPUT_MODE_ADD) {
			// 公開予約の最大件数チェック
			if (!$this->check_reserve_max_record($data_list)) {
				$ret .= '<p class="error_message">公開予約は最大' . $max . '件までの登録になります。</p>';
			}
		}

		// 日付の妥当性チェック
		if (!$this->check_date($reserve_date)) {
			$ret .= '<p class="error_message">「公開予約日時」の日付が有効ではありません。</p>';
		}

		// 未来の日付であるかチェック
		if (!$this->check_future_date($combine_datetime)) {
			$ret .= '<p class="error_message">「公開予約日時」は未来日時を設定してください。</p>';
		}

		// ブランチの重複チェック
		if (!$this->check_exist_branch($data_list, $branch_select_value, $selected_id)) {
			$ret .= '<p class="error_message">1つのブランチで複数の公開予約を作成することはできません。</p>';
		}

		// 公開予約日時の重複チェック
		if (!$this->check_exist_reserve($data_list, $combine_datetime, $selected_id)) {
			$ret .= '<p class="error_message">入力された日時はすでに公開予約が作成されています。</p>';
		}

		$this->debug_echo('■ do_validation_check end');

		return $ret;
	}

	/**
	 * 初期表示のコンテンツ作成
	 *	 
	 * @return 初期表示の出力内容
	 */
	private function create_top_contents() {
		
		$this->debug_echo('■ create_top_contents start');

		$ret = "";

		// 公開予約一覧を取得
		$data_list = $this->get_ts_reserve_list(null);

		// // お知らせリストの取得
		// $alert_list = $this->get_csv_alert_list();

		// if (count($alert_list) != 0) {
		// 	// お知らせリストの表示
		// 	$ret .= '<form name="formA" method="post">'
		// 		. '<div class="alert_box">'
		// 		. '<p class="alert_title">お知らせ</p>';
		// 	// データリスト
		// 	foreach ($alert_list as $data) {
				
		// 		$ret .= '<p class="alert_content" style="vertical-align: middle;">'
		// 			. '<span style="padding-right: 5px;"><img src="'. $this->img_error_icon . '"/></span>'
		// 			. '<a onClick="document.formA.submit();return false;" >'
		// 			. $data[TS_RESERVE_COLUMN_RESERVE] . '　' . $data['content']
		// 			. '</a></p>';
		// 	}

		// 	$ret .=  '<input type="hidden" name="history" value="履歴">'
		// 		. '</div>'
		// 		. '</form>';
		// }

		$ret .= '<div class="button_contents_box">'
			. '<form id="form_table" method="post">'
			. '<div class="button_contents" style="float:left">'
			. '<ul>'
			. '<li><input type="submit" id="add_btn" name="add" class="px2-btn" value="新規"/></li>'
			. '</ul>'
			. '</div>'
			. '<div class="button_contents" style="float:right;">'
			. '<ul>'
			. '<li><input type="submit" id="update_btn" name="update" class="px2-btn" value="変更"/></li>'
			. '<li><input type="submit" id="delete_btn" name="delete" class="px2-btn px2-btn--danger" value="削除"/></li>'
			. '<li><input type="submit" id="sokuji_btn" name="sokuji" class="px2-btn px2-btn--primary" value="即時公開"/></li>'
			. '<li><input type="submit" id="history_btn" name="history" class="px2-btn" value="履歴"/></li>'
			. '</ul>'
			// . '</div>'
			. '</div>';

		// テーブルヘッダー
		$ret .= '<div>'
		    . '<table name="list_tbl" class="table table-striped">'
			. '<thead>'
			. '<tr>'
			. '<th scope="row"></th>'
			. '<th scope="row">公開予約日時</th>'
			. '<th scope="row">コミット</th>'
			. '<th scope="row">ブランチ</th>'
			. '<th scope="row">コメント</th>'
			. '</tr>'
			. '</thead>'
			. '<tbody>';

		// $this->debug_echo('　□data_list：');
		// $this->debug_var_dump($data_list);

		// テーブルデータリスト
		foreach ((array)$data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . $array[self::RESERVE_ENTITY_ID] . '"/></td>'
				. '<td class="p-center">' . $array[self::RESERVE_ENTITY_RESERVE_DISPLAY] . '</td>'
				. '<td class="p-center">' . $array[self::RESERVE_ENTITY_COMMIT] . '</td>'
				. '<td class="p-center">' . $array[self::RESERVE_ENTITY_BRANCH] . '</td>'
				. '<td>' . $array[self::RESERVE_ENTITY_COMMENT] . '</td>'
				. '</tr>';
		}

		$ret .= '</tbody></table>'
			. '</div>'
			. '</form>'
			. '</div>';

		$this->debug_echo('■ create_top_contents end');

		return $ret;
	}

	/**
	 * 履歴表示のコンテンツ作成
	 *	 
	 * @return 履歴表示の出力内容
	 */
	private function create_history_contents() {
		
		$this->debug_echo('■ create_history_contents start');

		$ret = "";

		// 処理結果一覧を取得
		$data_list = $this->get_ts_result_list(null);

		$ret .= '<div style="overflow:hidden">'
			. '<form method="post">'
			. '<div class="button_contents" style="float:right;">'
			. '<ul>'
			. '<li><input type="submit" name="log" class="px2-btn px2-btn--primary" value="ログ"/></li>'
			. '<li><input type="submit" name="recovory" class="px2-btn px2-btn--primary" value="復元"/></li>'
			. '</div>'
			. '</div>';

		// ヘッダー
		$ret .= '<table name="list_tbl" class="table table-striped">'
				. '<thead>'
				. '<tr>'
				. '<th scope="row"></th>'
				. '<th scope="row">状態</th>'
				. '<th scope="row">公開種別</th>'
				. '<th scope="row">公開予約日時</th>'
				. '<th scope="row">処理開始日時</th>'
				. '<th scope="row">処理完了日時</th>'
				. '<th scope="row">コミット</th>'
				. '<th scope="row">ブランチ</th>'
				. '<th scope="row">コメント</th>'
				. '</tr>'
				. '</thead>'
				. '<tbody>';

		// データリスト
		foreach ((array)$data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . $array[self::RESULT_ENTITY_ID] . '"/></td>'
				. '<td class="p-center">' . $array[self::RESULT_ENTITY_STATUS] . '</td>'
				. '<td class="p-center">' . $array[self::RESULT_ENTITY_TYPE] . '</td>'
				. '<td class="p-center">' . $array[self::RESULT_ENTITY_RESERVE_DISPLAY] . '</td>'
				. '<td class="p-center">' . $array[self::RESULT_ENTITY_START_DISPLAY] . '</td>'
				. '<td class="p-center">' . $array[self::RESULT_ENTITY_END_DISPLAY] . '</td>'
				. '<td class="p-center">' . $array[self::RESULT_ENTITY_COMMIT] . '</td>'
				. '<td class="p-center">' . $array[self::RESULT_ENTITY_BRANCH] . '</td>'
				. '<td>' . $array[self::RESULT_ENTITY_COMMENT] . '</td>'
				. '</tr>';
		}

		$ret .= '</tbody></table>';
		
		$ret .= '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>'
			. '<li><input type="submit" id="back_btn" class="px2-btn px2-btn--primary" value="戻る"/></li>'
			. '</ul>'
			. '</div>'
			. '</div>'
			. '</form>'
			. '</div>';
		
		$this->debug_echo('■ create_history_contents end');

		return $ret;
	}

	/**
	 * 
	 */
	public function run() {
	
		// $this->debug_echo('■ run start');

		// $this->debug_echo('　□カレントパス：' . realpath('.'));
		// $this->debug_echo('　□__DIR__：' . __DIR__);

		// $path = self::PATH_CREATE_DIR . self::PATH_WAITING;
		// $this->debug_echo('　□相対パス' . $path);
		// $real_path = $this->file_control->normalize_path($this->file_control->get_realpath($path));

		// $this->debug_echo('　□絶対パス' . $real_path);

		// 画面表示
		$disp = '';  

		// エラーダイアログ表示
		$alert_message = '';

		// ダイアログの表示
		$dialog_disp = '';
		
		// 画面ロック用
		$disp_lock = '';

		// 処理実行結果格納
		$ret = '';

		// 入力画面へ表示させるエラーメッセージ
		// $error_message = '';

		//timezoneテスト ここから
		date_default_timezone_set('Asia/Tokyo');

		echo "--------------------------------</br>";
	
		$this->debug_echo('　□GMTの現在時刻：');
		$this->debug_echo(gmdate(DATE_ATOM, time()));

		$this->debug_echo('　□Asiaの現在時刻：');
		$this->debug_echo(date(DATE_ATOM, time()));


		$t = new \DateTime(gmdate(DATE_ATOM, time()));
		$t->setTimeZone(new \DateTimeZone('Asia/Tokyo'));
		$this->debug_echo('　□GMTから変換したAsiaの現在時刻：');
		$this->debug_echo($t->format(DATE_ATOM));


		$t = new \DateTime($t->format(DATE_ATOM));
		$t->setTimeZone(new \DateTimeZone('GMT'));

		$this->debug_echo('　□日本時間から変換したGMTの現在時刻：');
		$this->debug_echo($t->format(DATE_ATOM));

		// タイムゾーンが取得できる！！！！
		echo "タイムゾーン取得 ：" . date("e", date(DATE_ATOM, time())). "</br>";
		
		echo "--------------------------------</br>";
		//timezoneテスト ここまで

		try {

			// データベース接続
			$this->dbh = $this->pdo->connect();

			// テーブル作成（存在している場合は処理しない）
			$this->pdo->create_table($this->dbh);

			// gitのmaster情報取得
			$ret = json_decode($this->init());

			if ( !$ret->status ) {

				$alert_message = 'initialized faild';

			} else {
			
				// 初期化成功の場合
				$combine_reserve_time = '';
				$convert_reserve_time = '';

				// 画面入力された日付と時刻を結合
				$combine_reserve_time = $this->combine_date_time($this->options->_POST->reserve_date, $this->options->_POST->reserve_time);

				/**
			 	* 新規処理
			 	*/
				// 初期表示画面の「新規」ボタン押下
				if (isset($this->options->_POST->add)) {

					// 新規（入力）ダイアログ画面へ遷移
					$dialog_disp = $this->disp_add_dialog();

				// 新規ダイアログの「確認」ボタン押下
				} elseif (isset($this->options->_POST->add_check)) {

					$add_flg = true;

					// 一時コメント
					// 入力チェック処理
					// $this->input_error_message = $this->do_validation_check(self::INPUT_MODE_ADD);

					if ($input_error_message) {
						// 入力ダイアログのまま
						$dialog_disp = $this->disp_back_add_dialog();
					} else {
						// 確認ダイアログへ遷移
						$dialog_disp = $this->disp_check_add_dialog();
					}
					
				// 新規ダイアログの「確定」ボタン
				} elseif (isset($this->options->_POST->add_confirm)) {

					// // Gitファイルの取得
					// $ret = json_decode($this->file_copy($combine_reserve_time));

					// if ( !$ret->status ) {
					// 	// 処理失敗
					// 	$alert_message = 'add faild';
					// 	break;

					// } else {
					// 	// 処理成功

						if ( is_null($combine_reserve_time) || !isset($combine_reserve_time) ) {
							throw new \Exception("Convert time zone failed.");
						}

						// 公開予約情報の追加
						$this->insert_ts_reserve($combine_reserve_time);

					// }

				// 新規確認ダイアログの「戻る」ボタン押下
				} elseif (isset($this->options->_POST->add_back)) {
				
					$dialog_disp = $this->disp_back_add_dialog();


				/**
			 	* 変更処理
			 	*/
				// 初期表示画面の「変更」ボタン押下
				} elseif (isset($this->options->_POST->update)) {

					// 「変更入力ダイアログ」画面へ遷移
					$dialog_disp = $this->disp_update_dialog();

				// 変更ダイアログの確認ボタンが押下された場合
				} elseif (isset($this->options->_POST->update_check)) {
				
					// 一時コメント
					// 入力チェック処理
					// $this->input_error_message = $this->do_validation_check(self::INPUT_MODE_UPDATE);

					if ($this->input_error_message) {

						// 入力チェックエラーがあった場合はそのままの画面
						$dialog_disp = $this->disp_update_dialog();

					} else {

						// 入力チェックエラーがなかった場合は確認ダイアログへ遷移
						$dialog_disp = $this->disp_check_update_dialog();
					}	

				// 変更ダイアログの確定ボタンが押下された場合
				} elseif (isset($this->options->_POST->update_confirm)) {
					
					// // GitファイルをWAITINGディレクトリへコピー（ディレクトリ名は入力された日付）
					// $ret = json_decode($this->file_update($combine_reserve_time));
			
					// if ( !$ret->status ) {
					// 	// 処理失敗
					// 	$alert_message = 'update faild';
					// 	break;

					// } else {
						// 処理成功

						// CSV入力情報の変更
						$this->update_reserve_table($combine_reserve_time);

					// }


				// 変更確認ダイアログの「戻る」ボタン押下
				} elseif (isset($this->options->_POST->update_back)) {
				
					$dialog_disp = $this->disp_back_update_dialog();


				/**
			 	* 削除処理
			 	*/
				// 初期表示画面の「削除」ボタン押下
				} elseif (isset($this->options->_POST->delete)) {
				
					// Gitファイルの削除
					$ret = json_decode($this->file_delete());

					if ( !$ret->status ) {
						
						$alert_message = 'delete faild';
						// // 処理失敗
						// break;

					} else {

						// CSV情報の削除
						$this->delete_reserve_table();

					}

				/**
			 	* 即時公開処理
			 	*/
				// 初期表示画面の「即時公開」ボタン押下
				} elseif (isset($this->options->_POST->sokuji)) {
				
					// 即時公開（入力）ダイアログ画面へ遷移
					$dialog_disp = $this->disp_sokuji_dialog();


				// 即時公開ダイアログの確認ボタンが押下された場合
				} elseif (isset($this->options->_POST->sokuji_check)) {
				
					// 一時コメント
					// 入力チェック処理
					// $this->input_error_message = $this->do_validation_check(self::INPUT_MODE_SOKUJI);

					if ($this->input_error_message) {

						// 入力チェックエラーがあった場合はそのままの画面
						$dialog_disp = $this->disp_sokuji_dialog();

					} else {

						// 入力チェックエラーがなかった場合は確認ダイアログへ遷移
						$dialog_disp = $this->disp_check_sokuji_dialog();
					}	

				// 即時公開ダイアログの「確定」ボタンが押下された場合
				} elseif (isset($this->options->_POST->sokuji_confirm)) {
					
					// 即時公開処理
					$ret = json_decode($this->sokuji_release());
		
					if ( !$ret->status ) {

						$alert_message = 'sokuji_release faild';
					}

				// 即時公開確認ダイアログの「戻る」ボタン押下
				} elseif (isset($this->options->_POST->sokuji_back)) {
				
					$dialog_disp = $this->disp_back_sokuji_dialog();
				}
			}

			if ( !$ret->status ) {
				// 処理失敗の場合

				// エラーメッセージ表示
				$dialog_disp = '
				<script type="text/javascript">
					console.error("' . $ret->message . '");
					alert("' . $alert_message .'");
				</script>';
				
			}

			// 初期表示画面の「履歴」ボタン押下
			if (isset($this->options->_POST->history)) {
				// 履歴表示画面の表示
				$disp = $this->create_history_contents();
			} else {
				// 初期表示画面の表示
				$disp = $this->create_top_contents();
			}

			// 画面ロック用
			$disp_lock = '<div id="loader-bg"><div id="loading"></div></div>';	
	
		} catch (\Exception $e) {

			// データベース接続を閉じる
			$this->pdo->close($this->dbh);

			echo $e->getMessage();

			$this->debug_echo('■ run error end');

			return;
		}

		// データベース接続を閉じる
		$this->pdo->close();

		// $this->debug_echo('■ run end');

		// 画面表示
		return $disp . $disp_lock . $dialog_disp;
	}

	/**
	 * 公開予約一覧テーブルからリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	private function get_ts_reserve_list($now) {

		$this->debug_echo('■ get_ts_reserve_list start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

			// // テーブル作成
			// $this->pdo->create_table();

			// SELECT文作成（削除フラグ = 0、ソート順：公開予約日時の昇順）
			$select_sql = "
					SELECT * FROM TS_RESERVE WHERE delete_flg = " . self::DELETE_FLG_OFF . " ORDER BY reserve_datetime";
			// SELECT実行
			$ret_array = $this->pdo->select($this->dbh, $select_sql);

			foreach ((array)$ret_array as $array) {

				$conv_ret_array[] = $this->convert_ts_reserve_entity($array);
			}

			// $this->debug_echo('　□conv_ret_array：');
			// $this->debug_var_dump($conv_ret_array);

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->debug_echo('■ get_ts_reserve_list end');

		return $conv_ret_array;
	}

	/**
	 * 処理結果一覧テーブルからリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	private function get_ts_result_list($now) {

		$this->debug_echo('■ get_ts_reserve_list start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

			// SELECT文作成（世代削除フラグ = 0、ソート順：IDの降順）
			$select_sql = "
					SELECT * FROM TS_RESULT WHERE gen_delete_flg = " . self::DELETE_FLG_OFF . " ORDER BY result_id_seq DESC";
			// SELECT実行
			$ret_array = $this->pdo->select($this->dbh, $select_sql);

			foreach ((array)$ret_array as $array) {

				$conv_ret_array[] = $this->convert_ts_result_entity($array);
			}

			// $this->debug_echo('　□SELECTリストデータ：');
			// $this->debug_var_dump($ret_array);

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->debug_echo('■ get_ts_reserve_list end');

		return $conv_ret_array;
	}

	/**
	 * 公開予約テーブルから、選択された公開予約情報を取得する
	 *
	 * @return 選択行の情報
	 */
	private function get_selected_reserve_data() {


		$this->debug_echo('■ get_selected_reserve_data start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

			$selected_id =  $this->options->_POST->selected_id;

			$this->debug_echo('　□selected_id：' . $selected_id);

			if (!$selected_id) {
				$this->debug_echo('選択値が取得できませんでした。');
			} else {

				// SELECT文作成
				$select_sql = "SELECT * from TS_RESERVE
					WHERE reserve_id_seq = ". $selected_id;

				// // パラメータ作成
				// $params = array(
				// 	':id' => $selected_id
				// );

				// SELECT実行
				$ret_array = array_shift($this->pdo->select($this->dbh, $select_sql));

				$conv_ret_array = $this->convert_ts_reserve_entity($ret_array);

				// $this->debug_echo('　□SELECTデータ：');
				// $this->debug_var_dump($ret_array);
			}

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->debug_echo('■ get_selected_reserve_data end');

		return $conv_ret_array;
	}

	/**
	 * 公開予約テーブル登録処理
	 *
	 * @return なし
	 */
	private function insert_ts_reserve($combine_reserve_time) {

		$this->debug_echo('■ insert_ts_reserve start');

		$result = array('status' => true,
						'message' => '');

		try {

			// INSERT文作成
			$insert_sql = "INSERT INTO TS_RESERVE (
				reserve_datetime,
				branch_name,
				commit_hash,
				comment,
				delete_flg,
				insert_datetime,
				insert_user_id,
				update_datetime,
				update_user_id
			)VALUES(
				:reserve_datetime,
				:branch_name,
				:commit_hash,
				:comment,
				:delete_flg,
				:insert_datetime,
				:insert_user_id,
				:update_datetime,
				:update_user_id
			)";

			// 現在時刻
			// $now = date(self::DATETIME_FORMAT);
			$now = $this->get_current_datetime_of_gmt();

			// パラメータ作成
			$params = array(
				':reserve_datetime' => $this->convert_to_gmt_datetime($combine_reserve_time),
				':branch_name' => $this->options->_POST->branch_select_value,
				':commit_hash' => $this->commit_hash,
				':comment' => $this->options->_POST->comment,
				':delete_flg' => self::DELETE_FLG_OFF,
				':insert_datetime' => $now,
				':insert_user_id' => "dummy_insert_user",
				':update_datetime' => null,
				':update_user_id' => null
			);
		
			// INSERT実行
			$stmt = $this->pdo->execute($this->dbh, $insert_sql, $params);

		} catch (Exception $e) {

	  		echo '公開予約テーブルの登録処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->debug_echo('■ insert_ts_reserve end');

		return json_encode($result);
	}

	/**
	 * 公開予約テーブル更新処理
	 *
	 * @return なし
	 */
	private function update_reserve_table($combine_reserve_time) {

		$this->debug_echo('■ update_reserve_table start');

		$result = array('status' => true,
						'message' => '');

		try {

			$selected_id =  $this->options->_POST->selected_id;

			$this->debug_echo('　□selected_id：' . $selected_id);

			if (!$selected_id) {
				$this->debug_echo('選択IDが取得できませんでした。');
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
				// $now = date(self::DATETIME_FORMAT);
				$now = $this->get_current_datetime_of_gmt();

				// パラメータ作成
				$params = array(
					':reserve_datetime' => $this->convert_to_gmt_datetime($combine_reserve_time),
					':branch_name' => $this->options->_POST->branch_select_value,
					':commit_hash' => $this->commit_hash,
					':comment' => $this->options->_POST->comment,
					':update_datetime' => $now,
					':update_user_id' => "dummy_update_user",
					':reserve_id_seq' => $selected_id
				);

				// UPDATE実行
				$stmt = $this->pdo->execute($this->dbh, $update_sql, $params);
			}

		} catch (Exception $e) {

	  		echo '公開予約テーブルの更新処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->debug_echo('■ update_reserve_table end');

		return json_encode($result);
	}

	/**
	 * 公開予約テーブル論理削除処理
	 *
	 * @return なし
	 */
	private function delete_reserve_table() {

		$this->debug_echo('■ delete_reserve_table start');

		$result = array('status' => true,
						'message' => '');

		try {

			$selected_id =  $this->options->_POST->selected_id;

			$this->debug_echo('　□selected_id：' . $selected_id);

			if (!$selected_id) {
				$this->debug_echo('選択IDが取得できませんでした。');
			} else {

				// UPDATE文作成（論理削除）
				$update_sql = "UPDATE TS_RESERVE SET 
					delete_flg = :delete_flg,
					update_datetime = :update_datetime,
					update_user_id = :update_user_id 
					WHERE reserve_id_seq = :reserve_id_seq";

				// 現在時刻
				// $now = date(self::DATETIME_FORMAT);
				$now = $this->get_current_datetime_of_gmt();

				// パラメータ作成
				$params = array(
					':delete_flg' => self::DELETE_FLG_ON,
					':update_datetime' => $now,
					':update_user_id' => "dummy_delete_user",
					':reserve_id_seq' => $selected_id
				);

				// UPDATE実行
				$stmt = $this->pdo->execute($this->dbh, $update_sql, $params);
			}

		} catch (Exception $e) {

	  		echo '公開予約テーブルの論理削除処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->debug_echo('■ delete_reserve_table end');

		return json_encode($result);
	}


	/**
	 * 選択された処理結果情報を取得する
	 *
	 * @return 選択行の情報
	 */
	private function get_selected_result_data() {


		$this->debug_echo('■ get_selected_result_data start');

		$ret_array = array();

		$conv_ret_array = array();

		try {

			$selected_id =  $this->options->_POST->selected_id;

			$this->debug_echo('　□selected_id：' . $selected_id);

			if (!$selected_id) {
				$this->debug_echo('選択値が取得できませんでした。');
			} else {

				// SELECT文作成
				$select_sql = "SELECT * from TS_RESULT
					WHERE result_id_seq = ". $selected_id;

				// // パラメータ作成
				// $params = array(
				// 	':id' => $selected_id
				// );

				// SELECT実行
				$ret_array = array_shift($this->pdo->select($this->dbh, $select_sql));

				$conv_ret_array = $this->convert_ts_result_entity($ret_array);


				// $this->debug_echo('　□SELECTデータ：');
				// $this->debug_var_dump($ret_array);
			}

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";

			return $conv_ret_array;
		}
		
		$this->debug_echo('■ get_selected_result_data end');

		return $conv_ret_array;
	}



	/**
	 * 処理結果一覧テーブルの登録処理
	 *
	 * @return なし
	 */
	private function insert_ts_result($start_datetime) {

		$this->debug_echo('■ insert_ts_result start');

		$result = array('status' => true,
						'message' => '');

		try {

			// INSERT文作成
			$insert_sql = "INSERT INTO TS_RESULT (
				reserve_id,
				reserve_datetime,
				branch_name,
				commit_hash,
				comment,
				publish_type,
				status,
				change_check_flg,
				publish_honban_diff_flg,
				publish_pre_diff_flg,
				start_datetime,
				end_datetime,
				batch_user_id,
				gen_delete_flg,
				gen_delete_datetime,
				insert_datetime

			)VALUES(
				:reserve_id,
				:reserve_datetime,
				:branch_name,
				:commit_hash,
				:comment,
				:publish_type,
				:status,
				:change_check_flg,
				:publish_honban_diff_flg,
				:publish_pre_diff_flg,
				:start_datetime,
				:end_datetime,
				:batch_user_id,
				:gen_delete_flg,
				:gen_delete_datetime,
				:insert_datetime
			)";

			// 現在時刻
			// $now = date(self::DATETIME_FORMAT);
			$now = $this->get_current_datetime_of_gmt();

			// パラメータ作成
			$params = array(
				':reserve_id' => null,
				':reserve_datetime' => null,
				':branch_name' => $this->options->_POST->branch_select_value,
				':commit_hash' => $this->commit_hash,
				':comment' => $this->options->_POST->comment,
				':publish_type' => self::PUBLISH_TYPE_SOKUJI,
				':status' => self::PUBLISH_STATUS_RUNNING,
				':change_check_flg' => null,
				':publish_honban_diff_flg' => null,
				':publish_pre_diff_flg' => null,
				':start_datetime' => $start_datetime,
				':end_datetime' => null,
				':batch_user_id' => "dummy_batch_user_id",
				':gen_delete_flg' => self::DELETE_FLG_OFF,
				':gen_delete_datetime' => null,
				':insert_datetime' => gmdate(self::DATETIME_FORMAT)
			);
		
			// INSERT実行
			$stmt = $this->pdo->execute($this->dbh, $insert_sql, $params);

		} catch (Exception $e) {

	  		echo '処理結果テーブル登録処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->debug_echo('■ insert_ts_result end');

		return json_encode($result);
	}


	/**
	 * 処理結果一覧テーブルの更新処理
	 *
	 * @return なし
	 */
	private function update_ts_result($id) {

		$this->debug_echo('■ update_ts_result start');

		$result = array('status' => true,
						'message' => '');

		try {

			$this->debug_echo('id：' . $id);

			if (!$id) {
				$this->debug_echo('処理結果テーブルの更新IDが取得できませんでした。');
			} else {

				// UPDATE文作成
				$update_sql = "UPDATE TS_RESULT SET 
					status = :status,
					change_check_flg = :change_check_flg,
					publish_honban_diff_flg = :publish_honban_diff_flg,
					publish_pre_diff_flg = :publish_pre_diff_flg,
					end_datetime = :end_datetime

					WHERE result_id_seq = :result_id_seq";

				// 現在時刻
				$now = gmdate(self::DATETIME_FORMAT);

				// パラメータ作成
				$params = array(
					':status' => self::PUBLISH_STATUS_SUCCESS,
					':change_check_flg' => "0",
					':publish_honban_diff_flg' => "1",
					':publish_pre_diff_flg' => "1",
					':end_datetime' => $now,

					':result_id_seq' => $id
				);

				// UPDATE実行
				$stmt = $this->pdo->execute($this->dbh, $update_sql, $params);
			}

		} catch (Exception $e) {

	  		echo '処理結果テーブルの更新処理に失敗しました。' . $e->getMesseage();
	  		
	  		$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->debug_echo('■ update_ts_result end');

		return json_encode($result);
	}


	/**
	 * 新規追加時のGitファイルのコピー
	 *
	 * @return なし
	 */
	private function file_copy($combine_reserve_time) {

		$this->debug_echo('■ file_copy start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');
	
		// ディレクトリ名
		$dirname = $this->format_datetime($combine_reserve_time, self::DATETIME_FORMAT_SAVE);

		// 選択したブランチ
		$branch_name = trim(str_replace("origin/", "", $this->options->_POST->branch_select_value));

		try {

			// WAITINGディレクトリの絶対パス
			$waiting_real_path = $this->file_control->normalize_path($this->file_control->get_realpath(self::PATH_CREATE_DIR . self::PATH_WAITING));

			// WAITINGディレクトリが存在しない場合は作成
			if ( !$this->is_exists_mkdir($waiting_real_path) ) {

					// エラー処理
					throw new \Exception('Creation of Waiting directory failed.');
			}

			// WAITING配下公開ソースディレクトリの絶対パス
			$waiting_src_real_path = $this->file_control->normalize_path($this->file_control->get_realpath(self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname));

			$this->debug_echo('　□$waiting_src_real_path' . $waiting_src_real_path);

			// 公開予約ディレクトリをデリートインサート
			if ( !$this->is_exists_remkdir($waiting_src_real_path) ) {

				// エラー処理
				throw new \Exception('Creation of Waiting publish directory failed.');
			}

			// 公開予約ディレクトリへ移動
			if ( chdir($waiting_src_real_path) ) {

				// git init
				$command = 'git init';
				$this->command_execute($command, false);

				// git urlのセット
				$url = $this->options->git->protocol . "://" . urlencode($this->options->git->username) . ":" . urlencode($this->options->git->password) . "@" . $this->options->git->url;
				
				// initしたリポジトリに名前を付ける
				$command = 'git remote add origin ' . $url;
				$this->command_execute($command, false);

				// git fetch（リモートリポジトリの指定ブランチの情報をローカルブランチに取得）
				$command = 'git fetch origin' . ' ' . $branch_name;
				$this->command_execute($command, false);

				// git pull（）pullはリモート取得ブランチを任意のローカルブランチにマージするコマンド
				$command = 'git pull origin' . ' ' . $branch_name;
				$this->command_execute($command, false);
		
				// // 現在のブランチ取得
				// exec( 'git branch', $output);

				// コミットハッシュ値の取得
				$command = 'git rev-parse --short HEAD';
				$ret = $this->command_execute($command, false);

				foreach ( (array)$ret['output'] as $element ) {

					$this->commit_hash = $element;
				}

			} else {
				// WAITINGの公開予約ディレクトリが存在しない場合

				// エラー処理
				throw new \Exception('Waiting publish directory not found.');
			}

		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;
		
		chdir($current_dir);

		$this->debug_echo('■ file_copy end');

		return json_encode($result);

	}

	/**
	 * 変更時のGitファイルのチェックアウト
	 *
	 * @return なし
	 */
	private function file_update($combine_reserve_time) {
		
		$this->debug_echo('■ file_update start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		// 変更元の公開予約日時をフォーマット変換
		$before_dirname = $this->format_datetime($this->combine_date_time($this->options->_POST->change_before_reserve_date, $this->options->_POST->change_before_reserve_time), self::DATETIME_FORMAT_SAVE);

		// 変更後のディレクトリ名
		$dirname = $this->format_datetime($combine_reserve_time, self::DATETIME_FORMAT_SAVE);


		// 選択したブランチ
		$branch_name_org = $this->options->_POST->branch_select_value;
		// 選択したブランチ（origin無し）
		$branch_name = trim(str_replace("origin/", "", $branch_name_org));

		try {

			// 変更元のWAITING配下公開ソースディレクトリの絶対パス
			$before_waiting_src_real_path = $this->file_control->normalize_path($this->file_control->get_realpath(self::PATH_CREATE_DIR . self::PATH_WAITING . $before_dirname));

			// 変更元が存在するかチェック
			if ( !file_exists($before_waiting_src_real_path) ) {

				$this->debug_echo( '　□ $before_dirname：' . $before_waiting_src_real_path);
				throw new \Exception('Before publish directory not found.');
			}

			// 変更後のWAITING配下公開ソースディレクトリの絶対パス
			$waiting_src_real_path = $this->file_control->normalize_path($this->file_control->get_realpath(self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname));

			// ディレクトリ名が変更になる場合はリネームする
			if ($before_dirname != $dirname) {

				if ( file_exists( $before_waiting_src_real_path ) && !file_exists( $waiting_src_real_path ) ){
					
					rename( $before_waiting_src_real_path, $waiting_src_real_path );

				} else {
				// 名前変更前のディレクトリがない場合、または名前変更後のディレクトリが存在する場合は処理終了

					$this->debug_echo('　□ $before_dirname：' . $before_dirname);
					$this->debug_echo('　□ $dirname：' . $dirname);

					throw new \Exception('Waiting directory name could not be changed.');
				}
			}

			// 公開予約ディレクトリへ移動
			if ( chdir( $waiting_src_real_path ) ) {

				// 現在のブランチ取得
				$command = 'git branch';
				$ret = $this->command_execute($command, false);

				$now_branch;
				$already_branch_checkout = false;
				foreach ( (array)$ret['output'] as $value ) {

					// 「*」の付いてるブランチを現在のブランチと判定
					if ( strpos($value, '*') !== false ) {

						$value = trim(str_replace("* ", "", $value));
						$now_branch = $value;

					} else {

						$value = trim($value);

					}

					// 選択された(切り替える)ブランチがブランチの一覧に含まれているか判定
					if ( $value == $branch_name ) {
						$already_branch_checkout = true;
					}
				}

				// git fetch
				$command = 'git fetch origin';
				$this->command_execute($command, false);

				// 現在のブランチと選択されたブランチが異なる場合は、ブランチを切り替える
				if ( $now_branch !== $branch_name ) {

					if ($already_branch_checkout) {
						// 選択された(切り替える)ブランチが既にチェックアウト済みの場合
						$command = 'git checkout ' . $branch_name;
						$this->command_execute($command, false);


					} else {
						// 選択された(切り替える)ブランチがまだチェックアウトされてない場合
						$command = 'git checkout -b ' . $branch_name . ' ' . $branch_name_org;
						$this->command_execute($command, false);

					}
				}

				// コミットハッシュ値の取得
				$command = 'git rev-parse --short HEAD';
				$ret = $this->command_execute($command, false);

				foreach ( (array)$ret['output'] as $element ) {

					$this->commit_hash = $element;
				}

			} else {

				throw new \Exception('Waiting publish directory not found.');
			}
		
		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->debug_echo('■ file_update end');

		return json_encode($result);

	}

	/**
	 * Gitファイルの削除
	 *
	 * @return なし
	 */
	private function file_delete() {
		
		$this->debug_echo('■ file_delete start');

		$current_dir = realpath('.');
		$this->debug_echo('　□current_dir：' . $current_dir);

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$selected_ret = $this->get_selected_reserve_data();

		$dirname = $this->format_datetime($selected_ret[self::RESERVE_ENTITY_RESERVE], self::DATETIME_FORMAT_SAVE);

		try {


			// WAITING配下公開ソースディレクトリの絶対パス
			$waiting_src_real_path = $this->file_control->normalize_path($this->file_control->get_realpath(self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname));

			// WAITINGに公開予約ディレクトリが存在しない場合は無視する
			if( file_exists( $waiting_src_real_path )) {
				
				// 削除
				$command = 'rm -rf --preserve-root '. $waiting_src_real_path;

				$ret = $this->command_execute($command, false);

				if ( $ret['return'] !== 0 ) {
					$this->debug_echo('削除失敗');
					throw new \Exception('Delete directory failed.');
				}
			} else {
				$this->debug_echo('削除対象が存在しない');
			}
		
		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->debug_echo('■ file_delete end');

		return json_encode($result);

	}

	/**
	 * 即時公開処理
	 */
	private function sokuji_release() {

		$this->debug_echo('■ sokuji_release start');

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

	 		// ▼ 未実装
			/**
	 		* 公開タスクロックの確認
			*/
	 		// 公開タスクロックを確認し、ロックが掛かっている場合は処理終了
	 		// ロックがかかっていない場合は、処理を進める

	 		// ※ 画面から、どの公開処理が実施中か確認できるようにしたい
	 		//    処理が異常終了した場合にロックが解除されないままとなってしまう


	 	// 	// クーロン用
	 	// 	$now = gmdate(self::DATETIME_FORMAT_SAVE);
			// // 公開予約の一覧を取得
			// $data_list = $this->get_ts_reserve_list($now);
			// foreach ( (array)$selected_ret as $data ) {
			// 	// 公開対象の公開予約日時（文字列）
			// 	$dirname = $this->get_datetime_str($selected_ret, self::TS_RESERVE_COLUMN_RESERVE, SORT_DESC);
			// }

	 	// 	// 選択された公開予約データを取得
			// $selected_ret = $this->get_selected_reserve_data();

			// $dirname = '';

			// if ( $selected_ret ) {
			// 	// 公開対象の公開予約日時（文字列）
			// 	$dirname = date(self::DATETIME_FORMAT_SAVE, strtotime($selected_ret[self::TS_RESERVE_COLUMN_RESERVE]));
			// }

			// $this->debug_echo('　□公開予約ディレクトリ：');
			// $this->debug_echo($dirname);
			$this->debug_echo('　□現在日時：');
			$this->debug_echo($start_datetime);

			// 公開対象が存在する場合
			if ($start_datetime_dir) {

				// 　公開予約一覧CSVにファイルロックが掛かっていない場合
				// 　ファイルロックを掛ける
				// 　実施済み一覧CSVにファイルロックが掛かっていない場合
				// 　ファイルロックを掛ける
		 		// 公開対象の行を、実施済みへ切り取り移動する
		 		$this->debug_echo('　□処理結果テーブルの登録処理');
				

				/**
		 		* 処理結果テーブルの登録処理
				*/ 
				$ret = json_decode($this->insert_ts_result($start_datetime));

				// インサートしたシーケンスIDを取得（処理終了時の更新処理にて使用）
				$insert_id = $this->dbh->lastInsertId();

				$this->debug_echo('　□$insert_id：');
				$this->debug_echo($insert_id);

				if ( !$ret->status) {
					throw new \Exception("TS_RESULT insert failed.");
				}
				

				// // 予定CSVへデリート処理
				// $ret = json_decode($this->delete_reserve_table());

				// if ( !$ret->status ) {
				// // 削除失敗
		
				// 	// // エラーメッセージ
				// 	// $dialog_disp = '
				// 	// <script type="text/javascript">
				// 	// 	console.error("' . $ret->message . '");
				// 	// 	alert("publish faild");
				// 	// </script>';

				// 	throw new \Exception("Waiting csv delete failed.");
				// }

				// TODO:開発用に、windowsでは処理させていない。後で削除。
				if (!$is_windows) {

					// TODO:未実装
			 		// ファイルロックを解除する

					// ログファイルディレクトリが存在しない場合は作成
					if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_LOG) ) {
						// エラー処理
						throw new \Exception('Creation of log directory failed.');
					} else {
						
						// ログファイル内の公開予約ディレクトリが存在しない場合は削除（存在する場合は作成しない）
						if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_LOG . $dirname) ) {

							// エラー処理
							throw new \Exception('Creation of log publish directory failed.');
						}
					}

					// バックアップディレクトリが存在しない場合は作成
					if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_BACKUP) ) {
						// エラー処理
						throw new \Exception('Creation of backup directory failed.');
					} else {
						
						// 公開予約ディレクトリをデリートインサート
						if ( !$this->is_exists_remkdir(self::PATH_CREATE_DIR . self::PATH_BACKUP . $dirname) ) {

							// エラー処理
							throw new \Exception('Creation of backup publish directory failed.');
						}
					}

					// runningディレクトリが存在しない場合は作成
					if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_RUNNING) ) {
						// エラー処理
						throw new \Exception('Creation of running directory failed.');
					} else {
						
						// 公開予約ディレクトリをデリートインサート
						if ( !$this->is_exists_remkdir(self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname) ) {

							// エラー処理
							throw new \Exception('Creation of running publish directory failed.');
						}
					}

					// releasedディレクトリが存在しない場合は作成
					if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_RELEASED) ) {
						// エラー処理
						throw new \Exception('Creation of released directory failed.');
					} else {
						
						// 公開予約ディレクトリをデリートインサート
						if ( !$this->is_exists_remkdir(self::PATH_CREATE_DIR . self::PATH_RELEASED . $dirname) ) {

							// エラー処理
							throw new \Exception('Creation of released publish directory failed.');
						}
					}

					/**
			 		* 本番ソースを「backup」ディレクトリへコピー
					*/

					$this->debug_echo('　▼バックアップ処理開始');

					// バックアップの公開予約ディレクトリの存在確認
					if ( file_exists(self::PATH_CREATE_DIR . self::PATH_BACKUP . $dirname) ) {

						// 本番ソースからバックアップディレクトリへコピー

						// $honban_realpath = $current_dir . "/" . self::PATH_PROJECT_DIR;

						$this->debug_echo('　□カレントディレクトリ：');
						$this->debug_echo(realpath('.'));

						// TODO:ログフォルダに出力する
						$command = 'rsync -avzP ' . self::HONBAN_REALPATH  . '/' . ' ' . self::PATH_CREATE_DIR . self::PATH_BACKUP . $dirname . '/' . ' --log-file=' . self::PATH_CREATE_DIR . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

						$this->debug_echo('　□$command：');
						$this->debug_echo($command);

						$ret = $this->command_execute($command, true);

						$this->debug_echo('　▼本番バックアップの処理結果');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}
					} else {
							// エラー処理
							throw new \Exception('Backup directory not found.');
					}


					/**
			 		* 公開予約ソースを「WAITING」ディレクトリから「running」ディレクトリへ移動
					*/
					// runningの公開予約ディレクトリの存在確認
					if ( file_exists(self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname) ) {

						// TODO:ログフォルダに出力する
						$command = 'rsync -avzP --remove-source-files ' . self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname . '/' . ' ' . self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname . '/' . ' --log-file=' . self::PATH_CREATE_DIR . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

						$this->debug_echo('　□$command：');
						$this->debug_echo($command);

						$ret = $this->command_execute($command, true);

						$this->debug_echo('　▼RUNNINGへの移動の処理結果');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}


						// waitingの空ディレクトリを削除する
						$command = 'find ' .  self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname . '/ -type d -empty -delete' ;

						$this->debug_echo('　□$command：');
						$this->debug_echo($command);

						$ret = $this->command_execute($command, true);

						$this->debug_echo('　▼Waitingディレクトリの削除');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}

						// waitingディレクトリの削除が成功していることを確認
						if ( file_exists(self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname) ) {
							// ディレクトリが削除できていない場合
							// エラー処理
							throw new \Exception('Delete of waiting publish directory failed.');
						}
					} else {
							// エラー処理
							throw new \Exception('Waiting directory not found.');
					}

					/**
			 		* 「running」ディレクトリへ移動した公開予約ソースを本番環境へ同期
					*/
					// 本番ディレクトリの存在確認
					if ( file_exists(self::HONBAN_REALPATH) ) {

						// runningから本番ディレクトリへコピー

						// $honban_realpath = $current_dir . "/" . self::PATH_PROJECT_DIR;

						$this->debug_echo('　□カレントディレクトリ：');
						$this->debug_echo(realpath('.'));

						// TODO:ログフォルダに出力する
						// $command = 'rsync -avzP ' . self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname . '/' . ' ' . self::HONBAN_REALPATH . ' --log-file=' . self::PATH_CREATE_DIR . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

						// ★-aではエラーとなる。最低限の同期とする！所有者やグループは変更しない！
						// r ディレクトリを再帰的に調べる。
						// -l シンボリックリンクをリンクとして扱う
						// -p パーミッションも含める
						// -t 更新時刻などの時刻情報も含める
						// -o 所有者情報も含める
						// -g ファイルのグループ情報も含める
						// -D デバイスファイルはそのままデバイスとして扱う
						$command = 'rsync -rtvzP --delete ' . self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname . '/' . ' ' . self::HONBAN_REALPATH . '/' . ' ' . '--log-file=' . self::PATH_CREATE_DIR . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

						$this->debug_echo('　□$command：');
						$this->debug_echo($command);

						$ret = $this->command_execute($command, true);

						$this->debug_echo('　▼本番反映の処理結果');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}

					} else {
							// エラー処理
							throw new \Exception('Running directory not found.');
					}

					/**
			 		* 同期が正常終了したら、公開済みソースを「running」ディレクトリから「released」ディレクトリへ移動
					*/
					// runningの公開予約ディレクトリの存在確認
					if ( file_exists(self::PATH_CREATE_DIR . self::PATH_RELEASED . $dirname) ) {

						// TODO:ログフォルダに出力する
						$command = 'rsync -avzP --remove-source-files ' . self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname . '/' . ' ' . self::PATH_CREATE_DIR . self::PATH_RELEASED . $dirname . '/' . ' --log-file=' . self::PATH_CREATE_DIR . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

						$this->debug_echo('　□$command：');
						$this->debug_echo($command);

						$ret = $this->command_execute($command, true);

						$this->debug_echo('　▼REALEASEDへの移動の処理結果');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}


						// runningの空ディレクトリを削除する
						$command = 'find ' .  self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname . '/ -type d -empty -delete' ;

						$this->debug_echo('　□$command：');
						$this->debug_echo($command);

						$ret = $this->command_execute($command, true);

						$this->debug_echo('　▼Runningディレクトリの削除');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}

						// waitingディレクトリの削除が成功していることを確認
						if ( file_exists(self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname) ) {
							// ディレクトリが削除できていない場合
							// エラー処理
							throw new \Exception('Delete of waiting publish directory failed.');
						}
					} else {
							// エラー処理
							throw new \Exception('Running publish directory not found.');
					}

				}


				$this->debug_echo('　□処理結果テーブルの更新処理');


				// GMTの現在日時
				// $end_datetime = gmdate(self::DATETIME_FORMAT);
				// $end_datetime_dir = gmdate(self::DATETIME_FORMAT_SAVE);

				/**
		 		* 処理結果テーブルの更新処理
				*/
		 		$ret = $this->update_ts_result($insert_id);

				foreach ( (array)$ret['output'] as $element ) {
					$this->debug_echo($element);
				}

				// 実施済み一覧CSVにファイルロックが掛かっていない場合
				// 　ロックを掛ける
		 		// 「実施開始日時」を設定
		 		// 「実施終了日時」を設定
		 		
				// 公開が成功した場合
		 		// 　「公開完了日時」を設定

		 		// 公開が失敗し、復元が完了した場合
		 		// 　「復元完了日時」を設定

		 		// ▼優先度低
		 		// 本番環境と前回分のソースに差分が存在した場合
		 		// 　「差分確認フラグ1」を設定

		 		// ▼優先度低
		 		// 本番環境と今回分のソースに差分が存在した場合
		 		// 　「差分確認フラグ2」を設定

		 		// ▼優先度低
		 		// 今回分と前回分のソースに差分が存在した場合
		 		// 　「差分確認フラグ3」を設定

			} else {

				$this->debug_echo('　□ 公開対象が存在しない');

			}
		
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

		$this->debug_echo('■ sokuji_release end');

		return json_encode($result);
	}


	/**
	 * 公開対象の公開予約日時をフォーマット指定して返却する
	 *	 
	 * @param $array_list  = ソート対象の配列
	 * @param $sort_column = ソートするカラム
	 * @param $sort_kind   = ソートの種類（昇順、降順）
	 *	 
	 * @return 公開予約日時
	 */
	private function get_datetime_str($array_list, $sort_column, $sort_kind) {

		$this->debug_echo('■ get_datetime_str start');

		$ret_str = '';
		$lead_array = array();

		if (!empty($array_list)) {

			// $this->sort_list($array_list, $sort_name, $sort_kind);

			$lead_array = array_shift($array_list);
			
			// 先頭行の公開予約日時
			$ret_str = $this->format_datetime($lead_array[self::TS_RESERVE_COLUMN_RESERVE], self::DATETIME_FORMAT_SAVE);
		}

		$this->debug_echo('　□return ：' . $ret_str);

		$this->debug_echo('■ get_datetime_str end');

		return $ret_str;
	}


	/**
	 * ディレクトリの存在チェックをし、存在しなかった場合は作成する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	private function is_exists_mkdir($dirname) {

		$this->debug_echo('■ is_exists_mkdir start');

		$ret = true;

		if ( !file_exists($dirname) ) {

			// デプロイ先のディレクトリを作成
			if ( !mkdir($dirname, 0777)) {

				$ret = false;
			}
		}

		$this->debug_echo('■ is_exists_mkdir end');

		return $ret;
	}

	/**
	 * ディレクトリの再作成
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	private function is_exists_remkdir($dirpath) {
		
		$this->debug_echo('■ is_exists_remkdir start');
		$this->debug_echo('　■ $dirpath：' . $dirpath);

		if ( file_exists($dirpath) ) {
			$this->debug_echo('　■ $dirpath2：' . $dirpath);

			// 削除
			$command = 'rm -rf --preserve-root '. $dirpath;
			$ret = $this->command_execute($command, true);

			if ( $ret['return'] !== 0 ) {
				$this->debug_echo('[既存ディレクトリ削除失敗]');
				return false;
			}
		}

		// デプロイ先のディレクトリを作成
		if ( !file_exists($dirpath)) {
			if ( !mkdir($dirpath, 0777) ) {
				$this->debug_echo('　□ [再作成失敗]$dirpath：' . $dirpath);
				return false;
			}
		} else {
			$this->debug_echo('　□ [既存ディレクトリが残っている]$dirpath：' . $dirpath);
			return false;
		}
	
		$this->debug_echo('■ is_exists_remkdir end');

		return true;
	}

	/**
	 * コマンド実行処理
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function command_execute($command, $captureStderr) {
	
		$this->debug_echo('■ execute start');

	    $output = array();
	    $return = 0;

	    // 標準出力とエラー出力を両方とも出力する
	    if ($captureStderr === true) {
	        $command .= ' 2>&1';
	    }

	    exec($command, $output, $return);

		$this->debug_echo('■ execute end');

	    return array('output' => $output, 'return' => $return);
	}


	/**
	 * 引数日時を引数タイムゾーンの日時へ変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function convert_timezone_datetime($datetime) {
	
		// $this->debug_echo('■ convert_timezone_datetime start');

		$ret = '';

		if ($datetime) {
			// サーバのタイムゾーン取得
			$timezone = date_default_timezone_get();

			// $this->debug_echo('　□timezone：' . $timezone);

			// TODO:GMTと指定しているので、指定しなくてもわかるようにテーブルへ保持させる
			$t = new \DateTime($datetime, new \DateTimeZone(self::GMT));

			// タイムゾーン変更
			$t->setTimeZone(new \DateTimeZone($timezone));
		
			$ret = $t->format(DATE_ATOM);
		}
		
		// $this->debug_echo('タイムゾーン：' . $timezone);

		// $this->debug_echo('　□変換前の時刻：' . $datetime);
		// $this->debug_echo('　□変換後の時刻：'. $ret);
		
		// $this->debug_echo('■ convert_timezone_datetime end');

	    return $ret;
	}


	/**
	 * 引数日時をGMTの日時へ変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function convert_to_gmt_datetime($datetime) {
	
		$this->debug_echo('■ convert_to_gmt_datetime start');

		$ret = '';

		if ($datetime) {
			// サーバのタイムゾーン取得
			$timezone = date_default_timezone_get();

			// $this->debug_echo('　□timezone：' . $timezone);

			// TODO:ここでタイムゾーンを指定しない方法も調べる
			$t = new \DateTime($datetime, new \DateTimeZone($timezone));

			// タイムゾーン変更
			$t->setTimeZone(new \DateTimeZone(self::GMT));
		
			$ret = $t->format(DATE_ATOM);
		}
		
		// $this->debug_echo('タイムゾーン：' . $timezone);

		$this->debug_echo('　□変換前の時刻：' . $datetime);
		$this->debug_echo('　□変換後の時刻：'. $ret);
		
		$this->debug_echo('■ convert_to_gmt_datetime end');

	    return $ret;
	}

	/**
	 * 引数日時を引数タイムゾーンの日時へ変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function format_datetime($datetime, $format) {
	
		// $this->debug_echo('■ format_datetime start');

		$ret = '';

		if ($datetime) {
			$ret = date($format, strtotime($datetime));
		}

		// $this->debug_echo('　□変換前の時刻：');
		// $this->debug_echo($datetime);
		// $this->debug_echo('　□変換後の時刻：');
		// $this->debug_echo($ret);
	
		// $this->debug_echo('■ format_datetime end');

	    return $ret;
	}

	/**
	 * 公開予約テーブルの情報を変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function convert_ts_reserve_entity($array) {
	
		// $this->debug_echo('■ convert_ts_reserve_entity start');

		$entity = array();

		// ID
		$entity[self::RESERVE_ENTITY_ID] = $array[self::TS_RESERVE_COLUMN_ID];
		
		// 公開予約日時
		// タイムゾーンの時刻へ変換
		$tz_datetime = $this->convert_timezone_datetime($array[self::TS_RESERVE_COLUMN_RESERVE]);
		$entity[self::RESERVE_ENTITY_RESERVE] = $tz_datetime;
		$entity[self::RESERVE_ENTITY_RESERVE_DISPLAY] = $this->format_datetime($tz_datetime, self::DATETIME_FORMAT_DISPLAY);
		$entity[self::RESERVE_ENTITY_RESERVE_DATE] = $this->format_datetime($tz_datetime, self::DATE_FORMAT_YMD);
		$entity[self::RESERVE_ENTITY_RESERVE_TIME] = $this->format_datetime($tz_datetime, self::TIME_FORMAT_HI);

		// ブランチ
		$entity[self::RESERVE_ENTITY_BRANCH] = $array[self::TS_RESERVE_COLUMN_BRANCH];
		// コミット
		$entity[self::RESERVE_ENTITY_COMMIT] = $array[self::TS_RESERVE_COLUMN_COMMIT];
		// コメント
		$entity[self::RESERVE_ENTITY_COMMENT] = $array[self::TS_RESERVE_COLUMN_COMMENT];
	
		// $this->debug_echo('■ convert_ts_reserve_entity end');

	    return $entity;
	}


	/**
	 * 処理結果テーブルの情報を変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function convert_ts_result_entity($array) {
	
		// $this->debug_echo('■ convert_ts_result_entity start');

		$entity = array();

		// ID
		$entity[self::RESULT_ENTITY_ID] = $array[self::TS_RESULT_COLUMN_ID];
		
		// 公開予約日時
		// タイムゾーンの時刻へ変換
		$tz_datetime = $this->convert_timezone_datetime($array[self::TS_RESULT_COLUMN_RESERVE]);

		$entity[self::RESULT_ENTITY_RESERVE] = $tz_datetime;
		$entity[self::RESULT_ENTITY_RESERVE_DISPLAY] = $this->format_datetime($tz_datetime, self::DATETIME_FORMAT_DISPLAY);

		// 処理開始日時
		// タイムゾーンの時刻へ変換
		$tz_datetime = $this->convert_timezone_datetime($array[self::TS_RESULT_COLUMN_START]);

		$entity[self::RESULT_ENTITY_START] = $tz_datetime;
		$entity[self::RESULT_ENTITY_START_DISPLAY] = $this->format_datetime($tz_datetime, self::DATETIME_FORMAT_DISPLAY);


		// 処理終了日時
		// タイムゾーンの時刻へ変換
		$tz_datetime = $this->convert_timezone_datetime($array[self::TS_RESULT_COLUMN_END]);
		
		$entity[self::RESULT_ENTITY_END] = $tz_datetime;
		$entity[self::RESULT_ENTITY_END_DISPLAY] = $this->format_datetime($tz_datetime, self::DATETIME_FORMAT_DISPLAY);


		// ブランチ
		$entity[self::RESULT_ENTITY_BRANCH] = $array[self::TS_RESULT_COLUMN_BRANCH];
		// コミット
		$entity[self::RESULT_ENTITY_COMMIT] = $array[self::TS_RESULT_COLUMN_COMMIT];
		// コメント
		$entity[self::RESULT_ENTITY_COMMENT] = $array[self::TS_RESULT_COLUMN_COMMENT];
	
		// 状態
		$entity[self::RESULT_ENTITY_STATUS] = $this->convert_status($array[self::TS_RESULT_COLUMN_STATUS]);

		// 公開種別
		$entity[self::RESULT_ENTITY_TYPE] = $this->convert_publish_type($array[self::TS_RESULT_COLUMN_TYPE]);

		// $this->debug_echo('■ convert_ts_result_entity end');

	    return $entity;
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

