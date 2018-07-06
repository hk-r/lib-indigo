<?php

namespace indigo;

class publish
{
	private $main;

	private $pdoManager;

	/**
	 * PDOインスタンス
	 */
	private $dbh;

	// サーバのタイムゾーン
	const PARAM_TIME_ZONE = 'Asia/Tokyo';
	// サーバのタイムゾーン
	const GMT = 'GMT';

	// 日時フォーマット（Y-m-d H:i:s）
	const DATETIME_FORMAT = "Y-m-d H:i:s";
	// 時間フォーマット（Y-m-d）
	const DATE_FORMAT_YMD = "Y-m-d";
	// 時間フォーマット（H:i）
	const TIME_FORMAT_HI = "H:i";
	// 時間フォーマット（H:i:s）
	const TIME_FORMAT_HIS = "H:i:s";

	// 日時フォーマット_表示用（Y-m-d H:i）
	const DATETIME_FORMAT_DISPLAY = "Y-m-d H:i";
	// 日時フォーマット_保存用（YmdHis）
	const DATETIME_FORMAT_SAVE = "YmdHis";

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
	// 追加戻り表示モード
	const INPUT_MODE_ADD_BACK = 2;
	// 更新モード
	const INPUT_MODE_UPDATE = 3;
	// 更新戻り表示モード
	const INPUT_MODE_UPDATE_BACK = 4;
	// 即時公開モード
	const INPUT_MODE_SOKUJI = 5;
	// 即時公開戻り表示モード
	const INPUT_MODE_SOKUJI_BACK = 6;

	/**
	 * 公開種別
	 */
	// 予約公開
	const PUBLISH_TYPE_RESERVE = 1;
	// 復元公開
	const PUBLISH_TYPE_RESTORE = 2;
	// 即時公開
	const PUBLISH_TYPE_SOKUJI = 3;


	/**
	 * 公開ステータス
	 */
	// 処理中
	const PUBLISH_STATUS_RUNNING = 0;
	// 成功
	const PUBLISH_STATUS_SUCCESS = 1;
	// 成功（警告あり）
	const PUBLISH_STATUS_ALERT = 2;
	// 失敗
	const PUBLISH_STATUS_FAILED = 3;
	// スキップ
	const PUBLISH_STATUS_SKIP = 4;

	/**
	 * 公開用の操作ディレクトリパス定義
	 */
	// backupディレクトリパス
	const PATH_BACKUP = '/backup/';
	// waitingディレクトリパス
	const PATH_WAITING = '/waiting/';
	// runnningディレクトリパス
	const PATH_RUNNING = '/running/';
	// releasedディレクトリパス
	const PATH_RELEASED = '/released/';
	// logディレクトリパス
	const PATH_LOG = '/log/';


	// 生成ディレクトリパス（後々パラメタ化する）
	const PATH_CREATE_DIR = './../indigo_dir/';
	// 本番パス（後々パラメタ化する）
	const PATH_PROJECT_DIR = './../../indigo-test-project/';

	/**
	 * 公開実施管理CSVの列番号定義
	 */
	const TS_RESULT_COLUMN_ID = 'result_id_seq';			// ID
	const TS_RESULT_COLUMN_RESERVE = 'reserve_datetime';		// 公開予約日時
	const TS_RESULT_COLUMN_BRANCH = 'branch_name';		// ブランチ名
	const TS_RESULT_COLUMN_COMMIT = 'commit_hash';		// コミットハッシュ値（短縮）
	const TS_RESULT_COLUMN_COMMENT = 'comment';		// コメント
	// const TS_RESULT_COLUMN_SETTING = '';		// 設定日時

	const TS_RESULT_COLUMN_STATUS = 'status';		// 状態
	const TS_RESULT_COLUMN_TYPE = 'publish_type';		// 公開種別

	const TS_RESULT_COLUMN_START = 'start_datetime';		// 公開処理開始日時
	const TS_RESULT_COLUMN_END = 'end_datetime';			// 公開処理終了日時

	const TS_RESULT_COLUMN_RELEASED = 8;		// 公開完了日時
	const TS_RESULT_COLUMN_RESTORE = 9;		// 復元完了日時

