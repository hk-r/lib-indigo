<?php

namespace indigo;

class backupScreen
{
	private $main;

	private $check;
	private $tsBackup;
	private $common;

	/**
	 * PDOインスタンス
	 */
	private $dbh;

	/**
	 * 入力画面のエラーメッセージ
	 */
	private $input_error_message = '';


	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->check = new check($this);
		$this->tsBackup = new tsBackup($this);
		$this->common = new common($this);

	}


	/**
	 * バックアップ一覧表示のコンテンツ作成
	 *	 
	 * @return 初期表示の出力内容
	 */
	public function disp_backup_screen() {
		
		$this->common->debug_echo('■ disp_backup_screen start');

		$ret = "";

		// バックアップ一覧を取得
		$data_list = $this->tsBackup->get_ts_backup_list($dbh, null);

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
				. '<th scope="row">バックアップ日時</th>'
				. '<th scope="row">公開種別</th>'
				. '</tr>'
				. '</thead>'
				. '<tbody>';

		// データリスト
		foreach ((array)$data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . $array[tsBackup::BACKUP_ENTITY_ID_SEQ] . '"/></td>'
				. '<td class="p-center">' . $array[tsBackup::BACKUP_ENTITY_DATETIME_DISPLAY] . '</td>'
				. '<td class="p-center">' . $array[tsBackup::BACKUP_ENTITY_PUBLISH_TYPE] . '</td>'
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

		$this->common->debug_echo('■ disp_backup_screen end');

		return $ret;
	}
}
