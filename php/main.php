<?php

namespace indigo;

/**
 * メイン実行クラス
 *
 * indigo呼び出しの際に最初に呼ばれるクラス。
 *
 * 画面呼び出しの場合は、run() を呼び出す。
 * クーロン呼び出しの場合は、cron_run() を呼び出す。
 *
 * [コンストラクタ処理]
 *  パラメタ取得
 *  通常ログ、エラーログ生成
 *  作業ディレクトリ生成
 *  タイムゾーン設定
 *  データベース接続
 *  テーブル作成
 *  GitのMaster情報取得
 */
class main
{

	/**
	 * オプション
	 * 
	 * _GET,
	 * _POST,
	 *   - HTTP GET, POSTパラメータ (省略可)
	 * additional_params,
	 *   - フォーム送信時に付加する追加のパラメータ (省略可)
	 * realpath_workdir,
	 *   - indigo作業用ディレクトリ（絶対パス）
	 * relativepath_resourcedir,
	 *   - リソースディレクトリ（ドキュメントルートからの相対パス）
	 * url_ajax_call,
	 *   - ajax呼出クラス（ドキュメントルートからの相対パス）
	 * time_zone,
	 *   - 画面表示上のタイムゾーン
	 * realpath_workdir,
	 *   - indigo作業用ディレクトリ（絶対パス）
	 * user_id,
	 *   - ユーザID（任意）
	 * space_name,
	 *   - 空間名（任意）
	 * db = array(
	 * 		string 'dbms',
	 *  		- db種類（'mysql' or null（nullの場合はSQLite3を使用））
	 * 		string 'prefix',
	 * 		string 'database',
	 * 		string 'host',
	 * 		string 'port',
	 * 		string 'username',
	 * 		string 'password'
	 *  		- mysql用の設定項目
	 * ),
	 * 
	 * max_reserve_record,
	 *   - 予定最大件数
	 * 
	 * max_backup_generation,
	 *   - バックアップ世代管理件数
	 *
	 * server = array(
	 * 	// サーバの数だけ用意する
	 * 	array(
	 * 		string 'name':
	 * 			- サーバ名(任意)
	 * 		string 'dist':
	 * 			- 同期先絶対パス
	 * 	)
	 * ),
	 * ignore = array(
	 * 			'例）.git',
	 * 			'例）.htaccess',
	 * 			'例）/common'
	 *  		- 同期除外のディレクトリ、またはファイル名
	 * ),
	 * git = array(
	 * 		string 'giturl':
	 * 			- Gitリポジトリのurl　		例) github.com/hk-r/px2-sample-project.git
	 * 		string 'username':
	 * 			- Gitリポジトリのユーザ名　	例) hoge
	 * 		string 'password':
	 * 			- Gitリポジトリのパスワード　	例) fuga
	 * )
	 */
	public $options;
	
	// オプション ユーザID（任意項目）
	public $user_id;

	// オプション 空間名（任意項目）
	public $space_name;


	/** tomk79\filesystem のインスタンス */
	private $fs;

	/** indigo\common のインスタンス */
	private $common;

	/** indigo\gitManager のインスタンス */
	private $gitMgr;

	/** indigo\pdoManager のインスタンス */
	private $pdoMgr;

	/** indigo\publish のインスタンス */
	private $publish;

	/** indigo\screen\initScreen のインスタンス */
	private $initScreen;

	/** indigo\screen\historyScreen のインスタンス */
	private $historyScn;

	/** indigo\screen\backupScreen のインスタンス */
	private $backupScn;


	/**
	 * indigo\pdoManager::connect() DBインスタンス
	 */
	public $dbh;

	/**
	 * 作業ディレクトリ 絶対パス格納配列
	 */
	public $realpath_array = array('realpath_server' => '',	// 本番環境
								'realpath_backup' => '',	// バックアップ本番ソース
								'realpath_waiting' => '',	// 予定待機Gitソース
								'realpath_running' => '',	// 処理中ソース
								'realpath_released' => '',	// 処理完了ソース
								'realpath_log' => '');		// ログ

	/** indigoログパス */
	public $log_path;

	/** indigo全体操作ログパス */
	public $process_log_path;

	/** indigoエラーログパス */
	public $error_log_path;

	/** パラメタチェック */
	public $param_check_flg = true;

