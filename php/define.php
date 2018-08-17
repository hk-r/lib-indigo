<?php

namespace indigo;

class define
{
	public $options;
		
	/**
	 * 公開用の操作ディレクトリパス定義
	 */
	const FILE_DEFAULT_PERMISSION = '775';
	const DIR_DEFAULT_PERMISSION = '775';
	const FILESYSTEM_ENCODING = 'UTF-8';

	/**
	 * 公開用の操作ディレクトリパス定義
	 */
	const PATH_BACKUP = '/backup/';
	const PATH_WAITING = '/waiting/';
	const PATH_RUNNING = '/running/';
	const PATH_RELEASED = '/released/';
	const PATH_LOG = '/log/';
	const PATH_MASTER = '/master_repository/';


	/**
	 * 公開ステータス
	 */
	const PUBLISH_STATUS_RUNNING = '0';	// 処理中
	const PUBLISH_STATUS_SUCCESS = '1';	// 成功
	const PUBLISH_STATUS_ALERT	 = '2';	// 成功（警告あり）
	const PUBLISH_STATUS_FAILED  = '3';	// 失敗
	const PUBLISH_STATUS_SKIP	 = '4';	// スキップ

	/**
	 * 公開種別
	 */
	const PUBLISH_TYPE_RESERVE		  = '1'; // 予定公開
	const PUBLISH_TYPE_IMMEDIATE 	  = '2'; // 即時公開
	const PUBLISH_TYPE_MANUAL_RESTORE = '3'; // 手動復元公開
	const PUBLISH_TYPE_AUTO_RESTORE   = '4'; // 自動復元公開

	/**
	 * 日時フォーマット
	 */
	// DB格納用
	const DATETIME_FORMAT = "Y-m-d H:i:s";
	// ディレクトリ命名用
	const DATETIME_FORMAT_SAVE = "YmdHis";
	// 画面表示用
	const DATETIME_FORMAT_DISP = "Y-m-d H:i";

	// H:i:s
	const TIME_FORMAT_HIS = "H:i:s";


	// YmdHi
	const DATETIME_FORMAT_YMD = "Ymd";

	// 入力画面_日付用
	const DATE_FORMAT_YMD = "Y-m-d";
	// 入力画面_時刻用
	const TIME_FORMAT_HI = "H:i";

	/**
	 * 削除フラグ
	 */
	const DELETE_FLG_OFF = '0';	// 未削除
	const DELETE_FLG_ON = '1';	// 削除済み

	/**
	 * その他
	 */
	// 公開予定ディレクトリの付与文字列
	const DIR_NAME_RESERVE = '_reserve';

	// gitリモート名
	const GIT_REMOTE_NAME = 'origin';

	// gitリモート名
	const LIMIT_LIST_RECORD = 1000;
}
