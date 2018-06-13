<?php

namespace indigo;

class main
{
	public $options;

	private $file_control;


	// サーバのタイムゾーン
	const TIME_ZONE = 'Asia/Tokyo';
	// 時間フォーマット_表示用（Y-m-d H:i）
	const TIME_FORMAT_DISPLAY = "Y-m-d H:i";
	// 時間フォーマット_変換用（Y-m-d H:i:s）
	const TIME_FORMAT_CONV = "Y-m-d H:i:s";
	// 時間フォーマット_保存用（YmdHis）
	const TIME_FORMAT_SAVE = "YmdHis";

	// CSV区切り文字
	const CSV_DELIMITER		= ',';
	// CSV囲み文字
	const CSV_ENCLOSURE		= '"';

	// 公開予約管理CSVファイル
	const CSV_LIST_FILENAME = './../indigo_dir/csv/list.csv';
	// // 警告エラー時のお知らせCSVファイル
	// const CSV_LIST_FILENAME = './../indigo_dir/csv/alert.csv';

	/**
	 * 画像パス定義
	 */
	// 右矢印
	const IMG_ARROW_RIGHT = './../indigo_dir/images/arrow_right.png';
	// エラーアイコン
	const IMG_ERROR_ICON = './../indigo_dir/images/error_icon.png';


	/**
	 * 公開用の操作ディレクトリパス定義
	 */
	// backupディレクトリパス
	const PATH_BACKUP = './../indigo_dir/backup/';
	// copyディレクトリパス
	const PATH_COPY = './../indigo_dir/copy/';
	// logディレクトリパス
	const PATH_LOG = './../indigo_dir/log/';


	/**
	 * 公開予定管理CSVの列番号定義
	 */
	// 「入力値_公開予約日時」のカラム数
	const CSV_COLUMN_INPUT_DATETIME = 3;
	// 「公開予約日時」のカラム数
	const CSV_COLUMN_DATETIME = 4;
	// 「ステータス」列のカラム数
	const CSV_COLUMN_STATUS = 6;
	/**
	 * コミットハッシュ値
	 */
	private $commit_hash = '';

	/**
	 * サーバのタイムゾーン
	 */
	private $server_timezone = '';

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
						exec('git init', $output);

						// git urlのセット
						$url = $this->options->git->protocol . "://" . urlencode($this->options->git->username) . ":" . urlencode($this->options->git->password) . "@" . $this->options->git->url;

						exec('git remote add origin ' . $url, $output);

						// git fetch
						exec( 'git fetch origin', $output);

						// git pull
						exec( 'git pull origin master', $output);

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
				exec( 'git fetch', $output );

				// ブランチの一覧取得
				exec( 'git branch -r', $output );

				foreach ($output as $key => $value) {
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

		if ($status == 0) {
		
			$ret =  '？（公開前）';
		
		} else if ($status == 1) {
			
			$ret =  '★（公開中）';

		} else if ($status == 2) {
			
			$ret =  '〇（公開成功）';

		} else if ($status == 3) {
			
			$ret =  '△（警告あり）';
			
		} else if ($status == 4) {
			
			$ret =  '×（公開失敗）';
			
		}

		return $ret;
	}

	/**
	 * 公開予約一覧用の配列を「公開予定日時の昇順」へソートし返却する
	 *	 
	 * @param $array_list = ソート対象の配列
	 * @param $sort_name  = ソートするキー名称
	 * @param $sort_kind  = ソートの種類
	 *	 
	 * @return ソート後の配列
	 */
	private function sort_list($array_list, $sort_name, $sort_kind) {

		if (!empty($array_list)) {

			$sort_array = array();

			foreach($array_list as $key => $value) {
				$sort_array[$key] = $value[$sort_name];
			}

			// 公開予定日時の昇順へソート	
			array_multisort($sort_array, $sort_kind, $array_list);
		}

		return $array_list;
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

		foreach ($data_list as $array) {
			
			if (($array['id'] != $selected_id) && ($array['branch_name'] == $selected_branch)) {
				$ret = false;
				break;
			}
		}		

		return $ret;
	}

