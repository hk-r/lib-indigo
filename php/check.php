<?php

namespace indigo;

use indigo\db\tsReserve as tsReserve;

class check
{

	private $main;

	/**
	 * Constructor
	 *
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
	}

	/**
	 * ブランチの必須チェック
	 *	 
	 * @param $datetime       = 公開予約日時の日付
	 *	 
	 * @return 
	 *  未来日である：true
	 *  未来日でない：false
	 */
	public function is_null_branch($branch_select_value) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ is_null_branch start');

		$ret = true;
		if (!$branch_select_value) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ is_null_branch end');

		return $ret;
	}

	/**
	 * コミットの必須チェック
	 *	 
	 * @param $datetime       = 公開予約日時の日付
	 *	 
	 * @return 
	 *  未来日である：true
	 *  未来日でない：false
	 */
	public function is_null_commit_hash($commit_hash) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ is_null_commit_hash start');

		$ret = true;
		if (!$commit_hash) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ is_null_commit_hash end');

		return $ret;
	}

	/**
	 * 公開予約日付の必須チェック
	 *	 
	 * @param $datetime       = 公開予約日時の日付
	 *	 
	 * @return 
	 *  未来日である：true
	 *  未来日でない：false
	 */
	public function is_null_reserve_date($reserve_date) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ is_null_reserve_date start');

		$ret = true;
		if (!$reserve_date) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ is_null_reserve_date end');

		return $ret;
	}

	/**
	 * 公開予約時刻の必須チェック
	 *	 
	 * @param $datetime       = 公開予約日時の日付
	 *	 
	 * @return 
	 *  未来日である：true
	 *  未来日でない：false
	 */
	public function is_null_reserve_time($reserve_time) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ is_null_reserve_time start');

		$ret = true;
		if (!$reserve_time) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ is_null_reserve_time end');

		return $ret;
	}

	/**
	 * 公開予約の最大件数チェック
	 *	 
	 * @param $data_list       = データリスト
	 *	 
	 * @return 
	 *  チェックOK：true
	 *  チェックNG：false
	 */
	public function check_reserve_max_record($data_list) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ check_reserve_max_record start');

		$ret = true;

		// TODO:定数化
		$max = 10;

		if ($max <= count($data_list)) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ check_reserve_max_record end');

		return $ret;
	}

	/**
	 * 日付の妥当性チェック
	 *	 
	 * @param $reserve_date  = 公開予約日時
	 *	 
	 * @return 
	 *  チェックOK：true
	 *  チェックNG：false
	 */
	public function check_date($reserve_date) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ check_date start');

		$ret = true;

		// 日付の妥当性チェック
		list($Y, $m, $d) = explode('-', $reserve_date);

		if (!checkdate(intval($m), intval($d), intval($Y))) {
			$ret = false;
		}	

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ check_date end');

		return $ret;
	}

	/**
	 * 未来日付チェック
	 *	 
	 * @param $datetime       = 公開予約日時の日付
	 *	 
	 * @return 
	 *  未来日である：true
	 *  未来日でない：false
	 */
	public function check_future_date($datetime) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ check_future_date start');

		$ret = true;

		// GMTの現在日時
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		if (strtotime($now) > strtotime($datetime)) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ check_future_date end');

		return $ret;
	}


	/**
	 * 予約データの中に、同名ブランチが存在していないか重複チェック
	 *	 
	 * @param $data_list       = データリスト
	 * @param $selected_branch = 選択されたブランチ
	 * @param $selected_id     = 変更ID
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	public function check_exist_branch($data_list, $selected_branch, $selected_id) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ check_exist_branch start');

		$ret = true;

		foreach ((array)$data_list as $array) {
			
			if (($array[tsReserve::RESERVE_ENTITY_ID_SEQ] != $selected_id) && ($array[tsReserve::RESERVE_ENTITY_BRANCH] == $selected_branch)) {
				$ret = false;
				break;
			}
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ check_exist_branch end');

		return $ret;
	}

	/**
	 * 予約データの中に、同じ公開予約日時が存在していないか重複チェック
	 *	 
	 * @param $data_list     = データリスト
	 * @param $input_reserve = 入力された日時
	 * @param $selected_id   = 変更ID
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	public function check_exist_reserve($data_list, $input_reserve, $selected_id) {

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ check_exist_reserve start');

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '□ 入力日時：' . $input_reserve);

		$ret = true;

		foreach ((array)$data_list as $array) {

			$this->main->common()->put_process_log(__METHOD__, __LINE__, '□ 比較日時：' . $array[tsReserve::RESERVE_ENTITY_RESERVE_GMT]);

			if (($array[tsReserve::RESERVE_ENTITY_ID_SEQ] != $selected_id) &&
				($array[tsReserve::RESERVE_ENTITY_RESERVE_GMT] == $input_reserve)) {
				$ret = false;
				break;
			}
		}		

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ check_exist_reserve end');

		return $ret;
	}
}