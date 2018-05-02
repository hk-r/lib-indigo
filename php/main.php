<?php

namespace hk\indigo;

class main
{
	/** オプション 
		_GET,
		_POST,
		_COOKIE,
		_SESSION,
	*/
	public $options;

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {
		$this->options = json_decode(json_encode($options));
	}

	/**
	 * 
	 */
	public function run() {


	}
	
}
