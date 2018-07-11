<?php

namespace indigo;

class backupScreen
{
	public $options;

	private $check;
	private $publish;

	/**
	 * PDOインスタンス
	 */
	private $dbh;

	/**
	 * 入力画面のエラーメッセージ
	 */
	private $input_error_message = '';



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
	const INPUT_MODE_IMMEDIATE = 5;
	// 即時公開戻り表示モード
	const INPUT_MODE_IMMEDIATE_BACK = 6;



	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {

		$this->options = json_decode(json_encode($options));
		$this->check = new check($this);
		$this->publish = new publish($this);

	}


	/**
	 * 初期表示のコンテンツ作成
	 *	 
	 * @return 初期表示の出力内容
	 */
	public function disp_init_screen() {
		
		$this->common->debug_echo('■ create_top_contents start');

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

		// $this->common->debug_echo('　□data_list：');
		// $this->common->debug_var_dump($data_list);

		// テーブルデータリスト
		foreach ((array)$data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . $array[tsReserve::RESERVE_ENTITY_ID_SEQ] . '"/></td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_RESERVE_DISPLAY] . '</td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_COMMIT] . '</td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_BRANCH] . '</td>'
				. '<td>' . $array[tsReserve::RESERVE_ENTITY_COMMENT] . '</td>'
				. '</tr>';
		}

		$ret .= '</tbody></table>'
			. '</div>'
			. '</form>'
			. '</div>';

		$this->common->debug_echo('■ create_top_contents end');

