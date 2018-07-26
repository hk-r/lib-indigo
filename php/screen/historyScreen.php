<?php

namespace indigo;

class historyScreen
{
	private $main;
	private $tsOutput;
	private $common;

	/**
	 * PDOインスタンス
	 */
	private $dbh;
	

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->tsOutput = new tsOutput($this);
		$this->common = new common($this);
	}
	

	/**
	 * 履歴表示のコンテンツ作成
	 *	 
	 * @return 履歴表示の出力内容
	 */
	public function disp_history_screen() {
		
		$this->common->debug_echo('■ disp_history_screen start');

		$ret = "";

		// 公開処理結果一覧を取得
		$data_list = $this->tsOutput->get_ts_output_list($this->main->dbh, null);

		$ret .= '<div style="overflow:hidden">'
			. '<form id="form_table" method="post">'
			. '<div class="button_contents" style="float:right;">'
			. '<ul>'
			. '<li><input type="submit" id="log_btn" name="log" class="px2-btn px2-btn--primary" value="ログ"/></li>'
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
				. '<th scope="row">コミット</th>'
				. '<th scope="row">ブランチ</th>'
				. '<th scope="row">コメント</th>'
				. '<th scope="row">処理開始日時</th>'
				. '<th scope="row">処理完了日時</th>'
				. '<th scope="row">実行ユーザ</th>'
				. '</tr>'
				. '</thead>'
				. '<tbody>';

		// データリスト
		foreach ((array)$data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center">
				  <input type="radio" name="target" value="' . $array[tsOutput::OUTPUT_ENTITY_ID_SEQ] . '"/></td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_STATUS] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_PUBLISH_TYPE] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_RESERVE_DISP] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_COMMIT_HASH] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_BRANCH] . '</td>'
				. '<td>' 				  . $array[tsOutput::OUTPUT_ENTITY_COMMENT] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_START_DISP] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_END_DISP] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_INSERT_USER_ID] . '</td>'
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
		
		$this->common->debug_echo('■ disp_history_screen end');

		return $ret;
	}


	/**
	 * ログダイアログの表示
	 *	 
	 * @return ログダイアログの出力内容
	 */
	public function do_disp_log_dialog() {
		
		$this->common->debug_echo('■ do_disp_log_dialog start');

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		try {

			// 選択ID
			$selected_id =  $this->main->options->_POST->selected_id;

			// 公開処理結果情報の取得
			$selected_ret = $this->tsOutput->get_selected_ts_output($this->main->dbh, $selected_id);
			// ダイアログHTMLの作成
			$result['dialog_disp'] = $this->create_log_dialog_html($selected_ret);

			// // ダイアログHTMLの作成
			// $result['dialog_disp'] = $this->create_log_dialog_html();

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = 'View log dialog failed. ' . $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->common->debug_echo('■ do_disp_log_dialog end');

		return json_encode($result);
	}

	/**
	 * ログダイアログHTMLの作成
	 *	 
	 * @return ログダイアログ出力内容
	 */
	private function create_log_dialog_html($selected_ret) {
		
		$this->common->debug_echo('■ create_log_dialog_html start');

		$ret = '<div class="dialog" id="modal_dialog">'
			  . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			  . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			  . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			  . '<div class="dialog_box">';


			// logディレクトリの絶対パスを取得。
			$log_real_path = $this->fs->normalize_path($this->fs->get_realpath($this->main->options->workdir_relativepath . define::PATH_LOG));
			
			// 公開ディレクトリ名の取得
			$start_datetime_gmt = $selected_ret[tsOutput::OUTPUT_ENTITY_START_GMT];
			// 公開予約ディレクトリ名の取得
			$dirname = $this->common->format_gmt_datetime($start_datetime_gmt, define::DATETIME_FORMAT_SAVE);

			// ファイルを変数に格納
			$filename = $log_real_path . 'rsync_' . $dirname . '.log';
			// ファイルを読み込み変数に格納
			$content = file_get_contents($filename);

		$ret .= '<p>公開開始日時：' . $selected_ret[tsOutput::OUTPUT_ENTITY_START_DISP] . '</p>'
			  . '<p>公開終了日時：' . $selected_ret[tsOutput::OUTPUT_ENTITY_END_DISP] . '</p>'
			  . '<p>  User ID  ：' . $selected_ret[tsOutput::OUTPUT_ENTITY_INSERT_USER_ID] . '</p>'
			  . '<p>' . nl2br($content) .'</p>';

		$ret .=  '<div class="button_contents_box">'
			  . '<div class="button_contents">'
			  . '<ul>';
		
		// 「閉じる」ボタン
		$ret .= '<li><input type="submit" id="close_btn" class="px2-btn" value="閉じる"/></li>';
		
		$ret .= '</ul>'
			  . '</div>'
			  . '</div>'
			  . '</form>'
			  . '</div>'

			  . '</div>'
			  . '</div>'
			  . '</div></div>';
		
		$this->common->debug_echo('■ create_log_dialog_html end');

		return $ret;
	}

}
