<?php

namespace indigo\screen;

use indigo\db\tsOutput as tsOutput;
use indigo\db\tsBackup as tsBackup;
use indigo\define as define;

class historyScreen
{
	private $main;

	private $tsOutput;
	private $tsBackup;

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->tsOutput = new tsOutput($this->main);
		$this->tsBackup = new tsBackup($this->main);
	}
	

	/**
	 * 履歴表示のコンテンツ作成
	 *	 
	 * @return 履歴表示の出力内容
	 */
	public function disp_history_screen() {
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ disp_history_screen start');

		$ret = "";

		// 公開処理結果一覧を取得
		$output_list = $this->tsOutput->get_ts_output_list();

		$ret .= '<div style="overflow:hidden">'
			. '<form id="form_table" method="post">'
			. '<div class="button_contents" style="float:left">'
			. '<ul>'
			. '<li><h4>履歴一覧画面</h4></li>'
			. '</ul>'
			. '</div>'
			. '<div class="button_contents" style="float:right;">'
			. '<ul>'
			. '<li><input type="submit" id="log_btn" name="log" class="px2-btn px2-btn--primary" value="ログ"/></li>'
			. '</ul>'
			. '</div>'
			. '</div>';

		// ヘッダー
		$ret .= '<table name="list_tbl" class="table table-striped" style="table-layout:fixed;width:100%;">'
				. '<thead>'
				. '<tr>'
				. '<th width="3%" scope="row"></th>'
				. '<th width="8%" scope="row">状態</th>'
				. '<th width="8%" scope="row">公開種別</th>'
				. '<th width="10%" scope="row">公開予約日時</th>'
				. '<th width="10%" scope="row">バックアップ日時</th>'
				. '<th width="7%" scope="row">コミット</th>'
				. '<th width="14%" scope="row">ブランチ</th>'
				. '<th width="12%" scope="row">コメント</th>'
				. '<th width="10%" scope="row">処理開始日時</th>'
				. '<th width="10%" scope="row">処理完了日時</th>'
				. '<th width="8%" scope="row">実行ユーザ</th>'
				. '</tr>'
				. '</thead>'
				. '<tbody>';

		// データリスト
		foreach ((array)$output_list as $array) {
			
			$backup_datetime_disp = '';

			if ($array[tsOutput::OUTPUT_ENTITY_BACKUP_ID]) {
				// バックアップ情報の取得
				$backup_ret = $this->tsBackup->get_selected_ts_backup($array[tsOutput::OUTPUT_ENTITY_BACKUP_ID]);

				if ($backup_ret && $backup_ret[tsBackup::TS_BACKUP_DATETIME]) {
					$tz_datetime = $this->main->common()->convert_to_timezone_datetime($backup_ret[tsBackup::TS_BACKUP_DATETIME]);
					$backup_datetime_disp = $this->main->common()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);
				}
				
			}

			$ret .= '<tr>'
				. '<td class="p-center">
				  <input type="radio" name="target" value="' . htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_ID_SEQ]) . '"/></td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_STATUS_DISP]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_PUBLISH_TYPE]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_RESERVE_DISP]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($backup_datetime_disp) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_COMMIT_HASH]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_BRANCH]) . '</td>'
				. '<td>' 				  . htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_COMMENT]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_START_DISP]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_END_DISP]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_INSERT_USER_ID]) . '</td>'
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
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ disp_history_screen end');

		return $ret;
	}


	/**
	 * ログダイアログの表示
	 *	 
	 * @return ログダイアログの出力内容
	 */
	public function do_disp_log_dialog() {
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ do_disp_log_dialog start');

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		try {

			// 選択ID
			$selected_id =  $this->main->options->_POST->selected_id;

			// 公開処理結果情報の取得
			$selected_ret = $this->tsOutput->get_selected_ts_output($selected_id);

			// ダイアログHTMLの作成
			$result['dialog_disp'] = $this->create_log_dialog_html($selected_ret);

			// // ダイアログHTMLの作成
			// $result['dialog_disp'] = $this->create_log_dialog_html();

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = 'View log dialog failed. ' . $e->getMessage();

			return $result;
		}

		$result['status'] = true;

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ do_disp_log_dialog end');

		return $result;
	}

	/**
	 * ログダイアログHTMLの作成
	 *	 
	 * @return ログダイアログ出力内容
	 */
	private function create_log_dialog_html($selected_ret) {
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ create_log_dialog_html start');

		$ret = '<div class="dialog" id="modal_dialog">'
			  . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			  . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			  . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			  . '<div class="dialog_box">';

			
			// 公開ディレクトリ名の取得
			$start_datetime_gmt = $selected_ret[tsOutput::OUTPUT_ENTITY_START_GMT];
			// 公開予約ディレクトリ名の取得
			$dirname = $this->main->common()->format_gmt_datetime($start_datetime_gmt, define::DATETIME_FORMAT_SAVE);


			// logディレクトリの絶対パスを取得。
			$realpath_log = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($this->main->realpath_array['realpath_log'] . $dirname . "/"));
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ $realpath_log:' . $realpath_log);

			// ファイルを変数に格納
			$filename = $realpath_log . 'pub_copy_' . $dirname . '.log';

			$content = "";
			if (file_exists($filename)) {
				$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ $filename:' . $filename);

				// ファイルを読み込み変数に格納
				$content = file_get_contents($filename);
			}


		$ret .= '<p>User ID：' . htmlspecialchars($selected_ret[tsOutput::OUTPUT_ENTITY_INSERT_USER_ID]) . '</p>'
			  . '<p>公開開始日時：' . htmlspecialchars($selected_ret[tsOutput::OUTPUT_ENTITY_START_DISP]) . '</p>'
			  . '<p>公開終了日時：' . htmlspecialchars($selected_ret[tsOutput::OUTPUT_ENTITY_END_DISP]) . '</p>'
			  // . '<p>-----------------------------------------------------</p>'
			  . '<p>公開同期ログ：</p>'
			  . '<p>' . htmlspecialchars(nl2br($content)) .'</p>';
			  // . '<p>-----------------------------------------------------</p>';

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
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ create_log_dialog_html end');

		return $ret;
	}

}
