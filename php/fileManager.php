<?php
namespace indigo;

class fileManager
{
	/** indigo\mainのインスタンス */
	private $main;

	/**
	 * ファイルシステムの文字セット
	 */
	private $filesystem_encoding = null;

	const DIR_PERMISSION_0757 = 0757;

	/**
	 * コンストラクタ
	 * @param $main = mainのインスタンス
	 */
	public function __construct($main) {
		$this->main = $main;
	}

	/**
	 * 絶対パスを得る。
	 *
	 * パス情報を受け取り、スラッシュから始まるサーバー内部絶対パスに変換して返します。
	 *
	 * このメソッドは、PHPの `realpath()` と異なり、存在しないパスも絶対パスに変換します。
	 *
	 * @param string $path 対象のパス
	 * @param string $cd カレントディレクトリパス。
	 * 実在する有効なディレクトリのパス、または絶対パスの表現で指定される必要があります。
	 * 省略時、カレントディレクトリを自動採用します。
	 * @return string 絶対パス
	 */
	public function get_realpath( $path, $cd = '.' ){

		$is_dir = false;
		if( preg_match( '/(\/|\\\\)+$/s', $path ) ){
			$is_dir = true;
		}
		$path = $this->localize_path($path);
		if( is_null($cd) ){ $cd = '.'; }
		$cd = $this->localize_path($cd);
		$preg_dirsep = preg_quote(DIRECTORY_SEPARATOR, '/');

		if( $this->is_dir($cd) ){
			$cd = realpath($cd);
		}elseif( !preg_match('/^((?:[A-Za-z]\\:'.$preg_dirsep.')|'.$preg_dirsep.'{1,2})(.*?)$/', $cd) ){
			$cd = false;
		}
		if( $cd === false ){
			return false;
		}

		$prefix = '';
		$localpath = $path;
		if( preg_match('/^((?:[A-Za-z]\\:'.$preg_dirsep.')|'.$preg_dirsep.'{1,2})(.*?)$/', $path, $matched) ){
			// もともと絶対パスの指定か調べる
			$prefix = preg_replace('/'.$preg_dirsep.'$/', '', $matched[1]);
			$localpath = $matched[2];
			$cd = null; // 元の指定が絶対パスだったら、カレントディレクトリは関係ないので捨てる。
		}

		$path = $cd.DIRECTORY_SEPARATOR.'.'.DIRECTORY_SEPARATOR.$localpath;

		if( file_exists( $prefix.$path ) ){
			$rtn = realpath( $prefix.$path );
			if( $is_dir && $rtn != realpath('/') ){
				$rtn .= DIRECTORY_SEPARATOR;
			}
			return $rtn;
		}

		$paths = explode( DIRECTORY_SEPARATOR, $path );
		$path = '';
		foreach( $paths as $idx=>$row ){
			if( $row == '' || $row == '.' ){
				continue;
			}
			if( $row == '..' ){
				$path = dirname($path);
				if($path == DIRECTORY_SEPARATOR){
					$path = '';
				}
				continue;
			}
			if(!($idx===0 && DIRECTORY_SEPARATOR == '\\' && preg_match('/^[a-zA-Z]\:$/s', $row))){
				$path .= DIRECTORY_SEPARATOR;
			}
			$path .= $row;
		}

		$rtn = $prefix.$path;
		if( $is_dir ){
			$rtn .= DIRECTORY_SEPARATOR;
		}

		return $rtn;
	}

	/**
	 * パスをOSの標準的な表現に変換する。
	 *
	 * 受け取ったパスを、OSの標準的な表現に変換します。
	 * - スラッシュとバックスラッシュの違いを吸収し、`DIRECTORY_SEPARATOR` に置き換えます。
	 *
	 * @param string $path ローカライズするパス
	 * @return string ローカライズされたパス
	 */
	public function localize_path($path){
		$path = $this->convert_filesystem_encoding( $path );//文字コードを揃える
		$path = preg_replace( '/\\/|\\\\/s', '/', $path );//一旦スラッシュに置き換える。
		if( $this->is_unix() ){
			// Windows以外だった場合に、ボリュームラベルを受け取ったら削除する
			$path = preg_replace( '/^[A-Z]\\:\\//s', '/', $path );//Windowsのボリュームラベルを削除
		}
		$path = preg_replace( '/\\/+/s', '/', $path );//重複するスラッシュを1つにまとめる
		$path = preg_replace( '/\\/|\\\\/s', DIRECTORY_SEPARATOR, $path );
		return $path;
	}

	/**
	 * パスを正規化する。
	 *
	 * 受け取ったパスを、スラッシュ区切りの表現に正規化します。
	 * Windowsのボリュームラベルが付いている場合は削除します。
	 * URIスキーム(http, https, ftp など) で始まる場合、2つのスラッシュで始まる場合(`//www.example.com/abc/` など)、これを残して正規化します。
	 *
	 *  - 例： `\a\b\c.html` → `/a/b/c.html` バックスラッシュはスラッシュに置き換えられます。
	 *  - 例： `/a/b////c.html` → `/a/b/c.html` 余計なスラッシュはまとめられます。
	 *  - 例： `C:\a\b\c.html` → `/a/b/c.html` ボリュームラベルは削除されます。
	 *  - 例： `http://a/b/c.html` → `http://a/b/c.html` URIスキームは残されます。
	 *  - 例： `//a/b/c.html` → `//a/b/c.html` ドメイン名は残されます。
	 *
	 * @param string $path 正規化するパス
	 * @return string 正規化されたパス
	 */
	public function normalize_path($path){

		$path = trim($path);
		$path = $this->convert_encoding( $path );//文字コードを揃える
		$path = preg_replace( '/\\/|\\\\/s', '/', $path );//バックスラッシュをスラッシュに置き換える。
		$path = preg_replace( '/^[A-Z]\\:\\//s', '/', $path );//Windowsのボリュームラベルを削除
		$prefix = '';
		if( preg_match( '/^((?:[a-zA-Z0-9]+\\:)?\\/)(\\/.*)$/', $path, $matched ) ){
			$prefix = $matched[1];
			$path = $matched[2];
		}
		$path = preg_replace( '/\\/+/s', '/', $path );//重複するスラッシュを1つにまとめる

		return $prefix.$path;
	}
	
