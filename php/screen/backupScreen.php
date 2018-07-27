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

		// エラーがないので即時公開処理へ進む
		$result = $this->publish->exec_immediate_publish(define::PUBLISH_TYPE_IMMEDIATE);

		$this->main->common()->debug_echo('■ do_restore_publish end');

		return json_encode($result);
	}

	// /**
	//  * 復元公開処理
	//  */
	// public function do_restore_publish() {

	// 	$this->main->common()->debug_echo('■ do_restore_publish start');

	// 	//============================================================
	// 	// 公開処理実施
	// 	//============================================================
	// 	$result = json_encode($this->publish->exec_immediate_publish(define::PUBLISH_TYPE_RESTORE));

	// 	$insert_id;

	// 	try {

	// 		// // GMTの現在日時
	// 		// $start_datetime = $this->main->common()->get_current_datetime_of_gmt();

	// 		// $this->main->common()->debug_echo('　□ 公開処理開始日時：' . $start_datetime);

	// 		// // 作業用ディレクトリの絶対パスを取得
	// 		// $real_path = json_decode($this->main->common()->get_workdir_real_path($this->main->options));


	// 		//============================================================
	// 		// 公開処理結果テーブルの登録処理
	// 		//============================================================

	//  		$this->main->common()->debug_echo('　□ -----[復元公開]公開処理結果テーブルの登録処理-----');

	// 		// 現在時刻
	// 		$now = $this->main->common()->get_current_datetime_of_gmt();

	// 		$dataArray = array(
	// 			tsOutput::TS_OUTPUT_RESERVE_ID 		=> null,
	// 			tsOutput::TS_OUTPUT_BACKUP_ID 		=> null,
	// 			tsOutput::TS_OUTPUT_RESERVE 		=> null,
	// 			tsOutput::TS_OUTPUT_BRANCH 			=> null,
	// 			tsOutput::TS_OUTPUT_COMMIT_HASH 	=> null,
	// 			tsOutput::TS_OUTPUT_COMMENT 		=> null,
	// 			tsOutput::TS_OUTPUT_PUBLISH_TYPE 	=> define::PUBLISH_TYPE_RESTORE,
	// 			tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_RUNNING,
	// 			tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => null,
	// 			tsOutput::TS_OUTPUT_START 			=> $start_datetime,
	// 			tsOutput::TS_OUTPUT_END 			=> null,
	// 			tsOutput::TS_OUTPUT_GEN_DELETE_FLG 	=> define::DELETE_FLG_OFF,
	// 			tsOutput::TS_OUTPUT_GEN_DELETE 		=> null,
	// 			tsOutput::TS_OUTPUT_INSERT_DATETIME => $now,
	// 			tsOutput::TS_OUTPUT_INSERT_USER_ID 	=> $this->main->options->user_id,
	// 			tsOutput::TS_OUTPUT_UPDATE_DATETIME => null,
	// 			tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> null
	// 		);

	// 		// 公開処理結果テーブルの登録（インサートしたシーケンスIDをリターン値で取得）
	// 		$insert_id = $this->tsOutput->insert_ts_output($this->main->dbh, $dataArray);

	// 		$this->main->common()->debug_echo('　□ $insert_id：' . $insert_id);



	// 		//============================================================
	// 		// バックアップテーブルより、公開対象データの取得
	// 		//============================================================

	//  		$this->main->common()->debug_echo('　□ -----[復元公開]バックアップテーブルより、公開対象データの取得-----');

	// 		$selected_id =  $this->main->options->_POST->selected_id;

	// 		$selected_data = $this->tsBackup->get_selected_ts_backup($this->main->dbh, $selected_id);
		
	// 		if (!$selected_data) {
	// 			throw new \Exception('Target data not found.');
	// 		}

	// 		$dirname = $this->main->common()->format_gmt_datetime($selected_data[tsBackup::BACKUP_ENTITY_DATETIME_GMT], define::DATETIME_FORMAT_SAVE);
		
	// 		if (!$dirname) {
	// 			// エラー処理
	// 			throw new \Exception('Publish dirname create failed.');
	// 		}

	// 		//============================================================
	// 		// バックアップディレクトリを「backup」から「running」ディレクトリへ移動
	// 		//============================================================

	//  		$this->main->common()->debug_echo('　□ -----バックアップディレクトリを「backup」から「running」ディレクトリへコピー-----');

	// 		// runningディレクトリの絶対パスを取得。
	// 		$running_dirname = $this->main->common()->format_gmt_datetime($start_datetime, define::DATETIME_FORMAT_SAVE);

	// 		$this->publish->copy_dir($real_path->backup_real_path, $dirname, $real_path->running_real_path, $running_dirname, $real_path->log_real_path);


	// 		try {

	// 			/* トランザクションを開始する。オートコミットがオフになる */
	// 			$this->main->dbh->beginTransaction();

	// 			//============================================================
	// 			// バックアップテーブルの登録処理
	// 			//============================================================

	// 	 		$this->main->common()->debug_echo('　□ -----バックアップテーブルの登録処理-----');

	// 			// GMTの現在日時
	// 			$backup_datetime = $this->main->common()->get_current_datetime_of_gmt();

	// 			$this->tsBackup->insert_ts_backup($this->main->dbh, $this->main->options, $backup_datetime, $insert_id);


	// 			//============================================================
	// 			// 本番ソースを「backup」ディレクトリへコピー
	// 			//============================================================

	// 	 		$this->main->common()->debug_echo('　□ -----本番ソースを「backup」ディレクトリへコピー-----');

	// 	 		// バックアップディレクトリ名
	// 			$backup_dirname = $this->main->common()->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);

	// 			// バックアップファイル作成
	// 			$this->publish->create_backup($backup_dirname, $real_path);
			
	// 	 		/* 変更をコミットする */
	// 			$this->main->dbh->commit();
	// 			/* データベース接続はオートコミットモードに戻る */

	// 	    } catch (\Exception $e) {
		    
	// 	      /* 変更をロールバックする */
	// 	      $this->main->dbh->rollBack();
		 
	// 	      // throw $e;
	// 	      throw new \Exception($e->getMessage());
	// 	    }

	// 		try {

	// 			/* トランザクションを開始する。オートコミットがオフになる */
	// 			$this->main->dbh->beginTransaction();


	// 			//============================================================
	// 			// 公開処理結果テーブルの更新処理（成功）
	// 			//============================================================

	// 	 		$this->main->common()->debug_echo('　□ -----公開処理結果テーブルの更新処理（成功）-----');
				
	// 			// GMTの現在日時
	// 			$end_datetime = $this->main->common()->get_current_datetime_of_gmt();

	// 			$dataArray = array(
	// 				tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_SUCCESS,
	// 				tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
	// 				tsOutput::TS_OUTPUT_END 			=> $end_datetime,
	// 				tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> $this->main->options->user_id
	// 			);

	// 	 		$this->tsOutput->update_ts_output($this->main->dbh, $insert_id, $dataArray);

	// 			//============================================================
	// 			// ※公開処理※
	// 			//============================================================
				
	// 	 		$this->main->common()->debug_echo('　□ -----公開処理-----');
				
	// 			$this->publish->do_publish($running_dirname, $this->main->options);
			
	// 	 		/* 変更をコミットする */
	// 			$this->main->dbh->commit();
	// 			/* データベース接続はオートコミットモードに戻る */

	// 	    } catch (\Exception $e) {
		    
	// 	      /* 変更をロールバックする */
	// 	      $this->main->dbh->rollBack();
		      
	// 	      // throw $e;
	// 	      throw new \Exception($e->getMessage());
	// 	    }

	// 	} catch (\Exception $e) {

	// 		$result['status'] = false;
	// 		$result['message'] = 'Restore publish faild. ' . $e->getMessage();

	// 		//============================================================
	// 		// 公開処理結果テーブルの更新処理（失敗）
	// 		//============================================================

	//  		$this->main->common()->debug_echo('　□ -----公開処理結果テーブルの更新処理（失敗）-----');
	// 		// GMTの現在日時
	// 		$end_datetime = $this->main->common()->get_current_datetime_of_gmt();

	// 		$dataArray = array(
	// 			tsOutput::TS_OUTPUT_STATUS 			=> define::PUBLISH_STATUS_FAILED,
	// 			tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG => "0",
	// 			tsOutput::TS_OUTPUT_END 			=> $end_datetime,
	// 			tsOutput::TS_OUTPUT_UPDATE_USER_ID 	=> $this->options->user_id
	// 		);

	//  		$this->tsOutput->update_ts_output($this->main->dbh, $insert_id, $dataArray);

	// 		$this->main->common()->debug_echo('■ do_restore_publish error end');

	// 		chdir($current_dir);
	// 		return json_encode($result);
	// 	}

	// 	$result['status'] = true;

	// 	$this->main->common()->debug_echo('■ do_restore_publish end');

	// 	return json_encode($result);
	// }
}
