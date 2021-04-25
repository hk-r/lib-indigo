<?php

namespace pickles2\indigo\screen;

use pickles2\indigo\db\tsOutput as tsOutput;
use pickles2\indigo\db\tsBackup as tsBackup;
use pickles2\indigo\define as define;

/**
 * 履歴一覧表示画面処理クラス
 *
 * 履歴一覧表示画面に関連する処理をまとめたクラス。
 *
 */
class historyScreen
{
	private $main;

	private $tsOutput;
	private $tsBackup;

	/**
	 * コンストラクタ
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->tsOutput = new tsOutput($this->main);
		$this->tsBackup = new tsBackup($this->main);
	}
	

	/**
	 * 履歴一覧画面のHTML作成
	 *	 
	 * @return string $ret HTMLソースコード
	 */
	public function disp_history_screen() {
		
		// 公開処理結果一覧を取得
		$output_list = $this->tsOutput->get_ts_output_list();

		$ret = '<div style="overflow:hidden">'
			. '<form id="form_table" method="post">'
			. $this->main->get_additional_params()
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
				. '<th width="10%" scope="row">公開予定日時</th>'
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
					$tz_datetime = $this->main->utils()->convert_to_timezone_datetime($backup_ret[tsBackup::TS_BACKUP_DATETIME]);
					$backup_datetime_disp = $this->main->utils()->format_datetime($tz_datetime, define::DATETIME_FORMAT_DISP);
				}
				
			}

			$ret .= '<tr>'
				. '<td class="p-center">
				  <input type="radio" name="target" value="' . \htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_ID_SEQ]) . '"/></td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_STATUS_DISP]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_PUBLISH_TYPE]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_RESERVE_DISP]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($backup_datetime_disp) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_COMMIT_HASH]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_BRANCH]) . '</td>'
				. '<td>' 				  . \htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_COMMENT]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_START_DISP]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_END_DISP]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsOutput::OUTPUT_ENTITY_INSERT_USER_ID]) . '</td>'
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
	 * ログダイアログの表示
	 *	 
	 * @return string $dialog_disp HTMLソースコード
	 */
	public function do_disp_log_dialog() {
		
		// ダイアログHTMLの作成
		$dialog_disp = $this->create_log_dialog_html();

		return $dialog_disp;
	}

	/**
	 * ログダイアログのHTML作成
	 *	 
	 * @return string $ret ログダイアログHTML
	 */
	private function create_log_dialog_html() {

		$selected_id =  $this->main->options->_POST->selected_id;
		// 公開処理結果情報の取得
		$selected_ret = $this->tsOutput->get_selected_ts_output($selected_id);

		$start_datetime_gmt = $selected_ret[tsOutput::OUTPUT_ENTITY_START_GMT];
		// 公開予定ディレクトリ名の取得
		$dirname = $this->main->utils()->format_gmt_datetime($start_datetime_gmt, define::DATETIME_FORMAT_SAVE);

		// logディレクトリの絶対パスを取得。
		$realpath_log = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($this->main->realpath_array['realpath_log'] . $dirname . "/"));
	
		// ファイルを変数に格納
		$log_filename = $realpath_log . 'pub_copy_' . $dirname . '.log';

		$content = "";
		if (\file_exists($log_filename)) {
			// ファイルの読み込み
			$content = \file_get_contents($log_filename);
		}

		$ret = '';
		$ret .= '<div class="dialog" id="modal_dialog">'
			  . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			  . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			  . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			  . '<div class="dialog_box">';

		$ret .= '<table class="table table-striped">'
			  . '<tbody>'
			  . '<tr><th>ユーザーID</th><td>' . \htmlspecialchars($selected_ret[tsOutput::OUTPUT_ENTITY_INSERT_USER_ID]) . '</td></tr>'
			  . '<tr><th>公開開始日時</th><td>' . \htmlspecialchars($selected_ret[tsOutput::OUTPUT_ENTITY_START_DISP]) . '</td></tr>'
			  . '<tr><th>公開終了日時</th><td>' . \htmlspecialchars($selected_ret[tsOutput::OUTPUT_ENTITY_END_DISP]) . '</td></tr>'
			  . '<tr><th>公開同期ログ</th><td>' . nl2br(htmlspecialchars($content)) .'</td></tr>'
			  . '</tbody>'
			  . '</table>';

		$ret .= '<div class="button_contents_box">'
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
		
		return $ret;
	}

}
