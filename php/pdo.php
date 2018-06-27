<?php

namespace indigo;

class pdo
{

	// try {
	// 	// DBへ接続
	// 	$dbh = new PDO("sqlite:./sqlite/test.db");

	// 	// SQL作成
	// 	$sql = 'CREATE TABLE IF NOT EXISTS products (
	// 		    id          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	// 		    name        TEXT    NOT NULL,
	// 		    description TEXT    NULL,
	// 		    price       INTEGER NOT NULL,
	// 		    discount    INTEGER NOT NULL DEFAULT 0,
	// 		    reg_date    TEXT    NOT NULL
	// 	)';

	// 	// SQL実行
	// 	$res = $dbh->query($sql);


	// 	// SQL作成
	// 	$sql = 'INSERT INTO products (
	// 		    id          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	// 		    name        TEXT    NOT NULL,
	// 		    description TEXT    NULL,
	// 		    price       INTEGER NOT NULL,
	// 		    discount    INTEGER NOT NULL DEFAULT 0,
	// 		    reg_date    TEXT    NOT NULL
	// 	)';

	// } catch(PDOException $e) {

	// 	echo $e->getMessage();
	// 	die();
	// }

	// // 接続を閉じる
	// $dbh = null;

	/**
	 * PDOインスタンス
	 */
	private $dbh;

	/**
	 * Constructor
	 *
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){



				$this->debug_echo('■ __construct start');

		// $this->px = $px;
		// $this->conf = $this->px->conf();

		$this->dbh = false;//初期化

		$this->debug_echo('　□PDO1');

		try {

			// 接続種類
			$db = 'sqlite:';

			// データベースパス
			$db_path = './sqlite/';

			// データベース名
			$db_name = 'test.db';

			// データベースのユーザ名
			$db_user = null;
			// データベースのパスワード
			$db_password = null;

			// TODO:MySQLの場合
			// mysql:host=ホスト名;dbname=データベース名;charset=文字エンコード
			// $dsn = 'mysql:host=mysql000.db.sakura.ne.jp;dbname=example_php;charset=utf8';		

		$this->debug_echo('　□PDO2');

			// PDOインスタンスを生成
			$this->dbh = new \PDO(
				'sqlite:' . $db_path . $db_name,
				$db_user,		// ユーザID
				$db_password,	// パスワード
				array(		// オプションがあればこちらへ追記
					// \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
					\PDO::ATTR_PERSISTENT => false, // ←これをtrueにすると、"持続的な接続" になる
				)
			);


			// CREATE TABLE処理開始
			$this->debug_echo('　□CREATE TABLE START');

			// SQL作成
			$create_sql = 'CREATE TABLE IF NOT EXISTS list (
				id INTEGER PRIMARY KEY,
				reserve_dt TEXT,
				commit_hash TEXT,
				branch_name TEXT
			)';

			// SQL実行
			$res = $this->dbh->query($create_sql);



			// INSERT処理開始
			$this->debug_echo('　□INSERT START');

			$insert_sql = "INSERT INTO list (
				reserve_dt,
				commit_hash,
				branch_name
			)VALUES(
				:reserve_dt,
				:commit_hash,
				:branch_name
			)";

			$this->debug_echo('　□INSERT　SQL：' . $insert_sql);

			$stmt = $this->dbh->prepare($insert_sql);

		$this->debug_echo('　□PDO2.5');

			$params = array(
				':reserve_dt' => '2018-05-31T10:00:00+00:00',
				':commit_hash' => 'feie8e',
				':branch_name' => 'released/2018-05-31'
			);

		$this->debug_echo('　□PDO3');

			// 処理実行
			$stmt->execute($params);

		$this->debug_echo('　□PDO4');

			$params = array(
				':reserve_dt' => '2018-06-01T10:00:00+00:00',
				':commit_hash' => 'h83ohi',
				':branch_name' => 'released/2018-06-01'
			);

		$this->debug_echo('　□PDO5');

	
			// 処理実行
			$stmt->execute($params);

			// デバッグ用
			$this->debug_var_dump('　□INSERTエラー：' . $stmt->errorInfo());
			$count = $stmt->rowCount();
			$this->debug_echo('　□INSERT件数：' . $count);

			$this->debug_echo('　□INSERT END');



			// UPDATE処理開始
			$this->debug_echo('　□UPDATE START');

			$update_sql = "UPDATE list SET branch_name = :branch_name WHERE id = :id";

			$this->debug_echo('　□UPDATE　SQL：' . $update_sql);

			$stmt = $this->dbh->prepare($update_sql);

			$params = array(
				':branch_name' => 'released/2018-06-09',
				':id' => '2'
			);

			$stmt->execute($params);

			$this->debug_var_dump('　□UPDATEエラー：' . $stmt->errorInfo());
			$count = $stmt->rowCount();
			$this->debug_echo('　□UPDATE件数：' . $count);

			$this->debug_echo('　□UPDATE END');



			// DELETE処理開始
			$this->debug_echo('　□DELETE START');

			$delete_sql = "DELETE FROM list WHERE id = :id";

			$this->debug_echo('　□DELETE　SQL：' . $delete_sql);

			$stmt = $this->dbh->prepare($delete_sql);

			$params = array(
				':id' => '1'
			);

			$stmt->execute($params);

			$this->debug_var_dump('　□DELETEエラー：' . $stmt->errorInfo());
			$count = $stmt->rowCount();
			$this->debug_echo('　□DELETE件数：' . $count);

			$this->debug_echo('　□DELETE END');



			// SELECT処理開始
			$this->debug_echo('　□SELECT START');

			$select_sql = "SELECT * FROM list ORDER BY branch_name";


			$this->debug_echo('　□SELECT　SQL：' . $select_sql);

			$stmt = $this->dbh->query($select_sql);

			$this->debug_var_dump($stmt->errorInfo());
			$count = $stmt->rowCount();
			$this->debug_echo('　□SELECT：' . $count);

			// foreach文で配列の中身を一行ずつ出力
			foreach ($stmt as $row) {
			 
				// データベースのフィールド名で出力
				$this->debug_echo($row['id'].'：'.$row['branch_name']);
				
			}

			$this->debug_echo('　□SELECT END');


			// データベースとの接続を閉じる
			$dbh = null;

		} catch (Exception $e) {
			
			echo 'データベースにアクセスできません！' . $e->getMessage();
			// 強制終了
			exit;
		}

		$this->debug_echo('■ __construct end');


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
     *
	 * 受け取ったテキストを、ファイルシステムエンコードに変換する。
	 *
	 * @param mixed $text テキスト
	 * @return string 文字セット変換後のテキスト
	 
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
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_echo($text) {
	
		echo strval($text);
		echo "<br>";

		return;
	}

	/**
	 * ※デバッグ関数（エラー調査用）
	 *	 
	 */
	function debug_var_dump($text) {
	
		var_dump($text);
		echo "<br>";

		return;
	}

}