	/**
	 * 公開予定日時重複チェック
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

		foreach ($data_list as $array) {
			if (($array['id'] != $selected_id) && ($array['reserve_datetime'] == $input_reserve)) {
				$ret = false;
				break;
			}
		}		

		return $ret;
	}


	/**
	 * 日付変換（Y-m-i H:i:s）
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

			$ret = $date . ' ' . date('H:i:s',  strtotime($time));
		}

		return $ret;
	}

	/**
	 * 初期表示画面の新規ボタン押下時
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	private function disp_add_dialog($error_message) {
		
		$this->debug_echo('■ disp_add_dialog start');

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

		// ブランチリストを取得
		$get_branch_ret = json_decode($this->get_branch_list());
		$branch_list = array();
		$branch_list = $get_branch_ret->branch_list;

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(true, false, $error_message, $branch_list, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_add_dialog end');

		return $ret;
	}

	/**
	 * 新規ダイアログ表示の確認ボタン押下時
	 *	 
	 * @return 新規確認ダイアログの出力内容
	 */
	private function do_add_check_btn() {
		
		$this->debug_echo('■ do_add_check_btn start');

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

		// 確認ダイアログHTMLの作成
		$ret = $this->create_check_dialog_html($branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ do_add_check_btn end');

		return $ret;
	}


	/**
	 * 変更ダイアログの表示
	 *	 
	 * @param $init_trans_flg = 初期表示画面遷移フラグ
	 * @param $error_message  = エラーメッセージ出力内容
	 *
	 * @return 変更ダイアログの出力内容
	 */
	private function disp_update_dialog($init_trans_flg, $error_message) {
		
		// $ret = "";

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// $selected_id =  "";

		// 初期表示画面の変更ボタンから遷移してきた場合
		if ($init_trans_flg) {
			
			// // 選択されたID
			// $selected_id =  $this->options->_POST->selected_id;
			// 選択されたIDに紐づく情報を取得
			$selected_ret = $this->get_selected_data();
			
			$branch_select_value = $selected_ret['branch_name'];
			$reserve_date = date('Y-m-d',  strtotime($selected_ret['reserve_datetime']));
			$reserve_time = date('H:i',  strtotime($selected_ret['reserve_datetime']));
			$comment = $selected_ret['comment'];

		} else {

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
		}

		// ブランチリストを取得
		$get_branch_ret = json_decode($this->get_branch_list());
		$branch_list = array();
		$branch_list = $get_branch_ret->branch_list;

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(false, $init_trans_flg, $error_message, $branch_list, $branch_select_value, $reserve_date, $reserve_time, $comment);

		return $ret;
	}

	/**
	 * 変更ダイアログの確認ボタン押下
	 *	 
	 * @return 変更確認ダイアログの出力内容
	 */
	private function do_update_check_btn() {
				
		// 確認ダイアログHTMLの作成
		$ret = $this->create_change_check_dialog_html();

		return $ret;
	}

	/**
	 * 新規・変更の入力ダイアログHTMLの作成
	 *	 
	 * @param $add_flg       = 新規フラグ
	 * @param $error_message = エラーメッセージ出力内容
	 * @param $branch_list   = ブランチリスト
	 * @param $branch_select_value = ブランチ選択値
	 * @param $reserve_date = 公開予定日時
	 * @param $reserve_time = 公開予定時間
	 * @param $comment      = コメント
	 * @param $selected_id  = 変更時の選択ID
	 *
	 * @return 
	 *  入力ダイアログ出力内容
	 */
	private function create_dialog_html($add_flg, $init_trans_flg, $error_message, $branch_list,
		$branch_select_value, $reserve_date, $reserve_time, $comment) {
		
		$this->debug_echo('■ create_dialog_html start');

		$ret = "";

		$ret .= '<div class="dialog" id="modal_dialog">'
			  . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			  . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			  . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			  . '<div class="dialog_box">';
		
		 if ($error_message != '') {
		 // エラーメッセージの出力
			$ret .= '<div class="alert_box">'
				. $error_message
				. '</div>';
		 }

		if ( $add_flg ) {
			$ret .= '<h4>新規</h4>';
		} else {
		  	$ret .= '<h4>変更</h4>';
		}
			   
		$ret .= '<form method="post">';

		// 変更モードの場合
		if ( !$add_flg) {

			$ret .= $this->create_change_before_hidden_html($init_trans_flg);

		}

		$ret .= '<table class="table table-striped">'
			  . '<tr>'
			  . '<td class="dialog_thead">ブランチ</td>'
			  . '<td><select id="branch_list" class="form-control" name="branch_select_value">';

				foreach ($branch_list as $branch) {
					$ret .= '<option value="' . htmlspecialchars($branch) . '" ' . $this->compare_to_selected_value($branch_select_value, $branch) . '>' . htmlspecialchars($branch) . '</option>';
				}

		$ret .= '</select></td>'
			  . '</tr>'
			  // . '<tr>'
			  // . '<td class="dialog_thead">コミット</td>'
			  // . '<td>' . 'dummy' . '</td>'
			  // . '</tr>'
			  . '<tr>'
			  . '<td class="dialog_thead">公開予定日時（日本時間）</td>'
			  . '<td scope="row"><span style="margin-right:10px;"><input type="text" id="datepicker" name="reserve_date" value="'. $reserve_date . '" autocomplete="off" /></span>'
			  . '<input type="time" id="reserve_time" name="reserve_time" value="'. $reserve_time . '" /></td>'
			  . '</tr>'
			  . '<tr>'
			  . '<td class="dialog_thead">コメント</td>'
			  . '<td><input type="text" id="comment" name="comment" size="50" value="' . htmlspecialchars($comment) . '" /></td>'
			  . '</tr>'
			  . '</tbody></table>'

			  . '<div class="button_contents_box">'
			  . '<div class="button_contents">'
			  . '<ul>';

		if ( $add_flg ) {
			$ret .= '<li><input type="submit" id="add_check_btn" name="add_check" class="px2-btn px2-btn--primary" value="確認"/></li>';
		} else {
		  	$ret .= '<li><input type="submit" id="update_check_btn" name="update_check" class="px2-btn px2-btn--primary" value="確認"/></li>';
		}

		$ret .= '<li><input type="submit" id="close_btn" class="px2-btn" value="キャンセル"/></li>'
			  . '</ul>'
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


	/**
	 * 変更前hidden項目HTMLの作成
	 *	 
	 * @param $add_flg       = 新規フラグ
	 * @param $error_message = エラーメッセージ出力内容
	 * @param $branch_list   = ブランチリスト
	 * @param $branch_select_value = ブランチ選択値
	 * @param $reserve_date = 公開予定日時
	 * @param $reserve_time = 公開予定時間
	 * @param $comment      = コメント
	 * @param $selected_id  = 変更時の選択ID
	 *
	 * @return 
	 *  入力ダイアログ出力内容
	 */
	private function create_change_before_hidden_html($init_trans_flg) {
		
		$selected_id = '';
		$branch_select_value = '';
		$reserve_date = '';
		$reserve_time = '';
		$comment = '';

		// 初期画面より遷移
		if ($init_trans_flg) {

			// 選択されたID
			$selected_id =  $this->options->_POST->selected_id;
			// 選択されたIDに紐づく情報を取得
			$selected_ret = $this->get_selected_data();
			
			$branch_select_value = $selected_ret['branch_name'];
			$reserve_date = date('Y-m-d',  strtotime($selected_ret['reserve_datetime']));
			$reserve_time = date('H:i',  strtotime($selected_ret['reserve_datetime']));
			$comment = $selected_ret['comment'];
	
		} else {

			$selected_id =  $this->options->_POST->selected_id;		
			$branch_select_value = $this->options->_POST->change_before_branch_select_value;
			$reserve_date = $this->options->_POST->change_before_reserve_date;
			$reserve_time = $this->options->_POST->change_before_reserve_time;
			$comment = $this->options->_POST->change_before_comment;
		}

		$ret = '<input type="hidden" name="selected_id" value="' . $selected_id . '"/>'
  			  . '<input type="hidden" name="change_before_branch_select_value" value="'. $branch_select_value . '"/>'
  			  . '<input type="hidden" name="change_before_reserve_date" value="'. $reserve_date . '"/>'
  			  . '<input type="hidden" name="change_before_reserve_time" value="'. $reserve_time . '"/>'
  			  . '<input type="hidden" name="change_before_comment" value="'. $comment . '"/>';

		return $ret;
	}

	// /**
	//  * 新規・変更の出力確認ダイアログHTMLの作成
	//  *	 
	//  * @param $add_flg     = 新規フラグ
	//  * @param $branch_select_value = ブランチ選択値
	//  * @param $date        = 日付
	//  * @param $time        = 日時
	//  * @param $comment     = コメント
	//  * @param $selected_id = 変更時の選択ID
	//  *
	//  * @return 
	//  *  確認ダイアログ出力内容
	//  */
	// private function create_check_dialog_html($add_flg, $branch_select_value,
	// 	$date, $time, $comment, $selected_id) {
		
	// 	$ret = "";

	// 	$ret .= '<div class="dialog">'
	// 		 . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
	// 		 . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
	// 		 . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
	// 		 . '<div class="dialog_box">';
			 
	// 	if ( $add_flg ) {
	// 		$ret .= '<h4>追加確認</h4>';
	// 	} else {
	// 	  	$ret .= '<h4>変更確認</h4>';
	// 	}

	// 	$ret .= '<form method="post">'
	// 		 . '<input type="hidden" name="selected_id" value="' . $selected_id . '"/>'
	// 		 . '<table class="table table-bordered table-striped">'
	// 		 . '<tr>'
	// 		 . '<td>' . 'ブランチ' . '</td>'
	// 		 . '<td>' . $branch_select_value
	// 		 . '<input type="hidden" name="branch_select_value" value="' . $branch_select_value . '"/>'
	// 		 . '</td>'
	// 		 . '</tr>'
	// 		 . '<tr>'
	// 		 . '<td>' . 'コミット' . '</td>'
	// 		 . '<td>' . 'dummy' . '</td>'
	// 		 . '</tr>'
	// 		 . '<tr>'
	// 		 . '<td scope="row">' . '公開予定日時' . '</td>'
	// 		 . '<td>' . $date . ' ' . $time
	// 		 . '<input type="hidden" name="date" value="' . $date . '"/>'
	// 		 . '<input type="hidden" name="time" value="' . $time . '"/>'
	// 		 . '</td>'
	// 		 . '</tr>'
	// 		 . '<tr>'
	// 		 . '<td scope="row">' . 'コメント' . '</td>'
	// 		 . '<td scope="row">' . htmlspecialchars($comment) . '</td>'
	// 		 . '<input type="hidden" name="comment" value="' . htmlspecialchars($comment) . '"/>'
	// 		 . '</tr>'
	// 		 . '</tbody></table>'
			
	// 		. '<div class="unit">'
	// 		. '<div class="text-center">';

	// 	if ( $add_flg ) {
	// 		$ret .= '<input type="submit" name="add_confirm_btn" class="btn btn-default" value="確定"/>'
	// 			. '<input type="submit" name="add_back_btn" class="btn btn-default" value="戻る"/>';
	// 	} else {
	// 		$ret .= '<input type="submit" name="update_confirm_btn" class="btn btn-default" value="確定"/>'
	// 			. '<input type="submit" name="update_back_btn" class="btn btn-default" value="戻る"/>';
	// 	}

	// 	$ret .= '</div>'
	// 		. '</form>'
	// 		. '</div>'

	// 		 . '</div>'
	// 		 . '</div>'
	// 		 . '</div></div>';

	// 	return $ret;
	// }

	/**
	 * 新規の出力確認ダイアログHTMLの作成
	 *	 
	 * @param $add_flg     = 新規フラグ
	 * @param $branch_select_value = ブランチ選択値
	 * @param $reserve_date = 公開予定日付
	 * @param $reserve_time = 公開予定時間
	 * @param $comment      = コメント
	 *
	 * @return 
	 *  確認ダイアログ出力内容
	 */
	private function create_check_dialog_html($branch_select_value,
		$reserve_date, $reserve_time, $comment) {
		
		$ret = "";

		$ret .= '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>追加確認</h4>';

		$ret .= '<form method="post">'
			. '<table class="table table-striped">'
			. '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $branch_select_value
			. '<input type="hidden" name="branch_select_value" value="' . $branch_select_value . '"/>'
			. '</td>'
			. '</tr>'
			// . '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . '公開予定日時' . '</td>'
			. '<td>' . $reserve_date . ' ' . $reserve_time
			. '<input type="hidden" name="reserve_date" value="' . $reserve_date . '"/>'
			. '<input type="hidden" name="reserve_time" value="' . $reserve_time . '"/>'
			. '</td>'
			. '</tr>'
			. '<tr>'
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

		$ret .= '<li><input type="submit" id="confirm_btn" name="add_confirm" class="px2-btn px2-btn--primary" value="確定"/></li>'
			. '<li><input type="submit" id="back_btn" name="add_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'

			. '</div>'
			 . '</div>'

			. '</form>'
			 . '</div>'
			 . '</div></div></div>';

		return $ret;
	}


	/**
	 * 変更の出力確認ダイアログHTMLの作成（変更前後の比較有）
	 *
	 * @return 
	 *  確認ダイアログ出力内容
	 */
	private function create_change_check_dialog_html() {
		
		$img_filename = realpath('.') . self::IMG_ARROW_RIGHT;

		$ret = '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>変更確認</h4>'
			. '<form method="post">'
			. '<div class="colum_3">'
			. '<div class="left_box">';

		$ret .= '<input type="hidden" name="selected_id" value="' . $this->options->_POST->selected_id . '"/>'

			. '<input type="hidden" name="change_before_branch_select_value" value="' . $this->options->_POST->change_before_branch_select_value . '"/>'
			. '<input type="hidden" name="change_before_reserve_date" value="' . $this->options->_POST->change_before_reserve_date . '"/>'
			. '<input type="hidden" name="change_before_reserve_time" value="' . $this->options->_POST->change_before_reserve_time . '"/>' 
			. '<input type="hidden" name="change_before_comment" value="' . htmlspecialchars($this->options->_POST->change_before_comment) . '"/>'

			. '<input type="hidden" name="branch_select_value" value="' . $this->options->_POST->branch_select_value . '"/>'
			. '<input type="hidden" name="reserve_date" value="' . $this->options->_POST->reserve_date . '"/>'
			. '<input type="hidden" name="reserve_time" value="' . $this->options->_POST->reserve_time . '"/>'	 
			. '<input type="hidden" name="comment" value="' . htmlspecialchars($this->options->_POST->comment) . '"/>'

			. '<table class="table table-striped">'
			. '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $this->options->_POST->change_before_branch_select_value
			. '</td>'
			. '</tr>'
			// . '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . '公開予定日時' . '</td>'
			. '<td>' . $this->options->_POST->change_before_reserve_date . ' ' . $this->options->_POST->change_before_reserve_time
			. '</td>'
			. '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . htmlspecialchars($this->options->_POST->change_before_comment) . '</td>'
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
			. '<td class="dialog_thead">' . '公開予定日時' . '</td>'
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

		return $ret;
	}


	/**
	 * 入力チェック処理
	 *	 
	 * @return 
	 *  エラーメッセージHTML
	 */
	private function do_check_validation($add_flg) {
				
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

		// CSVより公開予約の一覧を取得する（ステータスが公開前のみ）
		$data_list = $this->get_csv_data_list(0);

		// 最大件数チェック
		if ($add_flg) {

			// TODO:定数化
			$max = 10;

			if ($max <= count($data_list)) {
				$ret .= '<p class="error_message">公開予約は最大' . $max . '件までの登録になります。</p>';
			}
		}

		// 日付の妥当性チェック
		list($Y, $m, $d) = explode('-', $this->options->_POST->reserve_date);

		if (!checkdate(intval($m), intval($d), intval($Y))) {
			$ret .= '<p class="error_message">「公開予定日時」の日付が有効ではありません。</p>';
		}

		// 公開予定日時の未来日時チェック
		$now = date(self::TIME_FORMAT_CONV);
		$datetime = $this->options->_POST->reserve_date . ' ' . date('H:i:s',  strtotime($this->options->_POST->reserve_time));

		if (strtotime($now) > strtotime($datetime)) {
			$ret .= '<p class="error_message">「公開予定日時」は未来日時を設定してください。</p>';
		}

		// ブランチの重複チェック
		if (!$this->check_exist_branch($data_list, $branch_select_value, $selected_id)) {
			$ret .= '<p class="error_message">1つのブランチで複数の公開予定を作成することはできません。</p>';
		}

		// 公開予定日時の重複チェック
		if (!$this->check_exist_reserve($data_list, $this->combine_date_time($reserve_date, $reserve_time), $selected_id)) {
			$ret .= '<p class="error_message">入力された日時はすでに公開予定が作成されています。</p>';
		}

		return $ret;
	}

	/**
	 * 初期表示のコンテンツ作成
	 *	 
	 * @return 初期表示の出力内容
	 */
	private function create_top_contents() {
		
		$ret = "";

		// CSVより公開予約の一覧を取得する（ステータスが公開前のみ）
		$data_list = $this->get_csv_data_list(0);
		// 取得したリストをソートする
		$data_list = $this->sort_list($data_list, 'reserve_datetime', SORT_ASC);

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
		// 			. $data['reserve_datetime'] . '　' . $data['content']
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
			. '<li><input type="submit" id="release_btn" name="release" class="px2-btn px2-btn--primary" value="即時公開"/></li>'
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
			. '<th scope="row">公開予定日時</th>'
			. '<th scope="row">（サーバ上日時）</th>'
			. '<th scope="row">コミット</th>'
			. '<th scope="row">ブランチ</th>'
			. '<th scope="row">コメント</th>'
			// . '<th>[*] id</th>'
			// . '<th>[*] 状態</th>'
			. '</tr>'
			. '</thead>'
			. '<tbody>';

		// テーブルデータリスト
		foreach ($data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . $array['id'] . '"/></td>'
				. '<td class="p-center">' . date(self::TIME_FORMAT_DISPLAY,  strtotime($array['input_reserve_datetime'])) . '</td>'
				. '<td class="p-center">' . date(self::TIME_FORMAT_DISPLAY,  strtotime($array['reserve_datetime'])) . '</td>'
				. '<td class="p-center">' . $array['commit'] . '</td>'
				. '<td class="p-center">' . $array['branch_name'] . '</td>'
				. '<td>' . $array['comment'] . '</td>'
				// . '<td>' . $array['id'] . '</td>'
				// . '<td>' . $this->convert_status($array['status']) . '</td>'
				. '</tr>';
		}

		$ret .= '</tbody></table>'
			. '</div>'
			. '</form>'
			. '</div>';
		
		return $ret;
	}

	/**
	 * 履歴表示のコンテンツ作成
	 *	 
	 * @return 履歴表示の出力内容
	 */
	private function create_history_contents() {
		
		$ret = "";

		// CSVより公開予約の一覧を取得する（全ステータス）
		$data_list = $this->get_csv_data_list(null);
		// 取得したリストをソートする
		$data_list = $this->sort_list($data_list, 'reserve_datetime', SORT_ASC);

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
				. '<th scope="row">公開予定日時</th>'
				. '<th scope="row">（サーバ上）</th>'
				. '<th scope="row">コミット</th>'
				. '<th scope="row">ブランチ</th>'
				. '<th scope="row">コメント</th>'
				. '</tr>'
				. '</thead>'
				. '<tbody>';

		// データリスト
		foreach ($data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . $array['id'] . '"/></td>'
				. '<td class="p-center">' . $this->convert_status( $array['status'] ). '</td>'
				. '<td class="p-center">' . date(self::TIME_FORMAT_DISPLAY,  strtotime($array['input_reserve_datetime'])) . '</td>'
				. '<td class="p-center">' . date(self::TIME_FORMAT_DISPLAY,  strtotime($array['reserve_datetime'])) . '</td>'
				. '<td class="p-center">' . $array['commit'] . '</td>'
				. '<td class="p-center">' . $array['branch_name'] . '</td>'
				. '<td>' . $array['comment'] . '</td>'
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
		
		return $ret;
	}

	/**
	 * 
	 */
	public function run() {
	
		// ダイアログの表示
		$dialog_disp = '';

		// gitのmaster情報取得
		$init_ret = $this->init();
		$init_ret = json_decode($init_ret);

		// 初期表示画面から遷移されたか
		$init_trans_flg = false;

		if ( !$init_ret->status ) {
			// 初期化失敗

			// エラーメッセージ
			$init_error_msg = '
			<script type="text/javascript">
				console.error("' . $init_ret->message . '");
				alert("initialize faild");
			</script>';
		}

		// 新規ボタンが押下された場合
		if (isset($this->options->_POST->add)) {
		
			$dialog_disp = $this->disp_add_dialog(null);

		// 変更ボタンが押下された場合
		} elseif (isset($this->options->_POST->update)) {
		
			$init_trans_flg = true;

			$dialog_disp = $this->disp_update_dialog($init_trans_flg, null);

		// 削除ボタンが押下された場合
		} elseif (isset($this->options->_POST->delete)) {
		
			// Gitファイルの削除
			$this->file_delete();
	
			$this->do_delete_btn();

		// 即時公開ボタンが押下された場合
		} elseif (isset($this->options->_POST->release)) {
		
			// echo '即時公開ボタン押下';
			
			$this->manual_release();

		// // 履歴ボタンが押下された場合
		// } elseif (isset($this->options->_POST->history)) {
		
		// 新規作成ダイアログの「確認」ボタンが押下された場合
		} elseif (isset($this->options->_POST->add_check)) {

			// 入力チェック
			$error_message = $this->do_check_validation(true);

			if ($error_message != '') {
				// 入力チェックエラーがあった場合はそのままの画面
				$dialog_disp = $this->disp_add_dialog($error_message);
			} else {
				// 入力チェックエラーがなかった場合は確認ダイアログへ遷移
				$dialog_disp = $this->do_add_check_btn();
			}
			
		// 新規ダイアログの確定ボタンが押下された場合
		} elseif (isset($this->options->_POST->add_confirm)) {

			// Gitファイルの取得
			$add_ret = $this->file_copy();
	
			$add_ret = json_decode($add_ret);

			if ( !$add_ret->status ) {
				// デプロイ失敗

				// エラーメッセージ
				$dialog_disp = '
				<script type="text/javascript">
					console.error("' . $add_ret->message . '");
					alert("add faild");
				</script>';

			} else {

				// CSV入力情報の追加
				$this->insert_list_csv_data();

			}

		// 新規確認ダイアログの戻るボタンが押下された場合
		} elseif (isset($this->options->_POST->add_back)) {
		
			$dialog_disp = $this->disp_add_dialog(null);

		// 変更ダイアログの確認ボタンが押下された場合
		} elseif (isset($this->options->_POST->update_check)) {
		
			$error_message = $this->do_check_validation(false);

			if ($error_message != '') {
				// 入力チェックエラーがあった場合はそのままの画面
				$dialog_disp = $this->disp_update_dialog($init_trans_flg, $error_message);
			} else {
				// 入力チェックエラーがなかった場合は確認ダイアログへ遷移
				$dialog_disp = $this->do_update_check_btn();
			}	

		// 変更ダイアログの確定ボタンが押下された場合
		} elseif (isset($this->options->_POST->update_confirm)) {
			
			// Gitファイルの取得
			$this->file_update();

			// CSV入力情報の変更
			$this->do_update_btn();
		
		// 変更確認ダイアログの戻るボタンが押下された場合
		} elseif (isset($this->options->_POST->update_back)) {
		
			$dialog_disp = $this->disp_update_dialog($init_trans_flg, null);

		}

		// // 画面表示
		$disp = '';  

		if (isset($this->options->_POST->history)) {
			// 履歴表示画面の表示
			$disp = $this->create_history_contents();
		} else {
			// 初期表示画面の表示
			$disp = $this->create_top_contents();
		}

		// 画面ロック用
		$disp_lock = '<div id="loader-bg"><div id="loading"></div></div>';	

		// 画面表示
		return $disp . $disp_lock . $dialog_disp;
	}

	/**
	 * CSVからお知らせリストを取得する
	 *
	 * @return お知らせリスト
	 */
	private function get_csv_alert_list()
	{

		$this->debug_echo('■ get_csv_alert_list start');

		$ret_array = array();

		// $filename = realpath('.') . $this->alert_filename;
		$filename = $this->alert_filename;

		if (!file_exists($filename)) {
			$this->debug_echo('お知らせ一覧ファイルが存在しない');

		} else {

			// Open file
			$handle = fopen( $filename, "r" );

			$title_array = array();

			$is_first = true;

			// CSVリストをループ
			while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

				if($is_first){
			        // タイトル行
			        foreach ($rowData as $k => $v) {
			        	$title_array[] = $v;
			        }
			        $is_first = false;
			        continue;
			    }
			    
			    // タイトルと値の2次元配列作成
			    $ret_array[] = array_combine ($title_array, $rowData) ;
			}

			// Close file
			fclose($handle);

		}

		$this->debug_echo('■ get_csv_alert_list end');

		return $ret_array;
	}


	/**
	 * CSVからデータリストを取得する
	 *
	 * @param $status = 取得対象のステータス
	 * @return データリスト
	 */
	private function get_csv_data_list($status)
	{

		$this->debug_echo('■ get_csv_data_list start');

		$current_dir = realpath('.');

		$ret_array = array();

		$filename = self::CSV_LIST_FILENAME;

		if (!file_exists($filename)) {
			$this->debug_echo($filename . '公開予約一覧ファイルが存在しない');

		} else {

			// Open file
			$handle = fopen( $filename, "r" );

			$title_array = array();

			$is_first = true;

			// CSVリストをループ
			while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

				if($is_first){
			        // タイトル行
			        foreach ($rowData as $k => $v) {
			        	$title_array[] = $v;
			        }
			        $is_first = false;
			        continue;
			    }
			    
				$set_flg = true;

			    // ステータスの指定があった場合
			    // TODO:要素番号を定数化
			    if (isset($status) && ($rowData[5] != $status)) {
					$set_flg = false;
			    }

			    if ($set_flg) {
			    	// タイトルと値の2次元配列作成
			    	$ret_array[] = array_combine ($title_array, $rowData);
			    }
			}

			// Close file
			fclose($handle);

		}
		
		chdir($current_dir);
		
		$this->debug_echo('■ get_csv_data_list end');

		return $ret_array;
	}


	/**
	 * CSVから選択された行の情報を取得する
	 *
	 * @return 選択行の情報
	 */
	private function get_selected_data() {

		$this->debug_echo('■ get_selected_data start');

		// $filename = realpath('.') . $this->list_filename;
		$filename = self::CSV_LIST_FILENAME;

		$selected_id =  $this->options->_POST->selected_id;

		$ret_array = array();

		if (!file_exists($filename) && !empty($selected_id)) {
			$this->debug_echo('公開予約一覧ファイルが存在しない');

		} else {

			$file = file($filename);

			// Open file
			$handle = fopen( $filename, "r" );
			
			$title_array = array();

			$is_first = true;

			// Loop through each line of the file in turn
			while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

				if($is_first){
			        // タイトル行
			        foreach ($rowData as $k => $v) {
			        	$title_array[] = $v;
			        }
			        $is_first = false;
			        continue;
			    }

				$num = intval($rowData[0]);

				if ($num == $selected_id) {
				    // タイトルと値の2次元配列作成
				    $ret_array = array_combine ($title_array, $rowData) ;
				}
			}


			// Close file
			fclose($handle);
		}

		$this->debug_echo('■ get_selected_data end');

		return $ret_array;
	}

