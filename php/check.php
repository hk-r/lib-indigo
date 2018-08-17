<?php

namespace indigo;

use indigo\db\tsReserve as tsReserve;

class check
{

	private $main;

	/**
	 * Constructor
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
	}

	/**
	 * ブランチの必須チェック
	 *	 
	 * @param string $branch_select_value ブランチ名
	 *	 
	 * @return bool $ret チェックOKの場合は `true`、NGの場合は `false` を返します。
	 */
	public function is_null_branch($branch_select_value) {

		$ret = true;
		if (!$branch_select_value) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '>>check is_null_branch:' . $ret);

		return $ret;
	}

	/**
	 * コミットの必須チェック
	 *	 
	 * @param string $commit_hash コミットハッシュ値
	 *	 
	 * @return bool $ret チェックOKの場合は `true`、NGの場合は `false` を返します。
	 */
	public function is_null_commit_hash($commit_hash) {

		$ret = true;
		if (!$commit_hash) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '>>check is_null_commit_hash:' . $ret);

		return $ret;
	}

	/**
	 * 公開予定日付の必須チェック
	 *	 
	 * @param string $reserve_date 公開予定日時の日付
	 *	 
	 * @return bool $ret チェックOKの場合は `true`、NGの場合は `false` を返します。
	 */
	public function is_null_reserve_date($reserve_date) {

		$ret = true;
		if (!$reserve_date) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '>>check is_null_reserve_date:' . $ret);

		return $ret;
	}

	/**
	 * 公開予定時刻の必須チェック
	 *	 
	 * @param strin $reserve_time 公開予定日時の時刻
	 *	 
	 * @return bool $ret チェックOKの場合は `true`、NGの場合は `false` を返します。
	 */
	public function is_null_reserve_time($reserve_time) {

		$ret = true;
		if (!$reserve_time) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '>>check is_null_reserve_time:' . $ret);

		return $ret;
	}

	/**
	 * 公開予定の最大件数チェック
	 *	 
	 * @param array[] $data_list 公開予定リスト
	 * @param string  $max       予定最大件数
	 *	 
	 * @return bool $ret チェックOKの場合は `true`、NGの場合は `false` を返します。
	 */
	public function check_reserve_max_record($data_list, $max) {

		$ret = true;

		if (isset($max) && $max <= count($data_list)) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '>>check check_reserve_max_record:' . $ret);

		return $ret;
	}

	/**
	 * 日付の妥当性チェック
	 *	 
	 * @param string $reserve_date 公開予定日時の日付
	 *	 
	 * @return bool $ret チェックOKの場合は `true`、NGの場合は `false` を返します。
	 */
	public function check_date($reserve_date) {

		$ret = true;

		// 日付の妥当性チェック
		list($Y, $m, $d) = explode('-', $reserve_date);

		if (!checkdate(intval($m), intval($d), intval($Y))) {
			$ret = false;
		}	

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '>>check check_date:' . $ret);

		return $ret;
	}

	/**
	 * 未来日付チェック
	 *	 
	 * @param string $datetime 公開予定日時
	 *	 
	 * @return bool $ret チェックOKの場合は `true`、NGの場合は `false` を返します。
	 */
	public function check_future_date($datetime) {

		$ret = true;

		// GMTの現在日時
		$now = $this->main->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT);

		if (strtotime($now) > strtotime($datetime)) {
			$ret = false;
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '>>check check_future_date:' . $ret);

		return $ret;
	}


	/**
	 * 予定データの中に、同名ブランチが存在していないか重複チェック
	 *	 
	 * @param array[] $data_list        公開予定リスト
	 * @param string  $selected_branch  選択されたブランチ名
	 * @param int     $selected_id      チェックデータ自身の公開予定ID
	 *	 
	 * @return bool $ret チェックOKの場合は `true`、NGの場合は `false` を返します。
	 */
	public function check_exist_branch($data_list, $selected_branch, $selected_id) {

		$ret = true;

		foreach ((array)$data_list as $array) {
			
			// 自身以外に重複ブランチが存在する場合はエラーとする
			if (($array[tsReserve::RESERVE_ENTITY_ID_SEQ] != $selected_id) && ($array[tsReserve::RESERVE_ENTITY_BRANCH] == $selected_branch)) {
				$ret = false;
				break;
			}
		}

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '>>check check_exist_branch:' . $ret);

		return $ret;
	}

	/**
	 * 予定データの中に、同じ公開予定日時が存在していないか重複チェック
	 *	 
	 * @param array[] $data_list        公開予定リスト
	 * @param string  $input_reserve    入力された公開予定日時
	 * @param int     $selected_id      チェックデータ自身の公開予定ID
	 *	 
	 * @return bool $ret チェックOKの場合は `true`、NGの場合は `false` を返します。
	 */
	public function check_exist_reserve($data_list, $input_reserve, $selected_id) {

		$ret = true;

		foreach ((array)$data_list as $array) {

			// 自身以外に同日時が存在する場合はエラーとする
			if (($array[tsReserve::RESERVE_ENTITY_ID_SEQ] != $selected_id) &&
				($array[tsReserve::RESERVE_ENTITY_RESERVE_GMT] == $input_reserve)) {
				$ret = false;
				break;
			}
		}		

		$this->main->common()->put_process_log(__METHOD__, __LINE__, '>>check check_exist_reserve:' . $ret);

		return $ret;
	}
}