	const TS_RESULT_COLUMN_DIFF_FLG1 = 10;	// 差分フラグ1（本番環境と前回分の差分）
	const TS_RESULT_COLUMN_DIFF_FLG2 = 11;	// 差分フラグ2（本番環境と今回分の差分）
	const TS_RESULT_COLUMN_DIFF_FLG3 = 12;	// 差分フラグ3（前回分と今回分の差分）

	/**
	 * 公開実施管理CSVの列番号定義
	 */
	const RESULT_ENTITY_ID = 'result_id_seq';			// ID
	const RESULT_ENTITY_RESERVE = 'reserve_datetime';		// 公開予約日時
	const RESULT_ENTITY_RESERVE_DISPLAY = 'reserve_display';	// 公開予約日時
	const RESULT_ENTITY_BRANCH = 'branch_name';		// ブランチ名
	const RESULT_ENTITY_COMMIT = 'commit_hash';		// コミットハッシュ値（短縮）
	const RESULT_ENTITY_COMMENT = 'comment';		// コメント
	// const RESULT_ENTITY_SETTING = '';		// 設定日時

	const RESULT_ENTITY_STATUS = 'status';		// 状態
	const RESULT_ENTITY_TYPE = 'publish_type';		// 公開種別

	const RESULT_ENTITY_START = 'start_datetime';		// 公開処理開始日時
	const RESULT_ENTITY_START_DISPLAY = 'start_display';	// 公開処理開始日時
	const RESULT_ENTITY_END = 'end_datetime';			// 公開処理終了日時
	const RESULT_ENTITY_END_DISPLAY = 'end_display';	// 公開処理終了日時

	const RESULT_ENTITY_RELEASED = 8;		// 公開完了日時
	const RESULT_ENTITY_RESTORE = 9;		// 復元完了日時

	const RESULT_ENTITY_DIFF_FLG1 = 10;	// 差分フラグ1（本番環境と前回分の差分）
	const RESULT_ENTITY_DIFF_FLG2 = 11;	// 差分フラグ2（本番環境と今回分の差分）
	const RESULT_ENTITY_DIFF_FLG3 = 12;	// 差分フラグ3（前回分と今回分の差分）

	// const HONBAN_REALPATH = '/var/www/html/indigo-test-project/';
	const HONBAN_REALPATH = '/var/www/html/test';
	
	// 削除済み
	const DELETE_FLG_ON = 1;
	// 未削除
	const DELETE_FLG_OFF = 0;

	// const DEFINE_MAX_RECORD = 10;		// 公開予約できる最大件数
	// const DEFINE_MAX_GENERATION = 10;	// 差分フラグ2（本番環境と今回分の差分）
	// const DEFINE_MAX_RECORD = 12;		// 差分フラグ3（前回分と今回分の差分）

	/**
	 * コミットハッシュ値
	 */
	private $commit_hash = '';

	/**
	 * 入力画面のエラーメッセージ
	 */
	private $input_error_message = '';

	/**
	 * 本番環境ディレクトリパス（仮）
	 */
	private $honban_path = './../honban/';

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($main) {

		$this->main = $main;
		$this->fileManager = new fileManager($this);
		$this->pdoManager = new pdoManager($this);

		$this->debug_echo('★publishクラスのコンストラクタ起動！');
	}


