<?php

namespace indigo;

class main
{
	public $options;

	private $fileManager;

	private $pdoManager;

	private $tsReserve;

	private $tsOutput;

	private $publish;
	/**
	 * PDOインスタンス
	 */
	private $dbh;
	
	
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
	// 日時フォーマット_保存用（YmdHis）
	const DATETIME_FORMAT_RESERVE = "YmdHis_reserve";

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
	const INPUT_MODE_immediate = 5;
	// 即時公開戻り表示モード
	const INPUT_MODE_immediate_BACK = 6;

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
	// masterディレクトリパス
	const PATH_MASTER = '/master_repository/';


	/**
	 * 公開予約エンティティのカラム定義
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
	 * 公開処理結果エンティティのカラム定義
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
	
	/**
	 * 削除フラグ
	 */
	const DELETE_FLG_ON = 1;	// 削除済み
	const DELETE_FLG_OFF = 0;	// 未削除


	/**
	 * コミットハッシュ値
	 */
	public $commit_hash = '';

	/**
	 * 入力画面のエラーメッセージ
	 */
	private $input_error_message = '';


	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {

		$this->options = json_decode(json_encode($options));
		$this->fileManager = new fileManager($this);
		$this->pdoManager = new pdoManager($this);
		$this->tsReserve = new tsReserve($this);
		$this->tsOutput = new tsOutput($this);
		$this->publish = new publish($this);
	}

	/**
	 * Gitのmaster情報を取得
	 */
	private function init() {

		$this->debug_echo('■ init start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		// masterディレクトリの絶対パス
		$master_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_MASTER));

		$this->debug_echo('　□ master_real_path：');
		$this->debug_echo($master_real_path);


		set_time_limit(0);

		try {

			if ( $master_real_path ) {

				// デプロイ先のディレクトリが無い場合は作成
				if ( !$this->fileManager->is_exists_mkdir( $master_real_path ) ) {
					// ディレクトリ作成に失敗
					throw new \Exception('Creation of master directory failed.');
				}

				// 「.git」フォルダが存在すれば初期化済みと判定
				if ( !file_exists( $master_real_path . "/.git") ) {
					// 存在しない場合

					// ディレクトリ移動
					if ( chdir( $master_real_path ) ) {

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
						// ディレクトリ移動に失敗
						throw new \Exception('Move to master directory failed.');
					}
				}
			}

		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);

			$this->debug_echo('■ init error end');

			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->debug_echo('■ init end');

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

		$output_array = array();
		$result = array('status' => true,
						'message' => '');

