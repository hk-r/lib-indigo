<?php
namespace indigo;

class file_control
{
	/** indigo\mainのインスタンス */
	private $main;

	/**
	 * コンストラクタ
	 * @param $main = mainのインスタンス
	 */
	public function __construct($main) {
		$this->main = $main;
	}

	// /**
	//  * status
	//  */
	// public function status($preview_server_name) {

	// 	$current_dir = realpath('.');

	// 	$server_list = $this->main->options->preview_server;

	// 	foreach ( $server_list as $preview_server ) {
	// 		chdir($current_dir);

	// 		if ( trim($preview_server->name) == trim($preview_server_name) ) {

	// 			// ディレクトリ移動
	// 			if ( chdir($preview_server->path) ) {

	// 				$result = array('changes' => array());

	// 				// git 状態取得
	// 				exec('git status --porcelain -s', $output);

	// 				if ( is_array($output) ) {
	// 					foreach ($output as $line) {
	// 						$result['changes'][] = array(
	// 							'file'      => substr($line, 3),
	// 							'index'     => substr($line, 0, 1),
	// 							'work_tree' => substr($line, 1, 1)
	// 						);
	// 					}
	// 				}

	// 				chdir($current_dir);
					
	// 				return $result;
	// 			}
	// 		}
	// 	}

	// 	return $result;
	// }
}