	/**
	 * ディレクトリが存在するかどうか調べる。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool ディレクトリが存在する場合 `true`、存在しない場合、またはファイルが存在する場合に `false` を返します。
	 */
	public function is_dir( $path ){
		$path = $this->localize_path($path);
		return @is_dir( $path );
	}//is_dir()

	/**
	 * サーバがUNIXパスか調べる。
	 *
	 * @return bool UNIXパスなら `true`、それ以外なら `false` を返します。
	 */
	public function is_unix(){
		if( DIRECTORY_SEPARATOR == '/' ){
			return true;
		}
		return false;
	}//is_unix()

	/**
	 * 受け取ったテキストを、ファイルシステムエンコードに変換する。
	 *
	 * @param mixed $text テキスト
	 * @return string 文字セット変換後のテキスト
	 */
	private function convert_filesystem_encoding( $text ){
		$RTN = $text;
		if( !is_callable( 'mb_internal_encoding' ) ){
			return $text;
		}
		if( !strlen( $this->filesystem_encoding ) ){
			return $text;
		}

		$to_encoding = $this->filesystem_encoding;
		$from_encoding = mb_internal_encoding().',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII';

		return $this->convert_encoding( $text, $to_encoding, $from_encoding );

	}//convert_filesystem_encoding()

	/**
	 * 受け取ったテキストを、ファイルシステムエンコードに変換する。
	 *
	 * @param mixed $text テキスト
	 * @param string $to_encoding 文字セット(省略時、内部文字セット)
	 * @param string $from_encoding 変換前の文字セット
	 * @return string 文字セット変換後のテキスト
	 */
	public function convert_encoding( $text, $to_encoding = null, $from_encoding = null ){
		$RTN = $text;
		if( !is_callable( 'mb_internal_encoding' ) ){
			return $text;
		}

		$to_encoding_fin = $to_encoding;
		if( !strlen($to_encoding_fin) ){
			$to_encoding_fin = mb_internal_encoding();
		}
		if( !strlen($to_encoding_fin) ){
			$to_encoding_fin = 'UTF-8';
		}

		$from_encoding_fin = (strlen($from_encoding)?$from_encoding.',':'').mb_internal_encoding().',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII';

		// ---
		if( is_array( $text ) ){
			$RTN = array();
			if( !count( $text ) ){
				return $text;
			}
			foreach( $text as $key=>$row ){
				$RTN[$key] = $this->convert_encoding( $row, $to_encoding, $from_encoding );
			}
		}else{
			if( !strlen( $text ) ){
				return $text;
			}
			$RTN = mb_convert_encoding( $text, $to_encoding_fin, $from_encoding_fin );
		}
		return $RTN;
	}//convert_encoding()

	/**
	 * ディレクトリが存在しない場合はディレクトリを作成する
	 *	 
	 * @param $dirpath = ディレクトリパス
	 *	 
	 * @return true:成功、false：失敗
	 */
	public function is_exists_mkdir($dirpath) {

		// $this->debug_echo('■ is_exists_mkdir start');

		$ret = true;

		if ($dirpath) {
			if ( !file_exists($dirpath) ) {
				// ディレクトリ作成
				if ( !mkdir($dirpath, self::DIR_PERMISSION_0757)) {
					$ret = false;
				}
			}
		} else {
			$ret = false;
		}

		// $this->debug_echo('　□ return：' . $ret);
		// $this->debug_echo('■ is_exists_mkdir end');

		return $ret;
	}

	/**
	 * ディレクトリの存在有無にかかわらず、ディレクトリを再作成する（存在しているものは削除する）
	 *	 
	 * @param $dirpath = ディレクトリパス
	 *	 
	 * @return true:成功、false：失敗
	 */
	public function is_exists_remkdir($dirpath) {
		
		$this->debug_echo('■ is_exists_remkdir start');
		$this->debug_echo('　■ $dirpath：' . $dirpath);

		if ( file_exists($dirpath) ) {
			$this->debug_echo('　■ $dirpath2：' . $dirpath);

			// 削除
			$command = 'rm -rf --preserve-root '. $dirpath;
			$ret = $this->main->command_execute($command, true);

			if ( $ret['return'] !== 0 ) {
				$this->debug_echo('[既存ディレクトリ削除失敗]');
				return false;
			}
		}

		// デプロイ先のディレクトリを作成
		if ( !file_exists($dirpath)) {
			if ( !mkdir($dirpath, self::DIR_PERMISSION_0757) ) {
				$this->debug_echo('　□ [再作成失敗]$dirpath：' . $dirpath);
				return false;
			}
		} else {
			$this->debug_echo('　□ [既存ディレクトリが残っている]$dirpath：' . $dirpath);
			return false;
		}
	
		$this->debug_echo('■ is_exists_remkdir end');

		return true;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_echo($text) {
	
		// echo strval($text);
		// echo "<br>";

		return;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_var_dump($text) {
	
		// var_dump($text);
		// echo "<br>";

		return;
	}

}