	/**
	 * GMTの現在時刻を取得
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	private function get_current_datetime_of_gmt() {

		return gmdate(DATE_ATOM, time());
	}


	/**
	 * タイムゾーンの現在時刻を取得
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	private function get_current_datetime_of_timezone() {

		return date(DATE_ATOM, time());
	}

	

	/**
	 * 即時公開処理
	 */
	public function immediate_release() {

		$this->debug_echo('■ immediate_release start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		// GMTの現在日時
		$start_datetime = gmdate(self::DATETIME_FORMAT);
		$start_datetime_dir = gmdate(self::DATETIME_FORMAT_SAVE);

		try {

	 		// ▼ 未実装
			/**
	 		* 公開タスクロックの確認
			*/

	 		// クーロン用
	 		$now = gmdate(self::DATETIME_FORMAT_SAVE);
			// 公開予約の一覧を取得
			$data_list = $this->get_ts_reserve_list($now);
			foreach ( (array)$selected_ret as $data ) {
				// 公開対象の公開予約日時（文字列）
				$dirname = $this->get_datetime_str($selected_ret, self::TS_RESERVE_COLUMN_RESERVE, SORT_DESC);
			}

	 	// 	// 選択された公開予約データを取得
			// $selected_ret = $this->get_selected_reserve_data();

			// $dirname = '';

			// if ( $selected_ret ) {
			// 	// 公開対象の公開予約日時（文字列）
			// 	$dirname = date(self::DATETIME_FORMAT_SAVE, strtotime($selected_ret[self::TS_RESERVE_COLUMN_RESERVE]));
			// }

			// $this->debug_echo('　□ 公開予約ディレクトリ：');
			// $this->debug_echo($dirname);
			$this->debug_echo('　□ 現在日時：');
			$this->debug_echo($start_datetime);

			// 公開対象が存在する場合
			if ($start_datetime_dir) {

				// 　公開予約一覧CSVにファイルロックが掛かっていない場合
				// 　ファイルロックを掛ける
				// 　実施済み一覧CSVにファイルロックが掛かっていない場合
				// 　ファイルロックを掛ける
		 		// 公開対象の行を、実施済みへ切り取り移動する
		 		$this->debug_echo('　□ 公開処理結果テーブルの登録処理');
				

				/**
		 		* 公開処理結果テーブルの登録処理
				*/ 
				$ret = json_decode($this->pdoManager->insert_ts_output($this->dbh, $this->main->options, $start_datetime));

				// インサートしたシーケンスIDを取得（処理終了時の更新処理にて使用）
				$insert_id = $this->dbh->lastInsertId();

				$this->debug_echo('　□ $insert_id：');
				$this->debug_echo($insert_id);

				if ( !$ret->status) {
					throw new \Exception("TS_OUTPUT insert failed.");
				}
				
				// TODO:開発用に、windowsでは処理させていない。後で削除。
				if (self::DEVELOP_ENV != '1') {

					// TODO:未実装
			 		// ファイルロックを解除する

					// ログファイルディレクトリが存在しない場合は作成
					if ( !$this->fileManager->is_exists_mkdir($this->main->options->indigo_workdir_path . self::PATH_LOG) ) {
						// エラー処理
						throw new \Exception('Creation of log directory failed.');
					} else {
						
						// ログファイル内の公開予約ディレクトリが存在しない場合は削除（存在する場合は作成しない）
						if ( !$this->fileManager->is_exists_mkdir($this->main->options->indigo_workdir_path . self::PATH_LOG . $dirname) ) {

							// エラー処理
							throw new \Exception('Creation of log publish directory failed.');
						}
					}

					// バックアップディレクトリが存在しない場合は作成
					if ( !$this->fileManager->is_exists_mkdir($this->main->options->indigo_workdir_path . self::PATH_BACKUP) ) {
						// エラー処理
						throw new \Exception('Creation of backup directory failed.');
					} else {
						
						// 公開予約ディレクトリをデリートインサート
						if ( !$this->is_exists_remkdir($this->main->options->indigo_workdir_path . self::PATH_BACKUP . $dirname) ) {

							// エラー処理
							throw new \Exception('Creation of backup publish directory failed.');
						}
					}

					// runningディレクトリが存在しない場合は作成
					if ( !$this->fileManager->is_exists_mkdir($this->main->options->indigo_workdir_path . self::PATH_RUNNING) ) {
						// エラー処理
						throw new \Exception('Creation of running directory failed.');
					} else {
						
						// 公開予約ディレクトリをデリートインサート
						if ( !$this->is_exists_remkdir($this->main->options->indigo_workdir_path . self::PATH_RUNNING . $dirname) ) {

							// エラー処理
							throw new \Exception('Creation of running publish directory failed.');
						}
					}

					// releasedディレクトリが存在しない場合は作成
					if ( !$this->fileManager->is_exists_mkdir($this->main->options->indigo_workdir_path . self::PATH_RELEASED) ) {
						// エラー処理
						throw new \Exception('Creation of released directory failed.');
					} else {
						
						// 公開予約ディレクトリをデリートインサート
						if ( !$this->is_exists_remkdir($this->main->options->indigo_workdir_path . self::PATH_RELEASED . $dirname) ) {

							// エラー処理
							throw new \Exception('Creation of released publish directory failed.');
						}
					}

					/**
			 		* 本番ソースを「backup」ディレクトリへコピー
					*/

					$this->debug_echo('　▼バックアップ処理開始');

					// バックアップの公開予約ディレクトリの存在確認
					if ( file_exists($this->main->options->indigo_workdir_path . self::PATH_BACKUP . $dirname) ) {

						// 本番ソースからバックアップディレクトリへコピー

						// $honban_realpath = $current_dir . "/" . self::PATH_PROJECT_DIR;

						$this->debug_echo('　□ カレントディレクトリ：');
						$this->debug_echo(realpath('.'));

						// TODO:ログフォルダに出力する
						// $command = 'rsync -avzP ' . self::HONBAN_REALPATH  . '/' . ' ' . $this->main->options->indigo_workdir_path . self::PATH_BACKUP . $dirname . '/' . ' --log-file=' . $this->main->options->indigo_workdir_path . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

						// $this->debug_echo('　□ $command：');
						// $this->debug_echo($command);

						// $ret = $this->command_execute($command, true);

						$this->debug_echo('　▼本番バックアップの公開処理結果');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}
					} else {
							// エラー処理
							throw new \Exception('Backup directory not found.');
					}


					/**
			 		* 公開予約ソースを「WAITING」ディレクトリから「running」ディレクトリへ移動
					*/
					// runningの公開予約ディレクトリの存在確認
					if ( file_exists($this->main->options->indigo_workdir_path . self::PATH_RUNNING . $dirname) ) {

						// TODO:ログフォルダに出力する
						$command = 'rsync -avzP --remove-source-files ' . $this->main->options->indigo_workdir_path . self::PATH_WAITING . $dirname . '/' . ' ' . $this->main->options->indigo_workdir_path . self::PATH_RUNNING . $dirname . '/' . ' --log-file=' . $this->main->options->indigo_workdir_path . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

						$this->debug_echo('　□ $command：');
						$this->debug_echo($command);

						$ret = $this->command_execute($command, true);

						$this->debug_echo('　▼RUNNINGへの移動の公開処理結果');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}


						// waitingの空ディレクトリを削除する
						$command = 'find ' .  $this->main->options->indigo_workdir_path . self::PATH_WAITING . $dirname . '/ -type d -empty -delete' ;

						$this->debug_echo('　□ $command：');
						$this->debug_echo($command);

						$ret = $this->command_execute($command, true);

						$this->debug_echo('　▼Waitingディレクトリの削除');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}

						// waitingディレクトリの削除が成功していることを確認
						if ( file_exists($this->main->options->indigo_workdir_path . self::PATH_WAITING . $dirname) ) {
							// ディレクトリが削除できていない場合
							// エラー処理
							throw new \Exception('Delete of waiting publish directory failed.');
						}
					} else {
							// エラー処理
							throw new \Exception('Waiting directory not found.');
					}

					/**
			 		* 「running」ディレクトリへ移動した公開予約ソースを本番環境へ同期
					*/
					// 本番ディレクトリの存在確認
					// if ( file_exists(self::HONBAN_REALPATH) ) {

					// 	// runningから本番ディレクトリへコピー

					// 	// $honban_realpath = $current_dir . "/" . self::PATH_PROJECT_DIR;

					// 	$this->debug_echo('　□ カレントディレクトリ：');
					// 	$this->debug_echo(realpath('.'));

					// 	// TODO:ログフォルダに出力する
					// 	// $command = 'rsync -avzP ' . $this->main->options->indigo_workdir_path . self::PATH_RUNNING . $dirname . '/' . ' ' . self::HONBAN_REALPATH . ' --log-file=' . $this->main->options->indigo_workdir_path . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

					// 	// ★-aではエラーとなる。最低限の同期とする！所有者やグループは変更しない！
					// 	// r ディレクトリを再帰的に調べる。
					// 	// -l シンボリックリンクをリンクとして扱う
					// 	// -p パーミッションも含める
					// 	// -t 更新時刻などの時刻情報も含める
					// 	// -o 所有者情報も含める
					// 	// -g ファイルのグループ情報も含める
					// 	// -D デバイスファイルはそのままデバイスとして扱う
					// 	$command = 'rsync -rtvzP --delete ' . $this->main->options->indigo_workdir_path . self::PATH_RUNNING . $dirname . '/' . ' ' . self::HONBAN_REALPATH . '/' . ' ' . '--log-file=' . $this->main->options->indigo_workdir_path . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

					// 	$this->debug_echo('　□ $command：');
					// 	$this->debug_echo($command);

					// 	$ret = $this->command_execute($command, true);

					// 	$this->debug_echo('　▼本番反映の公開処理結果');

					// 	foreach ( (array)$ret['output'] as $element ) {
					// 		$this->debug_echo($element);
					// 	}

					// } else {
					// 		// エラー処理
					// 		throw new \Exception('Running directory not found.');
					// }

					/**
			 		* 同期が正常終了したら、公開済みソースを「running」ディレクトリから「released」ディレクトリへ移動
					*/
					// runningの公開予約ディレクトリの存在確認
					if ( file_exists($this->main->options->indigo_workdir_path . self::PATH_RELEASED . $dirname) ) {

						// TODO:ログフォルダに出力する
						$command = 'rsync -avzP --remove-source-files ' . $this->main->options->indigo_workdir_path . self::PATH_RUNNING . $dirname . '/' . ' ' . $this->main->options->indigo_workdir_path . self::PATH_RELEASED . $dirname . '/' . ' --log-file=' . $this->main->options->indigo_workdir_path . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

						$this->debug_echo('　□ $command：');
						$this->debug_echo($command);

						$ret = $this->command_execute($command, true);

						$this->debug_echo('　▼REALEASEDへの移動の公開処理結果');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}


						// runningの空ディレクトリを削除する
						$command = 'find ' .  $this->main->options->indigo_workdir_path . self::PATH_RUNNING . $dirname . '/ -type d -empty -delete' ;

						$this->debug_echo('　□ $command：');
						$this->debug_echo($command);

						$ret = $this->command_execute($command, true);

						$this->debug_echo('　▼Runningディレクトリの削除');

						foreach ( (array)$ret['output'] as $element ) {
							$this->debug_echo($element);
						}

						// waitingディレクトリの削除が成功していることを確認
						if ( file_exists($this->main->options->indigo_workdir_path . self::PATH_RUNNING . $dirname) ) {
							// ディレクトリが削除できていない場合
							// エラー処理
							throw new \Exception('Delete of waiting publish directory failed.');
						}
					} else {
							// エラー処理
							throw new \Exception('Running publish directory not found.');
					}

				}

