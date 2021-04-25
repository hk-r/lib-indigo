<?php

namespace pickles2\indigo\productionServerType;

/**
 * ユーティリティ
 */
class directory
{

	private $main;
	private $server;
	private $from_realpath;
	private $realpath_tracelog;
	private $realpath_copylog;

	/**
	 * Constructor
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct( $main, $server, $from_realpath, $realpath_tracelog, $realpath_copylog ){
		$this->main = $main;
		$this->server = $server;
		$this->from_realpath = $from_realpath;
		$this->realpath_tracelog = $realpath_tracelog;
		$this->realpath_copylog = $realpath_copylog;
	}


	/**
	 * 本番環境へファイルを転送する
	 */
	public function publish(){
		$this->exec_sync($this->main->options->ignore, $this->from_realpath, $this->server->dist);
		return true;
	}

	/**
	 * rsyncコマンドにて公開処理を実施する
	 *
	 * runningディレクトリパスの最後にはスラッシュは付けない（スラッシュを付けると日付ディレクトリも含めて同期してしまう）
	 * log出力するファイルは、履歴一覧画面のログダイアログ表示にて使用するため、この処理のみ異なるファイルに出力する。
	 *
	 * [使用オプション]
	 *		-r 再帰的にコピー（指定ディレクトリ配下をすべて対象とする）
	 *		-h ファイルサイズのbytesをKやMで出力
	 *		-v 処理の経過を表示
	 *		-z 転送中のデータを圧縮する
	 *		--checksum ファイルの中身に差分があるファイルを対象とする
	 *		--delete   転送元に存在しないファイルは削除
	 *		--exclude  同期から除外する対象を指定
	 *		--log-file ログ出力
	 *
	 * @param  array  $ignore 			同期除外ファイル、ディレクトリ名
	 * @param  string $from_realpath 	同期元の絶対パス
	 * @param  string $to_realpath		同期先の絶対パス
	 */
	private function exec_sync($ignore, $from_realpath, $to_realpath) {

		$logstr = "==========rsyncコマンドによるディレクトリの同期実行==========" . "\r\n";
		$logstr .= "【同期元パス】" . $from_realpath . "\r\n";
		$logstr .= "【同期先パス】" . $to_realpath;
		$this->main->utils()->put_publish_log(__METHOD__, __LINE__, $logstr, $this->realpath_tracelog);

		// 除外コマンドの作成
		$exclude_command = '';
		foreach ($ignore as $key => $value) {
		 	$exclude_command .= "--exclude='" . $value . "' ";
		}

		$command = 'rsync --checksum -rhvz --delete ' .
					$exclude_command .
					$from_realpath . ' ' . $to_realpath . ' ' .
				   '--log-file=' . $this->realpath_copylog;

		$this->main->utils()->command_execute($command, true);
	}

}