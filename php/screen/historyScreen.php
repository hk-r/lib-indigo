<?php

namespace indigo;

class historyScreen
{
	public $options;

	/**
	 * PDOインスタンス
	 */
	private $dbh;
	

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {

		$this->options = json_decode(json_encode($options));

	}

	/**
	 * 履歴表示のコンテンツ作成
	 *	 
	 * @return 履歴表示の出力内容
	 */
	public function disp_history_screen() {
		
		$this->common->debug_echo('■ create_history_contents start');

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
				. '<td class="p-center"><input type="radio" name="target" value="' . $array[tsOutput::OUTPUT_ENTITY_ID_SEQ] . '"/></td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_STATUS] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_TYPE] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_RESERVE_DISPLAY] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_START_DISPLAY] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_END_DISPLAY] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_COMMIT] . '</td>'
				. '<td class="p-center">' . $array[tsOutput::OUTPUT_ENTITY_BRANCH] . '</td>'
				. '<td>' . $array[tsOutput::OUTPUT_ENTITY_COMMENT] . '</td>'
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
		
		$this->common->debug_echo('■ create_history_contents end');

		return $ret;
	}
}