		try {

			// masterディレクトリの絶対パス
			$master_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_MASTER));

			$this->debug_echo('　□ master_real_path：');
			$this->debug_echo($master_real_path);

			if ( chdir( $master_real_path )) {

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
				// ディレクトリ移動に失敗
				throw new \Exception('Move to master directory failed.');
			}

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);

			$this->debug_echo('■ get_branch_list error end');
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
		
			$ret =  '？（処理中）';
		
		} else if ($status == self::PUBLISH_STATUS_SUCCESS) {
			
			$ret =  '〇（公開成功）';

		} else if ($status == self::PUBLISH_STATUS_ALERT) {
			
			$ret =  '△（警告あり）';

		} else if ($status == self::PUBLISH_STATUS_FAILED) {
			
			$ret =  '×（公開失敗）';
			
		} else if ($status == self::PUBLISH_STATUS_SKIP) {
			
			$ret =  '-（スキップ）';
			
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

		} else if ($publish_type == self::PUBLISH_TYPE_IMMEDIATE) {
			
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
		$now = $this->get_current_datetime_of_gmt();

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
	 * GMTの現在時刻を取得
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	public function get_current_datetime_of_gmt() {

		return gmdate(DATE_ATOM, time());
	}


	// /**
	//  * タイムゾーンの現在時刻を取得
	//  *	 
	//  * @return 
	//  *  一致する場合：selected（文字列）
	//  *  一致しない場合：空文字
	//  */
	// private function get_current_datetime_of_timezone() {

	// 	return date(DATE_ATOM, time());
	// }

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

		// 画面選択された公開予約情報を取得
		$selected_id =  $this->options->_POST->selected_id;

		$selected_data = $this->tsReserve->get_selected_ts_reserve($this->dbh, $selected_id);
		
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
	private function disp_immediate_dialog() {
		
		$this->debug_echo('■ disp_immediate_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(self::INPUT_MODE_immediate, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_immediate_dialog end');

		return $ret;
	}


	/**
	 * 即時ダイアログの戻り表示
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	private function disp_back_immediate_dialog() {
		
		$this->debug_echo('■ disp_back_immediate_dialog start');

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
		$ret = $this->create_dialog_html(self::INPUT_MODE_immediate_BACK, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_back_immediate_dialog end');

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

		} elseif ( ($input_mode == self::INPUT_MODE_immediate) || ($input_mode == self::INPUT_MODE_immediate_BACK) ) {
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
		if ( ($input_mode == self::INPUT_MODE_immediate) || ($input_mode == self::INPUT_MODE_immediate_BACK) ) {

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

		} elseif ( ($input_mode == self::INPUT_MODE_immediate) ||  ($input_mode == self::INPUT_MODE_immediate_BACK) ) {
		  	$ret .= '<li><input type="submit" id="immediate_check_btn" name="immediate_check" class="px2-btn px2-btn--danger" value="確認"/></li>';

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

	// 	$this->debug_echo('　□ $init_trans_flg：' . $init_trans_flg);

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
	// 		$selected_ret = $this->get_selected_ts_reserve();
			
	// 		$this->debug_echo('　□ selected_ret ：' . $selected_ret);
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

	// 	$this->debug_echo('　□ ret ：');
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

		// 画面入力された日時を結合し、GMTへ変換する
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($reserve_date, $reserve_time);

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
			. '<input type="hidden" name="gmt_reserve_datetime" value="' . $gmt_reserve_datetime . '"/>'
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

		// 画面入力された日時を結合し、GMTへ変換する
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($reserve_date, $reserve_time);
	
		$before_branch_select_value = "";
		$before_reserve_date = "";
		$before_reserve_time = "";
		$before_comment = "";
		$before_gmt_reserve_datetime = "";

		// 画面選択された変更前の公開予約情報を取得
		$selected_id =  $this->options->_POST->selected_id;
		$selected_data = $this->tsReserve->get_selected_ts_reserve($this->dbh, $selected_id);

		if ($selected_data) {

			$before_branch_select_value = $selected_data[self::RESERVE_ENTITY_BRANCH];
			$before_reserve_date = $selected_data[self::RESERVE_ENTITY_RESERVE_DATE];
			$before_reserve_time = $selected_data[self::RESERVE_ENTITY_RESERVE_TIME];
			$before_comment = $selected_data[self::RESERVE_ENTITY_COMMENT];
	
			// 画面入力された日時を結合し、GMTへ変換する
			$before_gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($before_reserve_date, $before_reserve_time);
		
		}

		$img_filename = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::IMG_ARROW_RIGHT));

		$ret = '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>変更確認</h4>'
			. '<form method="post">'
			. '<div class="colum_3">'
			. '<div class="left_box">';

		$ret .= '<table class="table table-striped">';
	
		// 「ブランチ」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $before_branch_select_value . '</td>'
			. '</tr>';
		
		// 「コミット」項目（変更前）
		// $ret .= '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'
		
		// 「公開予約日時」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . '公開予約日時' . '</td>'
			. '<td>' . $before_reserve_date . ' ' . $before_reserve_time . '</td>'
			. '<input type="hidden" name="before_gmt_reserve_datetime" value="' . $before_gmt_reserve_datetime . '"/>'
			. '</tr>';
		
		// 「コメント」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . $before_comment . '</td>'
			. '</tr>'
			. '</tbody></table>'
			
		    . '</div>'

		    . '<div class="center_box">'
		    . '<img src="'. $img_filename .'"/>'
		    . '</div>'

            . '<div class="right_box">'
			. '<table class="table table-striped" style="width: 100%">'
		    . '<input type="hidden" name="selected_id" value="' . $this->options->_POST->selected_id . '"/>'

			// 「ブランチ」項目（変更後）
			. '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $this->options->_POST->branch_select_value . '</td>'
			. '<input type="hidden" name="branch_select_value" value="' . $branch_select_value . '"/>'
			. '</tr>'

			// 「コミット」項目（変更後）			
			// . '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'

			// 「公開日時」項目（変更後）
			. '<tr>'
			. '<td class="dialog_thead">' . '公開予約日時' . '</td>'
			. '<td>' . $reserve_date . ' ' . $reserve_time . '</td>'
			. '<input type="hidden" name="reserve_date" value="' . $reserve_date . '"/>'
			. '<input type="hidden" name="reserve_time" value="' . $reserve_time . '"/>'	 
			. '<input type="hidden" name="gmt_reserve_datetime" value="' . $gmt_reserve_datetime . '"/>'
			. '</tr>'

			// 「コメント」項目（変更後）
			. '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . $comment . '</td>'
			. '<input type="hidden" name="comment" value="' . $comment . '"/>'
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
	private function disp_check_immediate_dialog() {
		
		$this->debug_echo('■ disp_check_immediate_dialog start');

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
		$ret .= '<li><input type="submit" id="confirm_btn" name="immediate_confirm" class="px2-btn px2-btn--danger" value="確定（注意：本番環境への公開処理が開始されます）"/></li>';
		
		// 「キャンセル」ボタン
		$ret .= '<li><input type="submit" id="back_btn" name="immediate_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'

			. '</div>'
			 . '</div>'

			. '</form>'
			 . '</div>'
			 . '</div></div></div>';

		$this->debug_echo('■ disp_check_immediate_dialog end');

		return $ret;
	}


	/**
	 * 新規ダイアログの確定処理
	 *	 
	 * @return 確認ダイアログ出力内容
	 */
	private function do_add_confirm() {
		
		$this->debug_echo('■ do_add_confirm start');
	
		$current_dir = realpath('.');
	
		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			//============================================================
			// 指定ブランチのGit情報を「waiting」ディレクトリへコピー
			//============================================================

	 		$this->debug_echo('　□ -----Gitのファイルコピー処理-----');
			
			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_WAITING));

			// 公開予約ディレクトリ名の取得
			$dirname = $this->format_gmt_datetime($this->options->_POST->gmt_reserve_datetime, self::DATETIME_FORMAT_SAVE) . '_reserve';

			$this->debug_echo('　□ 公開予約ディレクトリ：');
			$this->debug_echo($dirname);

			// コピー処理
			$ret = json_decode($this->file_copy($waiting_real_path, $dirname));

			if ( !$ret->status ) {
				throw new \Exception('Git file copy failed.');
			}

	 		$this->debug_echo('　□ -----公開処理結果テーブルの登録処理-----');
			
			//============================================================
			// 入力情報を公開予約テーブルへ登録
			//============================================================
			$this->tsReserve->insert_ts_reserve($this->dbh, $this->options, $this->commit_hash);
			
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

		$this->debug_echo('■ do_add_confirm end');

		return json_encode($result);
	}


	/**
	 * 変更ダイアログの確定処理
	 *	 
	 * @return 確認ダイアログ出力内容
	 */
	private function do_update_confirm() {
		
		$this->debug_echo('■ do_add_confirm start');
	
		$current_dir = realpath('.');
	
		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_WAITING));

			//============================================================
			// 「waiting」ディレクトリの変更前の公開ソースディレクトリを削除
			//============================================================
			// 変更前の公開予約ディレクトリ名の取得
			$before_dirname = $this->format_gmt_datetime($this->options->_POST->before_gmt_reserve_datetime, self::DATETIME_FORMAT_SAVE) . '_reserve';

			$this->debug_echo('　□ 変更前の公開予約ディレクトリ：');
			$this->debug_echo($before_dirname);

			// コピー処理
			$ret = json_decode($this->file_delete($waiting_real_path, $before_dirname));

			if ( !$ret->status ) {
				throw new \Exception('Git file delete failed.');
			}

			//============================================================
			// 変更後ブランチのGit情報を「waiting」ディレクトリへコピー
			//============================================================
			// 公開予約ディレクトリ名の取得
			$dirname = $this->format_gmt_datetime($this->options->_POST->gmt_reserve_datetime, self::DATETIME_FORMAT_SAVE) . '_reserve';

			$this->debug_echo('　□ 変更後の公開予約ディレクトリ：');
			$this->debug_echo($dirname);

			// コピー処理
			$ret = json_decode($this->file_copy($waiting_real_path, $dirname));

			if ( !$ret->status ) {
				throw new \Exception('Git file copy failed.');
			}

	 		$this->debug_echo('　□ -----公開処理結果テーブルの更新処理-----');
			
			//============================================================
			// 入力情報を公開予約テーブルへ更新
			//============================================================
			$selected_id =  $this->options->_POST->selected_id;

			$this->tsReserve->update_reserve_table($this->dbh, $this->options, $selected_id, $this->commit_hash);
			
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

		$this->debug_echo('■ do_add_confirm end');

		return json_encode($result);
	}

	/**
	 * 削除処理
	 *	 
	 * @return 確認ダイアログ出力内容
	 */
	private function do_delete() {
		
		$this->debug_echo('■ do_delete start');
	
		$current_dir = realpath('.');
	
		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_WAITING));

			//============================================================
			// 「waiting」ディレクトリの変更前の公開ソースディレクトリを削除
			//============================================================
			// 公開予約ディレクトリ名の取得
			$selected_id =  $this->options->_POST->selected_id;
			$selected_ret = $this->tsReserve->get_selected_ts_reserve($this->dbh, $selected_id);
			$dirname = $this->format_gmt_datetime($selected_ret[self::RESERVE_ENTITY_RESERVE], self::DATETIME_FORMAT_SAVE) . '_reserve';

			$this->debug_echo('　□ 公開予約ディレクトリ：');
			$this->debug_echo($dirname);

			// コピー処理
			$ret = json_decode($this->file_delete($waiting_real_path, $dirname));

			if ( !$ret->status ) {
				throw new \Exception('Git file delete failed.');
			}
			$this->debug_echo('　□ -----公開処理結果テーブルの削除処理-----');
			
			//============================================================
			// 入力情報を公開予約テーブルへ更新
			//============================================================
			$this->tsReserve->delete_reserve_table($this->dbh, $selected_id);
			
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

		$this->debug_echo('■ do_delete end');

		return json_encode($result);
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
		$data_list = $this->tsReserve->get_ts_reserve_list($this->dbh);
	
		// 画面入力された日時を結合し、GMTへ変換する
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($reserve_date, $reserve_time);

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
		if (!$this->check_future_date($gmt_reserve_datetime)) {
			$ret .= '<p class="error_message">「公開予約日時」は未来日時を設定してください。</p>';
		}

		// ブランチの重複チェック
		if (!$this->check_exist_branch($data_list, $branch_select_value, $selected_id)) {
			$ret .= '<p class="error_message">1つのブランチで複数の公開予約を作成することはできません。</p>';
		}

		// 公開予約日時の重複チェック
		if (!$this->check_exist_reserve($data_list, $gmt_reserve_datetime, $selected_id)) {
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
		$data_list = $this->tsReserve->get_ts_reserve_list($this->dbh);

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
			. '<li><input type="submit" id="immediate_btn" name="immediate" class="px2-btn px2-btn--primary" value="即時公開"/></li>'
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

		// 公開処理結果一覧を取得
		$data_list = $this->tsOutput->get_ts_output_list($this->dbh, null);

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

		try {

			//============================================================
			// タイムゾーンの設定
			//============================================================
			$time_zone = $this->options->time_zone;
			if (!$time_zone) {
				throw new \Exception('Parameter of timezone not found.');
			}
			date_default_timezone_set($time_zone);

			//============================================================
			// データベース接続
			//============================================================
			$this->dbh = $this->pdoManager->connect();

			//============================================================
			// テーブル作成（既にある場合は作成しない）
			//============================================================
			$this->pdoManager->create_table($this->dbh);

			//============================================================
			// 作業用ディレクトリの作成（既にある場合は作成しない）
			//============================================================
			$this->create_work_dir();

			//============================================================
			// Gitのmaster情報取得
			//============================================================
			$ret = json_decode($this->init());
			
			if ( !$ret->status ) {
			
				$alert_message = 'initialized faild';
			
			} else {
		
				//============================================================
				// 新規関連処理
				//============================================================
				if (isset($this->options->_POST->add)) {
					// 初期表示画面の「新規」ボタン押下
					$dialog_disp = $this->disp_add_dialog();

				} elseif (isset($this->options->_POST->add_check)) {
					// 新規ダイアログの「確認」ボタン押下

					// 入力チェック処理（一時的にコメント）
					// $this->input_error_message = $this->do_validation_check(self::INPUT_MODE_ADD);

					if ($input_error_message) {
						// エラーがあるので入力ダイアログのまま
						$dialog_disp = $this->disp_back_add_dialog();
					} else {
						// エラーがないので確認ダイアログへ遷移
						$dialog_disp = $this->disp_check_add_dialog();
					}
					
				} elseif (isset($this->options->_POST->add_confirm)) {
					// 新規確認ダイアログの「確定」ボタン押下

					$ret = json_decode($this->do_add_confirm());	
					if ( !$ret->status ) {
						$alert_message = 'add confirm faild';
					}

				} elseif (isset($this->options->_POST->add_back)) {
					// 新規確認ダイアログの「戻る」ボタン押下
					$dialog_disp = $this->disp_back_add_dialog();

				//============================================================
				// 変更関連処理
				//============================================================
				} elseif (isset($this->options->_POST->update)) {
					// 初期表示画面の「変更」ボタン押下
					$dialog_disp = $this->disp_update_dialog();


				} elseif (isset($this->options->_POST->update_check)) {
				// 変更ダイアログの「確認」ボタン押下

					// 入力チェック処理（一時的にコメント）
					// $this->input_error_message = $this->do_validation_check(self::INPUT_MODE_UPDATE);

					if ($this->input_error_message) {
						// エラーがあるので入力ダイアログのまま
						$dialog_disp = $this->disp_update_dialog();
					} else {
						// エラーがないので確認ダイアログへ遷移
						$dialog_disp = $this->disp_check_update_dialog();
					}	

				} elseif (isset($this->options->_POST->update_confirm)) {
					// 変更確認ダイアログの「確定」ボタン押下
					
					$ret = json_decode($this->do_update_confirm());	
					if ( !$ret->status ) {
						$alert_message = 'update confirm faild';
					}

				} elseif (isset($this->options->_POST->update_back)) {
					// 変更確認ダイアログの「戻る」ボタン押下	
					$dialog_disp = $this->disp_back_update_dialog();

				//============================================================
				// 削除処理
				//============================================================
				} elseif (isset($this->options->_POST->delete)) {
					// 初期表示画面の「削除」ボタン押下				

					// Gitファイルの削除
					$ret = json_decode($this->do_delete());
					if ( !$ret->status ) {				
						$alert_message = 'delete faild';
					}

				//============================================================
				// 即時公開処理
				//============================================================
				} elseif (isset($this->options->_POST->immediate)) {
					// 初期表示画面の「即時公開」ボタン押下				
					$dialog_disp = $this->disp_immediate_dialog();

				} elseif (isset($this->options->_POST->immediate_check)) {
					// 即時公開ダイアログの「確認」ボタン押下
	
					// 入力チェック処理（一時的にコメント）
					// $this->input_error_message = $this->do_validation_check(self::INPUT_MODE_immediate);

					if ($this->input_error_message) {
						// エラーがあるので入力ダイアログのまま
						$dialog_disp = $this->disp_immediate_dialog();
					} else {
						// エラーがないので確認ダイアログへ遷移
						$dialog_disp = $this->disp_check_immediate_dialog();
					}	

				} elseif (isset($this->options->_POST->immediate_confirm)) {
					// 即時公開確認ダイアログの「確定」ボタン押下	
					
					$ret = json_decode($this->do_immediate_publish());
					if ( !$ret->status ) {
						$alert_message = 'Immediate publish faild';
					}

				} elseif (isset($this->options->_POST->immediate_back)) {
					// 即時公開確認ダイアログの「戻る」ボタン押下					
					$dialog_disp = $this->disp_back_immediate_dialog();
				}
			}

			if ( !$ret->status ) {
				// 処理失敗の場合

				// エラーメッセージ表示
				$dialog_disp = '
				<script type="text/javascript">
					console.error(' . "'" . $ret->message. "'" . ');
					alert("' . $alert_message .'");
				</script>';
			}

			if (isset($this->options->_POST->history)) {
				// 初期表示画面の「履歴」ボタン押下
				$disp = $this->create_history_contents();
			} else {
				// 初期表示画面の表示
				$disp = $this->create_top_contents();
			}

			// 画面ロック用
			$disp_lock = '<div id="loader-bg"><div id="loading"></div></div>';

		} catch (\Exception $e) {

			// データベース接続を閉じる
			$this->pdoManager->close($this->dbh);

			echo $e->getMessage();

			$this->debug_echo('■ run error end');

			return;
		}
		
		// データベース接続を閉じる
		$this->pdoManager->close();

		// $this->debug_echo('■ run end');

		// 画面表示
		return $disp . $disp_lock . $dialog_disp;
	}

	/**
	 * 公開ソースディレクトリを作成し、Gitファイルのコピー
	 *
	 * @return なし
	 */
	private function file_copy($path, $dirname) {

		$this->debug_echo('■ file_copy start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// 公開ソースディレクトリの絶対パスを取得。すでに存在している場合は削除して再作成する。
			$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($path . $dirname));

			$this->debug_echo('　□ $dir_real_path' . $dir_real_path);

			if ( !$this->fileManager->is_exists_remkdir($dir_real_path) ) {
				throw new \Exception('Creation of Waiting publish directory failed.');
			}

			//============================================================
			// 対象ディレクトリに移動し、指定ブランチのGit情報を取得する
			//============================================================
			if ( chdir($dir_real_path) ) {
				$this->git_pull();
			} else {
				throw new \Exception('Publish directory not found.');
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
	 * 新規追加時のGitファイルのコピー
	 *
	 * @return なし
	 */
	private function git_pull() {

		$this->debug_echo('■ git_pull start');

		try {

			// 指定ブランチ
			$branch_name = trim(str_replace("origin/", "", $this->options->_POST->branch_select_value));

			// git init
			$command = 'git init';
			$this->command_execute($command, false);

			// git urlのセット
			$url = $this->options->git->protocol . "://" . urlencode($this->options->git->username) . ":" . urlencode($this->options->git->password) . "@" . $this->options->git->url;
			
			// initしたリポジトリに名前を付ける
			$command = 'git remote add origin ' . $url;
			$this->command_execute($command, false);

			// git fetch（リモートリポジトリの指定ブランチの情報をローカルブランチへ反映）
			$command = 'git fetch origin' . ' ' . $branch_name;
			$this->command_execute($command, false);

			// git pull（リモート取得ブランチを任意のローカルブランチにマージするコマンド）
			$command = 'git pull origin' . ' ' . $branch_name;
			$this->command_execute($command, false);
	
		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;
		
		$this->debug_echo('■ git_pull end');

		return json_encode($result);

	}

	/**
	 * 公開ソースディレクトリをGitファイルごと削除
	 *
	 * @return なし
	 */
	private function file_delete($path, $dirname) {
		
		$this->debug_echo('■ file_delete start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// 公開ソースディレクトリの絶対パスを取得
			$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($path . $dirname));

			$this->debug_echo('　□ $dir_real_path' . $dir_real_path);

			if( $dir_real_path && file_exists( $dir_real_path )) {
				// ディレクトリが存在する場合、削除コマンド実行
				$command = 'rm -rf --preserve-root '. $dir_real_path;
				$ret = $this->command_execute($command, true);

				if ( $ret['return'] !== 0 ) {
					throw new \Exception('Delete directory failed.');
				}
			} 

			// else {
			// 	throw new \Exception('Delete directory not found.');
			// }
		
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
	public function do_immediate_publish() {

		$this->debug_echo('■ do_immediate_publish start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// GMTの現在日時
			$start_datetime = $this->get_current_datetime_of_gmt();

			$this->debug_echo('　□ 現在日時：');
			$this->debug_echo($start_datetime);

			// ============================================================
			// 指定ブランチのGit情報を「running」ディレクトリへコピー
			// ============================================================

			// runningディレクトリの絶対パスを取得。
			$running_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_RUNNING));

			// 公開予約ディレクトリ名の取得
			$dirname = $this->format_gmt_datetime($start_datetime, self::DATETIME_FORMAT_SAVE);

			$this->debug_echo('　□ 公開予約ディレクトリ：');
			$this->debug_echo($dirname);

			// コピー処理
			$ret = json_decode($this->file_copy($running_real_path, $dirname));

			if ( !$ret->status ) {
				throw new \Exception('Git file copy failed.');
			}

	 		$this->debug_echo('　□ -----公開処理結果テーブルの登録処理-----');
			

			//============================================================
			// 公開処理結果テーブルの登録処理
			//============================================================
			$ret = json_decode($this->tsOutput->insert_ts_output($this->dbh, $this->main->options, $start_datetime, self::PUBLISH_TYPE_IMMEDIATE));
			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT insert failed.");
			}

			// インサートしたシーケンスIDを取得（処理終了時の更新処理にて使用）
			$insert_id = $this->dbh->lastInsertId();

			$this->debug_echo('　□ $insert_id：');
			$this->debug_echo($insert_id);


			//============================================================
			// ※公開処理※
			//============================================================
			$ret = json_decode($this->publish->do_publish($dirname));
		
			if ( !$ret->status ) {
				throw new \Exception('Publish failed.');
			}


			//============================================================
			// 公開処理結果テーブルの更新処理
			//============================================================
			// GMTの現在日時
			$end_datetime = $this->get_current_datetime_of_gmt();

	 		$ret = json_decode($this->tsOutput->update_ts_output($this->dbh, $insert_id, $end_datetime, self::PUBLISH_STATUS_SUCCESS));
	 		
			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT update failed.");
			}

		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			$this->debug_echo('■ immediate_publish error end');

			chdir($current_dir);
			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->debug_echo('■ immediate_publish end');

		return json_encode($result);
	}

	/**
	 * コマンド実行処理
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	public function command_execute($command, $captureStderr) {
	
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
	 * 引数日時を引数タイムゾーンの日時へ変換する（画面表示時の変換用）
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function convert_to_timezone_datetime($datetime) {
	
		// $this->debug_echo('■ convert_to_timezone_datetime start');

		$ret = '';

		if ($datetime) {

			$timezone = date_default_timezone_get();
			$t = new \DateTime($datetime, new \DateTimeZone(self::GMT));
			$t->setTimeZone(new \DateTimeZone($timezone));
			$ret = $t->format(DATE_ATOM);
		
			// $this->debug_echo('タイムゾーン：' . $timezone);
		}

		// $this->debug_echo('　□変換前の時刻（GMT）：' . $datetime);
		// $this->debug_echo('　□変換後の時刻：'. $ret);
		
		// $this->debug_echo('■ convert_to_timezone_datetime end');

	    return $ret;
	}


	/**
	 * 引数の日付と日時を結合し、GMTの日時へ変換する
	 *	 
	 * @param $date = 設定タイムゾーンの日付
	 * @param $time = 設定タイムゾーンの時刻
	 *	 
	 * @return GMT日時
	 */
	function combine_to_gmt_date_and_time($date, $time) {
	
		// $this->debug_echo('■ combine_to_gmt_date_and_time start');

		$ret = '';

		if (isset($date) && isset($time)) {

			// サーバのタイムゾーン取得
			$timezone = date_default_timezone_get();
			$t = new \DateTime($date . ' ' . $time, new \DateTimeZone($timezone));

			// タイムゾーン変更
			$t->setTimeZone(new \DateTimeZone(self::GMT));
		
			$ret = $t->format(DATE_ATOM);
			// $this->debug_echo('　□timezone：' . $timezone);
		}
		
		// $this->debug_echo('　□変換前の時刻：' . $datetime);
		// $this->debug_echo('　□変換後の時刻（GMT）：'. $ret);
		
		// $this->debug_echo('■ combine_to_gmt_date_and_time end');

	    return $ret;
	}

	// /**
	//  * 引数日時をGMTの日時へ変換する
	//  *	 
	//  * @param $path = 作成ディレクトリ名
	//  *	 
	//  * @return ソート後の配列
	//  */
	// public function convert_to_gmt_datetime($datetime) {
	
	// 	$this->debug_echo('■ convert_to_gmt_datetime start');

	// 	$ret = '';

	// 	if ($datetime) {
	// 		// サーバのタイムゾーン取得
	// 		$timezone = date_default_timezone_get();
	// 		$t = new \DateTime($datetime, new \DateTimeZone($timezone));
	// 		$t->setTimeZone(new \DateTimeZone(self::GMT));
	// 		$ret = $t->format(DATE_ATOM);
				
	// 		// $this->debug_echo('タイムゾーン：' . $timezone);
	// 	}
		
	// 	$this->debug_echo('　□変換前の時刻：' . $datetime);
	// 	$this->debug_echo('　□変換後の時刻（GMT）：'. $ret);
		
	// 	$this->debug_echo('■ convert_to_gmt_datetime end');

	//     return $ret;
	// }

	/**
	 * 日付のフォーマット変換（※設定タイムゾーン用）
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
		
		// $this->debug_echo('　★変換前の時刻：' . $datetime);
		// $this->debug_echo('　★変換後の時刻：'. $ret);

		// $this->debug_echo('■ format_datetime end');

	    return $ret;
	}

	/**
	 * 日付のフォーマット変換（※GMT用）
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function format_gmt_datetime($datetime, $format) {
	
		$this->debug_echo('■ format_datetime start');

		$ret = '';

		if ($datetime) {
			$ret = gmdate($format, strtotime($datetime));
		}
		
		$this->debug_echo('　★変換前の時刻：' . $datetime);
		$this->debug_echo('　★変換後の時刻：'. $ret);

		$this->debug_echo('■ format_datetime end');

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
		$entity[self::RESERVE_ENTITY_ID] = $array[tsReserve::TS_RESERVE_RESERVE_ID_SEQ];
		
		// 公開予約日時
		// タイムゾーンの時刻へ変換
		$tz_datetime = $this->convert_to_timezone_datetime($array[tsReserve::TS_RESERVE_RESERVE]);
		$entity[self::RESERVE_ENTITY_RESERVE] = $tz_datetime;
		$entity[self::RESERVE_ENTITY_RESERVE_DISPLAY] = $this->format_datetime($tz_datetime, self::DATETIME_FORMAT_DISPLAY);
		$entity[self::RESERVE_ENTITY_RESERVE_DATE] = $this->format_datetime($tz_datetime, self::DATE_FORMAT_YMD);
		$entity[self::RESERVE_ENTITY_RESERVE_TIME] = $this->format_datetime($tz_datetime, self::TIME_FORMAT_HI);

		// ブランチ
		$entity[self::RESERVE_ENTITY_BRANCH] = $array[tsReserve::TS_RESERVE_BRANCH];
		// コミット
		$entity[self::RESERVE_ENTITY_COMMIT] = $array[tsReserve::TS_RESERVE_COMMIT];
		// コメント
		$entity[self::RESERVE_ENTITY_COMMENT] = $array[tsReserve::TS_RESERVE_COMMENT];
	
		// $this->debug_echo('■ convert_ts_reserve_entity end');

	    return $entity;
	}


	/**
	 * 公開処理結果テーブルの情報を変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function convert_ts_output_entity($array) {
	
		// $this->debug_echo('■ convert_ts_output_entity start');

		$entity = array();

		// ID
		$entity[self::RESULT_ENTITY_ID] = $array[tsOutput::TS_OUTPUT_RESULT_ID_SEQ];
		
		// 公開予約日時
		// タイムゾーンの時刻へ変換
		$tz_datetime = $this->convert_to_timezone_datetime($array[tsOutput::TS_OUTPUT_RESERVE]);

		$entity[self::RESULT_ENTITY_RESERVE] = $tz_datetime;
		$entity[self::RESULT_ENTITY_RESERVE_DISPLAY] = $this->format_datetime($tz_datetime, self::DATETIME_FORMAT_DISPLAY);

		// 処理開始日時
		// タイムゾーンの時刻へ変換
		$tz_datetime = $this->convert_to_timezone_datetime($array[tsOutput::TS_OUTPUT_START]);

		$entity[self::RESULT_ENTITY_START] = $tz_datetime;
		$entity[self::RESULT_ENTITY_START_DISPLAY] = $this->format_datetime($tz_datetime, self::DATETIME_FORMAT_DISPLAY);

		// 処理終了日時
		// タイムゾーンの時刻へ変換
		$tz_datetime = $this->convert_to_timezone_datetime($array[tsOutput::TS_OUTPUT_END]);
		
		$entity[self::RESULT_ENTITY_END] = $tz_datetime;
		$entity[self::RESULT_ENTITY_END_DISPLAY] = $this->format_datetime($tz_datetime, self::DATETIME_FORMAT_DISPLAY);

		// ブランチ
		$entity[self::RESULT_ENTITY_BRANCH] = $array[tsOutput::TS_OUTPUT_BRANCH];
		// コミット
		$entity[self::RESULT_ENTITY_COMMIT] = $array[tsOutput::TS_OUTPUT_COMMIT];
		// コメント
		$entity[self::RESULT_ENTITY_COMMENT] = $array[tsOutput::TS_OUTPUT_COMMENT];
	
		// 状態
		$entity[self::RESULT_ENTITY_STATUS] = $this->convert_status($array[tsOutput::TS_OUTPUT_STATUS]);

		// 公開種別
		$entity[self::RESULT_ENTITY_TYPE] = $this->convert_publish_type($array[tsOutput::TS_OUTPUT_PUBLISH_TYPE]);

		// $this->debug_echo('■ convert_ts_output_entity end');

	    return $entity;
	}



	/**
	 * 作業用ディレクトリの作成（既にある場合は作成しない）
	 *	
	 * @return ソート後の配列
	 */
	function create_work_dir() {
	
		$this->debug_echo('■ create_work_dir start');

		$ret = true;

		// logファイルディレクトリが存在しない場合は作成
		$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_LOG));

		$this->debug_echo('　□ パス：' . $dir_real_path);

		if ( !$this->fileManager->is_exists_mkdir($dir_real_path) ) {
			$ret = false;
		}

		// backupディレクトリが存在しない場合は作成
		$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_BACKUP));

		$this->debug_echo('　□ パス：' . $dir_real_path);

		if ( !$this->fileManager->is_exists_mkdir($dir_real_path) ) {
			$ret = false;
		}

		// waitingディレクトリが存在しない場合は作成
		$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_WAITING));

		$this->debug_echo('　□ パス：' . $dir_real_path);

		if ( !$this->fileManager->is_exists_mkdir($dir_real_path) ) {
			$ret = false;
		}

		// runningディレクトリが存在しない場合は作成
		$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_RUNNING));

		$this->debug_echo('　□ パス：' . $dir_real_path);

		if ( !$this->fileManager->is_exists_mkdir($dir_real_path) ) {
			$ret = false;
		}

		// releasedディレクトリが存在しない場合は作成
		$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_RELEASED));

		$this->debug_echo('　□ パス：' . $dir_real_path);

		if ( !$this->fileManager->is_exists_mkdir($dir_real_path) ) {
			$ret = false;
		}

		$this->debug_echo('■ create_work_dir end');

		return $ret;
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

