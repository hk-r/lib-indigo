<?php

namespace indigo;

class main
{
	public $options;

	private $file_control;

	private $pdo;

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

	// CSV区切り文字
	const CSV_DELIMITER		= ',';
	// CSV囲み文字
	const CSV_ENCLOSURE		= '"';

	// 公開待ち管理CSVファイル
	const CSV_WAITING_LIST_FILENAME = '/csv/waiting_list.csv';
	// 公開実施管理CSVファイル
	const CSV_RELEASED_LIST_FILENAME = '/csv/released_list.csv';

	/**
	 * 画像パス定義
	 */
	// 右矢印
	const IMG_ARROW_RIGHT = '/images/arrow_right.png';
	// エラーアイコン
	const IMG_ERROR_ICON = '/images/error_icon.png';


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
	 * 公開予定管理CSVの列番号定義
	 */
	const WAITING_CSV_COLUMN_ID = 0;		// ID
	const WAITING_CSV_COLUMN_RESERVE = 1;	// 公開予定日時
	const WAITING_CSV_COLUMN_BRANCH = 2;	// ブランチ名
	const WAITING_CSV_COLUMN_COMMIT = 3;	// コミットハッシュ値（短縮）
	const WAITING_CSV_COLUMN_COMMENT = 4;	// コメント
	const WAITING_CSV_COLUMN_SETTING = 5;	// 設定日時

	/**
	 * 公開実施管理CSVの列番号定義
	 */
	const RELEASED_CSV_COLUMN_ID = 0;			// ID
	const RELEASED_CSV_COLUMN_RESERVE = 1;		// 公開予定日時
	const RELEASED_CSV_COLUMN_BRANCH = 2;		// ブランチ名
	const RELEASED_CSV_COLUMN_COMMIT = 3;		// コミットハッシュ値（短縮）
	const RELEASED_CSV_COLUMN_COMMENT = 4;		// コメント
	const RELEASED_CSV_COLUMN_SETTING = 5;		// 設定日時

	const RELEASED_CSV_COLUMN_START = 6;		// 公開処理開始日時
	const RELEASED_CSV_COLUMN_END = 7;			// 公開処理終了日時
	const RELEASED_CSV_COLUMN_RELEASED = 8;		// 公開完了日時
	const RELEASED_CSV_COLUMN_RESTORE = 9;		// 復元完了日時

	const RELEASED_CSV_COLUMN_DIFF_FLG1 = 10;	// 差分フラグ1（本番環境と前回分の差分）
	const RELEASED_CSV_COLUMN_DIFF_FLG2 = 11;	// 差分フラグ2（本番環境と今回分の差分）
	const RELEASED_CSV_COLUMN_DIFF_FLG3 = 12;	// 差分フラグ3（前回分と今回分の差分）

	// const HONBAN_REALPATH = '/var/www/html/indigo-test-project/';
	const HONBAN_REALPATH = '/var/www/html/test';
	
	// const DEFINE_MAX_RECORD = 10;		// 公開予約できる最大件数
	// const DEFINE_MAX_GENERATION = 10;	// 差分フラグ2（本番環境と今回分の差分）
	// const DEFINE_MAX_RECORD = 12;		// 差分フラグ3（前回分と今回分の差分）

	/**
	 * コミットハッシュ値
	 */
	private $commit_hash = '';

	/**
	 * 本番環境ディレクトリパス（仮）
	 */
	private $honban_path = './../honban/';

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
		$this->file_control = new file_control($this);
		// $this->pdo = new pdo($this);

				$this->debug_echo('■ __construct start');

		// $this->px = $px;
		// $this->conf = $this->px->conf();

		$this->dbh = false;//初期化

