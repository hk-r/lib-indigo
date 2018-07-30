<?php

namespace indigo;

class cron
{
	public $options;

	/**
	 * オブジェクト
	 * @access private
	 */
	private $pdoMgr, $fs, $tsReserve, $tsOutput, $tsBackup, $publish, $common;


	/**
	 * PDOインスタンス
	 */
	public $dbh;

	
	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {

		$this->options = json_decode(json_encode($options));

		$this->fs = new \tomk79\filesystem(array(
		  'file_default_permission' => define::FILE_DEFAULT_PERMISSION,
		  'dir_default_pefrmission' => define::DIR_DEFAULT_PERMISSION,
		  'filesystem_encoding' 	=> define::FILESYSTEM_ENCODING
		));
		
		$this->common = new common($this);

		$this->publish = new publish($this);

		$this->pdoMgr = new pdoManager($this);
		$this->tsReserve = new tsReserve($this);
		$this->tsOutput = new tsOutput($this);
		$this->tsBackup = new tsBackup($this);
	}

	/**
	 * 
	 */
    public function run(){
	
		$this->common->debug_echo('■ [cron] run start');

		// 処理実行結果格納
		$result = json_decode(json_encode(
					array('status' => true,
					      'message' => '')
				  ));

		try {

			//============================================================
			// データベース接続
			//============================================================
			$this->dbh = $this->pdoMgr->connect();


			//============================================================
			// 公開処理実施
			//============================================================
			$result = json_decode(json_encode($this->publish->exec_publish(define::PUBLISH_TYPE_RESERVE, null)));
	
			if ( !$result->status ) {
				// 処理失敗の場合

				// TODO:エラーログ出力
				echo '例外キャッチ：' . $result->message;
			}

		} catch (\Exception $e) {

			// データベース接続を閉じる
			$this->pdoMgr->close($this->dbh);

			echo '例外キャッチ：' . $e->getMessage() . "<br>";

			return;
		}

		// データベース接続を閉じる
		$this->pdoMgr->close();

		$this->common->debug_echo('■ [cron] run end');

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
	 * `$common` オブジェクトを取得する。
	 *
	 * @return object $common オブジェクト
	 */
	public function common(){
		return $this->common;
	}

	/**
	 * response status code を取得する。
	 *
	 * `$px->set_status()` で登録した情報を取り出します。
	 *
	 * @return int ステータスコード (100〜599の間の数値)
	 */
	public function get_dbh(){
		return $this->dbh;
	}

}
