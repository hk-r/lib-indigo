<?php

namespace indigo\screen;

use indigo\db\tsBackup as tsBackup;
use indigo\define as define;

class backupScreen
{
	private $main;

	/**
	 * オブジェクト
	 * @access private
	 */
	private $check, $tsBackup, $tsOutput, $publish;

	/**
	 * 入力画面のエラーメッセージ
	 */
	private $input_error_message = '';


	/**
	 * コンストラクタ
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->tsBackup = new tsBackup($this->main);
		$this->publish = new \indigo\publish($this->main);
	}


	/**
	 * バックアップ一覧画面のHTML作成
	 *	 
	 * @return string $ret HTMLソースコード
	 */
	public function disp_backup_screen() {
		
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ disp_backup_screen start');

		
		// バックアップ一覧を取得
		$data_list = $this->tsBackup->get_ts_backup_list();

		$ret = '<div style="overflow:hidden">'
			. '<form id="form_table" method="post">'
			. '<div class="button_contents" style="float:left;">'
			. '<ul>'
			. '<li><h4>バックアップ一覧画面</h4></li>'
			. '</ul>'
			. '</div>'
			. '<div class="button_contents" style="float:right">'
			. '<ul>'
			. '<li><input type="submit" id="restore_btn" name="restore" class="px2-btn px2-btn--primary" value="復元"/></li>'
			. '</ul>'
			. '</div>'
			. '</div>';

		// ヘッダー
		$ret .= '<table name="list_tbl" class="table table-striped">'
				. '<thead>'
				. '<tr>'
				. '<th scope="row"></th>'
				. '<th scope="row">バックアップ日時</th>'
				. '<th scope="row">公開種別</th>'
				. '<th scope="row">公開予約日時</th>'
				. '<th scope="row">ブランチ</th>'
				. '<th scope="row">コミット</th>'
				. '<th scope="row">コメント</th>'
				. '<th scope="row">登録ユーザ</th>'
				. '</tr>'
				. '</thead>'
				. '<tbody>';

		// データリスト
		foreach ((array)$data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . htmlspecialchars($array[tsBackup::BACKUP_ENTITY_ID_SEQ]) . '"/></td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsBackup::BACKUP_ENTITY_DATETIME_DISP]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsBackup::BACKUP_ENTITY_PUBLISH_TYPE]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsBackup::BACKUP_ENTITY_RESERVE_DISP]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsBackup::BACKUP_ENTITY_BRANCH]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsBackup::BACKUP_ENTITY_COMMIT_HASH]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsBackup::BACKUP_ENTITY_COMMENT]) . '</td>'
				. '<td class="p-center">' . htmlspecialchars($array[tsBackup::BACKUP_ENTITY_INSERT_USER_ID]) . '</td>'
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

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ disp_backup_screen end');

		return $ret;
	}
}
