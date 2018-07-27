<?php

namespace indigo;

class initScreen
{
	/** mainオブジェクト */
	public $main;
	
	/**
	 * オブジェクト
	 * @access private
	 */
	private $tsReserve, $tsOutput, $tsBackup, $check, $publish;

	/**
	 * 入力画面のエラーメッセージ
	 */
	private $input_error_message = '';

	/**
	 * 画像パス定義
	 */
	// 右矢印
	const IMG_ARROW_RIGHT = '/images/arrow_right.png';
	// エラーアイコン
	const IMG_ERROR_ICON = '/images/error_icon.png';


	/**
	 * 入力モード
	 */
	// 追加モード
	const INPUT_MODE_ADD = 1;
	// 追加確認モード
	const INPUT_MODE_ADD_CHECK = 2;
	// 追加戻り表示モード
	const INPUT_MODE_ADD_BACK = 3;
	// 更新モード
	const INPUT_MODE_UPDATE = 4;
	// 更新確認モード
	const INPUT_MODE_UPDATE_CHECK = 5;
	// 更新戻り表示モード
	const INPUT_MODE_UPDATE_BACK = 6;
	// 即時公開モード
	const INPUT_MODE_IMMEDIATE = 7;
	// 即時公開確認モード
	const INPUT_MODE_IMMEDIATE_CHECK = 8;
	// 即時公開戻り表示モード
	const INPUT_MODE_IMMEDIATE_BACK = 9;


	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->tsReserve = new tsReserve($this);
		$this->tsOutput = new tsOutput($this);
		$this->tsBackup = new tsBackup($this);
		
