<?php
namespace indigo;

class file_control
{
	/** indigo\mainのインスタンス */
	private $main;

	/**
	 * Delimiter
	 */
	private $_delimiter		= ',';

	/**
	 * Enclosure
	 */
	private $_enclosure		= '"';

	/**
	 * 本番環境ディレクトリパス
	 */
	private $honban_path = './../honban/';

	/**
	 * backupディレクトリパス
	 */
	private $backup_path = './backup/';

	/**
	 * copyディレクトリパス
	 */
	private $copy_path = './copy/';

	/**
	 * 公開予約管理CSVファイル
	 */
	private $reserve_filename = './res/csv/list.csv';

	/**
	 * 公開予約管理CSV カラム列
	 */
	// ステータス列
	private $reserve_column_status = 5;
	// 公開予約日時の列
	private $reserve_column_datetime = 3;

	/**
	 * コンストラクタ
	 * @param $main = mainのインスタンス
	 */
	public function __construct($main) {
		$this->main = $main;
	}

	/**
	 * status
	 */
	public function process() {

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');
	
	
		try {

			// *** 公開予定から本番環境へ置き換えるものを1件抽出する。（抽出されたものが実行中の場合はスキップする　※処理終了）
			// 現在時刻
			$now = date("Y-m-d H:i:s");

			// 公開予約の一覧を取得
			$data_list = $this->get_csv_data_list(0, $now);

			if (!empty($data_list)) {

				// 取得した一覧から最新の1件を取得
				$datetime_str = $this->get_datetime_str($data_list, 'reserve_datetime', SORT_DESC);

				echo '<br>' . '実行する公開予約日時：';
				echo($datetime_str);
			}

			// *** 本番環境よりバックアップ取得

			// #本番環境ディレクトリのパス取得（１）
			// 本番環境の絶対パスを取得
			$honban_real_path = realpath('.') . $this->honban_path;
			echo '<br>' . '本番環境絶対パス：';
			echo $honban_real_path;

			// backupディレクトリの絶対パスを取得
			$bk_real_path = realpath('.') . $this->backup_path;
			echo '<br>' . 'バックアップフォルダの絶対パス：';
			echo $bk_real_path;

			// copyディレクトリの絶対パスを取得
			$copy_real_path = realpath('.') . $this->copy_path;
			echo '<br>' . 'コピーフォルダの絶対パス：';
			echo $copy_real_path;

			// #backupディレクトリに公開予定日時を名前としたフォルダを作成（２）
			if (!file_exists($bk_real_path . $datetime_str)) {

				// ディレクトリ作成
				if ( !mkdir( $bk_real_path . $datetime_str, 0777, true) ) {
					// ディレクトリが作成できない場合

					// エラー処理
					throw new Exception('Creation of master directory failed.');
				}
			}

			// #（１）の中身を（２）へコピー
			exec('cp -r '. $honban_real_path . '* ' . $bk_real_path . $datetime_str, $output);

			// **成功したら
			//   （１）の中身を削除
			//       *成功したら
			 		if (chdir($honban_real_path)) {
			       		exec('rm -rf ./* ./.*', $output);
			 		}
			//      次の処理へ！

			//       *失敗したら
			// 			・失敗ログを残し、処理終了
			// 			・中途半端に残っている（１）の中身を一旦削除して、
			// 			 backupディレクトリから戻す！
			// 			 ※そこも失敗してしまったら、本番環境が壊れている可能性があるので手動で戻してもらう

			// **失敗したら
			//   ・失敗ログを残し、処理終了
			//   ・backupディレクトリは削除？



			// *** 該当するcopyディレクトリの内容を本番環境へ反映
			
			// cron処理で実行対象として認識されたcopyディレクトリのパス取得（３）
			
			// （３）の中身を（１）へコピー
			exec('cp -r '. $copy_real_path . $datetime_str . '* ' . $honban_real_path . $datetime_str, $output);

			// **成功したら
			//   ・成功ログを出力し、処理を終了する

			// **失敗したら
			//   ・失敗ログを残し、処理終了
			//   ・中途半端にアップロードした（１）の中身をすべて削除して、
			// 	  backupディレクトリから戻す！
			// 	  ※そこも失敗してしまったら、本番環境が壊れているので手動で戻してもらわないといけない

		
		} catch (Exception $e) {

			set_time_limit(30);

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		set_time_limit(30);

		$result['status'] = false;
		
		chdir($current_dir);
		return json_encode($result);

	}

	/**
	 * CSVから公開前のリストを取得する
	 *
	 * @param $status = 取得対象のステータス
	 * @return データリスト
	 */
	private function get_csv_data_list($status, $now)
	{

		$ret_array = array();

		$filename = realpath('.') . $this->reserve_filename;

		if (!file_exists($filename)) {
		
			echo 'ファイルが存在しない';
		
		} else {

			// Open file
			$handle = fopen($filename, "r");

			$title_array = array();

			$is_first = true;

			// CSVリストをループ
			while ($rowData = fgetcsv($handle, 0, $this->_delimiter, $this->_enclosure)) {

				if($is_first){
			        // タイトル行
			        foreach ($rowData as $k => $v) {
			        	$title_array[] = $v;
			        }
			        $is_first = false;
			        continue;
			    }
			    
				$set_flg = true;

			    // 指定ステータスと一致しない場合
			    if (isset($status) && ($rowData[$this->reserve_column_status] != $status)) {
					$set_flg = false;
			    }

			    // 指定日時より未来日時の場合
			    if (isset($now) && ($rowData[$this->reserve_column_datetime] > $now)) {
			    	$set_flg = false;
			    }

			    if ($set_flg) {
			    	// タイトルと値の2次元配列作成
			    	$ret_array[] = array_combine ($title_array, $rowData);
			    }
			}

			// Close file
			fclose($handle);

		}
					
		return $ret_array;
	}

	/**
	 * 公開予約一覧用の配列を「公開予定日時の昇順」へソートし返却する
	 *	 
	 * @param $array_list = ソート対象の配列
	 * @param $sort_name  = ソートするキー名称
	 * @param $sort_kind  = ソートの種類
	 *	 
	 * @return ソート後の配列
	 */
	private function get_datetime_str($array_list, $sort_name, $sort_kind) {

		$ret = '';

		if (!empty($array_list)) {

			$sort_array = array();

			foreach($array_list as $key => $value) {
				$sort_array[$key] = $value[$sort_name];
			}

			// 公開予定日時の昇順へソート	
			array_multisort($sort_array, $sort_kind, $array_list);
			// 先頭行の公開予約日時
			$ret = date('YmdHis', strtotime($array_list[0][$sort_name]));
		}

		return $ret;
	}

}
