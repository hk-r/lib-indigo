<?php

namespace indigo;

class cron
{
	public $options;

	public $publish;

	private $fileManager;

	private $pdoManager;

	private $tsOutput;

	/**
	 * PDOインスタンス
	 */
	private $dbh;

	// 開発環境
	const DEVELOP_ENV = '1';
	
	/**
	 * 削除フラグ
	 */
	const DELETE_FLG_ON = 1;	// 削除済み
	const DELETE_FLG_OFF = 0;	// 未削除
	
	/**
	 * 公開種別
	 */
	// 予約公開
	const PUBLISH_TYPE_RESERVE = 1;

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
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {

		$this->debug_echo('■ [cron] __construct start');

		$this->options = json_decode(json_encode($options));
		$this->fileManager = new fileManager($this);
		$this->pdoManager = new pdoManager($this);
		$this->publish = new publish($this);
		$this->tsOutput = new tsOutput($this);

		$this->debug_echo('■ [cron] __construct end');

	}

	/**
	 * 
	 */
    public function run(){
	
		$this->debug_echo('■ [cron] run start');

		// 処理実行結果格納
		$ret = '';

		try {

			//============================================================
			// データベース接続
			//============================================================
			$this->dbh = $this->pdoManager->connect();


			//============================================================
			// 作業用ディレクトリの作成（既にある場合は作成しない）
			//============================================================
			$this->main->create_work_dir();


			//============================================================
			// 公開予約テーブルより、公開対象データの取得
			//============================================================
			// GMTの現在日時
			$start_datetime = $this->main->get_current_datetime_of_gmt();

			$this->debug_echo('　□ 現在日時：');
			$this->debug_echo($start_datetime);

			// 公開予約の一覧を取得
			$data_list = $this->get_ts_reserve_publish_list($this->dbh, $start_datetime);

			// TODO:ここで複数件取れてきた場合は、最新データ以外はスキップデータとして公開処理結果テーブルへ登録する
			foreach ( (array)$data_list as $data ) {

				$this->debug_echo('　□公開取得データ[配列]');
				$this->debug_var_dump($data);

				$dirname = $this->format_datetime($data[self::TS_RESERVE_COLUMN_RESERVE], self::DATETIME_FORMAT_SAVE);
		
				$this->debug_echo('　□公開ディレクトリ名');
				$this->debug_var_dump($dirname);
			}

			//============================================================
			// 公開予約ディレクトリを「waiting」から「running」ディレクトリへ移動
			//============================================================

			// waitingディレクトリの絶対パスを取得。
			$waiting_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_WAITING));

			// runningディレクトリの絶対パスを取得。
			$running_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_RUNNING));

			// logディレクトリの絶対パスを取得。
			$log_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->options->indigo_workdir_path . self::PATH_LOG));

			if ( file_exists($waiting_real_path) && file_exists($running_real_path) ) {

				// TODO:ログフォルダに出力する
				$command = 'rsync -avzP --remove-source-files ' . $waiting_real_path . $dirname . '/ ' . $running_real_path . $dirname . '/' . ' --log-file=' . $log_real_path . $dirname . '/rsync_' . $dirname . '.log' ;

				$this->debug_echo('　□ $command：');
				$this->debug_echo($command);

				$ret = $this->main->command_execute($command, true);

				$this->debug_echo('　▼ waiting⇒runningのファイル移動結果');

				foreach ( (array)$ret['output'] as $element ) {
					$this->debug_echo($element);
				}

				// waitingの空ディレクトリを削除する
				$command = 'find ' .  $waiting_real_path . $dirname . '/ -type d -empty -delete' ;

				$this->debug_echo('　□ $command：');
				$this->debug_echo($command);

				$ret = $this->main->command_execute($command, true);

				$this->debug_echo('　▼ Waitingディレクトリの削除');

				foreach ( (array)$ret['output'] as $element ) {
					$this->debug_echo($element);
				}

			} else {
					// エラー処理
					throw new \Exception('Waiting or running directory not found.');
			}


			//============================================================
			// 公開処理結果テーブルの登録処理
			//============================================================
			$ret = json_decode($this->tsOutput->insert_ts_output($this->dbh, $this->main->options, $start_datetime, self::PUBLISH_TYPE_RESERVE));
			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT insert failed.");
			}

			// インサートしたシーケンスIDを取得（処理終了時の更新処理にて使用）
			$insert_id = $this->dbh->lastInsertId();

			$this->debug_echo('　□ $insert_id：');
			$this->debug_echo($insert_id);



			//============================================================
			// ※公開処理※
			//============================================================
			$ret = json_decode($this->publish->do_publish($dirname));




			//============================================================
			// 公開処理結果テーブルの更新処理
			//============================================================
			// GMTの現在日時
			$end_datetime = $this->main->get_current_datetime_of_gmt();

	 		$ret = $this->tsOutput->update_ts_output($this->dbh, $insert_id, $end_datetime, self::PUBLISH_STATUS_SUCCESS);
			if ( !$ret->status) {
				throw new \Exception("TS_OUTPUT update failed.");
			}
	
		} catch (\Exception $e) {

			// データベース接続を閉じる
			$this->pdoManager->close($this->dbh);

			echo $e->getMessage();

			$this->debug_echo('■ [cron] run error end');

			return;
		}

		// データベース接続を閉じる
		$this->pdo->close();

		$this->debug_echo('■ [cron] run end');

		return;
    }

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_echo($text) {

		echo strval($text);
		echo "</br>";

		return;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_var_dump($text) {

		var_dump($text);
		echo "</br>";

		return;
	}

}