		$this->check = new check($this);
		$this->publish = new publish($this->main);

	}

	/**
	 * 初期表示画面のHTML作成
	 *	 
	 * @return 初期表示の出力内容
	 */
	public function do_disp_init_screen() {
		
		$this->main->common()->debug_echo('■ do_disp_init_screen start');

		$ret = "";

		// 公開予約一覧を取得
		$data_list = $this->tsReserve->get_ts_reserve_list($this->main->get_dbh());

		// // お知らせリストの取得
		// $alert_list = $this->get_csv_alert_list();

		// if (count($alert_list) != 0) {
		// 	// お知らせリストの表示
		// 	$ret .= '<form name="formA" method="post">'
		// 		. '<div class="alert_box">'
		// 		. '<p class="alert_title">お知らせ</p>';
		// 	// データリスト
		// 	foreach ($alert_list as $data) {
				
		// 		$ret .= '<p class="alert_content" style="vertical-align: middle;">'
		// 			. '<span style="padding-right: 5px;"><img src="'. $this->img_error_icon . '"/></span>'
		// 			. '<a onClick="document.formA.submit();return false;" >'
		// 			. $data[TS_RESERVE_COLUMN_RESERVE] . '　' . $data['content']
		// 			. '</a></p>';
		// 	}

		// 	$ret .=  '<input type="hidden" name="history" value="履歴">'
		// 		. '</div>'
		// 		. '</form>';
		// }

		$ret .= '<div class="button_contents_box">'
			. '<form id="form_table" method="post">'
			. '<div class="button_contents" style="float:left">'
			. '<ul>'
			. '<li><input type="submit" id="add_btn" name="add" class="px2-btn" value="新規"/></li>'
			. '</ul>'
			. '</div>'
			. '<div class="button_contents" style="float:right;">'
			. '<ul>'
			. '<li><input type="submit" id="update_btn" name="update" class="px2-btn" value="変更"/></li>'
			. '<li><input type="submit" id="delete_btn" name="delete" class="px2-btn px2-btn--danger" value="削除"/></li>'
			. '<li><input type="submit" id="immediate_btn" name="immediate" class="px2-btn px2-btn--primary" value="即時公開"/></li>'
			. '<li><input type="submit" id="history_btn" name="history" class="px2-btn" value="履歴"/></li>'
			. '<li><input type="submit" id="backup_btn" name="backup" class="px2-btn" value="バックアップ一覧"/></li>'
			. '</ul>'
			// . '</div>'
			. '</div>';

		// テーブルヘッダー
		$ret .= '<div>'
		    . '<table name="list_tbl" class="table table-striped">'
			. '<thead>'
			. '<tr>'
			. '<th scope="row"></th>'
			. '<th scope="row">公開予約日時</th>'
			. '<th scope="row">コミット</th>'
			. '<th scope="row">ブランチ</th>'
			. '<th scope="row">コメント</th>'
			. '<th scope="row">登録ユーザ</th>'
			. '<th scope="row">登録日時</th>'
			. '<th scope="row">更新ユーザ</th>'
			. '<th scope="row">更新日時</th>'
			. '</tr>'
			. '</thead>'
			. '<tbody>';

		// テーブルデータリスト
		foreach ((array)$data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . $array[tsReserve::RESERVE_ENTITY_ID_SEQ] . '"/></td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_RESERVE_DISP] . '</td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_COMMIT_HASH] . '</td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_BRANCH] . '</td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_COMMENT] . '</td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_INSERT_USER_ID] . '</td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_INSERT_DATETIME] . '</td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_UPDATE_USER_ID] . '</td>'
				. '<td class="p-center">' . $array[tsReserve::RESERVE_ENTITY_UPDATE_DATETIME] . '</td>'
				. '</tr>';
		}

		$ret .= '</tbody></table>'
			. '</div>'
			. '</form>'
			. '</div>';

		$this->main->common()->debug_echo('■ do_disp_init_screen end');

		return $ret;
	}

	/**
	 * 新規ダイアログの表示
	 *	 
	 * @return 新規ダイアログの出力内容
	 */
	public function do_disp_add_dialog() {
		
		$this->main->common()->debug_echo('■ disp_add_dialog start');

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		// ダイアログHTMLの作成
		$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_ADD);

		$this->main->common()->debug_echo('■ disp_add_dialog end');

		return json_encode($result);
	}


	/**
	 * 新規確認処理
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_check_add() {
		
		$this->main->common()->debug_echo('■ do_check_add start');

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_ADD);

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログのまま
			$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_ADD_BACK);

		} else {
			// エラーがないので確認ダイアログへ遷移
			$result['dialog_disp'] = $this->create_check_add_dialog_html();
		}

		$this->main->common()->debug_echo('■ do_check_add end');

		return json_encode($result);
	}

	/**
	 * 新規ダイアログの確定処理
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_confirm_add() {
		
		$this->main->common()->debug_echo('■ do_confirm_add start');

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_ADD_CHECK);

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログへ戻る
			$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_ADD_BACK);

		} else {
			// エラーがないので確定処理へ進む
			$result = $this->confirm_add();
		}

		$this->main->common()->debug_echo('■ do_confirm_add end');

		return json_encode($result);
	}

	/**
	 * 新規ダイアログの戻り表示
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_back_add_dialog() {
		
		$this->main->common()->debug_echo('■ disp_back_add_dialog start');

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		// 入力ダイアログへ戻る
		$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_ADD_BACK);

		$this->main->common()->debug_echo('■ disp_back_add_dialog end');

		return json_encode($result);
	}

	/**
	 * 変更ダイアログの表示
	 *	 
	 *
	 * @return 変更ダイアログの出力内容
	 */
	public function do_disp_update_dialog() {
		
		$this->main->common()->debug_echo('■ disp_update_dialog start');

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		// 入力ダイアログHTMLの作成
		$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_UPDATE);

		$this->main->common()->debug_echo('■ disp_update_dialog end');

		return json_encode($result);
	}

	/**
	 * 変更確認処理
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_check_update() {
		
		$this->main->common()->debug_echo('■ do_check_update start');

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_UPDATE);

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログのまま
			$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_UPDATE_BACK);
		} else {
			// エラーがないので確認ダイアログへ遷移
			$result['dialog_disp'] = $this->create_check_update_dialog_html();
		}

		$this->main->common()->debug_echo('■ do_check_update end');

		return json_encode($result);
	}

	/**
	 * 変更ダイアログの確定処理
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_confirm_update() {
		
		$this->main->common()->debug_echo('■ do_confirm_update start');

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_UPDATE_CHECK);

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログへ戻る
			$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_UPDATE_BACK);

		} else {
			// エラーがないので確定処理へ進む
			$result = $this->confirm_update();
		}

		$this->main->common()->debug_echo('■ do_confirm_update end');
		
		return json_encode($result);
	}

	/**
	 * 変更ダイアログの戻り表示
	 *	 
	 * @param $error_message  = エラーメッセージ出力内容
	 *
	 * @return 変更ダイアログの出力内容
	 */
	public function do_back_update_dialog() {
		
		$this->main->common()->debug_echo('■ do_back_update_dialog start');

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		// 入力ダイアログHTMLの作成
		$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_UPDATE_BACK);

		$this->main->common()->debug_echo('■ do_back_update_dialog end');

		return json_encode($result);
	}

	/**
	 * 即時公開ダイアログの表示
	 *	 
	 * @return 即時公開ダイアログの出力内容
	 */
	public function do_disp_immediate_dialog() {
		
		$this->main->common()->debug_echo('■ do_disp_immediate_dialog start');

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		// ダイアログHTMLの作成
		$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_IMMEDIATE);

		$this->main->common()->debug_echo('■ do_disp_immediate_dialog end');

		return json_encode($result);
	}

	/**
	 * 即時公開確認処理
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_check_immediate() {
		
		$this->main->common()->debug_echo('■ do_check_immediate start');

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_IMMEDIATE);

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログのまま
			$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_IMMEDIATE_BACK);
		} else {
			// エラーがないので確認ダイアログへ遷移
			$result['dialog_disp'] = $this->create_check_immediate_dialog_html();
		}

		$this->main->common()->debug_echo('■ do_check_immediate end');

		return json_encode($result);
	}

	/**
	 * 即時公開ボタン押下
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_immediate_publish() {
		
		$this->main->common()->debug_echo('■ do_immediate_publish start');

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_IMMEDIATE_CHECK);

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログへ戻る
			$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_IMMEDIATE_BACK);
		} else {
			// エラーがないので即時公開処理へ進む
			$result = $this->publish->exec_publish(define::PUBLISH_TYPE_IMMEDIATE);
		}

		$this->main->common()->debug_echo('■ do_immediate_publish end');

		return json_encode($result);
	}

	/**
	 * 即時ダイアログの戻り表示
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	public function do_back_immediate_dialog() {
		
		$this->main->common()->debug_echo('■ do_back_immediate_dialog start');

		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		// 入力ダイアログHTMLの作成
		$result['dialog_disp'] = $this->create_input_dialog_html(self::INPUT_MODE_IMMEDIATE_BACK);

		$this->main->common()->debug_echo('■ do_back_immediate_dialog end');

		return json_encode($result);
	}

	/**
	 * 新規ダイアログの確定処理
	 *	 
	 * @return 確認ダイアログ出力内容
	 */
	private function confirm_add() {
		
		$this->main->common()->debug_echo('■ confirm_add start');

		$output = "";
		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		try {

			//============================================================
			// 指定ブランチのGit情報を「waiting」ディレクトリへコピー
			//============================================================

	 		$this->main->common()->debug_echo('　□ -----Gitのファイルコピー処理-----');
			
			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($this->main->options->workdir_relativepath . define::PATH_WAITING));

			// 公開予約ディレクトリ名の取得
			$dirname = $this->main->common()->format_gmt_datetime($this->main->options->_POST->gmt_reserve_datetime, define::DATETIME_FORMAT_SAVE);

			if (!$dirname) {
				// エラー処理
				throw new \Exception('Dirname create failed.');
			} else {
				$dirname .= define::DIR_NAME_RESERVE;
			}

			// コピー処理
			$this->main->gitMgr()->git_file_copy($this->main->options, $waiting_real_path, $dirname);

	 		$this->main->common()->debug_echo('　□ -----公開処理結果テーブルの登録処理-----');
			
			//============================================================
			// 入力情報を公開予約テーブルへ登録
			//============================================================
			$this->tsReserve->insert_ts_reserve($this->main->get_dbh(), $this->main->options);
			
		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = 'Add confirm faild. ' . $e->getMessage();

			return $result;
		}

		$result['status'] = true;

		$this->main->common()->debug_echo('■ confirm_add end');

		return $result;
	}


	/**
	 * 変更ダイアログの確定処理
	 *	 
	 * @return 確認ダイアログ出力内容
	 */
	private function confirm_update() {
		
		$this->main->common()->debug_echo('■ confirm_update start');
	
		$output = "";
		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		try {

			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($this->main->options->workdir_relativepath . define::PATH_WAITING));

			//============================================================
			// 「waiting」ディレクトリの変更前の公開ソースディレクトリを削除
			//============================================================
			// 変更前の公開予約ディレクトリ名の取得
			$before_dirname = $this->main->common()->format_gmt_datetime($this->main->options->_POST->before_gmt_reserve_datetime, define::DATETIME_FORMAT_SAVE);
			
			if (!$before_dirname) {
				// エラー処理
				throw new \Exception('Dirname create failed.');
			} else {
				$before_dirname .= define::DIR_NAME_RESERVE;
			}

			$this->main->common()->debug_echo('　□ 変更前の公開予約ディレクトリ：');
			$this->main->common()->debug_echo($before_dirname);

			// コピー処理
			$this->main->gitMgr()->file_delete($waiting_real_path, $before_dirname);


			//============================================================
			// 変更後ブランチのGit情報を「waiting」ディレクトリへコピー
			//============================================================
			// 公開予約ディレクトリ名の取得
			$dirname = $this->main->common()->format_gmt_datetime($this->main->options->_POST->gmt_reserve_datetime, define::DATETIME_FORMAT_SAVE);

			if (!$dirname) {
				// エラー処理
				throw new \Exception('Dirname create failed.');
			} else {
				$dirname .= define::DIR_NAME_RESERVE;
			}

			$this->main->common()->debug_echo('　□ 変更後の公開予約ディレクトリ：');
			$this->main->common()->debug_echo($dirname);

			// コピー処理
			$this->main->gitMgr()->git_file_copy($this->main->options, $waiting_real_path, $dirname);

	 		$this->main->common()->debug_echo('　□ -----公開処理結果テーブルの更新処理-----');
			
			//============================================================
			// 入力情報を公開予約テーブルへ更新
			//============================================================
			$selected_id =  $this->main->options->_POST->selected_id;

			$this->tsReserve->update_ts_reserve($this->main->get_dbh(), $this->main->options, $selected_id);
			
		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = 'Update confirm faild. ' . $e->getMessage();

			return $result;
		}

		$result['status'] = true;

		$this->main->common()->debug_echo('■ confirm_update end');

		return $result;
	}

	/**
	 * 削除処理
	 *	 
	 * @return 確認ダイアログ出力内容
	 */
	public function do_delete() {
		
		$this->main->common()->debug_echo('■ do_delete start');
	
		$output = "";
		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		try {

			// 選択ID
			$selected_id =  $this->main->options->_POST->selected_id;

			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($this->main->options->workdir_relativepath . define::PATH_WAITING));


			try {

				/* トランザクションを開始する。オートコミットがオフになる */
				$this->main->get_dbh()->beginTransaction();

				//============================================================
				// 公開予約情報の論理削除
				//============================================================

				$this->main->common()->debug_echo('　□ -----公開予約情報の論理削除処理-----');

				$this->tsReserve->delete_reserve_table($this->main->get_dbh(), $this->main->options, $selected_id);

				//============================================================
				// 「waiting」ディレクトリの変更前の公開ソースディレクトリを削除
				//============================================================
				// 公開予約ディレクトリ名の取得
				$selected_ret = $this->tsReserve->get_selected_ts_reserve($this->main->get_dbh(), $selected_id);
				$dirname = $this->main->common()->format_gmt_datetime($selected_ret[tsReserve::RESERVE_ENTITY_RESERVE_GMT], define::DATETIME_FORMAT_SAVE);
				
				if (!$dirname) {
					// エラー処理
					throw new \Exception('Dirname create failed.');
				} else {
					$dirname .= define::DIR_NAME_RESERVE;
				}
				
				// コピー処理
				$this->main->gitMgr()->file_delete($waiting_real_path, $dirname);


				/* 変更をコミットする */
				$this->main->get_dbh()->commit();
				/* データベース接続はオートコミットモードに戻る */

		    } catch (\Exception $e) {
		    
		      /* 変更をロールバックする */
		      $this->main->get_dbh()->rollBack();
		 
		      throw $e;
		    }

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = 'Delete faild. ' . $e->getMessage();

			return json_encode($result);
		}

		$result['status'] = true;

		$this->main->common()->debug_echo('■ do_delete end');

		return json_encode($result);
	}

	/**
	 * 公開復元処理
	 */
	public function do_restore_publish_failure($output_id) {

		$this->main->common()->debug_echo('■ do_restore_publish_failure start');

		$output = "";
		$result = array('status' => true,
						'message' => '',
						'dialog_disp' => '');

		$result = $this->publish->exec_restore_publish($output_id);

		$this->main->common()->debug_echo('■ do_restore_publish_failure end');

		return json_encode($result);
	}

	/**
	 * 新規・変更・即時公開の入力ダイアログHTMLの作成
	 *	 
	 * @param $input_mode = 入力モード
	 *
	 * @return ログ出力内容
	 */
	private function create_input_dialog_html($input_mode) {
		
		$this->main->common()->debug_echo('■ create_input_dialog_html start');


		$ret = '<div class="dialog" id="modal_dialog">'
			  . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			  . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			  . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			  . '<div class="dialog_box">';

		 if ($this->input_error_message) {
		 // エラーメッセージの出力
			$ret .= '<div class="alert_box">'
				. $this->input_error_message
				. '</div>';
		 }

		// 入力モードによってタイトル変更
		if ( ($input_mode == self::INPUT_MODE_ADD) || ($input_mode == self::INPUT_MODE_ADD_BACK)) {
			$ret .= '<h4>新規</h4>';

		} elseif ( ($input_mode == self::INPUT_MODE_UPDATE) || ($input_mode == self::INPUT_MODE_UPDATE_BACK) ) {
		  	$ret .= '<h4>変更</h4>';

		} elseif ( ($input_mode == self::INPUT_MODE_IMMEDIATE) || ($input_mode == self::INPUT_MODE_IMMEDIATE_BACK) ) {
		  	$ret .= '<h4>即時公開</h4>';

		} else {
			throw new \Exception("Input mode is not found.");
		}

		$form = array('branch_select_value' => '',
						'reserve_date' => '',
						'reserve_time' => '',
						'commit_hash' => '',
						'comment' => '',
						'selected_id' => ''
					);

        // 引数の取得
		if (($input_mode == self::INPUT_MODE_ADD_BACK) || 
			($input_mode == self::INPUT_MODE_UPDATE_BACK) ||
			($input_mode == self::INPUT_MODE_IMMEDIATE_BACK)) {

			$form = $this->get_form_value();

		} elseif ($input_mode == self::INPUT_MODE_UPDATE) {

			// 画面選択された公開予約情報を取得
			$form['selected_id'] = $this->main->options->_POST->selected_id;

			$selected_data = $this->tsReserve->get_selected_ts_reserve($this->main->get_dbh(), $form['selected_id']);

			if ($selected_data) {

				$form['branch_select_value'] = $selected_data[tsReserve::RESERVE_ENTITY_BRANCH];
				$form['reserve_date'] = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_DATE];
				$form['reserve_time'] = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_TIME];
				$form['commit_hash'] = $selected_data[tsReserve::RESERVE_ENTITY_COMMIT_HASH];
				$form['comment'] = $selected_data[tsReserve::RESERVE_ENTITY_COMMENT];
			}
		}

        // masterディレクトリの絶対パス
        // $workdir_relativepath = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($this->main->options->workdir_relativepath));

		// mainクラス呼び出しディレクトリの相対パス
        $param_relativepath = $this->main->options->param_relativepath;
        
		// indigo作業ディレクトリ
        $workdir_relativepath = $this->main->options->workdir_relativepath;

		$ret .= '<form method="post">';

		$ret .= '<input type="hidden" name="selected_id" value="' . $form['selected_id'] . '"/>';


		$ret .= '<input type="hidden" id="param_relativepath" value="' . $param_relativepath . '"/>';
		$ret .= '<input type="hidden" id="workdir_relativepath" value="' . $workdir_relativepath . '"/>';
		
		$ret .= '<table class="table table-striped">'
			  . '<tr>';

		// 「ブランチ」項目
		$ret .= '<td class="dialog_thead">ブランチ</td>'
			  . '<td><select id="branch_list" class="form-control" name="branch_select_value">';

				// ブランチリストを取得
				$get_branch_ret = json_decode($this->main->gitMgr()->get_branch_list($this->main->options));
				$branch_list = $get_branch_ret->branch_list;

				foreach ((array)$branch_list as $branch) {
					$ret .= '<option value="' . htmlspecialchars($branch) . '" ' . $this->compare_to_selected_value($form['branch_select_value'], $branch) . '>' . htmlspecialchars($branch) . '</option>';
				}

		$ret .= '</select></td>'
			  . '</tr>';
		
		// 「コミット」項目
		$ret .= '<tr>'
			  . '<td class="dialog_thead">コミット</td>'
			  . '<td id="result">' . $form['commit_hash'] . '</td>'
			  . '<input type="hidden" id="commit_hash" name="commit_hash" value="' . $form['commit_hash'] . '"/>'
			  . '</tr>';

		// 「公開予約日時」項目
		if ( ($input_mode == self::INPUT_MODE_IMMEDIATE) || ($input_mode == self::INPUT_MODE_IMMEDIATE_BACK) ) {

			$ret .= '<tr>'
				  . '<td class="dialog_thead">公開予約日時</td>'
				  . '<td scope="row"><span style="margin-right:10px;color:#B61111">即時</span></td>'
				  . '</tr>';
		
		} else {

			$ret .= '<tr>'
				  . '<td class="dialog_thead">公開予約日時</td>'
				  . '<td scope="row"><span style="margin-right:10px;"><input type="text" id="datepicker" name="reserve_date" value="'. $form['reserve_date'] . '" autocomplete="off" /></span>'
				  . '<input type="time" id="reserve_time" name="reserve_time" value="'. $form['reserve_time'] . '" /></td>'
				  . '</tr>';
		}

		// 「コメント」項目
		$ret .= '<tr>'
			  . '<td class="dialog_thead">コメント</td>'
			  . '<td><input type="text" id="comment" name="comment" size="50" value="' . htmlspecialchars($form['comment']) . '" /></td>'
			  . '</tr>'
			  . '</tbody></table>'

			  . '<div class="button_contents_box">'
			  . '<div class="button_contents">'
			  . '<ul>';
		
		// 「確認」ボタン（入力モードによってidとnameを変更）
		if ( ($input_mode == self::INPUT_MODE_ADD) || ($input_mode == self::INPUT_MODE_ADD_BACK)) {
			$ret .= '<li><input type="submit" id="add_check_btn" name="add_check" class="px2-btn px2-btn--primary" value="確認"/></li>';

		} elseif ( ($input_mode == self::INPUT_MODE_UPDATE) || ($input_mode == self::INPUT_MODE_UPDATE_BACK) ) {
		  	$ret .= '<li><input type="submit" id="update_check_btn" name="update_check" class="px2-btn px2-btn--primary" value="確認"/></li>';

		} elseif ( ($input_mode == self::INPUT_MODE_IMMEDIATE) ||  ($input_mode == self::INPUT_MODE_IMMEDIATE_BACK) ) {
		  	$ret .= '<li><input type="submit" id="immediate_check_btn" name="immediate_check" class="px2-btn px2-btn--danger" value="確認"/></li>';

		} else {
			throw new \Exception("Input mode is not found.");
		}

		// 「キャンセル」ボタン
		$ret .= '<li><input type="submit" id="close_btn" class="px2-btn" value="キャンセル"/></li>';
		
		$ret .= '</ul>'
			  . '</div>'
			  . '</div>'
			  . '</form>'
			  . '</div>'

			  . '</div>'
			  . '</div>'
			  . '</div></div>';
		
		$this->main->common()->debug_echo('■ create_input_dialog_html end');

		return $ret;
	}

	/**
	 * 新規確認ダイアログの表示
	 *
	 * @return 確認ダイアログ出力内容
	 */
	private function create_check_add_dialog_html() {
		
		$this->main->common()->debug_echo('■ create_check_add_dialog_html start');

		// フォームパラメタの設定
		$form = $this->get_form_value();

		// 画面入力された日時を結合し、GMTへ変換する
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($form['reserve_date'], $form['reserve_time']);

		$ret = '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>追加確認</h4>';

		$ret .= '<form method="post">'
			. '<table class="table table-striped">';

		// 「ブランチ」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $form['branch_select_value']
			. '<input type="hidden" name="branch_select_value" value="' . $form['branch_select_value'] . '"/>'
			. '</td>'
			. '</tr>';

		// 「コミット」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コミット' . '</td>'
			. '<td>' . $form['commit_hash'] . '</td>'
			. '<input type="hidden" name="commit_hash" value="' . $form['commit_hash'] . '"/>'
			. '</tr>';

		// 「公開予約日時」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . '公開予約日時' . '</td>'
			. '<td>' . $form['reserve_date'] . ' ' . $form['reserve_time']
			. '<input type="hidden" name="reserve_date" value="' . $form['reserve_date'] . '"/>'
			. '<input type="hidden" name="reserve_time" value="' . $form['reserve_time'] . '"/>'
			. '<input type="hidden" name="gmt_reserve_datetime" value="' . $gmt_reserve_datetime . '"/>'
			. '</td>'
			. '</tr>';

		// 「コメント」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . htmlspecialchars($form['comment']) . '</td>'
			. '<input type="hidden" name="comment" value="' . htmlspecialchars($form['comment']) . '"/>'
			. '</tr>'

			. '</tbody></table>'
			
			. '<div class="unit">'
			. '<div class="text-center">';

		$ret .= '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>';

		// 「確定」ボタン
		$ret .= '<li><input type="submit" id="confirm_btn" name="add_confirm" class="px2-btn px2-btn--primary" value="確定"/></li>';
		
		// 「キャンセル」ボタン
		$ret .= '<li><input type="submit" id="back_btn" name="add_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'

			. '</div>'
			 . '</div>'

			. '</form>'
			 . '</div>'
			 . '</div></div></div>';

		$this->main->common()->debug_echo('■ create_check_add_dialog_html end');

		return $ret;
	}


	/**
	 * 変更確認ダイアログの表示
	 *
	 * @return 確認ダイアログ出力内容
	 */
	private function create_check_update_dialog_html() {
		
		$this->main->common()->debug_echo('■ create_check_update_dialog_html start');

		// フォームパラメタの設定
		$form = $this->get_form_value();

		// 画面入力された日時を結合し、GMTへ変換する
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($form['reserve_date'], $form['reserve_time']);
	
		$before_branch_select_value = "";
		$before_reserve_date = "";
		$before_reserve_time = "";
		$before_commit_hash = "";
		$before_comment = "";
		$before_gmt_reserve_datetime = "";

		// 画面選択された変更前の公開予約情報を取得
		$selected_id =  $this->main->options->_POST->selected_id;
		$selected_data = $this->tsReserve->get_selected_ts_reserve($this->main->get_dbh(), $selected_id);

		if ($selected_data) {

			$before_branch_select_value = $selected_data[tsReserve::RESERVE_ENTITY_BRANCH];
			$before_reserve_date = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_DATE];
			$before_reserve_time = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_TIME];
			$before_commit_hash = $selected_data[tsReserve::RESERVE_ENTITY_COMMIT_HASH];
			$before_comment = $selected_data[tsReserve::RESERVE_ENTITY_COMMENT];
	
			// 画面入力された日時を結合し、GMTへ変換する
			$before_gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($before_reserve_date, $before_reserve_time);
		
		}

		$img_filename = $this->main->options->workdir_relativepath . self::IMG_ARROW_RIGHT;

		$ret = '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>変更確認</h4>'
			. '<form method="post">'
			. '<div class="colum_3">'
			. '<div class="left_box">';

		$ret .= '<table class="table table-striped">';
	
		// 「ブランチ」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $before_branch_select_value . '</td>'
			. '</tr>';
		
		// 「コミット」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コミット' . '</td>'
			. '<td>' . $before_commit_hash . '</td>'
			. '</tr>';
		
		// 「公開予約日時」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . '公開予約日時' . '</td>'
			. '<td>' . $before_reserve_date . ' ' . $before_reserve_time . '</td>'
			. '<input type="hidden" name="before_gmt_reserve_datetime" value="' . $before_gmt_reserve_datetime . '"/>'
			. '</tr>';
		
		// 「コメント」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . $before_comment . '</td>'
			. '</tr>'
			. '</tbody></table>'
			
		    . '</div>'

		    . '<div class="center_box">'
		    . '<img src="'. $img_filename .'"/>'
		    . '</div>'

            . '<div class="right_box">'
			. '<table class="table table-striped" style="width: 100%">'
		    . '<input type="hidden" name="selected_id" value="' . $form['selected_id'] . '"/>'

			// 「ブランチ」項目（変更後）
			. '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $form['branch_select_value'] . '</td>'
			. '<input type="hidden" name="branch_select_value" value="' . $form['branch_select_value'] . '"/>'
			. '</tr>'

			// 「コミット」項目（変更後）			
			. '<tr>'
			. '<td class="dialog_thead">' . 'コミット' . '</td>'
			. '<td>' . $form['commit_hash'] . '</td>'
			. '<input type="hidden" name="commit_hash" value="' . $form['commit_hash'] . '"/>'	
			. '</tr>'

			// 「公開日時」項目（変更後）
			. '<tr>'
			. '<td class="dialog_thead">' . '公開予約日時' . '</td>'
			. '<td>' . $form['reserve_date'] . ' ' . $form['reserve_time'] . '</td>'
			. '<input type="hidden" name="reserve_date" value="' . $form['reserve_date'] . '"/>'
			. '<input type="hidden" name="reserve_time" value="' . $form['reserve_time'] . '"/>'	 
			. '<input type="hidden" name="gmt_reserve_datetime" value="' . $gmt_reserve_datetime . '"/>'
			. '</tr>'

			// 「コメント」項目（変更後）
			. '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . $form['comment'] . '</td>'
			. '<input type="hidden" name="comment" value="' . $form['comment'] . '"/>'
			. '</tr>'

			. '</tbody></table>'
		    . '</div>'
		 	. '</div>'

			. '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>';

		$ret .= '<li><input type="submit" id="confirm_btn" name="update_confirm" class="px2-btn px2-btn--primary" value="確定"/></li>'
			. '<li><input type="submit" id="back_btn" name="update_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'
			. '</form>'
			. '</div>'

			. '</div>'
			. '</div>'
			. '</div></div>';

		$this->main->common()->debug_echo('■ create_check_update_dialog_html end');

		return $ret;
	}

	/**
	 * 即時公開確認ダイアログの表示
	 *
	 * @return 確認ダイアログ出力内容
	 */
	private function create_check_immediate_dialog_html() {
		
		$this->main->common()->debug_echo('■ create_check_immediate_dialog_html start');

		// フォームパラメタの設定
		$form = $this->get_form_value();

		$ret = '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>即時公開確認</h4>';

		$ret .= '<form method="post">'
			. '<table class="table table-striped">';

		// 「ブランチ」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $form['branch_select_value']
			. '<input type="hidden" name="branch_select_value" value="' . $form['branch_select_value'] . '"/>'
			. '</td>'
			. '</tr>';

		// 「コミット」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コミット' . '</td>'
			. '<td>' . $form['commit_hash'] . '</td>'
			. '<input type="hidden" name="commit_hash" value="' . $form['commit_hash'] . '"/>'
			. '</tr>';

		// 「公開予約日時」項目
		$ret .= '<tr>'
			  . '<td class="dialog_thead">公開予約日時</td>'
			  . '<td scope="row"><span style="margin-right:10px;color:#B61111">即時</span></td>'
			  . '</tr>';

		// 「コメント」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . htmlspecialchars($form['comment']) . '</td>'
			. '<input type="hidden" name="comment" value="' . htmlspecialchars($form['comment']) . '"/>'
			. '</tr>'

			. '</tbody></table>'
			
			. '<div class="unit">'
			. '<div class="text-center">';

		$ret .= '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>';

		// 「確定」ボタン
		$ret .= '<li><input type="submit" id="confirm_btn" name="immediate_confirm" class="px2-btn px2-btn--danger" value="確定（注意：本番環境への公開処理が開始されます）"/></li>';
		
		// 「キャンセル」ボタン
		$ret .= '<li><input type="submit" id="back_btn" name="immediate_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'

			. '</div>'
			 . '</div>'

			. '</form>'
			 . '</div>'
			 . '</div></div></div>';

		$this->main->common()->debug_echo('■ create_check_immediate_dialog_html end');

		return $ret;
	}


	/**
	 * 入力チェック処理
	 *	 
	 * @param $input_mode = 入力モード
	 *
	 * @return エラーメッセージHTML
	 */
	private function do_validation_check($input_mode) {
				
		$this->main->common()->debug_echo('■ do_validation_check start');

		$ret = "";

		$date_required_error = true;
		$branch_required_error = true;

		$form = $this->get_form_value();

		/**
 		* 公開予約一覧を取得
		*/ 
		$data_list = $this->tsReserve->get_ts_reserve_list($this->main->get_dbh());
	
		// 画面入力された日時を結合し、GMTへ変換する
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($form['reserve_date'], $form['reserve_time']);

		// 必須チェック
		if (!$this->check->is_null_branch($form['branch_select_value'])) {
			$ret .= '<p class="error_message">ブランチを選択してください。</p>';
			$branch_required_error = false;
		}
		if (!$this->check->is_null_commit_hash($form['commit_hash'])) {
			$ret .= '<p class="error_message">コミットが取得されておりません。</p>';
		}

		if ($input_mode != self::INPUT_MODE_IMMEDIATE &&
			$input_mode != self::INPUT_MODE_IMMEDIATE_CHECK) {

			if (!$this->check->is_null_reserve_date($form['reserve_date'])) {
				$ret .= '<p class="error_message">日付を選択してください。</p>';
				$date_required_error = false;
			}
			if (!$this->check->is_null_reserve_time($form['reserve_time'])) {
				$ret .= '<p class="error_message">時刻を選択してください。</p>';
				$date_required_error = false;
			}

			if ($date_required_error) {
				// 日付と時刻が共に入力されている場合にのみチェックする
				// 日付の妥当性チェック
				if (!$this->check->check_date($form['reserve_date'])) {
					$ret .= '<p class="error_message">「公開予約日時」の日付が有効ではありません。</p>';
				}

				// 未来の日付であるかチェック
				if (!$this->check->check_future_date($gmt_reserve_datetime)) {
					$ret .= '<p class="error_message">「公開予約日時」は未来日時を設定してください。</p>';
				}

				// 公開予約日時の重複チェック
				if (!$this->check->check_exist_reserve($data_list, $gmt_reserve_datetime, $form['selected_id'])) {
					$ret .= '<p class="error_message">入力された日時はすでに公開予約が作成されています。</p>';
				}
			}
			
			if ($branch_required_error) {
				// ブランチの重複チェック
				if (!$this->check->check_exist_branch($data_list, $form['branch_select_value'], $form['selected_id'])) {
					$ret .= '<p class="error_message">1つのブランチで複数の公開予約を作成することはできません。</p>';
				}
			}
		}

		if ($input_mode == self::INPUT_MODE_ADD &&
			$input_mode == self::INPUT_MODE_ADD_CHECK) {
			// 公開予約の最大件数チェック
			if (!$this->check->check_reserve_max_record($data_list)) {
				$ret .= '<p class="error_message">公開予約は最大' . $max . '件までの登録になります。</p>';
			}
		}

		$this->main->common()->debug_echo('■ do_validation_check end');

		return $ret;
	}

	/**
	 * プルダウンで選択状態とさせる値であるか比較する
	 *	 
	 * @param $selected = 選択状態とする値
	 * @param $value    = 比較対象の値
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	private function compare_to_selected_value($selected, $value) {

		$ret = "";

		if (!empty($selected) && $selected == $value) {
			// 選択状態とする
			$ret = "selected";
		}

		return $ret;
	}

	/**
	 * 引数の日付と日時を結合し、GMTの日時へ変換する
	 *	 
	 * @param $date = 設定タイムゾーンの日付
	 * @param $time = 設定タイムゾーンの時刻
	 *	 
	 * @return GMT日時
	 */
	private function combine_to_gmt_date_and_time($date, $time) {
	
		// $this->main->common()->debug_echo('■ combine_to_gmt_date_and_time start');

		$ret = '';

		if (isset($date) && isset($time)) {

			// サーバのタイムゾーン取得
			$timezone = date_default_timezone_get();
			$t = new \DateTime($date . ' ' . $time, new \DateTimeZone($timezone));

			// タイムゾーン変更
			$t->setTimeZone(new \DateTimeZone('GMT'));
		
			// $ret = $t->format(DATE_ATOM);
			$ret = $t->format(define::DATETIME_FORMAT);
			// $this->main->common()->debug_echo('　□timezone：' . $timezone);
		}
		
		// $this->main->common()->debug_echo('　□変換前の時刻：' . $datetime);
		// $this->main->common()->debug_echo('　□変換後の時刻（GMT）：'. $ret);
		
		// $this->main->common()->debug_echo('■ combine_to_gmt_date_and_time end');

	    return $ret;
	}

	/**
	 * フォーム値の設定
	 *	 
	 * @return 新規ダイアログの出力内容
	 */
	private function get_form_value() {

		$this->main->common()->debug_echo('■ get_form_value start');

		$form = array('branch_select_value' => '',
						'reserve_date' => '',
						'reserve_time' => '',
						'commit_hash' => '',
						'comment' => '',
						'selected_id' => ''
					);

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->main->options->_POST->branch_select_value)) {
			$form['branch_select_value'] = $this->main->options->_POST->branch_select_value;
		}
		if (isset($this->main->options->_POST->reserve_date)) {
			$form['reserve_date'] = $this->main->options->_POST->reserve_date;
		}
		if (isset($this->main->options->_POST->reserve_time)) {
			$form['reserve_time'] = $this->main->options->_POST->reserve_time;
		}
		if (isset($this->main->options->_POST->commit_hash)) {
			$form['commit_hash'] = $this->main->options->_POST->commit_hash;
		}
		if (isset($this->main->options->_POST->comment)) {
			$form['comment'] = $this->main->options->_POST->comment;
		}
		if (isset($this->main->options->_POST->selected_id)) {
			$form['selected_id'] = $this->main->options->_POST->selected_id;
		}

		$this->main->common()->debug_echo('■ get_form_value end');

		return $form;
	}

}
