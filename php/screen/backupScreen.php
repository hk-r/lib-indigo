<?php

namespace indigo;

class backupScreen
{
	private $main;

	private $check;
	private $tsBackup;
	private $fileManager;
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
		$this->fileManager = new fileManager($this);
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
		$data_list = $this->tsBackup->get_ts_backup_list($this->main->dbh, null);

		$ret .= '<div style="overflow:hidden">'
			. '<form method="post">'
			. '<div class="button_contents" style="float:right;">'
			. '<ul>'
			. '<li><input type="submit" name="restore" class="px2-btn px2-btn--primary" value="復元"/></li>'
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



	/**
	 * 復元公開処理
	 */
	public function do_restore_publish() {

		$this->common->debug_echo('■ do_backup_publish start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			// GMTの現在日時
			$start_datetime = $this->common->get_current_datetime_of_gmt();

			$this->common->debug_echo('　□ 現在日時：');
			$this->common->debug_echo($start_datetime);

			// 本番環境ディレクトリの絶対パスを取得。
			$project_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->project_real_path . "/"));

			// backupディレクトリの絶対パスを取得。
			$backup_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_BACKUP));

			// runningディレクトリの絶対パスを取得。
			$running_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_RUNNING));

			// logディレクトリの絶対パスを取得。
			$log_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . define::PATH_LOG));

			//============================================================
			// 公開予約ディレクトリを「backup」から「running」ディレクトリへ移動
			//============================================================

	 		$this->common->debug_echo('　□ -----公開予約ディレクトリを「backup」から「running」ディレクトリへ移動-----');

			$ret = json_decode($this->publish->move_dir($backup_real_path, $dirname, $running_real_path, $dirname));

			if ( !$ret->status) {
				throw new \Exception($ret->message);
			}

			//============================================================
			// 公開処理結果テーブルの登録処理
			//============================================================

	 		$this->common->debug_echo('　□ -----公開処理結果テーブルの登録処理-----');

			$ret = json_decode($this->tsOutput->insert_ts_output($this->main->dbh, $this->main->options, $start_datetime, self::PUBLISH_TYPE_RESTORE));
			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT insert failed.");
			}

			// インサートしたシーケンスIDを取得（処理終了時の更新処理にて使用）
			$insert_id = $ret->insert_id;

			$this->common->debug_echo('　□ $insert_id：' . $insert_id);


			//============================================================
			// 本番ソースを「backup」ディレクトリへコピー
			//============================================================

	 		$this->common->debug_echo('　□ -----本番ソースを「backup」ディレクトリへコピー-----');

			// GMTの現在日時
			$backup_datetime = $this->common->get_current_datetime_of_gmt();
			$backup_dirname = $this->common->format_gmt_datetime($backup_datetime, define::DATETIME_FORMAT_SAVE);

			$this->common->debug_echo('　□ バックアップ日時：' . $backup_datetime);

			// バックアップファイル作成
			$ret = json_decode($this->publish->create_backup($project_real_path, $backup_real_path, $log_real_path, $backup_dirname));
		
			if ( !$ret->status) {
				throw new \Exception($ret->message);
			}


			//============================================================
			// バックアップテーブルの登録処理
			//============================================================
			$ret = json_decode($this->tsBackup->insert_ts_backup($this->main->dbh, $this->main->options, $backup_datetime, define::PUBLISH_TYPE_RESTORE));
			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT insert failed.");
			}


			//============================================================
			// ※公開処理※
			//============================================================
			$ret = json_decode($this->publish->do_publish($dirname));
		
			// 公開ステータスの設定
			$publish_status;
			if ( $ret->status) {
				$publish_status = define::PUBLISH_STATUS_SUCCESS;
			} else {
				$publish_status = define::PUBLISH_STATUS_FAILED;
			}


			//============================================================
			// 公開処理結果テーブルの更新処理
			//============================================================
			// GMTの現在日時
			$end_datetime = $this->common->get_current_datetime_of_gmt();

	 		$ret = json_decode($this->tsOutput->update_ts_output($this->main->dbh, $insert_id, $end_datetime, $publish_status));

			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT update failed. " . $ret->message);
			}

		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			$this->common->debug_echo('■ immediate_publish error end');

			chdir($current_dir);
			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->common->debug_echo('■ immediate_publish end');

		return json_encode($result);
	}
}
