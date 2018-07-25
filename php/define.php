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
	const PUBLISH_STATUS_RUNNING = 0;	// 処理中
	const PUBLISH_STATUS_SUCCESS = 1;	// 成功
	const PUBLISH_STATUS_ALERT = 2;		// 成功（警告あり）
	const PUBLISH_STATUS_FAILED = 3;	// 失敗
	const PUBLISH_STATUS_SKIP = 4;		// スキップ

	/**
	 * 公開種別
	 */
	
	const PUBLISH_TYPE_RESERVE = 1;		// 予約公開
	const PUBLISH_TYPE_RESTORE = 2;		// 復元公開
	const PUBLISH_TYPE_IMMEDIATE = 3;	// 即時公開


	/**
	 * 日付フォーマット
	 */
	// Y-m-d H:i:s
	const DATETIME_FORMAT = "Y-m-d H:i:s";
	// H:i:s
	const TIME_FORMAT_HIS = "H:i:s";
	// YmdHis（ディレクトリ命名用）
	const DATETIME_FORMAT_SAVE = "YmdHis";
	// Y-m-d H:i（画面表示用）
	const DATETIME_FORMAT_DISP = "Y-m-d H:i";


	/**
	 * 削除フラグ
	 */
	const DELETE_FLG_ON = 1;	// 削除済み
	const DELETE_FLG_OFF = 0;	// 未削除

	/**
	 * その他
	 */
	// 公開予約ディレクトリの付与文字列
	const DIR_NAME_RESERVE = '_reserve';

}