	/**
	 * コンストラクタ
	 * @param array $options オプション
	 */
	public function __construct($options) {

		//============================================================
		// オブジェクト生成
		//============================================================	
		$this->options = \json_decode(\json_encode($options));
		if( !is_object($this->options) ){
			$this->options = json_decode('{}');
		}
		if( !property_exists($this->options, '_GET') ){
			$this->options->_GET = json_decode(json_encode($_GET));
		}
		if( !property_exists($this->options, '_POST') ){
			$this->options->_POST = json_decode(json_encode($_POST));
		}

		$this->fs = new \tomk79\filesystem(array(
		  'file_default_permission' => define::FILE_DEFAULT_PERMISSION,
		  'dir_default_pefrmission' => define::DIR_DEFAULT_PERMISSION,
		  'filesystem_encoding' 	=> define::FILESYSTEM_ENCODING
		));

		$this->common = new common($this);

		$this->gitMgr = new gitManager($this);
		$this->pdoMgr = new pdoManager($this);
		$this->publish = new publish($this);

		$this->initScn = new \indigo\screen\initScreen($this);
		$this->historyScn = new \indigo\screen\historyScreen($this);
		$this->backupScn = new \indigo\screen\backupScreen($this);


		// ログディレクトリの作成
		if (array_key_exists('realpath_workdir', $this->options) && $this->options->realpath_workdir ) {

			// logディレクトリの生成
			$current_dir = \realpath('.');
			$this->log_path = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_LOG));
			if (chdir($this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir)))) {
				// logファイルディレクトリが存在しない場合は作成
				$this->fs()->mkdir($this->log_path);
			}
			\chdir($current_dir);


			//============================================================
			// エラーログ出力登録
			//============================================================
			$this->error_log_path = $this->fs()->normalize_path($this->fs()->get_realpath($this->log_path . 'error.log'));

			//============================================================
			// 通常ログ出力登録
			//============================================================	
			$log_dirname = $this->common()->get_current_datetime_of_gmt("Ymd");
			$this->process_log_path = $this->log_path . 'log_process_' . $log_dirname . '.log';

		}

		//============================================================
		// オプション情報入力チェック（必須項目のみ）
		//============================================================	

		// 省略可能な値を補完
		if( !property_exists($this->options, 'db') ){
			$this->options->db = json_decode('{}');
		}
		if( !property_exists($this->options->db, 'dbms') ){
			$this->options->db->dbms = null;
		}
		if( !property_exists($this->options->db, 'prefix') ){
			$this->options->db->prefix = null;
		}

		// 設定値チェック
		if (!( property_exists($this->options, 'realpath_workdir') && $this->options->realpath_workdir)){
			$this->param_check_flg = false;
		}elseif( !( property_exists($this->options, 'relativepath_resourcedir') && $this->options->relativepath_resourcedir) ){
			$this->param_check_flg = false;
		}elseif( !( property_exists($this->options, 'url_ajax_call') && $this->options->url_ajax_call) ){
			$this->param_check_flg = false;
		}elseif( !( property_exists($this->options, 'time_zone') && $this->options->time_zone) ){
			$this->param_check_flg = false;
		}elseif( !( property_exists($this->options, 'db') && $this->options->db) ){
			$this->param_check_flg = false;
		}elseif( !( property_exists($this->options->db, 'dbms')) ){ // ← null OK
			$this->param_check_flg = false;
		}elseif( !( property_exists($this->options, 'max_reserve_record') && $this->options->max_reserve_record) ){
			$this->param_check_flg = false;
		}elseif( !( property_exists($this->options, 'server') && $this->options->server) ){
			$this->param_check_flg = false;
		}elseif( !( property_exists($this->options->server[0], 'dist') && $this->options->server[0]->dist) ){
			$this->param_check_flg = false;
		}elseif( !( property_exists($this->options, 'git') && $this->options->git) ){
			$this->param_check_flg = false;
		}elseif( !( property_exists($this->options->git, 'giturl') && $this->options->git->giturl) ){
			$this->param_check_flg = false;

		} else {
			
			//============================================================
			// 作業ディレクトリ絶対パス格納
			//============================================================
		
			// backupディレクトリの絶対パスを取得。
			$this->realpath_array['realpath_backup'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_BACKUP));

			// waitingディレクトリの絶対パスを取得。
			$this->realpath_array['realpath_waiting'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_WAITING));

			// runningディレクトリの絶対パスを取得。
			$this->realpath_array['realpath_running'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_RUNNING));

			// releasedディレクトリの絶対パスを取得。
			$this->realpath_array['realpath_released'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_RELEASED));

			// logディレクトリの絶対パスを取得。
			$this->realpath_array['realpath_log'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_LOG));

			// Ajax API ログパス
			$this->realpath_array['realpath_ajax_log_path'] = $this->fs->normalize_path($this->fs->get_realpath($this->options->realpath_workdir . define::PATH_LOG)) . 'log_ajax_' . gmdate("Ymd", time()) . '.log';

			//============================================================
			// 作業ディレクトリ作成
			//============================================================
			$current_dir = \realpath('.');
			if (\chdir($this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir)))) {

				// backupディレクトリが存在しない場合は作成
				$this->fs()->mkdir($this->realpath_array['realpath_backup']);
				// waitingディレクトリが存在しない場合は作成
				$this->fs()->mkdir($this->realpath_array['realpath_waiting']);
				// runningディレクトリが存在しない場合は作成
				$this->fs()->mkdir($this->realpath_array['realpath_running']);
				// releasedディレクトリが存在しない場合は作成
				$this->fs()->mkdir($this->realpath_array['realpath_released']);

			} else {
				// ディレクトリ移動に失敗
				\chdir($current_dir);
				throw new \Exception('Move to indigo work directory failed.');
			}
			\chdir($current_dir);

			// 本番環境ディレクトリの絶対パスを取得。（配列1番目のサーバを設定）
			foreach ( (array)$this->options->server as $server ) {
				$this->realpath_array['realpath_server'] = $this->fs()->normalize_path($this->fs()->get_realpath($server->dist . "/"));
				break; // 現時点では最初の1つのみ有効なのですぐに抜ける
			}

			//============================================================
			// タイムゾーンの設定
			//============================================================
			// cron実行の場合は、タイムゾーンパラメタは存在しないので設定無し
			$time_zone = $this->options->time_zone;
			if (!$time_zone) {
				throw new \Exception('Parameter of timezone not found.');
			}
			\date_default_timezone_set($time_zone);

			$logstr = "設定タイムゾーン：" . $time_zone;
			$this->common()->put_process_log_block($logstr);


			//============================================================
			// データベース接続
			//============================================================
			$this->dbh = $this->pdoMgr->connect();


			//============================================================
			// テーブル作成（作成済みの場合はスキップ）
			//============================================================
			$this->pdoMgr->create_table();
			

			//============================================================
			// Gitのmaster情報取得
			//============================================================
			$this->gitMgr->get_git_master($this->options);
		}

		//============================================================
		// オプションの任意項目
		//============================================================
		if (\array_key_exists('user_id', $this->options)) {
			$this->user_id = $this->options->user_id;
		}
		if (\array_key_exists('space_name', $this->options)) {
			$this->space_name = $this->options->space_name;
		}
	}

	/**
	 * 追加のパラメータを取得する
	 */
	public function get_additional_params(){
		if( !property_exists($this->options, 'additional_params') ){
			return '';
		}
		$params = json_decode(json_encode($this->options->additional_params), true);
		$rtn = '';
		foreach($params as $key=>$value){
			$rtn .= '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'" />';
		}
		return $rtn;
	}

	/**
	 * master作業ディレクトリのパスを取得する
	 * TODO: このメソッドは ajax.php と重複している。1ヶ所にまとめたい。
	 */
	public function get_master_repository_dir(){
		$master_real_path = $this->fs->normalize_path( $this->fs->get_realpath( $this->options->realpath_workdir . define::PATH_MASTER ) );
		if( property_exists($this->options, 'realpath_git_master_dir') && strlen( $this->options->realpath_git_master_dir ) && is_dir( $this->options->realpath_git_master_dir ) ){
			$master_real_path = $this->fs->normalize_path( $this->fs->get_realpath( $this->options->realpath_git_master_dir ) );
		}
		return $master_real_path;
	}

	/**
	 * 実行する
	 *
	 * ボタンイベントのname値を検知し、別クラスへ記載されている処理を呼び出す。
	 * 各処理でエラーがキャッチされた場合は、$resultへ結果が格納されており、アラートメッセージの表示、エラーログへの書き込みを行う。
	 * 例外がスローされてきた場合は、こちらでキャッチし、エラーログへの書き込みを行う。
	 *
	 * @return string HTMLソースコード
	 */
	public function run() {

		// 画面表示
		$disp = '';  

		// エラーメッセージ表示
		$alert_message = '';

		// ダイアログの表示
		$dialog_html = '';
		
		// 画面ロック用
		$disp_lock = '';

		// 処理実行結果格納
		$result = array('status' => true,
						'message' => '',
					  	'dialog_html' => ''
				);
	
		try {

			if (!$this->param_check_flg) {
				throw new \ErrorException('パラメタが不足しています。');
			}

			$this->common()->put_process_log(__METHOD__, __LINE__, "■ run start");

	
			//============================================================
			// 新規関連処理
			//============================================================
			if (isset($this->options->_POST->add)) {
				// 初期表示画面の「新規」ボタン押下

				$this->common()->put_process_log(__METHOD__, __LINE__, "==========初期表示画面の「新規」ボタン押下==========");
				$dialog_html = $this->initScn->do_disp_add_dialog();

			} elseif (isset($this->options->_POST->add_check)) {
				// 新規ダイアログの「確認」ボタン押下

				$this->common()->put_process_log(__METHOD__, __LINE__, "==========新規ダイアログの「確認」ボタン押下==========");
				$dialog_html = $this->initScn->do_check_add();

			} elseif (isset($this->options->_POST->add_confirm)) {
				// 新規確認ダイアログの「確定」ボタン押下
		
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========新規ダイアログの「確定」ボタン押下==========");
				$result = $this->initScn->do_confirm_add();	
				$alert_message = $result['message'];
				$dialog_html   = $result['dialog_html'];

			} elseif (isset($this->options->_POST->add_back)) {
				// 新規確認ダイアログの「戻る」ボタン押下
								
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========新規ダイアログの「戻る」ボタン押下==========");
				$dialog_html = $this->initScn->do_back_add_dialog();

			//============================================================
			// 変更関連処理
			//============================================================
			} elseif (isset($this->options->_POST->update)) {
				// 初期表示画面の「変更」ボタン押下
							
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========初期表示画面の「変更」ボタン押下==========");
				$dialog_html = $this->initScn->do_disp_update_dialog();

			} elseif (isset($this->options->_POST->update_check)) {
				// 変更ダイアログの「確認」ボタン押下
				
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========変更ダイアログの「確認」ボタン押下==========");
				$dialog_html = $this->initScn->do_check_update();

			} elseif (isset($this->options->_POST->update_confirm)) {
				// 変更確認ダイアログの「確定」ボタン押下
			
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========変更ダイアログの「確定」ボタン押下==========");
				$result = $this->initScn->do_confirm_update();	
				$alert_message = $result['message'];
				$dialog_html   = $result['dialog_html'];

			} elseif (isset($this->options->_POST->update_back)) {
				// 変更確認ダイアログの「戻る」ボタン押下
				
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========変更ダイアログの「戻る」ボタン押下==========");
				$dialog_html = $this->initScn->do_back_update_dialog();


			//============================================================
			// 削除処理
			//============================================================
			} elseif (isset($this->options->_POST->delete)) {
				// 初期表示画面の「削除」ボタン押下
			
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========初期表示画面の「削除」ボタン押下==========");
				$result = $this->initScn->do_delete();
				$alert_message = $result['message'];

			//============================================================
			// 手動復元処理
			//============================================================
			} elseif (isset($this->options->_POST->restore)) {
				// バックアップ一覧画面の「復元ボタン押下
		
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========バックアップ一覧画面の「復元」ボタン押下==========");
				$result = $this->publish->exec_publish(define::PUBLISH_TYPE_MANUAL_RESTORE, null);

				// 画面アラート用のメッセージ
				$alert_message = "≪手動復元公開処理≫" . $result['message'];

				if ( !$result['status'] ) {
					// 処理失敗の場合、復元処理

					$this->common()->put_process_log(__METHOD__, __LINE__, "** 手動復元公開処理エラー終了 **" . $result['message']);

					if ($result['backup_id']) {
						// バックアップが作成されている場合
						$this->common()->put_process_log(__METHOD__, __LINE__, "==========自動復元処理の呼び出し==========");
						$result = $this->publish->exec_publish(define::PUBLISH_TYPE_AUTO_RESTORE, $result['output_id']);

						// 画面アラート用のメッセージ
						$alert_message .= "≪自動復元公開処理≫" . $result['message'];

						if ( !$result['status'] ) {
							// 処理失敗の場合、復元処理
							
							$this->common()->put_process_log(__METHOD__, __LINE__, "** 自動復元公開処理エラー終了 **" . $result['message']);
						}
					}
				}

			//============================================================
			// 即時公開処理
			//============================================================
			} elseif (isset($this->options->_POST->immediate)) {
				// 初期表示画面の「即時公開」ボタン押下

				$this->common()->put_process_log(__METHOD__, __LINE__, "==========初期表示画面の「即時公開」ボタン押下==========");
				$dialog_html = $this->initScn->do_disp_immediate_dialog();

			} elseif (isset($this->options->_POST->immediate_check)) {
				// 即時公開ダイアログの「確認」ボタン押下
				
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========即時公開入力ダイアログの「確認」ボタン押下==========");
				$dialog_html = $this->initScn->do_check_immediate();

			} elseif (isset($this->options->_POST->immediate_confirm)) {
				// 即時公開確認ダイアログの「確定」ボタン押下
				
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========即時公開確認ダイアログの「確定」ボタン押下==========");
				$result = $this->initScn->do_immediate_publish();

				// 画面アラート用のメッセージ
				$alert_message = "≪即時公開処理≫" . $result['message'];
				$dialog_html   = $result['dialog_html'];

				if ( !$result['status'] ) {
					// 処理失敗の場合、復元処理

					$this->common()->put_process_log(__METHOD__, __LINE__, "** 即時公開処理エラー終了 **" . $result['message']);

					if ($result['backup_id']) {
						// バックアップが作成されている場合
						$this->common()->put_process_log(__METHOD__, __LINE__, "==========自動復元処理の呼び出し==========");
						$result = $this->publish->exec_publish(define::PUBLISH_TYPE_AUTO_RESTORE, $result['output_id']);

						// 画面アラート用のメッセージ
						$alert_message .= "≪自動復元公開処理≫" . $result['message'];

						if ( !$result['status'] ) {
							// 処理失敗の場合、復元処理
							
							$this->common()->put_process_log(__METHOD__, __LINE__, "** 自動復元公開処理エラー終了 **" . $result['message']);
						}
					}



				}

			} elseif (isset($this->options->_POST->immediate_back)) {
				// 即時公開確認ダイアログの「戻る」ボタン押下

				$this->common()->put_process_log(__METHOD__, __LINE__, "==========即時公開確認ダイアログの「戻る」ボタン押下==========");
				$dialog_html = $this->initScn->do_back_immediate_dialog();

			//============================================================
			// ログ表示処理
			//============================================================
			} elseif (isset($this->options->_POST->log)) {
				// 履歴表示画面の「新規」ボタン押下
				
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========履歴表示画面の「ログ」ボタン押下==========");
				$dialog_html = $this->historyScn->do_disp_log_dialog();
			}

			if ( $alert_message ) {
				// 処理失敗の場合

				// $logstr = "**********************************************************************************" . "\r\n";
				// $logstr .= " ステータスエラー " . "\r\n";
				// $logstr .= "**********************************************************************************";
				// $this->common()->put_process_log_block($logstr);

				$logstr = "[アラートメッセージ]" . $alert_message;
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

				// エラーメッセージ表示
				$dialog_html = '
				<script type="text/javascript">
					alert("'.  $alert_message . '");
				</script>';

			} else {

				if (array_key_exists('dialog_html', $result) && $result['dialog_html']) {
					$dialog_html = $result['dialog_html'];	
				}
			}

			if (isset($this->options->_POST->history) ||
				isset($this->options->_POST->log)) {
				// 初期表示画面の「履歴」ボタン押下
				
				$logstr = "==========履歴画面の表示==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$disp = $this->historyScn->disp_history_screen();

			} elseif (isset($this->options->_POST->backup)) {
				// 初期表示画面の「バックアップ一覧」ボタン押下

				$logstr = "==========バックアップ一覧画面の表示==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$disp = $this->backupScn->disp_backup_screen();
				
			} else {
				// 初期表示画面の表示

				$logstr = "==========初期表示画面の表示==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$disp = $this->initScn->do_disp_init_screen();
 
			}

			// 画面ロック用
			$disp_lock = '<div id="loader-bg"><div id="loading"></div></div>';

		} catch (\ErrorException $e) {

			$alert_title = "エラーが発生しました。";

			// エラーメッセージ表示
			$dialog_html = '<div><h3>'.  $alert_title . '</h3>';

			if (\file_exists($this->log_path)) {
				$logstr =  "***** ERROR *****" . "\r\n";
				$logstr .= "[ErrorException]" . "\r\n";
				$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
				$logstr .= "Error message:" . $e->getMessage() . "\r\n";
				$this->common()->put_error_log($logstr);
			} else {
				$alert_line = $e->getFile() . " in " . $e->getLine();
				$alert_message = "Error message:" . $e->getMessage();
				$dialog_html .= '<p>'.  $alert_line . '</p>';
				$dialog_html .= '<p>'.  $alert_message . '</p></div>';
			}

			return $dialog_html;

		} catch (\Exception $e) {

			$alert_title = "例外エラーが発生しました。";

			$logstr = "** run() Exception caught **" . "\r\n";
			$logstr .= $e->getMessage() . "\r\n";
			$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			$logstr =  "***** EXCEPTION ERROR *****" . "\r\n";
			$logstr .= "[Exception]" . "\r\n";
			$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
			$logstr .= "Error message:" . $e->getMessage() . "\r\n";
			$this->common()->put_error_log($logstr);

			// エラーメッセージ表示
			$dialog_html = '<h3>'.  $alert_title . '</h3>';

			return $dialog_html;

		}
		
		// データベース接続を閉じる
		$this->pdoMgr->close();

		$this->common()->put_process_log(__METHOD__, __LINE__, "■ run() end");

		// 画面表示
		return $disp . $disp_lock . $dialog_html;
	}

	/**
	 * Ajax API 処理を実行する
	 */
	public function ajax_run(){
		return $this->get_commit_hash();
	}

	/**
	 * Gitブランチのコミットハッシュ値を取得
	 *
	 * @return json json_encode($ret) コミットハッシュ値(json変換)
	 * 
	 * @throws Exception ブランチ名、作業ディレクトリ名パラメタがGETから取得できなかった場合
	 * @throws Exception masterブランチディレクトリへの移動に失敗した場合
	 */
	private function get_commit_hash() {

		$commit_hash;

		$ret = array(
			'commit_hash' => ''
		);

		$current_dir = realpath('.');

		if (isset($this->options->_POST->branch_name) && isset($this->options->realpath_workdir)) {

			// masterディレクトリの絶対パス
			$master_real_path = $this->get_master_repository_dir();

			if ( $master_real_path ) {

				if ( \chdir( $master_real_path ) ) {

					// コミットハッシュ値取得
					$command = 'git log --pretty=%h ' . define::GIT_REMOTE_NAME . '/' . $this->options->_POST->branch_name . ' -1';
					 
					$this->put_ajax_log($command);

					\exec($command, $output, $return);
					foreach ( (array)$output as $data ) {
						$commit_hash = $data;
					}

				} else {

					$this->put_ajax_log("Error. Move to work directory failed.");

					// ディレクトリ移動に失敗
					throw new \Exception('Failed to get git commitHash.');
				} 
			}
		} else {

			$this->put_ajax_log("Error. Parameter not found.");

			// ディレクトリ移動に失敗
			throw new \Exception('Failed to get git commitHash.');
		} 
		
		if ($commit_hash) {
			$ret['commit_hash'] = $commit_hash;
		}
		
		\chdir($current_dir);

		\header('Content-Type: application/json; charset=utf-8');

		$this->put_ajax_log("【commit hash】 " . $commit_hash);

		return \json_encode($ret);
	}

	/**
	 * ajax用のログ書き込み
	 *
	 * @param string $text 出力文字列
	 * 
	 * @return 成功した場合に TRUE を、失敗した場合に FALSEを返却
	 */
	private function put_ajax_log($text){
		
		$datetime = \gmdate("Y-m-d H:i:s", \time());

		$str = "[" . $datetime . "]" . " " .
			   "[pid:" . \getmypid() . "]" . " " .
			   "[userid:" . $this->user_id . "]" . " " .
			   "[spacename:" . $this->space_name . "]" . " " .
			   "[" . __METHOD__ . "]" . " " .
			   "[line:" . __LINE__ . "]" . " " .
			   $text . "\r\n";

		return error_log( $str, 3, $this->realpath_array['realpath_ajax_log_path'] );
	}

	/**
	 * cron を実行する
	 *
	 * サーバにて登録されたクーロン処理から呼び出されるメソッド。
	 * 処理結果は$resultへ格納されており、エラーが発生した場合はエラーログへの書き込みを行う。
	 * 例外がスローされてきた場合は、こちらでキャッチし、エラーログへの書き込みを行う。
	 *
	 * @return string HTMLソースコード
	 */
	public function cron_run(){
	
		$this->common()->put_process_log(__METHOD__, __LINE__, '■ [cron] run start');

		// 処理実行結果格納
		$result = array('status' => true,
						  'message' => ''
				  );

		try {

			$logstr = "===============================================" . "\r\n";
			$logstr .= "予定公開処理開始" . "\r\n";
			$logstr .= "===============================================";
			$this->common()->put_process_log_block($logstr);

			$result = $this->publish->exec_publish(define::PUBLISH_TYPE_RESERVE, null);
	
			if ( !$result['status'] ) {
				// 予定公開処理失敗の場合

				$this->common()->put_process_log(__METHOD__, __LINE__, "** 予定公開処理エラー終了 **" . $result['message']);

				if ($result['backup_id']) {
					// バックアップが作成されている場合

					$this->common()->put_process_log(__METHOD__, __LINE__, "==========自動復元処理の呼び出し==========");

					$result = $this->publish->exec_publish(define::PUBLISH_TYPE_AUTO_RESTORE, $result['output_id']);

					if ( !$result['status'] ) {
						// 自動復元処理失敗の場合

						$this->common()->put_process_log(__METHOD__, __LINE__, "** 自動復元公開処理エラー終了 **" . $result['message']);
					}
				}
			}

		} catch (\ErrorException $e) {

			$alert_title = "エラーが発生しました。";

			if (\file_exists($this->log_path)) {
				$logstr =  "***** ERROR *****" . "\r\n";
				$logstr .= "[ErrorException]" . "\r\n";
				$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
				$logstr .= "Error message:" . $e->getMessage() . "\r\n";
				$this->common()->put_error_log($logstr);
			} else {
				$alert_line = $e->getFile() . " in " . $e->getLine();
				$alert_message = "Error message:" . $e->getMessage();
			}

			// エラーメッセージ表示
			$dialog_html = '<h3>'.  $alert_title . '</h3>';
			$dialog_html .= '<p>'.  $alert_line . '</p>';
			$dialog_html .= '<p>'.  $alert_message . '</p>';

			return $dialog_html;

		} catch (\Exception $e) {

			$alert_title = "例外エラーが発生しました。";

			$logstr = "** cron_run() Exception caught **" . "\r\n";
			$logstr .= $e->getMessage() . "\r\n";
			$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			$logstr =  "***** EXCEPTION ERROR *****" . "\r\n";
			$logstr .= "[Exception]" . "\r\n";
			$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
			$logstr .= "Error message:" . $e->getMessage() . "\r\n";
			$this->common()->put_error_log($logstr);

			// エラーメッセージ表示
			$dialog_html = '<h3>'.  $alert_title . '</h3>';

			return $dialog_html;
		}

		// データベース接続を閉じる
		$this->pdoMgr->close();

		$logstr = '□ $result->message: ' . $result['message'] . "\r\n";;
		$logstr .= "===============================================" . "\r\n";
		$logstr .= "予定公開処理終了" . "\r\n";
		$logstr .= "===============================================";
		$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		$this->common()->put_process_log(__METHOD__, __LINE__, '■ [cron] run end');

		return;
	}

	/**
	 * `$fs` オブジェクトを取得する。
	 *
	 * `$fs`(class [tomk79\filesystem](tomk79.filesystem.html))のインスタンスを返します。
	 *
	 * @see https://github.com/tomk79/filesystem
	 * @return object $fs オブジェクト
	 */
	public function fs(){
		return $this->fs;
	}

	/**
	 * `$gitMgr` オブジェクトを取得する。
	 *
	 * @return object $gitMgr オブジェクト
	 */
	public function gitMgr(){
		return $this->gitMgr;
	}

	/**
	 * `$pdoMgr` オブジェクトを取得する。
	 *
	 * @return object $pdoMgr オブジェクト
	 */
	public function pdoMgr(){
		return $this->pdoMgr;
	}

	/**
	 * `$common` オブジェクトを取得する。
	 *
	 * @return object $common オブジェクト
	 */
	public function common(){
		return $this->common;
	}

	/**
	 * `$dbh` オブジェクトを取得する。
	 *
	 * @return object $dbh オブジェクト
	 */
	public function dbh(){
		return $this->dbh;
	}

}
