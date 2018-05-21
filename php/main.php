<?php

namespace indigo;

class main
{
	public $options;

	/**
	 * Delimiter
	 *
	 * @access	private
	 * @var string
	 */
	private $_delimiter		= ',';

	/**
	 * Enclosure
	 *
	 * @access	private
	 * @var	string
	 */
	private $_enclosure		= '"';

	/**
	 * 公開予約管理CSVファイル
	 */
	private $list_filename = '\res\csv\list.csv';

	/**
	 * 警告エラー時のお知らせCSVファイル
	 */
	private $alert_filename = '\res\csv\alert.csv';

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {
		$this->options = json_decode(json_encode($options));
	}

	/**
	 * ブランチリストを取得
	 *	 
	 * @return 指定リポジトリ内のブランチリストを返す
	 */
	private function get_branch_list() {

		$current_dir = realpath('.');

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
				throw new Exception('Repository directory not found.');
			}

		} catch (Exception $e) {

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		$result['status'] = true;

		chdir($current_dir);
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
	 * @param $time = 日時
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	private function convert_reserve_datetime($date, $time) {

		$ret = '';

		if (isset($date) && isset($time)) {

			$ret = $date . ' ' . date('H:i:s',  strtotime($time));
		}

		return $ret;
	}

	/**
	 * 初期表示画面の新規ボタン押下時
	 *	 
	 * @param $error_message_disp = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	private function disp_add_dialog($error_message_disp) {
		
		$ret = "";

		$branch_selected_value = "";
		$date = "";
		$time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_selected_value)) {
			$branch_selected_value = $this->options->_POST->branch_selected_value;
		}

		if (isset($this->options->_POST->date)) {
			$date = $this->options->_POST->date;
		}

		if (isset($this->options->_POST->time)) {
			$time = $this->options->_POST->time;
		}
		
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}

		// ブランチリストを取得
		$get_branch_ret = json_decode($this->get_branch_list());
		$branch_list = array();
		$branch_list = $get_branch_ret->branch_list;

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(true, $error_message_disp, $branch_list, $branch_selected_value, $date, $time, $comment, '');

		return $ret;
	}

	/**
	 * 新規ダイアログ表示の確認ボタン押下時
	 *	 
	 * @return 新規確認ダイアログの出力内容
	 */
	private function do_add_check_btn() {
				
		$ret = "";

		$branch_selected_value = "";
		$date = "";
		$time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_selected_value)) {
			$branch_selected_value = $this->options->_POST->branch_selected_value;
		}

		if (isset($this->options->_POST->date)) {
			$date = $this->options->_POST->date;
		}

		if (isset($this->options->_POST->time)) {
			$time = $this->options->_POST->time;
		}
		
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}

		$selected_id = '';

		// 確認ダイアログHTMLの作成
		$ret = $this->create_check_dialog_html(true, $branch_selected_value, $date, $time, $comment, $selected_id);

		return $ret;
	}


	/**
	 * 変更ダイアログの表示
	 *	 
	 * @param $error_message_disp = エラーメッセージ出力内容
	 *
	 * @return 変更ダイアログの出力内容
	 */
	private function disp_update_dialog($error_message_disp) {
		
		$ret = "";

		$branch_selected_value = "";
		$date = "";
		$time = "";
		$comment = "";

		$selected_id =  "";

		// 初期表示画面の変更ボタンから遷移してきた場合
		if (isset($this->options->_POST->radio_selected_id)) {

			// 選択されたID
			$selected_id =  $this->options->_POST->radio_selected_id;
			// 選択されたIDに紐づく情報を取得
			$selected_ret = $this->get_selected_data();
			
			$branch_selected_value = $selected_ret['branch_name'];
			$date = date('Y-m-d',  strtotime($selected_ret['reserve_datetime']));
			$time = date('H:i',  strtotime($selected_ret['reserve_datetime']));
			$comment = $selected_ret['comment'];;

		} else {

			// フォームパラメタが設定されている場合変数へ設定
			if (isset($this->options->_POST->branch_selected_value)) {
				$branch_selected_value = $this->options->_POST->branch_selected_value;
			}

			if (isset($this->options->_POST->date)) {
				$date = $this->options->_POST->date;
			}

			if (isset($this->options->_POST->time)) {
				$time = $this->options->_POST->time;
			}
			
			if (isset($this->options->_POST->comment)) {
				$comment = $this->options->_POST->comment;
			}

			if (isset($this->options->_POST->selected_id)) {
				$selected_id = $this->options->_POST->selected_id;
			}	
		}

		// ブランチリストを取得
		$get_branch_ret = json_decode($this->get_branch_list());
		$branch_list = array();
		$branch_list = $get_branch_ret->branch_list;

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(false, $error_message_disp, $branch_list, $branch_selected_value, $date, $time, $comment, $selected_id);

		return $ret;
	}

	/**
	 * 変更ダイアログの確認ボタン押下
	 *	 
	 * @return 変更確認ダイアログの出力内容
	 */
	private function do_update_check_btn() {
		
		
		$ret = "";

		$branch_selected_value = "";
		$date = "";
		$time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_selected_value)) {
			$branch_selected_value = $this->options->_POST->branch_selected_value;
		}

		if (isset($this->options->_POST->date)) {
			$date = $this->options->_POST->date;
		}

		if (isset($this->options->_POST->time)) {
			$time = $this->options->_POST->time;
		}
		
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}

		if (isset($this->options->_POST->selected_id)) {
			$selected_id = $this->options->_POST->selected_id;
		}

		// 確認ダイアログHTMLの作成
		$ret = $this->create_check_dialog_html(false, $branch_selected_value, $date, $time, $comment, $selected_id);

		return $ret;
	}

	/**
	 * 新規・変更の入力ダイアログHTMLの作成
	 *	 
	 * @param $add_flg            = 新規フラグ
	 * @param $error_message_disp = エラーメッセージ出力内容
	 * @param $branch_list        = ブランチリスト
	 * @param $branch_selected_value = ブランチ選択値
	 * @param $date        = 日付
	 * @param $time        = 日時
	 * @param $comment     = コメント
	 * @param $selected_id = 変更時の選択ID
	 *
	 * @return 
	 *  入力ダイアログ出力内容
	 */
	private function create_dialog_html($add_flg, $error_message_disp, $branch_list,
		$branch_selected_value, $date, $time, $comment, $selected_id) {
		
		$ret = "";

		$ret .= '<div class="dialog">'
			  . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			  . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			  . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			  . '<div class="dialog_box">';

		 if ($error_message_disp != '') {
		 // エラーメッセージの出力
			$ret .= '<div class="alert_box">'
				. $error_message_disp
				. '</div>';
		 }

		if ( $add_flg ) {
			$ret .= '<h4>新規</h4>';
		} else {
		  	$ret .= '<h4>変更</h4>';
		}
			   
		$ret .= '<form method="post">'
			  . '<input type="hidden" name="selected_id" value="' . htmlspecialchars($selected_id) . '"/>'
			  . '<table class="table table-bordered table-striped">'
			  . '<tr>'
			  . '<td>ブランチ</td>'
			  . '<td><select id="branch_list" class="form-control" name="branch_selected_value">';

				foreach ($branch_list as $branch) {
					$ret .= '<option value="' . htmlspecialchars($branch) . '" ' . $this->compare_to_selected_value($branch_selected_value, $branch) . '>' . htmlspecialchars($branch) . '</option>';
				}

		$ret .= '</select></td>'
			  . '</tr>'
			  . '<tr>'
			  . '<td>コミット</td>'
			  . '<td>' . 'dummy' . '</td>'
			  . '</tr>'
			  . '<tr>'
			  . '<td scope="row">公開予定日時</td>'
			  . '<td scope="row"><span style="margin-right:10px;"><input type="text" id="datepicker" name="date" value="'. $date . '" /></span>'
			  . '<input type="time" id="time" name="time" value="'. $time . '"/></td>'
			  . '</tr>'
			  . '<tr>'
			  . '<td scope="row">コメント</td>'
			  . '<td scope="row"><input type="text" id="comment" name="comment" size="50" value="' . htmlspecialchars($comment) . '" /></td>'
			  . '</tr>'
			  . '</tbody></table>'

			  . '<div class="unit">'
			  . '<div class="text-center">';
		
		if ( $add_flg ) {
			$ret .= '<input type="submit" id="add_check_btn" name="add_check_btn" class="btn btn-default" value="確認"/>';
		} else {
		  	$ret .= '<input type="submit" id="update_check_btn" name="update_check_btn" class="btn btn-default" value="確認"/>';
		}

		$ret .= '<input type="submit" id="close_btn" class="btn btn-default" value="キャンセル"/>'
			  . '</div>'
			  . '</form>'
			  . '</div>'

			  . '</div>'
			  . '</div>'
			  . '</div></div>';

		return $ret;
	}


	/**
	 * 新規・変更の出力確認ダイアログHTMLの作成
	 *	 
	 * @param $add_flg     = 新規フラグ
	 * @param $branch_selected_value = ブランチ選択値
	 * @param $date        = 日付
	 * @param $time        = 日時
	 * @param $comment     = コメント
	 * @param $selected_id = 変更時の選択ID
	 *
	 * @return 
	 *  確認ダイアログ出力内容
	 */
	private function create_check_dialog_html($add_flg, $branch_selected_value,
		$date, $time, $comment, $selected_id) {
		
		$ret = "";

		$ret .= '<div class="dialog">'
			 . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			 . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			 . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			 . '<div class="dialog_box">';
			 
		if ( $add_flg ) {
			$ret .= '<h4>追加確認</h4>';
		} else {
		  	$ret .= '<h4>変更確認</h4>';
		}

		$ret .= '<form method="post">'
			 . '<input type="hidden" name="selected_id" value="' . $selected_id . '"/>'
			 . '<table class="table table-bordered table-striped">'
			 . '<tr>'
			 . '<td>' . 'ブランチ' . '</td>'
			 . '<td>' . $branch_selected_value
			 . '<input type="hidden" name="branch_selected_value" value="' . $branch_selected_value . '"/>'
			 . '</td>'
			 . '</tr>'
			 . '<tr>'
			 . '<td>' . 'コミット' . '</td>'
			 . '<td>' . 'dummy' . '</td>'
			 . '</tr>'
			 . '<tr>'
			 . '<td scope="row">' . '公開予定日時' . '</td>'
			 . '<td>' . $date . ' ' . $time
			 . '<input type="hidden" name="date" value="' . $date . '"/>'
			 . '<input type="hidden" name="time" value="' . $time . '"/>'
			 . '</td>'
			 . '</tr>'
			 . '<tr>'
			 . '<td scope="row">' . 'コメント' . '</td>'
			 . '<td scope="row">' . htmlspecialchars($comment) . '</td>'
			 . '<input type="hidden" name="comment" value="' . htmlspecialchars($comment) . '"/>'
			 . '</tr>'
			 . '</tbody></table>'
			
			. '<div class="unit">'
			. '<div class="text-center">';

		if ( $add_flg ) {
			$ret .= '<input type="submit" name="add_confirm_btn" class="btn btn-default" value="確定"/>'
				. '<input type="submit" name="add_back_btn" class="btn btn-default" value="戻る"/>';
		} else {
			$ret .= '<input type="submit" name="update_confirm_btn" class="btn btn-default" value="確定"/>'
				. '<input type="submit" name="update_back_btn" class="btn btn-default" value="戻る"/>';
		}

		$ret .= '</div>'
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

		$branch_selected_value = "";
		$date = "";
		$time = "";
		$comment = "";
		$selected_id = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_selected_value)) {
			$branch_selected_value = $this->options->_POST->branch_selected_value;
		}

		if (isset($this->options->_POST->date)) {
			$date = $this->options->_POST->date;
		}

		if (isset($this->options->_POST->time)) {
			$time = $this->options->_POST->time;
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
			$max = 5;

			if ($max <= count($data_list)) {
				$ret .= '<p class="error_message">公開予約は最大' . $max . '件までの登録になります。</p>';
			}
		}

		// 日付の妥当性チェック
		list($Y, $m, $d) = explode('-', $this->options->_POST->date);

		if (!checkdate(intval($m), intval($d), intval($Y))) {
			$ret .= '<p class="error_message">「公開予定日時」の日付が有効ではありません。</p>';
		}

		// 公開予定日時の未来日時チェック
		$now = date("Y-m-d H:i:s");
		$target_day = $this->options->_POST->date . ' ' . date('H:i:s',  strtotime($this->options->_POST->time));

		if (strtotime($now) > strtotime($target_day)) {
			$ret .= '<p class="error_message">「公開予定日時」は未来日時を設定してください。</p>';
		}

		// ブランチの重複チェック
		if (!$this->check_exist_branch($data_list, $branch_selected_value, $selected_id)) {
			$ret .= '<p class="error_message">1つのブランチで複数の公開予定を作成することはできません。</p>';
		}

		// 公開予定日時の重複チェック
		if (!$this->check_exist_reserve($data_list, $this->convert_reserve_datetime($date, $time), $selected_id)) {
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

		// お知らせリストの取得
		$alert_list = $this->get_csv_alert_list();

		if (count($alert_list) != 0) {
			// お知らせリストの表示
			$ret .= '<form name="formA" method="post">'
				. '<div class="alert_box">'
				. '<p class="alert_title">お知らせ</p>';
			// データリスト
			foreach ($alert_list as $data) {
				
				$ret .= '<p class="alert_content">'
					. '<a onClick="document.formA.submit();return false;" >'
					. $data['reserve_datetime'] . '　' . $data['content']
					. '</a></p>';
			}

			$ret .=  '<input type="hidden" name="history" value="履歴">'
				. '</div>'
				. '</form>';
		}

		$ret .= '<div class="unit" style="overflow:hidden">'
			. '<form id="form_tbl" method="post">'
			. '<div style="float:left">'
			. '<input type="submit" name="add" class="btn btn-default" value="新規"/>'
			. '</div>'
			. '<div style="float:right;">'
			. '<input type="submit" id="update_btn" name="update" class="btn btn-default" value="変更"/>'
			. '<input type="submit" id="delete_btn" name="delete" class="btn btn-default" value="削除"/>'
			. '<input type="submit" id="public_btn" name="public" class="btn btn-default" value="即時公開"/>'
			. '<input type="submit" id="history_btn" name="history" class="btn btn-default" value="履歴"/>'
			. '</div>'
			. '</div>';

		// テーブルヘッダー
		$ret .= '<table name="list_tbl" class="table table-bordered table-striped">'
			. '<thead>'
			. '<tr>'
			. '<th scope="row"></th>'
			. '<th scope="row">公開予定日時</th>'
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
				. '<td><input type="radio" id="reserve_' . $array['id'] . '" name="target" value="' . $array['id'] . '"/></td>'
				. '<td>' . date('Y-m-d H:i',  strtotime($array['reserve_datetime'])) . '</td>'
				. '<td>' . $array['commit'] . '</td>'
				. '<td>' . $array['branch_name'] . '</td>'
				. '<td>' . $array['comment'] . '</td>'
				// . '<td>' . $array['id'] . '</td>'
				// . '<td>' . $this->convert_status($array['status']) . '</td>'
				. '</tr>';
		}

		$ret .= '</tbody></table>'
			. '</form>';

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

		$ret .= '<div class="unit" style="overflow:hidden">'
			. '<form method="post">'
			. '<div style="float:right;">'
			. '<input type="submit" name="log" class="btn btn-default" value="ログ"/>'
			. '<input type="submit" name="recovory" class="btn btn-default" value="復元"/>'
			. '</div>'
			. '</div>';

		// ヘッダー
		$ret .= '<table name="list_tbl" class="table table-bordered table-striped">'
				. '<thead>'
				. '<tr>'
				. '<th scope="row"></th>'
				. '<th scope="row"></th>'
				. '<th scope="row">公開予定日時</th>'
				. '<th scope="row">コミット</th>'
				. '<th scope="row">ブランチ</th>'
				. '<th scope="row">コメント</th>'
				. '</tr>'
				. '</thead>'
				. '<tbody>';

		// データリスト
		foreach ($data_list as $array) {
			
			$ret .= '<tr>'
				. '<td><input type="radio" id="reserve_' . $array['id'] . '" name="target" value="' . $array['id'] . '"/></td>'
				. '<td>' . $this->convert_status( $array['status'] ). '</td>'
				. '<td>' . date('Y-m-d H:i',  strtotime($array['reserve_datetime'])) . '</td>'
				. '<td>' . $array['commit'] . '</td>'
				. '<td>' . $array['branch_name'] . '</td>'
				. '<td>' . $array['comment'] . '</td>'
				. '</tr>';
		}

		$ret .= '</tbody></table>';
		
		$ret .= '<div class="unit">'
			. '<div class="text-center">'
			. '<input type="submit" id="history_back_btn" class="btn btn-default" value="戻る"/>'
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

		// 新規ボタンが押下された場合
		if (isset($this->options->_POST->add)) {
		
			$dialog_disp = $this->disp_add_dialog(null);

		// 変更ボタンが押下された場合
		} elseif (isset($this->options->_POST->update)) {
		
			$dialog_disp = $this->disp_update_dialog(null);

		// 削除ボタンが押下された場合
		} elseif (isset($this->options->_POST->delete)) {
		
			$this->do_delete_btn();

		// 即時公開ボタンが押下された場合
		} elseif (isset($this->options->_POST->public)) {
		
			// TODO:未実装
			echo '即時公開ボタン押下';
			
		// // 履歴ボタンが押下された場合
		// } elseif (isset($this->options->_POST->history)) {
		
		// 新規作成ダイアログの「確認」ボタンが押下された場合
		} elseif (isset($this->options->_POST->add_check_btn)) {

			// 入力チェック
			$error_message_disp = $this->do_check_validation(true);

			if ($error_message_disp != '') {
				// 入力チェックエラーがあった場合はそのままの画面
				$dialog_disp = $this->disp_add_dialog($error_message_disp);
			} else {
				// 入力チェックエラーがなかった場合は確認ダイアログへ遷移
				$dialog_disp = $this->do_add_check_btn();
			}
			
		// 新規ダイアログの確定ボタンが押下された場合
		} elseif (isset($this->options->_POST->add_confirm_btn)) {
			
			// 入力情報の追加
			$this->insert_list_csv_data();
		
		// 新規確認ダイアログの戻るボタンが押下された場合
		} elseif (isset($this->options->_POST->add_back_btn)) {
		
			$dialog_disp = $this->disp_add_dialog(null);

		// 変更ダイアログの確認ボタンが押下された場合
		} elseif (isset($this->options->_POST->update_check_btn)) {
		
			$error_message_disp = $this->do_check_validation(false);

			if ($error_message_disp != '') {
				// 入力チェックエラーがあった場合はそのままの画面
				$dialog_disp = $this->disp_update_dialog($error_message_disp);
			} else {
				// 入力チェックエラーがなかった場合は確認ダイアログへ遷移
				$dialog_disp = $this->do_update_check_btn();
			}	

		// 変更ダイアログの確定ボタンが押下された場合
		} elseif (isset($this->options->_POST->update_confirm_btn)) {
			
			// 入力情報の変更
			$this->do_update_btn();
		
		// 変更確認ダイアログの戻るボタンが押下された場合
		} elseif (isset($this->options->_POST->update_back_btn)) {
		
			$dialog_disp = $this->disp_update_dialog(null);

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
		
		// 画面表示
		return $disp . $dialog_disp;
	}

	/**
	 * CSVからお知らせリストを取得する
	 *
	 * @return お知らせリスト
	 */
	private function get_csv_alert_list()
	{

		$ret_array = array();

		$filename = realpath('.') . $this->alert_filename;

		if (!file_exists($filename)) {
			echo 'ファイルが存在しない';

		} else {

			// Open file
			$handle = fopen( $filename, "r" );

			$title_array = array();

			$is_first = true;

			// CSVリストをループ
			while ($rowData = fgetcsv($handle, 0, $this->_delimiter, $this->_enclosure)) {

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

		$ret_array = array();

		$filename = realpath('.') . $this->list_filename;

		if (!file_exists($filename)) {
			echo 'ファイルが存在しない';

		} else {

			// Open file
			$handle = fopen( $filename, "r" );

			$title_array = array();

			$is_first = true;

			// CSVリストをループ
			while ($rowData = fgetcsv($handle, 0, $this->_delimiter, $this->_enclosure)) {

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
					
		return $ret_array;
	}


	/**
	 * CSVから選択された行の情報を取得する
	 *
	 * @return 選択行の情報
	 */
	private function get_selected_data() {

		$filename = realpath('.') . $this->list_filename;

		$selected_id =  $this->options->_POST->radio_selected_id;

		$ret_array = array();

		if (!file_exists($filename) && !empty($selected_id)) {
			echo 'ファイルが存在しない';

		} else {

			$file = file($filename);

			// Open file
			$handle = fopen( $filename, "r" );
			
			$title_array = array();

			$is_first = true;

			// Loop through each line of the file in turn
			while ($rowData = fgetcsv($handle, 0, $this->_delimiter, $this->_enclosure)) {

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
		}
		return $ret_array;
	}

	/**
	 * 登録処理（CSVへの行追加）
	 *
	 * @return なし
	 */
	private function insert_list_csv_data()
	{

		$filename = realpath('.') . $this->list_filename;

		if (!file_exists($filename)) {
			echo 'ファイルが存在しない';

		} else {

			// Open file
			$handle_r = fopen( $filename, "r" );
			$is_first = true;

			$max = 0;

			// Loop through each line of the file in turn
			while ($rowData = fgetcsv($handle_r, 0, $this->_delimiter, $this->_enclosure)) {

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
			$date = date("Y-m-d H:i:s");

			// id, ブランチ名, コミット, 公開予定日時, コメント, 状態, 設定日時
			$array = array(
				$max,
				$this->options->_POST->branch_selected_value,
				"dummy",
				$this->convert_reserve_datetime($this->options->_POST->date, $this->options->_POST->time),
				$this->options->_POST->comment,
				0,
				$date
			);

			fputcsv( $handle, $array, $this->_delimiter, $this->_enclosure);
			fclose( $handle);
		}
	}

	/**
	 * 削除処理（CSVから行削除）
	 *
	 * @return なし
	 */
	private function do_delete_btn() {

		$filename = realpath('.') . $this->list_filename;

		$selected_id =  $this->options->_POST->radio_selected_id;

		if (!file_exists($filename) && !empty($selected_id)) {
			echo 'ファイルが存在しない';

		} else {

			$file = file($filename);

			// Open file
			$handle = fopen( $filename, "r" );
			
			$cnt = 0;

			// Loop through each line of the file in turn
			while ($rowData = fgetcsv($handle, 0, $this->_delimiter, $this->_enclosure)) {

				$num = intval($rowData[0]);

				if ($num == $selected_id) {
					unset($file[$cnt]);
					file_put_contents($filename, $file);
					break;
				}

				$cnt++;
			}
		}
	}

	/**
	 * 変更処理（CSVへ行削除＆行追加）
	 *
	 * @return なし
	 */
	private function do_update_btn() {

		$filename = realpath('.') . $this->list_filename;

		if (!file_exists($filename)) {
			echo 'ファイルが存在しない';

		} else {

			$file = file($filename);

			// Open file
			$handle_r = fopen( $filename, "r" );
			
			$cnt = 0;
			$max = 0;

			$selected_id =  $this->options->_POST->selected_id;

			$is_first = true;

			// Loop through each line of the file in turn
			while ($rowData = fgetcsv($handle_r, 0, $this->_delimiter, $this->_enclosure)) {

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
			$date = date("Y-m-d H:i:s");

			// id, ブランチ, 公開予定日時, 状態, 設定日時
			$array = array(
				$max,
				$this->options->_POST->branch_selected_value,
				"update",
				$this->convert_reserve_datetime($this->options->_POST->date, $this->options->_POST->time),
				$this->options->_POST->comment,
				0,
				$date
			);

			fputcsv( $handle, $array, $this->_delimiter, $this->_enclosure);
			fclose( $handle);
		}
	}
}