				$this->debug_echo('　□ 公開処理結果テーブルの更新処理');


				// GMTの現在日時
				// $end_datetime = gmdate(self::DATETIME_FORMAT);
				// $end_datetime_dir = gmdate(self::DATETIME_FORMAT_SAVE);

				/**
		 		* 公開処理結果テーブルの更新処理
				*/
		 		$ret = $this->update_ts_output($this->dbh, $insert_id);

				foreach ( (array)$ret['output'] as $element ) {
					$this->debug_echo($element);
				}

				// 実施済み一覧CSVにファイルロックが掛かっていない場合
				// 　ロックを掛ける
		 		// 「実施開始日時」を設定
		 		// 「実施終了日時」を設定
		 		
				// 公開が成功した場合
		 		// 　「公開完了日時」を設定

		 		// 公開が失敗し、復元が完了した場合
		 		// 　「復元完了日時」を設定

		 		// ▼優先度低
		 		// 本番環境と前回分のソースに差分が存在した場合
		 		// 　「差分確認フラグ1」を設定

		 		// ▼優先度低
		 		// 本番環境と今回分のソースに差分が存在した場合
		 		// 　「差分確認フラグ2」を設定

		 		// ▼優先度低
		 		// 今回分と前回分のソースに差分が存在した場合
		 		// 　「差分確認フラグ3」を設定

			} else {

				$this->debug_echo('　□ 公開対象が存在しない');

			}
		
		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			$this->debug_echo('■ immediate_release error end');

			chdir($current_dir);
			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->debug_echo('■ immediate_release end');

		return json_encode($result);
	}




	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_echo($text) {
	
		echo strval($text);
		echo "<br>";

		return;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_var_dump($text) {
	
		var_dump($text);
		echo "<br>";

		return;
	}

}

