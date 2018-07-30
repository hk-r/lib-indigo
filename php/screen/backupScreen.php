<?php

namespace indigo;

class backupScreen
{
	private $main;

	/**
	 * オブジェクト
	 * @access private
	 */
	private $check, $tsBackup, $tsOutput, $publish;

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
		$this->tsOutput = new tsOutput($this);
		$this->publish = new publish($this->main);
	}


	/**
	 * バックアップ一覧表示のコンテンツ作成
	 *	 
	 * @return 初期表示の出力内容
	 */
	public function disp_backup_screen() {
		
		$this->main->common()->debug_echo('■ disp_backup_screen start');

		$ret = "";

		// バックアップ一覧を取得
		$data_list = $this->tsBackup->get_ts_backup_list($this->main->dbh, null);

		$ret .= '<div style="overflow:hidden">'
			. '<form id="form_table" method="post">'
			. '<input type="hidden" name="selected_id" value="' . $this->main->options->_POST->selected_id . '"/>'
			. '<div class="button_contents" style="float:right;">'
			. '<ul>'
			. '<li><input type="submit" id="restore_btn" name="restore" class="px2-btn px2-btn--primary" value="復元"/></li>'
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
				. '<td class="p-center"><input type="radio" name="target" value="' . $array[tsBackup::BACKUP_ENTITY_ID_SEQ] . '"/></td>'
				. '<td class="p-center">' . $array[tsBackup::BACKUP_ENTITY_DATETIME_DISP] . '</td>'
				. '<td class="p-center">' . $array[tsBackup::BACKUP_ENTITY_PUBLISH_TYPE] . '</td>'
				. '<td class="p-center">' . $array[tsBackup::BACKUP_ENTITY_RESERVE_DISP] . '</td>'
				. '<td class="p-center">' . $array[tsBackup::BACKUP_ENTITY_BRANCH] . '</td>'
				. '<td class="p-center">' . $array[tsBackup::BACKUP_ENTITY_COMMIT_HASH] . '</td>'
				. '<td class="p-center">' . $array[tsBackup::BACKUP_ENTITY_COMMENT] . '</td>'
				. '<td class="p-center">' . $array[tsBackup::BACKUP_ENTITY_INSERT_USER_ID] . '</td>'
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

		$this->main->common()->debug_echo('■ disp_backup_screen end');

		return $ret;
	}

	/**
	 * 復元ボタン押下
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_restore_publish() {
		
		$this->main->common()->debug_echo('■ do_restore_publish start');

		$selected_id =  $this->main->options->_POST->selected_id;

		// エラーがないので即時公開処理へ進む
		$result = $this->publish->exec_publish(define::PUBLISH_TYPE_MANUAL_RESTORE, $selected_id);

		$this->main->common()->debug_echo('■ do_restore_publish end');

		return json_encode($result);
	}
}
