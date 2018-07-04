<?php

namespace indigo;

class cron
{
	public $options;

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {

		$this->options = json_decode(json_encode($options));
		// $this->file_control = new file_control($this);
		// $this->pdo = new pdo($this);
	}

    public function run(){
        // $email = new Email('default');
        
        echo 'start!';
        $this->debug_echo('------------------------');
		$this->debug_echo('------------------------');
		$this->debug_echo(__DIR__);
		$this->debug_echo('Hello World!');

    }


	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_echo($text) {

		echo strval($text);
		echo "\n";

		return;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_var_dump($text) {

		var_dump($text);
		echo "\n";

		return;
	}

}






// // require_once("./../.px_execute.php");
// require __DIR__ . '/pdo.php';

// debug_echo('------------------------');
// debug_echo('------------------------');
// debug_echo(__DIR__);
// debug_echo('Hello World!');

// // $arr = array( "tokyo"  => "東京",
// //             "osaka"  => "大阪",
// //             "nagoya" => "名古屋"
// //           );
// // $log = $arr;
// error_log(print_r($log, TRUE), 3, 'C:\workspace\sample-lib-indigo\vendor\pickles2\lib-indigo\php\output.log');


// connect();


// /**
//  * ※デバッグ関数（エラー調査用）
//  *	 
//  */
// function debug_echo($text) {

// 	echo strval($text);
// 	echo "\n";

// 	return;
// }

// /**
//  * ※デバッグ関数（エラー調査用）
//  *	 
//  */
// function debug_var_dump($text) {

// 	var_dump($text);
// 	echo "\n";

// 	return;
// }