		return $ret;
	}

	/**
	 * 新規ダイアログの表示
	 *	 
	 * @return 新規ダイアログの出力内容
	 */
	public function disp_add_dialog() {
		
		$this->common->debug_echo('■ disp_add_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(self::INPUT_MODE_ADD, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->common->debug_echo('■ disp_add_dialog end');

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
		
		$this->common->debug_echo('■ disp_back_add_dialog start');

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

		$this->common->debug_echo('■ disp_back_add_dialog end');

		return $ret;
	}

	/**
	 * 変更ダイアログの表示
	 *	 
	 *
	 * @return 変更ダイアログの出力内容
	 */
	public function disp_update_dialog() {
		
		$this->common->debug_echo('■ disp_update_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// 画面選択された公開予約情報を取得
		$selected_id =  $this->options->_POST->selected_id;

		$selected_data = $this->tsReserve->get_selected_ts_reserve($this->dbh, $selected_id);
		
		$this->common->debug_echo('　□ 公開予約データ');
		$this->common->debug_var_dump($selected_data);
		$this->common->debug_echo('　');

		if ($selected_data) {

			$branch_select_value = $selected_data[tsReserve::RESERVE_ENTITY_BRANCH];

			$reserve_date = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_DATE];
			$reserve_time = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_TIME];

			$comment = $selected_data[tsReserve::RESERVE_ENTITY_COMMENT];
		}

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(self::INPUT_MODE_UPDATE, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->common->debug_echo('■ disp_update_dialog end');

		return $ret;
	}

	/**
	 * 変更ダイアログの戻り表示
	 *	 
	 * @param $error_message  = エラーメッセージ出力内容
	 *
	 * @return 変更ダイアログの出力内容
	 */
	public function disp_back_update_dialog() {
		
		$this->common->debug_echo('■ disp_back_update_dialog start');

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

		$this->common->debug_echo('■ disp_back_update_dialog end');

		return $ret;
	}

	/**
	 * 即時公開ダイアログの表示
	 *	 
	 * @return 即時公開ダイアログの出力内容
	 */
	public function disp_immediate_dialog() {
		
		$this->common->debug_echo('■ disp_immediate_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(self::INPUT_MODE_IMMEDIATE, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->common->debug_echo('■ disp_immediate_dialog end');

		return $ret;
	}


	/**
	 * 即時ダイアログの戻り表示
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function disp_back_immediate_dialog() {
		
		$this->common->debug_echo('■ disp_back_immediate_dialog start');

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
		$ret = $this->create_dialog_html(self::INPUT_MODE_IMMEDIATE_BACK, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->common->debug_echo('■ disp_back_immediate_dialog end');

		return $ret;
	}


	/**
	 * 新規・変更・即時公開の入力ダイアログHTMLの作成
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
		
		$this->common->debug_echo('■ create_dialog_html start');

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

		} elseif ( ($input_mode == self::INPUT_MODE_IMMEDIATE) || ($input_mode == self::INPUT_MODE_IMMEDIATE_BACK) ) {
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
		if ( ($input_mode == self::INPUT_MODE_IMMEDIATE) || ($input_mode == self::INPUT_MODE_IMMEDIATE_BACK) ) {

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

		} elseif ( ($input_mode == self::INPUT_MODE_IMMEDIATE) ||  ($input_mode == self::INPUT_MODE_IMMEDIATE_BACK) ) {
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

		$this->common->debug_echo('■ create_dialog_html end');

		return $ret;
	}

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
		
		$this->common->debug_echo('■ disp_check_add_dialog start');

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

		$this->common->debug_echo('■ disp_check_add_dialog end');

		return $ret;
	}


	/**
	 * 変更確認ダイアログの表示
	 *
	 * @return 
	 *  確認ダイアログ出力内容
	 */
	private function disp_check_update_dialog() {
		
		$this->common->debug_echo('■ disp_check_update_dialog start');

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

			$before_branch_select_value = $selected_data[tsReserve::RESERVE_ENTITY_BRANCH];
			$before_reserve_date = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_DATE];
			$before_reserve_time = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_TIME];
			$before_comment = $selected_data[tsReserve::RESERVE_ENTITY_COMMENT];
	
			// 画面入力された日時を結合し、GMTへ変換する
			$before_gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($before_reserve_date, $before_reserve_time);
		
		}

		$img_filename = $this->options->indigo_workdir_path . self::IMG_ARROW_RIGHT;

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

		$this->common->debug_echo('■ disp_check_update_dialog end');

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
		
		$this->common->debug_echo('■ disp_check_immediate_dialog start');

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

		$this->common->debug_echo('■ disp_check_immediate_dialog end');

		return $ret;
	}

	/**
	 * 新規確認処理
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_add_check() {
		
		$this->common->debug_echo('■ do_add_check start');

		// // 入力チェック処理（一時的にコメント）
		// $this->input_error_message = $this->do_validation_check(self::INPUT_MODE_ADD);

		if ($this->$input_error_message) {
			// エラーがあるので入力ダイアログのまま
			$ret = $this->disp_back_add_dialog();
		} else {
			// エラーがないので確認ダイアログへ遷移
			$ret = $this->disp_check_add_dialog();
		}

		$this->common->debug_echo('■ do_add_check end');

		return $ret;
	}

	/**
	 * 変更確認処理
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_update_check() {
		
		$this->common->debug_echo('■ do_add_check start');

		// // 入力チェック処理（一時的にコメント）
		// $this->input_error_message = $this->do_validation_check(self::INPUT_MODE_UPDATE);

		if ($this->$input_error_message) {
			// エラーがあるので入力ダイアログのまま
			$ret = $this->disp_update_dialog();
		} else {
			// エラーがないので確認ダイアログへ遷移
			$ret = $this->disp_check_update_dialog();
		}

		$this->common->debug_echo('■ do_add_check end');

		return $ret;
	}

	/**
	 * 変更確認処理
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_immediate_check() {
		
		$this->common->debug_echo('■ do_immediate_check start');

		// // 入力チェック処理（一時的にコメント）
		// $this->input_error_message = $this->do_validation_check(self::INPUT_MODE_IMMEDIATE);

		if ($this->$input_error_message) {
			// エラーがあるので入力ダイアログのまま
			$ret = $this->disp_immediate_dialog();
		} else {
			// エラーがないので確認ダイアログへ遷移
			$ret = $this->disp_check_immediate_dialog();
		}

		$this->common->debug_echo('■ do_immediate_check end');

		return $ret;
	}

	/**
	 * 新規ダイアログの確定処理
	 *	 
	 * @return 確認ダイアログ出力内容
	 */
	public function do_add_confirm() {
		
		$this->common->debug_echo('■ do_add_confirm start');
	
		$current_dir = realpath('.');
	
		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			//============================================================
			// 指定ブランチのGit情報を「waiting」ディレクトリへコピー
			//============================================================

	 		$this->common->debug_echo('　□ -----Gitのファイルコピー処理-----');
			
			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_WAITING));

			$this->common->debug_echo('　□ waiting_real_path');
			$this->common->debug_echo($waiting_real_path);

			// 公開予約ディレクトリ名の取得
			$dirname = $this->common->format_gmt_datetime($this->options->_POST->gmt_reserve_datetime, define::DATETIME_FORMAT_SAVE) . '_reserve';

			$this->common->debug_echo('　□ 公開予約ディレクトリ：');
			$this->common->debug_echo($dirname);

			// コピー処理
			$ret = json_decode($this->file_copy($waiting_real_path, $dirname));

			if ( !$ret->status ) {
				throw new \Exception('Git file copy failed.');
			}

	 		$this->common->debug_echo('　□ -----公開処理結果テーブルの登録処理-----');
			
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

		$this->common->debug_echo('■ do_add_confirm end');

		return json_encode($result);
	}


	/**
	 * 変更ダイアログの確定処理
	 *	 
	 * @return 確認ダイアログ出力内容
	 */
	public function do_update_confirm() {
		
		$this->common->debug_echo('■ do_add_confirm start');
	
		$current_dir = realpath('.');
	
		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_WAITING));

			//============================================================
			// 「waiting」ディレクトリの変更前の公開ソースディレクトリを削除
			//============================================================
			// 変更前の公開予約ディレクトリ名の取得
			$before_dirname = $this->common->format_gmt_datetime($this->options->_POST->before_gmt_reserve_datetime, define::DATETIME_FORMAT_SAVE) . '_reserve';

			$this->common->debug_echo('　□ 変更前の公開予約ディレクトリ：');
			$this->common->debug_echo($before_dirname);

			// コピー処理
			$ret = json_decode($this->file_delete($waiting_real_path, $before_dirname));

			if ( !$ret->status ) {
				throw new \Exception('Git file delete failed.');
			}

			//============================================================
			// 変更後ブランチのGit情報を「waiting」ディレクトリへコピー
			//============================================================
			// 公開予約ディレクトリ名の取得
			$dirname = $this->common->format_gmt_datetime($this->options->_POST->gmt_reserve_datetime, define::DATETIME_FORMAT_SAVE) . '_reserve';

			$this->common->debug_echo('　□ 変更後の公開予約ディレクトリ：');
			$this->common->debug_echo($dirname);

			// コピー処理
			$ret = json_decode($this->file_copy($waiting_real_path, $dirname));

			if ( !$ret->status ) {
				throw new \Exception('Git file copy failed.');
			}

	 		$this->common->debug_echo('　□ -----公開処理結果テーブルの更新処理-----');
			
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

		$this->common->debug_echo('■ do_add_confirm end');

		return json_encode($result);
	}

	/**
	 * 削除処理
	 *	 
	 * @return 確認ダイアログ出力内容
	 */
	public function do_delete() {
		
		$this->common->debug_echo('■ do_delete start');
	
		$current_dir = realpath('.');
	
		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_WAITING));

			//============================================================
			// 「waiting」ディレクトリの変更前の公開ソースディレクトリを削除
			//============================================================
			// 公開予約ディレクトリ名の取得
			$selected_id =  $this->options->_POST->selected_id;
			$selected_ret = $this->tsReserve->get_selected_ts_reserve($this->dbh, $selected_id);
			$dirname = $this->common->format_gmt_datetime($selected_ret[tsReserve::RESERVE_ENTITY_RESERVE], define::DATETIME_FORMAT_SAVE) . '_reserve';

			$this->common->debug_echo('　□ 公開予約ディレクトリ：');
			$this->common->debug_echo($dirname);

			// コピー処理
			$ret = json_decode($this->file_delete($waiting_real_path, $dirname));

			if ( !$ret->status ) {
				throw new \Exception('Git file delete failed.');
			}
			$this->common->debug_echo('　□ -----公開処理結果テーブルの削除処理-----');
			
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

		$this->common->debug_echo('■ do_delete end');

		return json_encode($result);
	}


	/**
	 * 即時公開処理
	 */
	public function do_immediate_publish() {

		$this->common->debug_echo('■ do_immediate_publish start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// GMTの現在日時
			$start_datetime = $this->common->get_current_datetime_of_gmt();

			$this->common->debug_echo('　□ 現在日時：');
			$this->common->debug_echo($start_datetime);

			// ============================================================
			// 指定ブランチのGit情報を「running」ディレクトリへコピー
			// ============================================================

			// runningディレクトリの絶対パスを取得。
			$running_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_RUNNING));

			// 公開予約ディレクトリ名の取得
			$dirname = $this->common->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

			$this->common->debug_echo('　□ 公開予約ディレクトリ：');
			$this->common->debug_echo($dirname);

			// コピー処理
			$ret = json_decode($this->file_copy($running_real_path, $dirname));

			if ( !$ret->status ) {
				throw new \Exception('Git file copy failed.');
			}

	 		$this->common->debug_echo('　□ -----公開処理結果テーブルの登録処理-----');
			

			//============================================================
			// 公開処理結果テーブルの登録処理
			//============================================================
			$ret = json_decode($this->tsOutput->insert_ts_output($this->dbh, $this->main->options, $start_datetime, self::PUBLISH_TYPE_IMMEDIATE));
			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT insert failed.");
			}

			// インサートしたシーケンスIDを取得（処理終了時の更新処理にて使用）
			$insert_id = $this->dbh->lastInsertId();

			$this->common->debug_echo('　□ $insert_id：');
			$this->common->debug_echo($insert_id);


			//============================================================
			// ※公開処理※
			//============================================================
			$ret = json_decode($this->publish->do_publish($dirname));
		
			// 公開ステータスの設定
			$publish_status;
			if ( $ret->status) {
				$publish_status = define::PUBLISH_STATUS_SUCCESS;
			} else {
				$publish_status = define::PUBLISH_STATUS_FAILED;
			}


			//============================================================
			// 公開処理結果テーブルの更新処理
			//============================================================
			// GMTの現在日時
			$end_datetime = $this->common->get_current_datetime_of_gmt();

	 		$ret = $this->tsOutput->update_ts_output($this->dbh, $insert_id, $end_datetime, $publish_status);

			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT update failed. " . $ret->message);
			}

		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			$this->common->debug_echo('■ immediate_publish error end');

			chdir($current_dir);
			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->common->debug_echo('■ immediate_publish end');

		return json_encode($result);
	}

	/**
	 * 入力チェック処理
	 *	 
	 * @return 
	 *  エラーメッセージHTML
	 */
	private function do_validation_check($input_mode) {
				
		$this->common->debug_echo('■ do_validation_check start');

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
			if (!$this->check->check_reserve_max_record($data_list)) {
				$ret .= '<p class="error_message">公開予約は最大' . $max . '件までの登録になります。</p>';
			}
		}

		// 日付の妥当性チェック
		if (!$this->check->check_date($reserve_date)) {
			$ret .= '<p class="error_message">「公開予約日時」の日付が有効ではありません。</p>';
		}

		// 未来の日付であるかチェック
		if (!$this->check->check_future_date($gmt_reserve_datetime)) {
			$ret .= '<p class="error_message">「公開予約日時」は未来日時を設定してください。</p>';
		}

		// ブランチの重複チェック
		if (!$this->check->check_exist_branch($data_list, $branch_select_value, $selected_id)) {
			$ret .= '<p class="error_message">1つのブランチで複数の公開予約を作成することはできません。</p>';
		}

		// 公開予約日時の重複チェック
		if (!$this->check->check_exist_reserve($data_list, $gmt_reserve_datetime, $selected_id)) {
			$ret .= '<p class="error_message">入力された日時はすでに公開予約が作成されています。</p>';
		}

		$this->common->debug_echo('■ do_validation_check end');

		return $ret;
	}


	/**
	 * ブランチリストを取得
	 *	 
	 * @return 指定リポジトリ内のブランチリストを返す
	 */
	private function get_branch_list() {


		$this->common->debug_echo('■ get_branch_list start');

		$current_dir = realpath('.');

		$output_array = array();
		$result = array('status' => true,
						'message' => '');

		try {

			// masterディレクトリの絶対パス
			$master_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_MASTER));

			$this->common->debug_echo('　□ master_real_path：');
			$this->common->debug_echo($master_real_path);

			if ( chdir( $master_real_path )) {

				// fetch
				$command = 'git fetch';
				$this->common->command_execute($command, false);

				// ブランチの一覧取得
				$command = 'git branch -r';
				$ret = $this->common->command_execute($command, false);

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

			$this->common->debug_echo('■ get_branch_list error end');
			return json_encode($result);
		}

		$result['status'] = true;

		chdir($current_dir);

		$this->common->debug_echo('■ get_branch_list end');
		return json_encode($result);

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
	 * 公開ソースディレクトリを作成し、Gitファイルのコピー
	 *
	 * @return なし
	 */
	private function file_copy($path, $dirname) {

		$this->common->debug_echo('■ file_copy start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// 公開ソースディレクトリの絶対パスを取得。すでに存在している場合は削除して再作成する。
			$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($path . $dirname));

			$this->common->debug_echo('　□ $dir_real_path' . $dir_real_path);

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

		$this->common->debug_echo('■ file_copy end');

		return json_encode($result);

	}

	/**
	 * 新規追加時のGitファイルのコピー
	 *
	 * @return なし
	 */
	private function git_pull() {

		$this->common->debug_echo('■ git_pull start');

		try {

			// 指定ブランチ
			$branch_name = trim(str_replace("origin/", "", $this->options->_POST->branch_select_value));

			// git init
			$command = 'git init';
			$this->common->command_execute($command, false);

			// git urlのセット
			$url = $this->options->git->protocol . "://" . urlencode($this->options->git->username) . ":" . urlencode($this->options->git->password) . "@" . $this->options->git->url;
			
			// initしたリポジトリに名前を付ける
			$command = 'git remote add origin ' . $url;
			$this->common->command_execute($command, false);

			// git fetch（リモートリポジトリの指定ブランチの情報をローカルブランチへ反映）
			$command = 'git fetch origin' . ' ' . $branch_name;
			$this->common->command_execute($command, false);

			// git pull（リモート取得ブランチを任意のローカルブランチにマージするコマンド）
			$command = 'git pull origin' . ' ' . $branch_name;
			$this->common->command_execute($command, false);
	
		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;
		
		$this->common->debug_echo('■ git_pull end');

		return json_encode($result);

	}

	/**
	 * 公開ソースディレクトリをGitファイルごと削除
	 *
	 * @return なし
	 */
	private function file_delete($path, $dirname) {
		
		$this->common->debug_echo('■ file_delete start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// 公開ソースディレクトリの絶対パスを取得
			$dir_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($path . $dirname));

			$this->common->debug_echo('　□ $dir_real_path' . $dir_real_path);

			if( $dir_real_path && file_exists( $dir_real_path )) {
				// ディレクトリが存在する場合、削除コマンド実行
				$command = 'rm -rf --preserve-root '. $dir_real_path;
				$ret = $this->common->command_execute($command, true);

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

		$this->common->debug_echo('■ file_delete end');

		return json_encode($result);

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
	
		// $this->common->debug_echo('■ combine_to_gmt_date_and_time start');

		$ret = '';

		if (isset($date) && isset($time)) {

			// サーバのタイムゾーン取得
			$timezone = date_default_timezone_get();
			$t = new \DateTime($date . ' ' . $time, new \DateTimeZone($timezone));

			// タイムゾーン変更
			$t->setTimeZone(new \DateTimeZone('GMT'));
		
			// $ret = $t->format(DATE_ATOM);
			$ret = $t->format(define::DATETIME_FORMAT);
			// $this->common->debug_echo('　□timezone：' . $timezone);
		}
		
		// $this->common->debug_echo('　□変換前の時刻：' . $datetime);
		// $this->common->debug_echo('　□変換後の時刻（GMT）：'. $ret);
		
		// $this->common->debug_echo('■ combine_to_gmt_date_and_time end');

	    return $ret;
	}

}
