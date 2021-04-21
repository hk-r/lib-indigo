<?php

namespace indigo;

/**
 * コミットハッシュ値ajax取得クラス。
 *
 * 入力ダイアログのブランチに紐づくコミットハッシュ値をGitを介して取得するクラス。
 * 
 * 注意: これは古い機能です。
 * `indigo\main::ajax_run()` に置き換えられました。
 * 将来削除される予定です。
 */
class ajax
{
	private $main;

	/**
	 * コンストラクタ
	 *
	 * @param array $options パラメタ情報
	 */
	public function __construct($options) {
		$this->main = new main($options);
	}

	/**
	 * Ajax API 処理を実行する
	 *
	 * 注意: これは古い機能です。
	 * `indigo\main::ajax_run()` に置き換えられました。
	 * 将来削除される予定です。
	 */
	public function ajax_run(){
		return $this->main->ajax_run();
	}
}