		if( class_exists('\\PDO') ) {
			
			$this->debug_echo('　□PDO1');

			try {

				// 接続種類
				$db = 'sqlite:';

				// データベースパス
				$db_path = './sqlite/';

				// データベース名
				$db_name = 'test.db';

				// データベースのユーザ名
				$db_user = null;
				// データベースのパスワード
				$db_password = null;

				// TODO:MySQLの場合
				// mysql:host=ホスト名;dbname=データベース名;charset=文字エンコード
				// $dsn = 'mysql:host=mysql000.db.sakura.ne.jp;dbname=example_php;charset=utf8';		

			$this->debug_echo('　□PDO2');

				// PDOインスタンスを生成
				$this->dbh = new \PDO(
					'sqlite:' . $db_path . $db_name,
					$db_user,		// ユーザID
					$db_password,	// パスワード
					array(		// オプションがあればこちらへ追記
						// \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
						\PDO::ATTR_PERSISTENT => false, // ←これをtrueにすると、"持続的な接続" になる
					)
				);

				// INSERT処理開始
				$this->debug_echo('　□INSERT START');

				$insert_sql = "INSERT INTO list (
					-- id,
					reserve_dt,
					commit_hash,
					branch_name
				)VALUES(
					-- :id,
					:reserve_dt,
					:commit_hash,
					:branch_name
				)";

				$this->debug_echo('　□INSERT　SQL：' . $insert_sql);

				$stmt = $this->dbh->prepare($insert_sql);

				$params = array(
					// ':id' => '',
					':reserve_dt' => '2018-05-31T10:00:00+00:00',
					':commit_hash' => 'feie8e',
					':branch_name' => 'released/2018-05-31'
				);

				// 処理実行
				$stmt->execute($params);


				$params = array(
					// ':id' => '',
					':reserve_dt' => '2018-06-01T10:00:00+00:00',
					':commit_hash' => 'h83ohi',
					':branch_name' => 'released/2018-06-01'
				);

				// 処理実行
				$stmt->execute($params);

				// デバッグ用
				$this->debug_var_dump('　□INSERTエラー：' . $stmt->errorInfo());
				$count = $stmt->rowCount();
				$this->debug_echo('　□INSERT件数：' . $count);

				$this->debug_echo('　□INSERT END');



				// UPDATE処理開始
				$this->debug_echo('　□UPDATE START');

				$update_sql = "UPDATE list SET branch_name = :branch_name WHERE id = :id";

				$this->debug_echo('　□UPDATE　SQL：' . $update_sql);

				$stmt = $this->dbh->prepare($update_sql);

				$params = array(
					':branch_name' => 'released/2018-06-09',
					':id' => '2'
				);

				$stmt->execute($params);

				$this->debug_var_dump('　□UPDATEエラー：' . $stmt->errorInfo());
				$count = $stmt->rowCount();
				$this->debug_echo('　□UPDATE件数：' . $count);

				$this->debug_echo('　□UPDATE END');



				// DELETE処理開始
				$this->debug_echo('　□DELETE START');

				$delete_sql = "DELETE FROM list WHERE id = :id";

				$this->debug_echo('　□DELETE　SQL：' . $delete_sql);

				$stmt = $this->dbh->prepare($delete_sql);

				$params = array(
					':id' => '1'
				);

				$stmt->execute($params);

				$this->debug_var_dump('　□DELETEエラー：' . $stmt->errorInfo());
				$count = $stmt->rowCount();
				$this->debug_echo('　□DELETE件数：' . $count);

				$this->debug_echo('　□DELETE END');



				// SELECT処理開始
				$this->debug_echo('　□SELECT START');

				$select_sql = "SELECT * FROM list ORDER BY branch_name";


				$this->debug_echo('　□SELECT　SQL：' . $select_sql);

				$stmt = $this->dbh->query($select_sql);

				$this->debug_var_dump($stmt->errorInfo());
				$count = $stmt->rowCount();
				$this->debug_echo('　□SELECT：' . $count);

				// foreach文で配列の中身を一行ずつ出力
				foreach ($stmt as $row) {
				 
					// データベースのフィールド名で出力
					$this->debug_echo($row['id'].'：'.$row['branch_name']);
					
				}

				$this->debug_echo('　□SELECT END');


				// データベースとの接続を閉じる
				$dbh = null;

			} catch (Exception $e) {
				
				echo 'データベースにアクセスできません！' . $e->getMessage();
				// 強制終了
				exit;
			}
			
		}

		$this->debug_echo('■ __construct end');


	}

	/**
	 * Gitのmaster情報を取得
	 */
	private function init() {

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$master_path = $this->options->git->repository;

		set_time_limit(0);

		try {

			if ( strlen($master_path) ) {

				// デプロイ先のディレクトリが無い場合は作成
				if ( !file_exists( $master_path ) ) {
					// 存在しない場合

					// ディレクトリ作成
					if ( !mkdir( $master_path, 0777, true ) ) {
						// ディレクトリが作成できない場合

						// エラー処理
						throw new \Exception('Creation of master directory failed.');
					}
					
					// コマンドでディレクトリを作成する場合
					// exec('mkdir -p ' . $master_path . '2>&1', $output, $return_var);
				}

				// 「.git」フォルダが存在すれば初期化済みと判定
				if ( !file_exists( $master_path . "/.git") ) {
					// 存在しない場合

					// ディレクトリ移動
					if ( chdir( $master_path ) ) {

						// git セットアップ
						$command = 'git init';
						$this->execute($command, false);

						// git urlのセット
						$url = $this->options->git->protocol . "://" . urlencode($this->options->git->username) . ":" . urlencode($this->options->git->password) . "@" . $this->options->git->url;

						$command = 'git remote add origin ' . $url;
						$this->execute($command, false);

						// git fetch
						$command = 'git fetch origin';
						$this->execute($command, false);

						// git pull
						$command = 'git pull origin master';
						$this->execute($command, false);

					} else {
						// ディレクトリが存在しない場合

						// エラー処理
						throw new \Exception('master directory not found.');
					}
				}
			}

		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);
		return json_encode($result);
	}

	/**
	 * ブランチリストを取得
	 *	 
	 * @return 指定リポジトリ内のブランチリストを返す
	 */
	private function get_branch_list() {


		$this->debug_echo('■ get_branch_list start');

		$current_dir = realpath('.');
		// echo $current_dir;

		$output_array = array();
		$result = array('status' => true,
						'message' => '');

		try {

			if ( chdir( $this->options->git->repository )) {

				// fetch
				$command = 'git fetch';
				$this->execute($command, false);

				// ブランチの一覧取得
				$command = 'git branch -r';
				$ret = $this->execute($command, false);

				foreach ((array)$ret['output'] as $key => $value) {
					if( strpos($value, '/HEAD') !== false ){
						continue;
					}
					$output_array[] = trim($value);
				}

				$result['branch_list'] = $output_array;

			} else {
				// 指定リポジトリのディレクトリが存在しない場合

				// エラー処理
				throw new \Exception('Repository directory not found.');
			}

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		$result['status'] = true;

		chdir($current_dir);


		$this->debug_echo('■ get_branch_list end');
		return json_encode($result);

	}

	/**
	 * ステータスを画面表示用に変換し返却する
	 *	 
	 * @param $status = ステータスのコード値
	 *	 
	 * @return 画面表示用のステータス情報
	 */
	private function convert_status($status) {

		$ret = '';

		if ($status == 0) {
		
			$ret =  '？（公開前）';
		
		} else if ($status == 1) {
			
			$ret =  '★（公開中）';

		} else if ($status == 2) {
			
			$ret =  '〇（公開成功）';

		} else if ($status == 3) {
			
			$ret =  '△（警告あり）';
			
		} else if ($status == 4) {
			
			$ret =  '×（公開失敗）';
			
		}

		return $ret;
	}

	/**
	 * 配列を指定カラムでソートして返却する
	 *	 
	 * @param $array_list  = ソート対象の配列
	 * @param $sort_column = ソートするカラム
	 * @param $sort_kind   = ソートの種類（昇順、降順）
	 *	 
	 * @return ソート後の配列
	 */
	private function sort_list($array_list, $sort_column, $sort_kind) {

		if (!empty($array_list)) {

			$sort_array = array();

			foreach((array)$array_list as $key => $value) {
				$sort_array[$key] = $value[$sort_column];
			}

			// 公開予定日時の昇順へソート	
			array_multisort($sort_array, $sort_kind, $array_list);
		}

		return $array_list;
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
	 * 公開予約の最大件数チェック
	 *	 
	 * @param $data_list       = データリスト
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	private function check_reserve_max_record($data_list) {

		$ret = true;

		// TODO:定数化
		$max = 10;

		if ($max <= count($data_list)) {
			$ret = false;
		}

		return $ret;
	}

	/**
	 * 日付の妥当性チェック
	 *	 
	 * @param $reserve_date  = 公開予定日時
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	private function check_date($reserve_date) {

		$ret = true;

		// 日付の妥当性チェック
		list($Y, $m, $d) = explode('-', $reserve_date);

		if (!checkdate(intval($m), intval($d), intval($Y))) {
			$ret = false;
		}	

		return $ret;
	}

	/**
	 * 日付未来チェック
	 *	 
	 * @param $datetime       = 公開予定日時の日付
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	private function check_future_date($datetime) {

		$ret = true;

		// GMTの現在日時
		$now = gmdate(self::DATETIME_FORMAT);

		if (strtotime($now) > strtotime($datetime)) {
			$ret = false;
		}	

		return $ret;
	}


	/**
	 * ブランチ重複チェック
	 *	 
	 * @param $data_list       = データリスト
	 * @param $selected_branch = 選択されたブランチ
	 * @param $selected_id   = 変更ID
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	private function check_exist_branch($data_list, $selected_branch, $selected_id) {

		$ret = true;

		foreach ((array)$data_list as $array) {
			
			if (($array[self::WAITING_CSV_COLUMN_ID] != $selected_id) && ($array[self::WAITING_CSV_COLUMN_BRANCH] == $selected_branch)) {
				$ret = false;
				break;
			}
		}		

		return $ret;
	}

	/**
	 * 公開予定日時重複チェック
	 *	 
	 * @param $data_list     = データリスト
	 * @param $input_reserve = 入力された日時
	 * @param $selected_id   = 変更ID
	 *	 
	 * @return 
	 *  重複なし：true
	 *  重複あり：false
	 */
	private function check_exist_reserve($data_list, $input_reserve, $selected_id) {

		$ret = true;

		foreach ((array)$data_list as $array) {
			if (($array[self::WAITING_CSV_COLUMN_ID] != $selected_id) && ($array[self::WAITING_CSV_COLUMN_RESERVE] == $input_reserve)) {
				$ret = false;
				break;
			}
		}		

		return $ret;
	}


	/**
	 * 日付変換（Y-m-i H:i:s）
	 *	 
	 * @param $date = 日付
	 * @param $time = 時間
	 *	 
	 * @return 
	 *  一致する場合：selected（文字列）
	 *  一致しない場合：空文字
	 */
	private function combine_date_time($date, $time) {

		$ret = '';

		if (isset($date) && isset($time)) {

			$ret = $date . ' ' . date(self::TIME_FORMAT_HIS,  strtotime($time));
		}

		return $ret;
	}

	/**
	 * 新規ダイアログの表示
	 *	 
	 * @return 新規ダイアログの出力内容
	 */
	private function disp_add_dialog() {
		
		$this->debug_echo('■ disp_add_dialog start');

		$error_message = "";

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// ブランチリストを取得
		$get_branch_ret = json_decode($this->get_branch_list());
		$branch_list = $get_branch_ret->branch_list;

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(true, true, $error_message, $branch_list, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_add_dialog end');

		return $ret;
	}


	/**
	 * 新規ダイアログの戻り表示
	 *	 
	 * @param $error_message = エラーメッセージ出力内容
	 *
	 * @return 新規ダイアログの出力内容
	 */
	private function disp_back_add_dialog($error_message) {
		
		$this->debug_echo('■ disp_back_add_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_select_value)) {
			$branch_select_value = $this->options->_POST->branch_select_value;
		}
		if (isset($this->options->_POST->reserve_date)) {
			$reserve_date = $this->options->_POST->reserve_date;
		}
		if (isset($this->options->_POST->reserve_time)) {
			$reserve_time = $this->options->_POST->reserve_time;
		}
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}


		// // 画面入力された日付と時刻を結合し、指定タイムゾーンの時刻へ変換。その後、日付と時刻へ分割する。
		// $combine_reserve_time = $this->combine_date_time($this->options->_POST->reserve_date, $this->options->_POST->reserve_time);
		// $local_reserve_time = $this->convert_timezone_datetime($combine_reserve_time, self::DATETIME_FORMAT, self::PARAM_TIME_ZONE);	
		// $reserve_date = date(self::DATE_FORMAT_YMD, strtotime($local_reserve_time));
		// $reserve_time = date(self::TIME_FORMAT_HI, strtotime($local_reserve_time));


		// ブランチリストを取得
		$get_branch_ret = json_decode($this->get_branch_list());
		$branch_list = $get_branch_ret->branch_list;

		// 入力ダイアログHTMLの作成
		$ret = $this->create_dialog_html(true, false, $error_message, $branch_list, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_back_add_dialog end');

		return $ret;
	}


	/**
	 * 新規確認ダイアログの表示
	 *	 
	 * @return 新規確認ダイアログの出力内容
	 */
	private function disp_add_check_dialog() {
		
		$this->debug_echo('■ disp_add_check_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_select_value)) {
			$branch_select_value = $this->options->_POST->branch_select_value;
		}
		if (isset($this->options->_POST->reserve_date)) {
			$reserve_date = $this->options->_POST->reserve_date;
		}
		if (isset($this->options->_POST->reserve_time)) {
			$reserve_time = $this->options->_POST->reserve_time;
		}
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}

		// 新規確認ダイアログHTMLの作成
		$ret = $this->create_add_check_dialog_html($branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_add_check_dialog end');

		return $ret;
	}


	/**
	 * 変更ダイアログの表示
	 *	 
	 *
	 * @return 変更ダイアログの出力内容
	 */
	private function disp_update_dialog() {
		
		$this->debug_echo('■ disp_update_dialog start');

		$error_message = "";

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// // 選択されたID
		// $selected_id =  $this->options->_POST->selected_id;

		// 画面選択された公開予約情報を取得
		$selected_data = $this->get_selected_data();
		
		$this->debug_echo('　□ selected_data');
		$this->debug_var_dump($selected_data);
		$this->debug_echo('　');

		if ($selected_data) {

			$branch_select_value = $selected_data[self::WAITING_CSV_COLUMN_BRANCH];

			$reserve_date = date(self::DATE_FORMAT_YMD,  strtotime($selected_data[self::WAITING_CSV_COLUMN_RESERVE]));
			$reserve_time = date(self::TIME_FORMAT_HI,  strtotime($selected_data[self::WAITING_CSV_COLUMN_RESERVE]));

			$comment = $selected_data[self::WAITING_CSV_COLUMN_COMMENT];
		}

		// ブランチリストを取得
		$get_branch_ret = json_decode($this->get_branch_list());
		$branch_list = $get_branch_ret->branch_list;

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(false, true, $error_message, $branch_list, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_update_dialog end');

		return $ret;
	}


	/**
	 * 変更ダイアログの戻り表示
	 *	 
	 * @param $error_message  = エラーメッセージ出力内容
	 *
	 * @return 変更ダイアログの出力内容
	 */
	private function disp_back_update_dialog($error_message) {
		
		$this->debug_echo('■ disp_back_update_dialog start');

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_select_value)) {
			$branch_select_value = $this->options->_POST->branch_select_value;
		}
		if (isset($this->options->_POST->reserve_date)) {
			$reserve_date = $this->options->_POST->reserve_date;
		}
		if (isset($this->options->_POST->reserve_time)) {
			$reserve_time = $this->options->_POST->reserve_time;
		}
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}
	
		// ブランチリストを取得
		$get_branch_ret = json_decode($this->get_branch_list());
		$branch_list = $get_branch_ret->branch_list;

		// ダイアログHTMLの作成
		$ret = $this->create_dialog_html(false, false, $error_message, $branch_list, $branch_select_value, $reserve_date, $reserve_time, $comment);

		$this->debug_echo('■ disp_back_update_dialog end');

		return $ret;
	}

	/**
	 * 変更確認ダイアログの表示
	 *	 
	 * @return 変更確認ダイアログの出力内容
	 */
	private function disp_update_check_dialog() {
				
		// 変更確認ダイアログHTMLの作成
		$ret = $this->create_update_check_dialog_html();

		return $ret;
	}

	/**
	 * 新規・変更の入力ダイアログHTMLの作成
	 *	 
	 * @param $add_flg       = 新規フラグ
	 * @param $error_message = エラーメッセージ出力内容
	 * @param $branch_list   = ブランチリスト
	 * @param $branch_select_value = ブランチ選択値
	 * @param $reserve_date = 公開予定日時
	 * @param $reserve_time = 公開予定時間
	 * @param $comment      = コメント
	 * @param $selected_id  = 変更時の選択ID
	 *
	 * @return 
	 *  入力ダイアログ出力内容
	 */
	private function create_dialog_html($add_flg, $init_trans_flg, $error_message, $branch_list,
		$branch_select_value, $reserve_date, $reserve_time, $comment) {
		
		$this->debug_echo('■ create_dialog_html start');

		$ret = "";

		$ret .= '<div class="dialog" id="modal_dialog">'
			  . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			  . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			  . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			  . '<div class="dialog_box">';
		
		 if ($error_message != '') {
		 // エラーメッセージの出力
			$ret .= '<div class="alert_box">'
				. $error_message
				. '</div>';
		 }

		if ( $add_flg ) {
			$ret .= '<h4>新規</h4>';
		} else {
		  	$ret .= '<h4>変更</h4>';
		}
			   
		$ret .= '<form method="post">';

		// 変更モードの場合、変更前の値をhidden項目に保持させる
		if ( !$add_flg) {
			$ret .= $this->create_change_before_hidden_html($init_trans_flg);
		}

		$ret .= '<table class="table table-striped">'
			  . '<tr>'
			  . '<td class="dialog_thead">ブランチ</td>'
			  . '<td><select id="branch_list" class="form-control" name="branch_select_value">';

				foreach ((array)$branch_list as $branch) {
					$ret .= '<option value="' . htmlspecialchars($branch) . '" ' . $this->compare_to_selected_value($branch_select_value, $branch) . '>' . htmlspecialchars($branch) . '</option>';
				}

		$ret .= '</select></td>'
			  . '</tr>'
			  // . '<tr>'
			  // . '<td class="dialog_thead">コミット</td>'
			  // . '<td>' . 'dummy' . '</td>'
			  // . '</tr>'
			  . '<tr>'
			  . '<td class="dialog_thead">公開予定日時</td>'
			  . '<td scope="row"><span style="margin-right:10px;"><input type="text" id="datepicker" name="reserve_date" value="'. $reserve_date . '" autocomplete="off" /></span>'
			  . '<input type="time" id="reserve_time" name="reserve_time" value="'. $reserve_time . '" /></td>'
			  . '</tr>'
			  . '<tr>'
			  . '<td class="dialog_thead">コメント</td>'
			  . '<td><input type="text" id="comment" name="comment" size="50" value="' . htmlspecialchars($comment) . '" /></td>'
			  . '</tr>'
			  . '</tbody></table>'

			  . '<div class="button_contents_box">'
			  . '<div class="button_contents">'
			  . '<ul>';

		if ( $add_flg ) {
			$ret .= '<li><input type="submit" id="add_check_btn" name="add_check" class="px2-btn px2-btn--primary" value="確認"/></li>';
		} else {
		  	$ret .= '<li><input type="submit" id="update_check_btn" name="update_check" class="px2-btn px2-btn--primary" value="確認"/></li>';
		}

		$ret .= '<li><input type="submit" id="close_btn" class="px2-btn" value="キャンセル"/></li>'
			  . '</ul>'
			  . '</div>'
			  . '</div>'
			  . '</form>'
			  . '</div>'

			  . '</div>'
			  . '</div>'
			  . '</div></div>';

		$this->debug_echo('■ create_dialog_html end');

		return $ret;
	}

	/**
	 * 変更前hidden項目HTMLの作成
	 *	 
	 * @param $add_flg       = 新規フラグ
	 * @param $error_message = エラーメッセージ出力内容
	 * @param $branch_list   = ブランチリスト
	 * @param $branch_select_value = ブランチ選択値
	 * @param $reserve_date = 公開予定日時
	 * @param $reserve_time = 公開予定時間
	 * @param $comment      = コメント
	 * @param $selected_id  = 変更時の選択ID
	 *
	 * @return 
	 *  入力ダイアログ出力内容
	 */
	private function create_change_before_hidden_html($init_trans_flg) {
		
		$this->debug_echo('■ create_change_before_hidden_html start');

		$this->debug_echo('　□$init_trans_flg：' . $init_trans_flg);

		$selected_id = '';
		$branch_select_value = '';
		$reserve_date = '';
		$reserve_time = '';
		$comment = '';

		// 初期画面からの遷移の場合、CSVから変更の情報を取得する
		if ($init_trans_flg) {

			// 選択されたID
			$selected_id = $this->options->_POST->selected_id;
			// 選択されたIDに紐づく情報を取得
			$selected_ret = $this->get_selected_data();
			
			$this->debug_echo('　□selected_ret ：');
			$this->debug_echo($selected_ret);

			if ($selected_ret) {
				$branch_select_value = $selected_ret[self::WAITING_CSV_COLUMN_BRANCH];
				$reserve_date = date(self::DATE_FORMAT_YMD,  strtotime($selected_ret[self::WAITING_CSV_COLUMN_RESERVE]));
				$reserve_time = date(self::TIME_FORMAT_HI,  strtotime($selected_ret[self::WAITING_CSV_COLUMN_RESERVE]));
				$comment = $selected_ret[self::WAITING_CSV_COLUMN_COMMENT];
			}
			
		} else {

			$selected_id =  $this->options->_POST->selected_id;		
			$branch_select_value = $this->options->_POST->change_before_branch_select_value;
			$reserve_date = $this->options->_POST->change_before_reserve_date;
			$reserve_time = $this->options->_POST->change_before_reserve_time;
			$comment = $this->options->_POST->change_before_comment;
		}

		$ret = '<input type="hidden" name="selected_id" value="' . $selected_id . '"/>'
  			  . '<input type="hidden" name="change_before_branch_select_value" value="'. $branch_select_value . '"/>'
  			  . '<input type="hidden" name="change_before_reserve_date" value="'. $reserve_date . '"/>'
  			  . '<input type="hidden" name="change_before_reserve_time" value="'. $reserve_time . '"/>'
  			  . '<input type="hidden" name="change_before_comment" value="'. $comment . '"/>';

		$this->debug_echo('　□ret ：');
		$this->debug_echo($ret);

		$this->debug_echo('■ create_change_before_hidden_html end');

		return $ret;
	}


	/**
	 * 新規の出力確認ダイアログHTMLの作成
	 *	 
	 * @param $add_flg     = 新規フラグ
	 * @param $branch_select_value = ブランチ選択値
	 * @param $reserve_date = 公開予定日付
	 * @param $reserve_time = 公開予定時間
	 * @param $comment      = コメント
	 *
	 * @return 確認ダイアログ出力内容
	 */
	private function create_add_check_dialog_html($branch_select_value,
		$reserve_date, $reserve_time, $comment) {
		
		$this->debug_echo('■ create_add_check_dialog_html start');

		$ret = "";

		$ret .= '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>追加確認</h4>';

		$ret .= '<form method="post">'
			. '<table class="table table-striped">'
			. '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $branch_select_value
			. '<input type="hidden" name="branch_select_value" value="' . $branch_select_value . '"/>'
			. '</td>'
			. '</tr>'
			// . '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . '公開予定日時' . '</td>'
			. '<td>' . $reserve_date . ' ' . $reserve_time
			. '<input type="hidden" name="reserve_date" value="' . $reserve_date . '"/>'
			. '<input type="hidden" name="reserve_time" value="' . $reserve_time . '"/>'
			. '</td>'
			. '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . htmlspecialchars($comment) . '</td>'
			. '<input type="hidden" name="comment" value="' . htmlspecialchars($comment) . '"/>'
			. '</tr>'
			. '</tbody></table>'
			
			. '<div class="unit">'
			. '<div class="text-center">';

		$ret .= '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>';

		$ret .= '<li><input type="submit" id="confirm_btn" name="add_confirm" class="px2-btn px2-btn--primary" value="確定"/></li>'
			. '<li><input type="submit" id="back_btn" name="add_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'

			. '</div>'
			 . '</div>'

			. '</form>'
			 . '</div>'
			 . '</div></div></div>';

		$this->debug_echo('■ create_add_check_dialog_html end');

		return $ret;
	}


	/**
	 * 変更の出力確認ダイアログHTMLの作成（変更前後の比較有）
	 *
	 * @return 
	 *  確認ダイアログ出力内容
	 */
	private function create_update_check_dialog_html() {
		
		$this->debug_echo('■ create_update_check_dialog_html start');

		$img_filename = self::PATH_CREATE_DIR . self::IMG_ARROW_RIGHT;

		$ret = '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>変更確認</h4>'
			. '<form method="post">'
			. '<div class="colum_3">'
			. '<div class="left_box">';

		$ret .= '<input type="hidden" name="selected_id" value="' . $this->options->_POST->selected_id . '"/>'

			. '<input type="hidden" name="change_before_branch_select_value" value="' . $this->options->_POST->change_before_branch_select_value . '"/>'
			. '<input type="hidden" name="change_before_reserve_date" value="' . $this->options->_POST->change_before_reserve_date . '"/>'
			. '<input type="hidden" name="change_before_reserve_time" value="' . $this->options->_POST->change_before_reserve_time . '"/>' 
			. '<input type="hidden" name="change_before_comment" value="' . htmlspecialchars($this->options->_POST->change_before_comment) . '"/>'

			. '<input type="hidden" name="branch_select_value" value="' . $this->options->_POST->branch_select_value . '"/>'
			. '<input type="hidden" name="reserve_date" value="' . $this->options->_POST->reserve_date . '"/>'
			. '<input type="hidden" name="reserve_time" value="' . $this->options->_POST->reserve_time . '"/>'	 
			. '<input type="hidden" name="comment" value="' . htmlspecialchars($this->options->_POST->comment) . '"/>'

			. '<table class="table table-striped">'
			. '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $this->options->_POST->change_before_branch_select_value
			. '</td>'
			. '</tr>'
			// . '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . '公開予定日時' . '</td>'
			. '<td>' . $this->options->_POST->change_before_reserve_date . ' ' . $this->options->_POST->change_before_reserve_time
			. '</td>'
			. '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . htmlspecialchars($this->options->_POST->change_before_comment) . '</td>'
			. '</tr>'
			. '</tbody></table>'
			
		    . '</div>'

		    . '<div class="center_box">'
		    . '<img src="'. $img_filename .'"/>'
		    . '</div>'

            . '<div class="right_box">'
			. '<table class="table table-striped" style="width: 100%">'
			. '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . $this->options->_POST->branch_select_value
			. '</td>'
			. '</tr>'
			// . '<tr>'
			// . '<td class="dialog_thead">' . 'コミット' . '</td>'
			// . '<td>' . 'dummy' . '</td>'
			// . '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . '公開予定日時' . '</td>'
			. '<td>' . $this->options->_POST->reserve_date . ' ' . $this->options->_POST->reserve_time
			. '</td>'
			. '</tr>'
			. '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . htmlspecialchars($this->options->_POST->comment) . '</td>'
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

		$this->debug_echo('■ create_update_check_dialog_html end');

		return $ret;
	}


	/**
	 * 入力チェック処理
	 *	 
	 * @return 
	 *  エラーメッセージHTML
	 */
	private function do_check_validation($add_flg) {
				
		$this->debug_echo('■ do_check_validation start');

		$ret = "";

		$branch_select_value = "";
		$reserve_date = "";
		$reserve_time = "";
		$comment = "";
		$selected_id = "";

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->options->_POST->branch_select_value)) {
			$branch_select_value = $this->options->_POST->branch_select_value;
		}

		if (isset($this->options->_POST->reserve_date)) {
			$reserve_date = $this->options->_POST->reserve_date;
		}

		if (isset($this->options->_POST->reserve_time)) {
			$reserve_time = $this->options->_POST->reserve_time;
		}
		
		if (isset($this->options->_POST->comment)) {
			$comment = $this->options->_POST->comment;
		}

		if (isset($this->options->_POST->selected_id)) {
			$selected_id = $this->options->_POST->selected_id;
		}

		// CSVより公開予約の一覧を取得する
		$data_list = $this->get_csv_data_list(null);
	
		// 日時結合（画面表示日時）
		$combine_datetime = $this->combine_date_time($reserve_date, $reserve_time);


		if ($add_flg) {
			// 公開予約の最大件数チェック
			if (!$this->check_reserve_max_record($data_list)) {
				$ret .= '<p class="error_message">公開予約は最大' . $max . '件までの登録になります。</p>';
			}
		}

		// 日付の妥当性チェック
		if (!$this->check_date($reserve_date)) {
			$ret .= '<p class="error_message">「公開予定日時」の日付が有効ではありません。</p>';
		}

		// 未来の日付であるかチェック
		if (!$this->check_future_date($combine_datetime)) {
			$ret .= '<p class="error_message">「公開予定日時」は未来日時を設定してください。</p>';
		}

		// ブランチの重複チェック
		if (!$this->check_exist_branch($data_list, $branch_select_value, $selected_id)) {
			$ret .= '<p class="error_message">1つのブランチで複数の公開予定を作成することはできません。</p>';
		}

		// 公開予定日時の重複チェック
		if (!$this->check_exist_reserve($data_list, $combine_datetime, $selected_id)) {
			$ret .= '<p class="error_message">入力された日時はすでに公開予定が作成されています。</p>';
		}

		$this->debug_echo('■ do_check_validation end');

		return $ret;
	}

	/**
	 * 初期表示のコンテンツ作成
	 *	 
	 * @return 初期表示の出力内容
	 */
	private function create_top_contents() {
		
		$this->debug_echo('■ create_top_contents start');

		$ret = "";

		// CSVより公開予約の一覧を取得する
		$data_list = $this->get_csv_data_list(null);
		// 取得したリストをソートする
		$data_list = $this->sort_list($data_list, self::WAITING_CSV_COLUMN_RESERVE, SORT_ASC);

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
		// 			. $data[WAITING_CSV_COLUMN_RESERVE] . '　' . $data['content']
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
			. '<li><input type="submit" id="release_btn" name="release" class="px2-btn px2-btn--primary" value="即時公開"/></li>'
			. '<li><input type="submit" id="history_btn" name="history" class="px2-btn" value="履歴"/></li>'
			. '</ul>'
			// . '</div>'
			. '</div>';

		// テーブルヘッダー
		$ret .= '<div>'
		    . '<table name="list_tbl" class="table table-striped">'
			. '<thead>'
			. '<tr>'
			. '<th scope="row"></th>'
			. '<th scope="row">公開予定日時</th>'
			. '<th scope="row">コミット</th>'
			. '<th scope="row">ブランチ</th>'
			. '<th scope="row">コメント</th>'
			// . '<th>[*] id</th>'
			// . '<th>[*] 状態</th>'
			. '</tr>'
			. '</thead>'
			. '<tbody>';

		// テーブルデータリスト
		foreach ((array)$data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . $array[self::WAITING_CSV_COLUMN_ID] . '"/></td>'
				. '<td class="p-center">' . date(self::DATETIME_FORMAT_DISPLAY,  strtotime($array[self::WAITING_CSV_COLUMN_RESERVE])) . '</td>'
				. '<td class="p-center">' . $array[self::WAITING_CSV_COLUMN_COMMIT] . '</td>'
				. '<td class="p-center">' . $array[self::WAITING_CSV_COLUMN_BRANCH] . '</td>'
				. '<td>' . $array[self::WAITING_CSV_COLUMN_COMMENT] . '</td>'
				// . '<td>' . $array['id'] . '</td>'
				// . '<td>' . $this->convert_status($array['status']) . '</td>'
				. '</tr>';
		}

		$ret .= '</tbody></table>'
			. '</div>'
			. '</form>'
			. '</div>';

		$this->debug_echo('■ create_top_contents end');

		return $ret;
	}

	/**
	 * 履歴表示のコンテンツ作成
	 *	 
	 * @return 履歴表示の出力内容
	 */
	private function create_history_contents() {
		
		$this->debug_echo('■ create_history_contents start');

		$ret = "";

		// CSVより公開予約の一覧を取得する
		// （公開済みを見るように変更！！）
		$data_list = $this->get_released_csv_data_list(null);
		// 取得したリストをソートする
		$data_list = $this->sort_list($data_list, self::RELEASED_CSV_COLUMN_RESERVE, SORT_ASC);

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
				. '<th scope="row">公開完了日時</th>'
				. '<th scope="row">復元完了日時</th>'
				. '<th scope="row">公開予定日時</th>'
				. '<th scope="row">コミット</th>'
				. '<th scope="row">ブランチ</th>'
				. '<th scope="row">コメント</th>'
				. '</tr>'
				. '</thead>'
				. '<tbody>';

		// データリスト
		foreach ((array)$data_list as $array) {
			
			$released_datetime = '';
			if ($array[self::RELEASED_CSV_COLUMN_RELEASED]) {
				$released_datetime = date(self::DATETIME_FORMAT_DISPLAY,  strtotime($array[self::RELEASED_CSV_COLUMN_RELEASED]));
			}
			
			$restore_datetime = '';
			if ($array[self::RELEASED_CSV_COLUMN_RESTORE]) {
				$restore_datetime = date(self::DATETIME_FORMAT_DISPLAY,  strtotime($array[self::RELEASED_CSV_COLUMN_RESTORE]));
			}

			$reserve_datetime = '';
			if ($array[self::RELEASED_CSV_COLUMN_RESERVE]) {
				$reserve_datetime = date(self::DATETIME_FORMAT_DISPLAY,  strtotime($array[self::RELEASED_CSV_COLUMN_RESERVE]));
			}

			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . $array[self::RELEASED_CSV_COLUMN_ID] . '"/></td>'
				. '<td class="p-center">' . $released_datetime . '</td>'
				. '<td class="p-center">' . $restore_datetime . '</td>'
				. '<td class="p-center">' . $reserve_datetime . '</td>'
				. '<td class="p-center">' . $array[self::RELEASED_CSV_COLUMN_COMMIT] . '</td>'
				. '<td class="p-center">' . $array[self::RELEASED_CSV_COLUMN_BRANCH] . '</td>'
				. '<td>' . $array[self::RELEASED_CSV_COLUMN_COMMENT] . '</td>'
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
		
		$this->debug_echo('■ create_history_contents end');

		return $ret;
	}

	/**
	 * 
	 */
	public function run() {
	
		$this->debug_echo('■ run start');

		$this->debug_echo('　□カレントパス：' . realpath('.'));
		$this->debug_echo('　□__DIR__：' . __DIR__);

		$alert_message = '';

		// ダイアログの表示
		$dialog_disp = '';

		// 処理実行結果格納
		$ret = '';

		// 入力画面へ表示させるエラーメッセージ
		$error_message = '';

		// 初期表示画面から遷移されたか
		$init_trans_flg = false;
		// 追加処理か
		$add_flg = flase;

		//timezoneテスト ここから
		// date_default_timezone_set('Asia/Tokyo');

		// echo "--------------------------------</br>";
	
		$this->debug_echo('　□GMT：');
		$this->debug_echo(gmdate(DATE_ATOM, time()));

		$this->debug_echo('　□date：');
		$this->debug_echo(date(DATE_ATOM, time()));


		$t = new \DateTime(gmdate(DATE_ATOM, time()));
		$t->setTimeZone(new \DateTimeZone('Asia/Tokyo'));
		$this->debug_echo('　□日本時間：');
		$this->debug_echo($t->format(DATE_ATOM));


		// $t = new \DateTime(gmdate(DATE_ATOM, time()));
		// $t->setTimeZone(new \DateTimeZone('Asia/Tokyo'));

		// echo "gmtから日本時間へ：" . $t->format(DATE_ATOM). "</br>";


		// $t = new \DateTime($t->format(DATE_ATOM));
		// $t->setTimeZone(new \DateTimeZone('GMT'));

		// echo "日本時間からgmtへ：" . $t->format(DATE_ATOM). "</br></br>";

		// // タイムゾーンが取得できる！！！！
		// echo "タイムゾーン取得 ：" . date("e", date(DATE_ATOM, time())). "</br>";
		// 		echo "--------------------------------</br>";
		//timezoneテスト ここまで


		// gitのmaster情報取得
		$ret = json_decode($this->init());

		if ( !$ret->status ) {

			$alert_message = 'initialized faild';

		} else {
		
			// 初期化成功の場合

			// $combine_reserve_time = '';
			// $convert_reserve_time = '';

			// // 画面入力された日付と時刻を結合
			// $combine_reserve_time = $this->combine_date_time($this->options->_POST->reserve_date, $this->options->_POST->reserve_time);

			// // サーバのタイムゾーン日時へ変換
			// $convert_reserve_time = $this->convert_timezone_datetime($combine_reserve_time, self::DATETIME_FORMAT, self::PARAM_TIME_ZONE);
					
			// if ( is_null($combine_reserve_time) || !isset($combine_reserve_time) ) {
			// 	throw new \Exception("Combine date time failed.");
			// }


			/**
		 	* 新規処理
		 	*/
			// 初期表示画面の「新規」ボタン押下
			if (isset($this->options->_POST->add)) {

				// 新規（入力）ダイアログ画面へ遷移
				$dialog_disp = $this->disp_add_dialog();

			// 新規ダイアログの「確認」ボタン押下
			} elseif (isset($this->options->_POST->add_check)) {

				$add_flg = true;

				// 入力チェック処理
				$error_message = $this->do_check_validation($add_flg);

				if ($error_message != '') {
					// 入力ダイアログのまま
					$dialog_disp = $this->disp_back_add_dialog($error_message);
				} else {
					// 確認ダイアログへ遷移
					$dialog_disp = $this->disp_add_check_dialog();
				}
				
			// 新規ダイアログの「確定」ボタン
			} elseif (isset($this->options->_POST->add_confirm)) {

				// Gitファイルの取得
				$ret = json_decode($this->file_copy($combine_reserve_time));

				if ( !$ret->status ) {
					// 処理失敗
					$alert_message = 'add faild';
					break;

				} else {
					// 処理成功

					if ( is_null($combine_reserve_time) || !isset($combine_reserve_time) ) {
						throw new \Exception("Convert time zone failed.");
					}

					// CSV入力情報の追加
					$this->insert_list_csv_data($combine_reserve_time);

				}

			// 新規確認ダイアログの「戻る」ボタン押下
			} elseif (isset($this->options->_POST->add_back)) {
			
				$dialog_disp = $this->disp_back_add_dialog(null);


			/**
		 	* 変更処理
		 	*/
			// 初期表示画面の「変更」ボタン押下
			} elseif (isset($this->options->_POST->update)) {
			
				$init_trans_flg = true;

				// 「変更入力ダイアログ」画面へ遷移（エラーメッセージ＝空）
				$dialog_disp = $this->disp_update_dialog($init_trans_flg, $error_message);

			// 変更ダイアログの確認ボタンが押下された場合
			} elseif (isset($this->options->_POST->update_check)) {
			
				$error_message = $this->do_check_validation(false);

				if ($error_message != '') {

					// 入力チェックエラーがあった場合はそのままの画面
					$dialog_disp = $this->disp_update_dialog($init_trans_flg, $error_message);

				} else {

					// 入力チェックエラーがなかった場合は確認ダイアログへ遷移
					$dialog_disp = $this->disp_update_check_dialog();
				}	

			// 変更ダイアログの確定ボタンが押下された場合
			} elseif (isset($this->options->_POST->update_confirm)) {
				
				// GitファイルをWAITINGディレクトリへコピー（ディレクトリ名は入力された日付）
				$ret = json_decode($this->file_update($combine_reserve_time));
		
				if ( !$ret->status ) {
					// 処理失敗
					$alert_message = 'update faild';
					break;

				} else {
					// 処理成功

					// CSV入力情報の変更
					$this->update_list_csv_data($combine_reserve_time);

				}


			// 変更確認ダイアログの「戻る」ボタン押下
			} elseif (isset($this->options->_POST->update_back)) {
			
				$dialog_disp = $this->disp_update_dialog($init_trans_flg, null);


			/**
		 	* 削除処理
		 	*/
			// 初期表示画面の「削除」ボタン押下
			} elseif (isset($this->options->_POST->delete)) {
			
				// Gitファイルの削除
				$ret = json_decode($this->file_delete());

					if ( !$ret->status ) {
						
						$alert_message = 'delete faild';
						// 処理失敗
						break;

				} else {

					// CSV情報の削除
					$this->delete_list_csv_data();

				}

			/**
		 	* 即時公開処理
		 	*/
			// 初期表示画面の「即時公開」ボタン押下
			} elseif (isset($this->options->_POST->release)) {
			
				// 即時公開処理
				$this->manual_release();

			}
		}

		// // 画面表示
		$disp = '';  

		// 初期表示画面の「履歴」ボタン押下
		if (isset($this->options->_POST->history)) {
			// 履歴表示画面の表示
			$disp = $this->create_history_contents();
		} else {
			// 初期表示画面の表示
			$disp = $this->create_top_contents();
		}


		if ( !$ret->status ) {
			// 処理失敗の場合

			// エラーメッセージ表示
			$dialog_disp = '
			<script type="text/javascript">
				console.error("' . $ret->message . '");
				alert("' . $alert_message .'");
			</script>';
		}

		// 画面ロック用
		$disp_lock = '<div id="loader-bg"><div id="loading"></div></div>';	

		// 画面表示
		return $disp . $disp_lock . $dialog_disp;
	}

	// /**
	//  * CSVからお知らせリストを取得する
	//  *
	//  * @return お知らせリスト
	//  */
	// private function get_csv_alert_list()
	// {

	// 	$this->debug_echo('■ get_csv_alert_list start');

	// 	$ret_array = array();

	// 	// $filename = realpath('.') . $this->alert_filename;
	// 	$filename = $this->alert_filename;

	// 	if (!file_exists($filename)) {
	// 		$this->debug_echo('お知らせ一覧ファイルが存在しない');

	// 	} else {

	// 		// Open file
	// 		$handle = fopen( $filename, "r" );

	// 		$title_array = array();

	// 		$is_first = true;

	// 		// CSVリストをループ
	// 		while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

	// 			if($is_first){
	// 		        // タイトル行
	// 		        foreach ($rowData as $k => $v) {
	// 		        	$title_array[] = $v;
	// 		        }
	// 		        $is_first = false;
	// 		        continue;
	// 		    }
			    
	// 		    // タイトルと値の2次元配列作成
	// 		    $ret_array[] = array_combine ($title_array, $rowData) ;
	// 		}

	// 		// Close file
	// 		fclose($handle);

	// 	}

	// 	$this->debug_echo('■ get_csv_alert_list end');

	// 	return $ret_array;
	// }


	/**
	 * CSVからデータリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	private function get_csv_data_list($now) {

		$this->debug_echo('■ get_csv_data_list start');

		$ret_array = array();

		$filename = self::PATH_CREATE_DIR . self::CSV_WAITING_LIST_FILENAME;

		try {

			if (!file_exists($filename)) {
				
				// エラー処理
				throw new \Exception('waiting_list.csv not found.');

			} else {

				// Open file
				$handle = fopen( $filename, "r" );

				$is_first = true;

				// CSVリストをループ
				while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

					if($is_first){
				        $is_first = false;
				        continue;
				    }

				    if ($now) {
				    // 指定日時が設定されている

						$datetime = date(self::TIME_FORMAT_HIS,  strtotime($rowData[self::WAITING_CSV_COLUMN_RESERVE]));

					    // 指定日時より未来日時の場合
				    	if ($datetime && ($datetime > $now)) {
				    		continue;
				    	}
				    }

					$ret_array[] = $rowData;
				}

				// Close file
				fclose($handle);
			}

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";
			return $ret_array;
		}
		
		$this->debug_echo('■ get_csv_data_list end');

		return $ret_array;
	}

	/**
	 * 実施済みCSVからデータリストを取得する
	 *
	 * @param $now = 現在時刻
	 * @return データリスト
	 */
	private function get_released_csv_data_list($now) {

		$this->debug_echo('■ get_released_csv_data_list start');

		$ret_array = array();

		$filename = self::PATH_CREATE_DIR . self::CSV_RELEASED_LIST_FILENAME;

		try {

			if (!file_exists($filename)) {
				
				// エラー処理
				throw new \Exception('released_list.csv not found.');

			} else {

				// Open file
				$handle = fopen( $filename, "r" );

				$is_first = true;

				// CSVリストをループ
				while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

					if($is_first){
				        $is_first = false;
				        continue;
				    }

				  //   if ($now) {
				  //   // 指定日時が設定されている

						// $datetime = date(self::TIME_FORMAT_HIS,  strtotime($rowData[self::WAITING_CSV_COLUMN_RESERVE]));

					 //    // 指定日時より未来日時の場合
				  //   	if ($datetime && ($datetime > $now)) {
				  //   		continue;
				  //   	}
				  //   }

					$ret_array[] = $rowData;
				}

				// Close file
				fclose($handle);
			}

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";
			return $ret_array;
		}
		
		$this->debug_echo('■ get_released_csv_data_list end');

		return $ret_array;
	}


	/**
	 * CSVから選択された行の情報を取得する
	 *
	 * @return 選択行の情報
	 */
	private function get_selected_data() {

		$this->debug_echo('■ get_selected_data start');

		// $filename = realpath('.') . $this->list_filename;
		$filename = self::PATH_CREATE_DIR . self::CSV_WAITING_LIST_FILENAME;

		$selected_id =  $this->options->_POST->selected_id;

		$this->debug_echo('　□selected_id：' . $selected_id);

		$ret_array = array();

		try {

			if (!file_exists($filename) && !$selected_id) {
				$this->debug_echo('公開予約一覧ファイルが存在しない');
				throw new \Exception("Update data not found.");
			} else {

				$file = file($filename);

				// Open file
				$handle = fopen( $filename, "r" );
				
				$is_first = true;

				while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

					if($is_first){
				        $is_first = false;
				        continue;
				    }

					$num = intval($rowData[self::WAITING_CSV_COLUMN_ID]);

					$this->debug_echo('　★num：' . $num);
					$this->debug_echo('　★select_id：' . $selected_id);
					if ($num == $selected_id) {
					    $ret_array = $rowData;
					    break;
					}
				}


				// Close file
				fclose($handle);
			}

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";
			return $ret_array;
		}

		$this->debug_echo('　□ ret_array');
		$this->debug_var_dump($ret_array);
		// $this->debug_echo('　');

		$this->debug_echo('■ get_selected_data end');

		return $ret_array;
	}

	/**
	 * 公開予定一覧CSVへ行追加
	 *
	 * @return なし
	 */
	private function insert_list_csv_data($combine_reserve_time){

		$output = "";
		$result = array('status' => true,
						'message' => '');
	
		$this->debug_echo('■ insert_list_csv_data start');

		try {

			$filename = self::PATH_CREATE_DIR . self::CSV_WAITING_LIST_FILENAME;

			if (!file_exists($filename)) {
				$this->debug_echo('公開予約一覧ファイルが存在しない');

			} else {

				// Open file
				$handle_r = fopen( $filename, "r" );

				if ($handle_r === false) {
					// スロー処理！
					// throw new PHPExcel_Writer_Exception("Could not open file $pFilename for writing.");
				}

				$is_first = true;

				$max = 0;

				// Loop through each line of the file in turn
				while ($rowData = fgetcsv($handle_r, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

					if($is_first){
				        // タイトル行
				        $is_first = false;
				        continue;
				    }

				    $num = intval($rowData[self::WAITING_CSV_COLUMN_ID]);

				    if ($num > $max) {
						$max = $num;
					}
				}

				$max++;

				// Open file
				$handle = fopen( $filename, 'a+' );


				if ($handle === false) {
					// スロー処理！
					// throw new PHPExcel_Writer_Exception("Could not open file $pFilename for writing.");
				}

				// 現在時刻
				$now = date(self::DATETIME_FORMAT);

				$array[self::WAITING_CSV_COLUMN_ID] = $max;
				$array[self::WAITING_CSV_COLUMN_RESERVE] = $combine_reserve_time;
				$array[self::WAITING_CSV_COLUMN_BRANCH] = $this->options->_POST->branch_select_value;
				$array[self::WAITING_CSV_COLUMN_COMMIT] = $this->commit_hash;
				$array[self::WAITING_CSV_COLUMN_COMMENT] = $this->options->_POST->comment;
				$array[self::WAITING_CSV_COLUMN_SETTING] = $now;

				fputcsv( $handle, $array, self::CSV_DELIMITER, self::CSV_ENCLOSURE);

				fclose( $handle);
			}


			// Close file
			fclose($handle_r);

		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		$this->debug_echo('■ insert_list_csv_data end');

		return json_encode($result);
	}

	/**
	 * 公開予定一覧CSVへ変更処理（CSVへ行削除＆行追加）
	 *
	 * @return なし
	 */
	private function update_list_csv_data($combine_reserve_time) {

		$this->debug_echo('■ update_list_csv_data start');

		$output = "";
		$result = array('status' => true,
						'message' => '');
	
		// $filename = realpath('.') . $this->list_filename;
		$filename = self::PATH_CREATE_DIR . self::CSV_WAITING_LIST_FILENAME;

		$selected_id =  $this->options->_POST->selected_id;

		$this->debug_echo('　□$selected_id');
		$this->debug_echo($selected_id);

		try {

			if (!file_exists($filename) && !$selected_id) {
				$this->debug_echo('ファイルが存在しない、または、選択IDが不正です。');

			} else {

				$file = file($filename);

				// Open file
				$handle_r = fopen( $filename, "r" );
				
				if ($handle_r === false) {
					// スロー処理！
					$this->debug_echo('ファイルオープンエラー');
				}

				$cnt = 0;
				$max = 0;

				$is_first = true;

				// Loop through each line of the file in turn
				while ($rowData = fgetcsv($handle_r, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

					if($is_first){
				        // タイトル行は飛ばす
				        $is_first = false;
				        $cnt++;
				        continue;
				    }

				    // idカラムの値を取得
					$num = intval($rowData[self::WAITING_CSV_COLUMN_ID]);

					// 追加時のid値生成
				    if ($num > $max) {
						$max = $num;
					}

					// 変更対象となるid値の場合削除する
					if ($num == $selected_id) {
						unset($file[$cnt]);
						file_put_contents($filename, $file);
					}

					$cnt++;
				}

				$max++;

				// Open file
				$handle = fopen( $filename, 'a+' );

				if ($handle === false) {
					// スロー処理！
					// throw new PHPExcel_Writer_Exception("Could not open file $pFilename for writing.");
				}
				$this->debug_echo('　□4');
				// 現在時刻
				$now = date(self::DATETIME_FORMAT);

				$array[self::WAITING_CSV_COLUMN_ID] = $max;
				$array[self::WAITING_CSV_COLUMN_RESERVE] = $combine_reserve_time;
				$array[self::WAITING_CSV_COLUMN_BRANCH] = $this->options->_POST->branch_select_value;
				$array[self::WAITING_CSV_COLUMN_COMMIT] = $this->commit_hash;
				$array[self::WAITING_CSV_COLUMN_COMMENT] = $this->options->_POST->comment;
				$array[self::WAITING_CSV_COLUMN_SETTING] = $now;

				fputcsv( $handle, $array, self::CSV_DELIMITER, self::CSV_ENCLOSURE);
				fclose( $handle);
			}

			// Close file
			fclose($handle_r);

		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		$this->debug_echo('■ update_list_csv_data end');

		return json_encode($result);
	}

	/**
	 * 削除処理（CSVから行削除）
	 *
	 * @return なし
	 */
	private function delete_list_csv_data() {

		$this->debug_echo('■ delete_list_csv_data start');

		$result = array('status' => true,
						'message' => '');

		$filename = self::PATH_CREATE_DIR . self::CSV_WAITING_LIST_FILENAME;

		$selected_id =  $this->options->_POST->selected_id;

		try {


			if (!file_exists($filename))  {

				// エラー処理
				throw new \Exception('file not found.');
			
			} elseif (!$selected_id) {

				// エラー処理
				throw new \Exception('Select id is undefined.');

			} else {

				$file = file($filename);

				// Open file
				$handle = fopen( $filename, "r" );

				$cnt = 0;

				// Loop through each line of the file in turn
				while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

					$num = intval($rowData[self::WAITING_CSV_COLUMN_ID]);

					if ($num == $selected_id) {

						unset($file[$cnt]);
						file_put_contents($filename, $file);

						break;
					}

					$cnt++;
				}

				// Close file
				fclose($handle);
			}

		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;
		
		$this->debug_echo('■ delete_list_csv_data end');

		return json_encode($result);
	}

	/**
	 * 実施済み一覧CSVから選択された行の情報を取得する
	 *
	 * @return 選択行の情報
	 */
	private function get_released_selected_data($id) {

		$this->debug_echo('■ get_released_selected_data start');

		// $filename = realpath('.') . $this->list_filename;
		$filename = self::PATH_CREATE_DIR . self::CSV_RELEASED_LIST_FILENAME;

		$this->debug_echo('　□id：' . $id);

		$ret_array = array();

		try {

			if (!file_exists($filename) && !$id) {
				$this->debug_echo('実施済み一覧ファイルが存在しない');

			} else {

				$file = file($filename);

				// Open file
				$handle = fopen( $filename, "r" );
				
				// $title_array = array();

				$is_first = true;

				// Loop through each line of the file in turn
				while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

					if($is_first){
				        $is_first = false;
				        continue;
				    }

					$num = intval($rowData[self::RELEASED_CSV_COLUMN_ID]);

					if ($num == $id) {
					    // // タイトルと値の2次元配列作成
					    // $ret_array = array_combine ($title_array, $rowData) ;
					    $ret_array = $rowData;
					    break;
					}
				}

				// Close file
				fclose($handle);
			}

		} catch (\Exception $e) {

			echo "例外キャッチ：", $e->getMessage(), "\n";
			return $ret_array;
		}

		$this->debug_echo('　□ ret_array');
		$this->debug_var_dump($ret_array);
		$this->debug_echo('　');

		$this->debug_echo('■ get_released_selected_data end');

		return $ret_array;
	}

	/**
	 * 実施済み一覧CSVへ行追加
	 *
	 * @return なし
	 */
	private function insert_released_list_csv_data($selected_ret) {

		$this->debug_echo('■ insert_released_list_csv_data start');

		$output = "";
		$result = array('status' => true,
						'message' => '',
						'insert_id' => '');
	
		try {

			$filename = self::PATH_CREATE_DIR . self::CSV_RELEASED_LIST_FILENAME;

			if (!file_exists($filename)) {
				$this->debug_echo('実施済み一覧ファイルが存在しない');

			} else {

				// Open file
				$handle_r = fopen( $filename, "r" );

				if ($handle_r === false) {
					// スロー処理！
					// throw new PHPExcel_Writer_Exception("Could not open file $pFilename for writing.");
				}

				$is_first = true;

				$max = 0;

				// Loop through each line of the file in turn
				while ($rowData = fgetcsv($handle_r, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

					if($is_first){
				        // タイトル行
				        $is_first = false;
				        continue;
				    }

				    $num = intval($rowData[self::RELEASED_CSV_COLUMN_ID]);

				    if ($num > $max) {
						$max = $num;
					}
				}

				$max++;

				// Open file
				$handle = fopen( $filename, 'a+' );


				if ($handle === false) {
					// スロー処理！
					// throw new PHPExcel_Writer_Exception("Could not open file $pFilename for writing.");
				}

				$this->debug_echo('selected_ret');
				$this->debug_var_dump($selected_ret);
	
				// // 現在時刻
				// $now = date(self::DATETIME_FORMAT);

				$array[self::RELEASED_CSV_COLUMN_ID] = $max;
				$array[self::RELEASED_CSV_COLUMN_RESERVE] = $selected_ret[self::WAITING_CSV_COLUMN_RESERVE];
				$array[self::RELEASED_CSV_COLUMN_BRANCH] = $selected_ret[self::WAITING_CSV_COLUMN_BRANCH];
				$array[self::RELEASED_CSV_COLUMN_COMMIT] = $selected_ret[self::WAITING_CSV_COLUMN_COMMIT];
				$array[self::RELEASED_CSV_COLUMN_COMMENT] = $selected_ret[self::WAITING_CSV_COLUMN_COMMENT];
				$array[self::RELEASED_CSV_COLUMN_SETTING] = $selected_ret[self::WAITING_CSV_COLUMN_SETTING];

				$array[self::RELEASED_CSV_COLUMN_START] = '';
				$array[self::RELEASED_CSV_COLUMN_END] = '';
				$array[self::RELEASED_CSV_COLUMN_RELEASED] = '';
				$array[self::RELEASED_CSV_COLUMN_RESTORE] = '';
				$array[self::RELEASED_CSV_COLUMN_DIFF_FLG1] = '';
				$array[self::RELEASED_CSV_COLUMN_DIFF_FLG2] = '';
				$array[self::RELEASED_CSV_COLUMN_DIFF_FLG3] = '';

				fputcsv( $handle, $array, self::CSV_DELIMITER, self::CSV_ENCLOSURE);

				fclose( $handle);

				$result['insert_id'] = $max;
			}


			// Close file
			fclose($handle_r);

		} catch (\Exception $e) {

			// set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		$this->debug_echo('■ insert_released_list_csv_data end');

		return json_encode($result);
	}


	/**
	 * 実施済みCSVの変更処理（CSVへ行削除＆行追加）
	 *
	 * @return なし
	 */
	private function update_running_list_csv_data($start_datetime, $id) {

		$this->debug_echo('■ update_running_list_csv_data start');

		$output = "";
		$result = array('status' => true,
						'message' => '');
	
		$filename = self::PATH_CREATE_DIR . self::CSV_RELEASED_LIST_FILENAME;

		try {

			if (!file_exists($filename) && !$id) {
				$this->debug_echo('ファイルが存在しない、または、選択IDが不正です。');

			} else {


				$update_data = $this->get_released_selected_data($id);

				$file = file($filename);

				// Open file
				$handle_r = fopen( $filename, "r" );
				
				if ($handle_r === false) {
					// スロー処理！
					$this->debug_echo('ファイルオープンエラー');
				}

				$cnt = 0;
				// $max = 0;

				$is_first = true;


				// Loop through each line of the file in turn
				while ($rowData = fgetcsv($handle_r, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

					if($is_first){
				        // タイトル行は飛ばす
				        $is_first = false;
				        $cnt++;
				        continue;
				    }

				    // idカラムの値を取得
					$num = intval($rowData[self::RELEASED_CSV_COLUMN_ID]);

					// // 追加時のid値生成
				 //    if ($num > $max) {
					// 	$max = $num;
					// }

					// 変更対象となるid値の場合削除する
					if ($num == $id) {

						$this->debug_echo('　□update_data');
						$this->debug_var_dump($update_data);
			
						unset($file[$cnt]);
						file_put_contents($filename, $file);
					}

					$cnt++;
				}

				// $max++;

				// Open file
				$handle = fopen( $filename, 'a+' );

				if ($handle === false) {
					// スロー処理！
					// throw new PHPExcel_Writer_Exception("Could not open file $pFilename for writing.");
				}
				
				// 現在時刻
				$now = date(DATE_ATOM);

				$array[self::RELEASED_CSV_COLUMN_ID] = $update_data[self::RELEASED_CSV_COLUMN_ID];
				$array[self::RELEASED_CSV_COLUMN_RESERVE] = $update_data[self::RELEASED_CSV_COLUMN_RESERVE];
				$array[self::RELEASED_CSV_COLUMN_BRANCH] = $update_data[self::RELEASED_CSV_COLUMN_BRANCH];
				$array[self::RELEASED_CSV_COLUMN_COMMIT] = $update_data[self::RELEASED_CSV_COLUMN_COMMIT];
				$array[self::RELEASED_CSV_COLUMN_COMMENT] = $update_data[self::RELEASED_CSV_COLUMN_COMMENT];
				$array[self::RELEASED_CSV_COLUMN_SETTING] = $update_data[self::RELEASED_CSV_COLUMN_SETTING];

				$array[self::RELEASED_CSV_COLUMN_START] = $start_datetime;
				$array[self::RELEASED_CSV_COLUMN_END] = $now;
				$array[self::RELEASED_CSV_COLUMN_RELEASED] = $now;
				$array[self::RELEASED_CSV_COLUMN_RESTORE] = '';
				$array[self::RELEASED_CSV_COLUMN_DIFF_FLG1] = 0;
				$array[self::RELEASED_CSV_COLUMN_DIFF_FLG2] = 0;
				$array[self::RELEASED_CSV_COLUMN_DIFF_FLG3] = 0;

				fputcsv( $handle, $array, self::CSV_DELIMITER, self::CSV_ENCLOSURE);
				fclose( $handle);
			}

			// Close file
			fclose($handle_r);

		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;

		$this->debug_echo('■ update_running_list_csv_data end');

		return json_encode($result);
	}


	// /**
	//  * 実施済み一覧CSVの削除処理（CSVから行削除）
	//  *
	//  * @return なし
	//  */
	// private function delete_released_list_csv_data() {

	// 	$this->debug_echo('■ delete_list_csv_data start');

	// 	$result = array('status' => true,
	// 					'message' => '');

	// 	$filename = self::PATH_CREATE_DIR . self::CSV_RELEASED_LIST_FILENAME;

	// 	$selected_id =  $this->options->_POST->selected_id;

	// 	try {


	// 		if (!file_exists($filename))  {

	// 			// エラー処理
	// 			throw new \Exception('file not found.');
			
	// 		} elseif (!$selected_id) {

	// 			// エラー処理
	// 			throw new \Exception('Select id is undefined.');

	// 		} else {

	// 			$file = file($filename);

	// 			// Open file
	// 			$handle = fopen( $filename, "r" );
				
	// 			$cnt = 0;

	// 			// Loop through each line of the file in turn
	// 			while ($rowData = fgetcsv($handle, 0, self::CSV_DELIMITER, self::CSV_ENCLOSURE)) {

	// 				$num = intval($rowData[self::RELEASED_CSV_COLUMN_ID]);

	// 				if ($num == $selected_id) {

	// 					unset($file[$cnt]);
	// 					file_put_contents($filename, $file);

	// 					break;
	// 				}

	// 				$cnt++;
	// 			}

	// 		}

	// 		// Close file
	// 		fclose($handle);


	// 	} catch (\Exception $e) {

	// 		set_time_limit(30);

	// 		$result['status'] = false;
	// 		$result['message'] = $e->getMessage();

	// 		return json_encode($result);
	// 	}

	// 	set_time_limit(30);

	// 	$result['status'] = true;
		
	// 	$this->debug_echo('■ delete_list_csv_data end');

	// 	return json_encode($result);
	// }

	/**
	 * 新規追加時のGitファイルのコピー
	 *
	 * @return なし
	 */
	private function file_copy($combine_reserve_time) {

		$this->debug_echo('■ file_copy start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');
	
		// ディレクトリ名
		$dirname = date(self::DATETIME_FORMAT_SAVE, strtotime($combine_reserve_time));

		// 選択したブランチ
		$branch_name = trim(str_replace("origin/", "", $this->options->_POST->branch_select_value));

		try {

			// WAITINGディレクトリが存在しない場合は作成
			if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_WAITING) ) {

					// エラー処理
					throw new \Exception('Creation of Waiting directory failed.');
			}

			// WAITINGディレクトリへ移動
			if ( chdir(self::PATH_CREATE_DIR . self::PATH_WAITING) ) {

				// 公開予定ディレクトリをデリートインサート
				if ( !$this->is_exists_remkdir($dirname) ) {

					// エラー処理
					throw new \Exception('Creation of Waiting publish directory failed.');
				}

				// 公開予定ディレクトリへ移動
				if ( chdir($dirname) ) {

					// git init
					$command = 'git init';
					$this->execute($command, false);

					// git urlのセット
					$url = $this->options->git->protocol . "://" . urlencode($this->options->git->username) . ":" . urlencode($this->options->git->password) . "@" . $this->options->git->url;
					
					// initしたリポジトリに名前を付ける
					$command = 'git remote add origin ' . $url;
					$this->execute($command, false);

					// git fetch（リモートリポジトリの指定ブランチの情報をローカルブランチに取得）
					$command = 'git fetch origin' . ' ' . $branch_name;
					$this->execute($command, false);

					// git pull（）pullはリモート取得ブランチを任意のローカルブランチにマージするコマンド
					$command = 'git pull origin' . ' ' . $branch_name;
					$this->execute($command, false);
			
					// // 現在のブランチ取得
					// exec( 'git branch', $output);

					// コミットハッシュ値の取得
					$command = 'git rev-parse --short HEAD';
					$ret = $this->execute($command, false);

					foreach ( (array)$ret['output'] as $element ) {

						$this->commit_hash = $element;
					}

				} else {
					// WAITINGの公開予定ディレクトリが存在しない場合

					// エラー処理
					throw new \Exception('Waiting publish directory not found.');
				}

			} else {
				// WAITINGディレクトリが存在しない場合

				// エラー処理
				throw new \Exception('Waiting directory not found.');
			}

		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;
		
		chdir($current_dir);

		$this->debug_echo('■ file_copy end');

		return json_encode($result);

	}

	/**
	 * 変更時のGitファイルのチェックアウト
	 *
	 * @return なし
	 */
	private function file_update($combine_reserve_time) {
		
		$this->debug_echo('■ file_update start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		// 変更前の公開予定日時をフォーマット変換
		$before_dirname = date(self::DATETIME_FORMAT_SAVE, strtotime($this->combine_date_time($this->options->_POST->change_before_reserve_date, $this->options->_POST->change_before_reserve_time)));

		// // 変更前のWAITINGディレクトリパスを取得
		// $before_path = self::PATH_WAITING . $before_dir_name;

		// 今回作成するディレクトリ名
		$dirname = date(self::DATETIME_FORMAT_SAVE, strtotime($combine_reserve_time));

		// 選択したブランチ
		$branch_name_org = $this->options->_POST->branch_select_value;
		// 選択したブランチ（origin無し）
		$branch_name = trim(str_replace("origin/", "", $branch_name_org));

		try {

			// WAITINGディレクトリの公開予定ディレクトリの存在チェック
			if ( !file_exists(self::PATH_CREATE_DIR . self::PATH_WAITING . $before_dirname) ) {

				$this->debug_echo( '　□ $before_dirname：' . $before_dirname);
				throw new \Exception('Before publish directory not found.');
			}

			// ディレクトリ名が変更になる場合はリネームする
			if ($before_dirname != $dirname) {

				if ( file_exists( self::PATH_CREATE_DIR . self::PATH_WAITING . $before_dirname ) && !file_exists( self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname ) ){
					
					rename( self::PATH_CREATE_DIR . self::PATH_WAITING . $before_dirname, self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname );

				} else {
				// 名前変更前のディレクトリがない場合、または名前変更後のディレクトリが存在する場合は処理終了

					$this->debug_echo('　□ $before_dirname：' . $before_dirname);
					$this->debug_echo('　□ $dirname：' . $dirname);

					throw new \Exception('Waiting directory name could not be changed.');
				}
			}

			// 公開予定ディレクトリへ移動
			if ( chdir( self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname ) ) {

				// 現在のブランチ取得
				$command = 'git branch';
				$ret = $this->execute($command, false);

				$now_branch;
				$already_branch_checkout = false;
				foreach ( (array)$ret['output'] as $value ) {

					// 「*」の付いてるブランチを現在のブランチと判定
					if ( strpos($value, '*') !== false ) {

						$value = trim(str_replace("* ", "", $value));
						$now_branch = $value;

					} else {

						$value = trim($value);

					}

					// 選択された(切り替える)ブランチがブランチの一覧に含まれているか判定
					if ( $value == $branch_name ) {
						$already_branch_checkout = true;
					}
				}

				// git fetch
				$command = 'git fetch origin';
				$this->execute($command, false);

				// 現在のブランチと選択されたブランチが異なる場合は、ブランチを切り替える
				if ( $now_branch !== $branch_name ) {

					if ($already_branch_checkout) {
						// 選択された(切り替える)ブランチが既にチェックアウト済みの場合
						$command = 'git checkout ' . $branch_name;
						$this->execute($command, false);


					} else {
						// 選択された(切り替える)ブランチがまだチェックアウトされてない場合
						$command = 'git checkout -b ' . $branch_name . ' ' . $branch_name_org;
						$this->execute($command, false);

					}
				}

				// コミットハッシュ値の取得
				$command = 'git rev-parse --short HEAD';
				$ret = $this->execute($command, false);

				foreach ( (array)$ret['output'] as $element ) {

					$this->commit_hash = $element;
				}

			} else {

				throw new \Exception('Waiting publish directory not found.');
			}
		
		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->debug_echo('■ file_update end');

		return json_encode($result);

	}

	/**
	 * Gitファイルの削除
	 *
	 * @return なし
	 */
	private function file_delete() {
		
		$this->debug_echo('■ file_delete start');

		$current_dir = realpath('.');
		$this->debug_echo('　□current_dir：' . $current_dir);

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$selected_ret = $this->get_selected_data();

		$dirname = date(self::DATETIME_FORMAT_SAVE, strtotime($selected_ret[self::WAITING_CSV_COLUMN_RESERVE]));

		try {

			// WAITINGに公開予定ディレクトリが存在しない場合は無視する
			if( file_exists( self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname )) {
				
				// 削除
				$command = 'rm -rf '. self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname;
				$ret = $this->execute($command, false);

				if ( $ret['return'] !== 0 ) {
					$this->debug_echo('削除失敗');
					throw new \Exception('Delete directory failed.');
				}
			} else {
				$this->debug_echo('削除対象が存在しない');
			}
		
		} catch (\Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->debug_echo('■ file_delete end');

		return json_encode($result);

	}

	/**
	 * 即時公開
	 */
	private function manual_release() {

		$this->debug_echo('■ manual_release start');

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$project_real_path = '';

		// GMT取得
		// $start_datetime = gmdate(DATE_ATOM, time());
		$start_datetime = date(DATE_ATOM);

		try {

	 		// ▼ 未実装
			/**
	 		* 公開タスクロックの確認
			*/
	 		// 公開タスクロックを確認し、ロックが掛かっている場合は処理終了
	 		// ロックがかかっていない場合は、処理を進める

	 		// ※ 画面から、どの公開処理が実施中か確認できるようにしたい
	 		//    処理が異常終了した場合にロックが解除されないままとなってしまう


			/**
	 		* 公開予定一覧CSVの切り取り
			*/
			// 公開対象が存在するか

	 	// 	// クーロン用
	 	// 	$now = gmdate(self::DATETIME_FORMAT_SAVE);
			// // 公開予約の一覧を取得
			// $data_list = $this->get_csv_data_list($now);
			// foreach ( (array)$selected_ret as $data ) {
			// 	// 公開対象の公開予定日時（文字列）
			// 	$dirname = $this->get_datetime_str($selected_ret, self::WAITING_CSV_COLUMN_RESERVE, SORT_DESC);
			// }

	 		// 予定CSVより画面選択された行データを取得
			$selected_ret = $this->get_selected_data();

			$dirname = '';

			if ( $selected_ret ) {
				// 公開対象の公開予定日時（文字列）
				$dirname = date(self::DATETIME_FORMAT_SAVE, strtotime($selected_ret[self::WAITING_CSV_COLUMN_RESERVE]));
			}

			$this->debug_echo('　□公開予定ディレクトリ：');
			$this->debug_echo($dirname);
			$this->debug_echo('　□現在日時：');
			$this->debug_echo($start_datetime);

			// 公開対象が存在する場合
			if ($dirname) {

				// 　公開予定一覧CSVにファイルロックが掛かっていない場合
				// 　ファイルロックを掛ける
				// 　実施済み一覧CSVにファイルロックが掛かっていない場合
				// 　ファイルロックを掛ける
		 		// 公開対象の行を、実施済みへ切り取り移動する
		 		$this->debug_echo('　▼CSVの切り取り作業開始');
				
		 		// 実施済みCSVへインサート処理
				$ret = json_decode($this->insert_released_list_csv_data($selected_ret));

				if ( !$ret->status || !$ret->insert_id) {
					// 削除失敗
			
					// // エラーメッセージ
					// $dialog_disp = '
					// <script type="text/javascript">
					// 	console.error("' . $ret->message . '");
					// 	alert("publish faild");
					// </script>';

					throw new \Exception("Released csv insert failed.");

				}
				
				// 実施CSVへインサートしたID（最後のアップデートに使用する）
				$insert_id = $ret->insert_id;

				// $this->debug_echo('　□$insert_id：');
				// $this->debug_echo($insert_id);

				// 予定CSVへデリート処理
				$ret = json_decode($this->delete_list_csv_data());

				if ( !$ret->status ) {
				// 削除失敗
		
					// // エラーメッセージ
					// $dialog_disp = '
					// <script type="text/javascript">
					// 	console.error("' . $ret->message . '");
					// 	alert("publish faild");
					// </script>';

					throw new \Exception("Waiting csv delete failed.");
				}

				// TODO:未実装
		 		// ファイルロックを解除する

				// ログファイルディレクトリが存在しない場合は作成
				if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_LOG) ) {
					// エラー処理
					throw new \Exception('Creation of log directory failed.');
				} else {
					
					// ログファイル内の公開予定ディレクトリが存在しない場合は削除（存在する場合は作成しない）
					if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_LOG . $dirname) ) {

						// エラー処理
						throw new \Exception('Creation of log publish directory failed.');
					}
				}

				// バックアップディレクトリが存在しない場合は作成
				if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_BACKUP) ) {
					// エラー処理
					throw new \Exception('Creation of backup directory failed.');
				} else {
					
					// 公開予定ディレクトリをデリートインサート
					if ( !$this->is_exists_remkdir(self::PATH_CREATE_DIR . self::PATH_BACKUP . $dirname) ) {

						// エラー処理
						throw new \Exception('Creation of backup publish directory failed.');
					}
				}

				// runningディレクトリが存在しない場合は作成
				if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_RUNNING) ) {
					// エラー処理
					throw new \Exception('Creation of running directory failed.');
				} else {
					
					// 公開予定ディレクトリをデリートインサート
					if ( !$this->is_exists_remkdir(self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname) ) {

						// エラー処理
						throw new \Exception('Creation of running publish directory failed.');
					}
				}

				// releasedディレクトリが存在しない場合は作成
				if ( !$this->is_exists_mkdir(self::PATH_CREATE_DIR . self::PATH_RELEASED) ) {
					// エラー処理
					throw new \Exception('Creation of released directory failed.');
				} else {
					
					// 公開予定ディレクトリをデリートインサート
					if ( !$this->is_exists_remkdir(self::PATH_CREATE_DIR . self::PATH_RELEASED . $dirname) ) {

						// エラー処理
						throw new \Exception('Creation of released publish directory failed.');
					}
				}

				/**
		 		* 本番ソースを「backup」ディレクトリへコピー
				*/

				$this->debug_echo('　▼バックアップ処理開始');

				// バックアップの公開予定ディレクトリの存在確認
				if ( file_exists(self::PATH_CREATE_DIR . self::PATH_BACKUP . $dirname) ) {

					// 本番ソースからバックアップディレクトリへコピー

					// $honban_realpath = $current_dir . "/" . self::PATH_PROJECT_DIR;

					$this->debug_echo('　□カレントディレクトリ：');
					$this->debug_echo(realpath('.'));

					// TODO:ログフォルダに出力する
					$command = 'rsync -avzP ' . self::HONBAN_REALPATH  . '/' . ' ' . self::PATH_CREATE_DIR . self::PATH_BACKUP . $dirname . '/' . ' --log-file=' . self::PATH_CREATE_DIR . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

					$this->debug_echo('　□$command：');
					$this->debug_echo($command);

					$ret = $this->execute($command, true);

					$this->debug_echo('　▼本番バックアップの処理結果');

					foreach ( (array)$ret['output'] as $element ) {
						$this->debug_echo($element);
					}
				} else {
						// エラー処理
						throw new \Exception('Backup directory not found.');
				}


				/**
		 		* 公開予定ソースを「WAITING」ディレクトリから「running」ディレクトリへ移動
				*/
				// runningの公開予定ディレクトリの存在確認
				if ( file_exists(self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname) ) {

					// TODO:ログフォルダに出力する
					$command = 'rsync -avzP --remove-source-files ' . self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname . '/' . ' ' . self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname . '/' . ' --log-file=' . self::PATH_CREATE_DIR . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

					$this->debug_echo('　□$command：');
					$this->debug_echo($command);

					$ret = $this->execute($command, true);

					$this->debug_echo('　▼RUNNINGへの移動の処理結果');

					foreach ( (array)$ret['output'] as $element ) {
						$this->debug_echo($element);
					}


					// waitingの空ディレクトリを削除する
					$command = 'find ' .  self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname . '/ -type d -empty -delete' ;

					$this->debug_echo('　□$command：');
					$this->debug_echo($command);

					$ret = $this->execute($command, true);

					$this->debug_echo('　▼Waitingディレクトリの削除');

					foreach ( (array)$ret['output'] as $element ) {
						$this->debug_echo($element);
					}

					// waitingディレクトリの削除が成功していることを確認
					if ( file_exists(self::PATH_CREATE_DIR . self::PATH_WAITING . $dirname) ) {
						// ディレクトリが削除できていない場合
						// エラー処理
						throw new \Exception('Delete of waiting publish directory failed.');
					}
				} else {
						// エラー処理
						throw new \Exception('Waiting directory not found.');
				}

				/**
		 		* 「running」ディレクトリへ移動した公開予定ソースを本番環境へ同期
				*/
				// 本番ディレクトリの存在確認
				if ( file_exists(self::HONBAN_REALPATH) ) {

					// runningから本番ディレクトリへコピー

					// $honban_realpath = $current_dir . "/" . self::PATH_PROJECT_DIR;

					$this->debug_echo('　□カレントディレクトリ：');
					$this->debug_echo(realpath('.'));

					// TODO:ログフォルダに出力する
					// $command = 'rsync -avzP ' . self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname . '/' . ' ' . self::HONBAN_REALPATH . ' --log-file=' . self::PATH_CREATE_DIR . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

					// ★-aではエラーとなる。最低限の同期とする！所有者やグループは変更しない！
					// r ディレクトリを再帰的に調べる。
					// -l シンボリックリンクをリンクとして扱う
					// -p パーミッションも含める
					// -t 更新時刻などの時刻情報も含める
					// -o 所有者情報も含める
					// -g ファイルのグループ情報も含める
					// -D デバイスファイルはそのままデバイスとして扱う
					$command = 'rsync -rtvzP --delete ' . self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname . '/' . ' ' . self::HONBAN_REALPATH . '/' . ' ' . '--log-file=' . self::PATH_CREATE_DIR . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

					$this->debug_echo('　□$command：');
					$this->debug_echo($command);

					$ret = $this->execute($command, true);

					$this->debug_echo('　▼本番反映の処理結果');

					foreach ( (array)$ret['output'] as $element ) {
						$this->debug_echo($element);
					}

				} else {
						// エラー処理
						throw new \Exception('Running directory not found.');
				}

				/**
		 		* 同期が正常終了したら、公開済みソースを「running」ディレクトリから「released」ディレクトリへ移動
				*/
				// runningの公開予定ディレクトリの存在確認
				if ( file_exists(self::PATH_CREATE_DIR . self::PATH_RELEASED . $dirname) ) {

					// TODO:ログフォルダに出力する
					$command = 'rsync -avzP --remove-source-files ' . self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname . '/' . ' ' . self::PATH_CREATE_DIR . self::PATH_RELEASED . $dirname . '/' . ' --log-file=' . self::PATH_CREATE_DIR . self::PATH_LOG . $dirname . '/rsync_' . $dirname . '.log' ;

					$this->debug_echo('　□$command：');
					$this->debug_echo($command);

					$ret = $this->execute($command, true);

					$this->debug_echo('　▼REALEASEDへの移動の処理結果');

					foreach ( (array)$ret['output'] as $element ) {
						$this->debug_echo($element);
					}


					// runningの空ディレクトリを削除する
					$command = 'find ' .  self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname . '/ -type d -empty -delete' ;

					$this->debug_echo('　□$command：');
					$this->debug_echo($command);

					$ret = $this->execute($command, true);

					$this->debug_echo('　▼Runningディレクトリの削除');

					foreach ( (array)$ret['output'] as $element ) {
						$this->debug_echo($element);
					}

					// waitingディレクトリの削除が成功していることを確認
					if ( file_exists(self::PATH_CREATE_DIR . self::PATH_RUNNING . $dirname) ) {
						// ディレクトリが削除できていない場合
						// エラー処理
						throw new \Exception('Delete of waiting publish directory failed.');
					}
				} else {
						// エラー処理
						throw new \Exception('Running publish directory not found.');
				}


				$this->debug_echo('　▼実施CSV更新');
				
				/**
		 		* 処理結果をCSVへ書き込む
				*/
		 		$ret = $this->update_running_list_csv_data($start_datetime, $insert_id);

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

			$this->debug_echo('■ manual_release error end');

			chdir($current_dir);
			return json_encode($result);
		}

		// set_time_limit(30);

		$result['status'] = true;

		chdir($current_dir);

		$this->debug_echo('■ manual_release end');

		return json_encode($result);
	}


	/**
	 * 公開対象の公開予定日時をフォーマット指定して返却する
	 *	 
	 * @param $array_list  = ソート対象の配列
	 * @param $sort_column = ソートするカラム
	 * @param $sort_kind   = ソートの種類（昇順、降順）
	 *	 
	 * @return 公開予定日時
	 */
	private function get_datetime_str($array_list, $sort_column, $sort_kind) {

		$this->debug_echo('■ get_datetime_str start');

		$ret_str = '';
		$lead_array = array();

		if (!empty($array_list)) {

			$this->sort_list($array_list, $sort_name, $sort_kind);

			$lead_array = array_shift($array_list);
			
			// 先頭行の公開予約日時
			$ret_str = date(self::DATETIME_FORMAT_SAVE, strtotime($lead_array[self::WAITING_CSV_COLUMN_RESERVE]));
		}

		$this->debug_echo('　□return ：' . $ret_str);

		$this->debug_echo('■ get_datetime_str end');

		return $ret_str;
	}


	/**
	 * ディレクトリの存在チェックをし、存在しなかった場合は作成する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	private function is_exists_mkdir($dirname) {

		$this->debug_echo('■ is_exists_mkdir start');

		$ret = true;

		if ( !file_exists($dirname) ) {

			// デプロイ先のディレクトリを作成
			if ( !mkdir($dirname, 0777)) {

				$ret = false;
			}
		}

		$this->debug_echo('■ is_exists_mkdir end');

		return $ret;
	}

	/**
	 * ディレクトリの再作成
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	private function is_exists_remkdir($dirname) {
		
		$this->debug_echo('■ is_exists_remkdir start');

		if ( file_exists($dirname) ) {

			// 削除
			$command = 'rm -rf '. $dirname;
			$ret = $this->execute($command, true);

			if ( $ret['return'] !== 0 ) {
				$this->debug_echo('[既存ディレクトリ削除失敗]');
				return false;
			}
		}

		// デプロイ先のディレクトリを作成
		if ( !file_exists($dirname)) {
			if ( !mkdir($dirname, 0777) ) {
				$this->debug_echo('　□ [再作成失敗]$dirname：' . $dirname);
				return false;
			}
		} else {
			$this->debug_echo('　□ [既存ディレクトリが残っている]$dirname：' . $dirname);
			return false;
		}
	
		$this->debug_echo('■ is_exists_remkdir end');

		return true;
	}

	/**
	 * コマンド実行処理
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function execute($command, $captureStderr) {
	
		$this->debug_echo('■ execute start');

	    $output = array();
	    $return = 0;

	    // 標準出力とエラー出力を両方とも出力する
	    if ($captureStderr === true) {
	        $command .= ' 2>&1';
	    }

	    exec($command, $output, $return);

		$this->debug_echo('■ execute end');

	    return array('output' => $output, 'return' => $return);
	}


	/**
	 * 入力された日時をサーバのタイムゾーンの日時へ変換する
	 *	 
	 * @param $path = 作成ディレクトリ名
	 *	 
	 * @return ソート後の配列
	 */
	function convert_timezone_datetime($reserve_datetime, $format, $time_zone) {
	
		$this->debug_echo('■ convert_timezone_datetime start');

		// サーバのタイムゾーン取得
		$timezone = date_default_timezone_get();

		$t = new \DateTime($reserve_datetime, new \DateTimeZone($time_zone));

		// タイムゾーン変更
		$t->setTimeZone(new \DateTimeZone($timezone));
	
		$ret = $t->format($format);
		
		// $this->debug_echo('タイムゾーン：' . $timezone);

		$this->debug_echo('　□変換前の時刻：');
		$this->debug_echo($reserve_datetime);
		$this->debug_echo('　□変換後の時刻：');
		$this->debug_echo($t->format($format));
	
		$this->debug_echo('■ convert_timezone_datetime end');

	    return $ret;
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