	/**
	 * 登録処理（CSVへの行追加）
	 *
	 * @return なし
	 */
	private function insert_list_csv_data()
	{

		$this->debug_echo('■ insert_list_csv_data start');

		try {

			// $filename = realpath('.') . $this->list_filename;
			$filename = self::CSV_LIST_FILENAME;

			if (!file_exists($filename)) {
				$this->debug_echo('公開予約一覧ファイルが存在しない');

			} else {

				// Open file
				$handle_r = fopen( $filename, "r" );

				if ($handle_r === false) {
					// スロー処理！
					// throw new PHPExcel_Writer_Exception("Could not open file $pFilename for writing.");
				}

				$is_first = true;

				$max = 0;

				// Loop through each line of the file in turn
				while ($rowData = fgetcsv($handle_r, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

					if($is_first){
				        // タイトル行

				        $is_first = false;
				        continue;
				    }

				    $num = intval($rowData[0]);

				    if ($num > $max) {
						$max = $num;
					}
				}

				$max++;

				// Open file
				$handle = fopen( $filename, 'a+' );


				if ($handle === false) {
					// スロー処理！
					// throw new PHPExcel_Writer_Exception("Could not open file $pFilename for writing.");
				}

				// 現在時刻
				$now = date(self::TIME_FORMAT_CONV);

				// 日付と時刻を結合
				$combine_reserve_time = $this->combine_date_time($this->options->_POST->reserve_date, $this->options->_POST->reserve_time);
		
				if ( is_null($combine_reserve_time) || !isset($combine_reserve_time) ) {
					throw new \Exception("Combine date time failed.");
				}

				// サーバのタイムゾーン日時へ変換
				$convert_reserve_time = $this->convert_timezone_datetime($combine_reserve_time, self::TIME_FORMAT_CONV);
				
				if ( is_null($convert_reserve_time) || !isset($convert_reserve_time) ) {
					throw new \Exception("Convert time zone failed.");
				}

				// id, ブランチ名, コミット, 公開予定日時, コメント, 状態, 設定日時
				$array = array(
					$max,
					$this->options->_POST->branch_select_value,
					$this->commit_hash,
					$this->$combine_reserve_time,
					$this->$convert_reserve_time,
					$this->options->_POST->comment,
					0,
					$now
				);

				fputcsv( $handle, $array, self::CSV_DELIMITER, self::CSV_ENCLOSURE);

				fclose( $handle);
			}


			// Close file
			fclose($handle_r);

		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			
			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);
			
		$this->debug_echo('■ insert_list_csv_data end');

		return json_encode($result);
	}

	/**
	 * 変更処理（CSVへ行削除＆行追加）
	 *
	 * @return なし
	 */
	private function do_update_btn() {

		// $filename = realpath('.') . $this->list_filename;
		$filename = self::CSV_LIST_FILENAME;

		$selected_id =  $this->options->_POST->selected_id;

		if (!file_exists($filename) && !$selected_id) {
			$this->debug_echo('ファイルが存在しない、または、選択IDが不正です。');

		} else {

			$file = file($filename);

			// Open file
			$handle_r = fopen( $filename, "r" );
			
			$cnt = 0;
			$max = 0;

			$is_first = true;

			// Loop through each line of the file in turn
			while ($rowData = fgetcsv($handle_r, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

				if($is_first){
			        // タイトル行は飛ばす
			        $is_first = false;
			        $cnt++;
			        continue;
			    }

			    // idカラムの値を取得
				$num = intval($rowData[0]);

				// 追加時のid値生成
			    if ($num > $max) {
					$max = $num;
				}

				// 変更対象となるid値の場合
				if ($num == $selected_id) {
					unset($file[$cnt]);
					file_put_contents($filename, $file);
				}

				$cnt++;
			}

			$max++;

			// Open file
			$handle = fopen( $filename, 'a+' );


			// 現在時刻
			$now = date(self::TIME_FORMAT_CONV);

			// 日付と時刻を結合
			$combine_reserve_time = $this->combine_date_time($this->options->_POST->reserve_date, $this->options->_POST->reserve_time);
			
			if ( is_null($combine_reserve_time) || !isset($combine_reserve_time) ) {
				throw new \Exception("Combine date time failed.");
			}
			
			// サーバのタイムゾーン日時へ変換
			$convert_reserve_time = $this->convert_timezone_datetime($combine_reserve_time, self::TIME_FORMAT_CONV);

			if ( is_null($convert_reserve_time) || !isset($convert_reserve_time) ) {
				throw new \Exception("Convert time zone failed.");
			}

			if ( is_null($convert_reserve_time) || !isset($convert_reserve_time)) {
				// スロー処理！
				// throw new PHPExcel_Writer_Exception("Could not open file $pFilename for writing.");
			}

			// id, ブランチ, 公開予定日時, 状態, 設定日時
			$array = array(
				$max,
				$this->options->_POST->branch_select_value,
				$this->commit_hash,
				$this->$combine_reserve_time,
				$this->$convert_reserve_time,
				$this->options->_POST->comment,
				0,
				$now
			);

			fputcsv( $handle, $array, self::CSV_DELIMITER, self::CSV_ENCLOSURE);
			fclose( $handle);
		}

		// Close file
		fclose($handle_r);
	}

	/**
	 * 削除処理（CSVから行削除）
	 *
	 * @return なし
	 */
	private function do_delete_btn() {

		// $filename = realpath('.') . $this->list_filename;
		$filename = self::CSV_LIST_FILENAME;

		$selected_id =  $this->options->_POST->selected_id;

		if (!file_exists($filename) && empty($selected_id)) {
			$this->debug_echo('ファイルが存在しない、または、選択IDが不正です。');

		} else {

			$file = file($filename);

			// Open file
			$handle = fopen( $filename, "r" );
			
			$cnt = 0;

			// Loop through each line of the file in turn
			while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

				$num = intval($rowData[0]);

				if ($num == $selected_id) {
					unset($file[$cnt]);
					file_put_contents($filename, $file);
					break;
				}

				$cnt++;
			}
		}

		// Close file
		fclose($handle);
	}

	/**
	 * 新規追加時のGitファイルのコピー
	 *
	 * @return なし
	 */
	private function file_copy() {

		$this->debug_echo('■ file_copy start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');
	
		// ディレクトリ名
		$dirname = date(self::TIME_FORMAT_SAVE, 
			strtotime($this->combine_date_time($this->options->_POST->reserve_date, $this->options->_POST->reserve_time)));

		// 選択したブランチ
		$branch_name = trim(str_replace("origin/", "", $this->options->_POST->branch_select_value));

		try {

			// コピーディレクトリが存在しない場合は作成
			if ( !$this->is_exists_mkdir(self::PATH_COPY) ) {

					// エラー処理
					throw new \Exception('Creation of copy directory failed.');
			}

			// コピーディレクトリへ移動
			if ( chdir(self::PATH_COPY) ) {

				// コピーディレクトリをデリートインサート
				if ( !$this->is_exists_remkdir(self::PATH_COPY, $dirname) ) {

					// エラー処理
					throw new \Exception('Creation of copy publish directory failed.');
				}

				// コピーディレクトリへ移動
				if ( chdir($dirname) ) {

					// git init
					exec('git init', $output);

					// git urlのセット
					$url = $this->options->git->protocol . "://" . urlencode($this->options->git->username) . ":" . urlencode($this->options->git->password) . "@" . $this->options->git->url;
					
					// initしたリポジトリに名前を付ける
					exec( 'git remote add origin ' . $url, $output);

					// git fetch（リモートリポジトリの指定ブランチの情報をローカルブランチに取得）
					exec( 'git fetch origin' . ' ' . $branch_name, $output);

					// git pull（）pullはリモート取得ブランチを任意のローカルブランチにマージするコマンド
					// exec( 'git pull origin master', $output);
					exec( 'git pull origin' . ' ' . $branch_name, $output);
					
					// 現在のブランチ取得
					exec( 'git branch', $output);

					// コミットハッシュ値の取得
					exec( 'git rev-parse --short HEAD', $hash);

					foreach ( $hash as $value ) {
						$this->commit_hash = $value;
					}

				} else {
					// コピー用のディレクトリが存在しない場合

					// エラー処理
					throw new \Exception('Copy publish directory not found.');
				}

			} else {
				// コピー用のディレクトリが存在しない場合

				// エラー処理
				throw new \Exception('Copy directory not found.');
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
	private function file_update()
	{
		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$before_dir_name = date(self::TIME_FORMAT_SAVE, strtotime($this->combine_date_time($this->options->_POST->change_before_reserve_date, $this->options->_POST->change_before_reserve_time)));

		$before_path = self::PATH_COPY . $before_dir_name;


		$dir_name = date(self::TIME_FORMAT_SAVE, strtotime($this->combine_date_time($this->options->_POST->reserve_date, $this->options->_POST->reserve_time)));

		// 選択したブランチ
		$branch_name_org = $this->options->_POST->branch_select_value;
		// 選択したブランチ（origin無し）
		$branch_name = trim(str_replace("origin/", "", $branch_name_org));

		try {

			// デプロイ先のディレクトリがない場合は終了
			if ( !file_exists($before_path) ) {

				$this->debug_echo($before_path . '：ディレクトリが存在しません。');
				throw new \Exception('Creation of preview server directory failed.');
			}

			// ディレクトリ移動
			if ( chdir( $before_path ) ) {

				// 現在のブランチ取得
				exec( 'git branch', $output);

				$now_branch;
				$already_branch_checkout = false;
				foreach ( $output as $value ) {

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
				exec( 'git fetch origin', $output );

				// 現在のブランチと選択されたブランチが異なる場合は、ブランチを切り替える
				if ( $now_branch !== $branch_name ) {

					if ($already_branch_checkout) {
						// 選択された(切り替える)ブランチが既にチェックアウト済みの場合
						// echo 'チェックアウト済み';
						exec( 'git checkout ' . $branch_name, $output);

					} else {
						// 選択された(切り替える)ブランチがまだチェックアウトされてない場合
						// echo 'チェックアウトまだ';
						exec( 'git checkout -b ' . $branch_name . ' ' . $branch_name_org, $output);
					}
				}

				// コミットハッシュ値の取得
				exec( 'git rev-parse --short HEAD', $hash);

				foreach ( $hash as $value ) {
					$this->commit_hash = $value;
				}
				
				// ディレクトリ名が変更になる場合はリネームする
				if ($before_dir_name != $dir_name) {

					if ( file_exists( $before_dir_name ) && !file_exists( $dir_name ) ){
						
						rename( $before_dir_name, $dir_name );
					} else {
						// print $before_dir_name. ',' . $dir_name;
						// print 'ディレクトリ名が変更できませんでした。';
						throw new \Exception('Copy directory name could not be changed.');
					}
				}
				
			} else {
				// コピー用のディレクトリが存在しない場合

				// エラー処理
				throw new \Exception('Copy directory not found.');
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
	 * Gitファイルの削除（※ゆくゆくはLINUXコマンドでディレクトリごと削除する）
	 *
	 * @return なし
	 */
	private function file_delete()
	{
		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$selected_ret = $this->get_selected_data();

		$dir_name = date(self::TIME_FORMAT_SAVE, strtotime($selected_ret['reserve_datetime']));

		try {

			// ディレクトリ移動
			if ( chdir( self::PATH_COPY ) ) {

				if( file_exists( $dir_name )){
					
					rename( $dir_name,  'BK_' . $dir_name );

				}else{
					
					throw new \Exception('Copy directory name could not be changed.');
				}
		
			} else {
				// コピー用のディレクトリが存在しない場合

				// エラー処理
				throw new \Exception('Copy directory not found.');
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
	 * 即時公開
	 */
	private function manual_release() {


		try {

			// TODO:後で別クラスへ分割;
			// $this->file_control->process();

			// *** 公開予定から本番環境へ置き換えるものを1件抽出する。（抽出されたものが実行中の場合はスキップする　※処理終了）
			// 現在時刻
			$now = date(self::TIME_FORMAT_CONV);
			
			// 公開予約の一覧を取得
			$data_list = $this->get_csv_data_list_cron(0, $now);

			if (!empty($data_list)) {

				// 取得した一覧から最新の1件を取得
				$datetime_str = $this->get_datetime_str($data_list, 'reserve_datetime', SORT_DESC);
			}

			// *** 本番環境よりバックアップ取得

			// #本番環境ディレクトリのパス取得（１）
			// 本番環境の絶対パスを取得
			$honban_real_path = realpath('.') . $this->honban_path;
			// echo '<br>' . '本番環境絶対パス：';
			// echo $honban_real_path;

			// backupディレクトリの絶対パスを取得
			$bk_real_path = realpath('.') . self::PATH_BACKUP;
			// echo '<br>' . 'バックアップフォルダの絶対パス：';
			// echo $bk_real_path;

			// copyディレクトリの絶対パスを取得
			$copy_real_path = realpath('.') . self::PATH_COPY;
			// echo '<br>' . 'コピーフォルダの絶対パス：';
			// echo $copy_real_path;

			// logディレクトリの絶対パスを取得
			$log_real_path = realpath('.') . self::PATH_LOG;
			// echo '<br>' . 'ログフォルダの絶対パス：';
			// echo $log_real_path;


			// ディレクトリ作成
			if (!mkdir("/var/www/html/sample-lib-indigo/indigo_dir/backup", 0777, true)) {
				// ディレクトリが作成できない場合

				$this->debug_echo('error');

				// エラー処理
				throw new \Exception('Creation of backup directory failed.');
			}


			// // #backupディレクトリに公開予定日時を名前としたフォルダを作成（２）
			// if (!file_exists($bk_real_path . $datetime_str)) {

			// 	// ディレクトリ作成
			// 	if (!mkdir($bk_real_path . $datetime_str, 0777, true)) {
			// 		// ディレクトリが作成できない場合

			// 		// エラー処理
			// 		throw new \Exception('Creation of backup directory failed.');
			// 	}
			// }

			// #（１）の中身を（２）へコピー
			$command = 'cp -r '. $honban_real_path . '* ' . $bk_real_path . $datetime_str . ' 2>&1';
			exec($command, $output, $status);
			error_log('['.date(DATE_ATOM).'] '.$command."\n".'return code : '.$status."\n", 3, '../logs/ansible-view.log');
			
			foreach($output as $row){
	    		error_log($row."\n", 3, '/var/log/test/test.log');
			}
			
			var_dump($output);
			
			// **成功したら
			//   （１）の中身を削除
			//       *成功したら
			//        TODO:実行処理追加！
			 		if (chdir($honban_real_path)) {
			       		exec('rm -rf ./* ./.*', $output);
			 		}

			//      次の処理へ！

			//       *失敗したら
			// 			・失敗ログを残し、処理終了
			 		if (chdir($honban_real_path)) {
			       		exec('rm -rf ./* ./.*', $output);
			 		}


			// （３）の中身を（１）へコピー
			 exec('cp -r '. $copy_real_path . $datetime_str . '* ' . $honban_real_path . $datetime_str, $output);

		} catch (\Exception $e) {

			$this->debug_echo("例外キャッチ：", $e->getMessage());
		}
	}

	
	/**
	 * CSVから公開前のリストを取得する
	 *
	 * @param $status = 取得対象のステータス
	 * @return データリスト
	 */
	private function get_csv_data_list_cron($status, $now) {

		$ret_array = array();

		// $filename = realpath('.') . $this->list_filename;
		$filename = self::CSV_LIST_FILENAME;

		if (!file_exists($filename)) {
		
			$this->debug_echo('ファイルが存在しない');
		
		} else {

			// Open file
			$handle = fopen($filename, "r");

			$title_array = array();

			$is_first = true;

			// CSVリストをループ
			while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

				if($is_first){
			        // タイトル行
			        foreach ($rowData as $k => $v) {
			        	$title_array[] = $v;
			        }
			        $is_first = false;
			        continue;
			    }
			    
				$set_flg = true;

			    // 指定ステータスと一致しない場合
			    if (isset($status) && ($rowData[self::CSV_COLUMN_STATUS] != $status)) {
					$set_flg = false;
			    }

			    // 指定日時より未来日時の場合
			    if (isset($now) && ($rowData[self::CSV_COLUMN_DATETIME] > $now)) {
			    	$set_flg = false;
			    }

			    if ($set_flg) {
			    	// タイトルと値の2次元配列作成
			    	$ret_array[] = array_combine ($title_array, $rowData);
			    }
			}

			// Close file
			fclose($handle);

		}
					
		return $ret_array;
	}

	/**
	 * 公開予約一覧用の配列を「公開予定日時の昇順」へソートし返却する
	 *	 
	 * @param $array_list = ソート対象の配列
	 * @param $sort_name  = ソートするキー名称
	 * @param $sort_kind  = ソートの種類
	 *	 
	 * @return ソート後の配列
	 */
	private function get_datetime_str($array_list, $sort_name, $sort_kind) {

		$ret = '';

		if (!empty($array_list)) {

			$sort_array = array();

			foreach($array_list as $key => $value) {
				$sort_array[$key] = $value[$sort_name];
			}

			// 公開予定日時の昇順へソート	
			array_multisort($sort_array, $sort_kind, $array_list);
			// 先頭行の公開予約日時
			$ret = date('YmdHis', strtotime($array_list[0][$sort_name]));
		}

		return $ret;
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
	 * ディレクトリの存在チェックをし、存在しなかった場合は削除し、再作成作成する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	private function is_exists_remkdir($dirpath, $dirname) {
		
		$this->debug_echo('■ is_exists_remkdir start');

		if ( file_exists($dirname) ) {

			// 削除
			$command = 'rm -rf '. $dirname;
			$ret = $this->execute($command, true);

			if ( $ret['return'] !== 0 ) {
				$this->debug_echo('削除失敗');
				return false;
			}
		}

		// デプロイ先のディレクトリを作成
		if ( file_exists($dirname) || !mkdir($dirname, 0777) ) {

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
	function execute($command, $captureStderr) {
	
		$this->debug_echo('■ execute start');

	    $output = array();
	    $return = 0;

	    // 標準出力とエラー出力を両方とも出力する
	    if ($captureStderr === true) {
	        $command .= ' 2>&1';
	    }

	    exec($command, $output, $return);

	    $output = implode("\n", $output);
	
		$this->debug_echo('■ execute end');

	    return array('output' => $output, 'return' => $return);
	}


	/**
	 * 入力された日時をサーバのタイムゾーンの日時へ変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function convert_timezone_datetime($input_datetime, $format) {
	
		$this->debug_echo('■ convert_timezone_datetime start');

		// サーバのタイムゾーン取得
		$timezone = date_default_timezone_get();

		$t = new \DateTime($input_datetime, new \DateTimeZone(self::TIME_ZONE));

		$this->debug_echo('　□ 2');

		// タイムゾーン変更
		$t->setTimeZone(new \DateTimeZone($timezone));
	
		$ret = $t->format($format);
		
		$this->debug_echo($t->format($format));
	
		$this->debug_echo('■ convert_timezone_datetime end');

	    return $ret;
	}

	/**
	 * ※デバッグ用
	 *	 
	 */
	function debug_echo($text) {
	
		echo strval($text);
		echo "<br>";

		return;
	}

}
