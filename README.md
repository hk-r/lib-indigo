pickles2/lib-indigo
======================

## 導入方法 - Setup
### 1. composerの設定
#### 1-1. `composer.json` に `pickles2/lib-indigo` を設定する

`require` の項目に、`pickles2/lib-indigo` を追加します。

```json
{
	〜 中略 〜
    "require": {
        "php": ">=5.3.0" ,
        "pickles2/lib-indigo": "^0.1"
    },
	〜 中略 〜
}
```

#### 1-2. composer update を実行する

1-1の設定後は、`composer update` を実行して変更を反映することを忘れずに。
実行するとvendorディレクトリなどが作成されます。

```
$ composer update
```


### 2. Resourceファイルを配置する
indigoを動作させる上で必要となるResrouceファイルをプロジェクトに取込みます。
#### 2-1. Resourceファイル取込用スクリプトをプロジェクトへコピーする
```
$ cp yourProject/vendor/pickles2/lib-indigo/res_install_script.php yourProject
```

#### 2-2. Resourceファイル格納用のディレクトリを作成する。
```
$ mkdir yourProject/[directoryName(ex. res)]
```

#### 2-3. スクリプトをコマンドラインで実行する
```
$ php res_install_script.php [resourceInstallPath(ex. ./res)]
```

### 3. Resourceを読み込み、フロントエンドを初期化する

```html
<!-- Bootstrap -->
<link rel="stylesheet" href="/[resourceInstallPath]/bootstrap/css/bootstrap.min.css">
<script src="/[resourceInstallPath]/bootstrap/js/bootstrap.min.js"></script>

<!-- Indigo -->
<link rel="stylesheet" href="/[resourceInstallPath]/styles/common.css">
<script src="/[resourceInstallPath]/scripts/common.js"></script>

<!-- jQuery UI -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js"></script>
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">

<script>
	// Initialize Indigo
	window.addEventListener('load', function(){
		var dateFormat = 'yy-mm-dd';
		
		$.datepicker.setDefaults($.datepicker.regional["ja"]);
		
		$("#datepicker").datepicker({
			dateFormat: dateFormat
		});

		var indigo = new window.Indigo();
		indigo.init();
	});
</script>
```

#### フロントエンドの初期化オプション

```js
var indigo = new window.Indigo({
	ajaxBridge: function(data, callback){
		// バックエンドとのデータ受け渡しの方式を変更したい場合に、
		// このオプションを指定します。
		var rtn = '';
		var error = false;
		$.ajax ({
			type: 'POST',
			url: '/path/to/ajax.php',
			data: data,
			dataType: 'json',
			success: function(data, dataType) {
				rtn = data;
			},
			error: function(jqXHR, textStatus, errorThrown) {
				error = textStatus;
			},
			complete: function(){
				callback(rtn, error);
			}
		});
	}
});
indigo.init();
```


### 4. indigo作業用のディレクトリを作成する。

後述の6. indigoの実行パラメタ設定 にて 「 indigo作業用ディレクトリ（絶対パス）：'realpath_workdir'」 にパス設定を行うディレクトリとなります。

#### 4-1. ディレクトリを作成します。

```
$ mkdir yourProject/[directoryName(ex. indigo_dir)]
```


#### 4-2. apache（その他）ユーザに書き込み権限を付与します。
```
$ chmod -R o+w yourProject/[directoryName(ex. indigo_dir)]
```


#### 5. 同期先の本番環境ディレクトリのパーミッションを変更する。
apache（その他）ユーザに書き込み権限を付与します。
※-Rオプションを付けることで、指定ディレクトリ以下に存在するディレクトリ・ファイルも全て再帰的に権限変更を行います。
```
$ chmod -R o+w honbanProject/[directoryName(ex. indigo-test-project)]
```



### 6. indigoの実行パラメタ設定

各種パラメータを設定します。こちらに記載したパラメタが別ファイルから呼び出されます。

```php
<?php

function call_parameter () {

	$parameter = array(
		// POST
		'_POST' => $_POST,

		// GET
		'_GET' => $_GET,

		// フォーム送信時に付加する追加のパラメータ (省略可)
		'additional_params' => array(
			'hoge' => 'fuga',
		),

		// indigo作業用ディレクトリの絶対パス
		// indigoは、このディレクトリに内部で利用する情報を書き込みます。
		'realpath_workdir' => '/var/www/html/sample-lib-indigo/', // directoryName (ex. indigo_dir)

		// git local のマスターデータディレクトリの絶対パス
		// 省略時は、 `realpath_workdir` 内に自動生成されます。
		'realpath_git_master_dir' => '/var/www/html/sample-lib-indigo/master_repository/',

		// リソースディレクトリ（ドキュメントルートからの相対パス）
		'relativepath_resourcedir'	=> './../res/', // directoryName (ex. res)

		// ajax呼出クラス（ドキュメントルートからの相対パス）
		'url_ajax_call' => './ajax.php',
		
		// 画面表示上のタイムゾーン
		'time_zone' => 'Asia/Tokyo',

		// ユーザID
		'user_id' => 'user01', // 省略可

		// 空間名
		'space_name' => 'project0001', // 省略可

		// DB設定
		'db' => array(

			// 'mysql' or 'sqlite' (省略時は SQLite を採用)
			'dbms' => null,
			'prefix' => 'indigo_', // テーブル名の接頭辞
			'database' => null,
			'host' => null,
			'port' => null,
			'username' => null,
			'password' => null,
		),

		// 予約最大件数
		'max_reserve_record' => 10,

		// バックアップ世代管理件数
		// ※ v0.2.0 時点では未対応
		'max_backup_generation' => 5,

		// 本番環境パス (同期先)
		// ※ v0.2.0 時点では先頭の設定内容のみ有効
		'server' => array(
			array(
				// 任意の名前
				'name' => 'server1',
				// 同期先絶対パス
				'dist' => '/path/to/document_root_01/htdocs/'
			),
			array(
				// 任意の名前
				'name' => 'server2',
				// 同期先絶対パス
				'dist' => '/path/to/document_root_02/htdocs/'
			)
		),

		// 同期除外ディレクトリ、またはファイル
		'ignore' => array(
			'.git',
			'.htaccess'
		),

		// Git情報定義
		'git' => array(

			// Gitリポジトリのurl（現在はhttpsプロトコルのみ対応）
			'giturl' => 'https://github.com/gk-r/indigo-test-project.git',

			// ユーザ名
			// Gitリポジトリのユーザ名を設定
			'username' => 'hoge',

			// パスワード
			// Gitリポジトリのパスワードを設定
			'password' => 'fuga'
		)
	);
	return $parameter;
};
```


### 7. indigoの画面実行

6.で作成したパラメータを引数にlib-indigoのmainクラスの呼び出しを行います。

```php
<?php

require __DIR__ . '/../vendor/autoload.php';
// 6.1で作成したパラメタ記載ファイル
require __DIR__ . '/parameter.php';

// parameter.phpのcall_parameterメソッド
$parameter = call_parameter();

// load indigo\main
$indigo = new indigo\main($parameter);

// 実行する
echo $indigo->run();
```



### 8. ajax呼び出しクラス

6.で作成したパラメータを引数に設定し、lib-indigoのajaxクラスの呼び出しを行います。
※先述の 6. 「ajax呼出クラス（絶対パス）：'url_ajax_call'」 のファイル名と一致するようにファイルを作成してください。

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

// 6.1で作成したパラメタ記載ファイル
require __DIR__ . '/parameter.php';

// parameter.phpのcall_parameterメソッド
$parameter = call_parameter();

// load indigo\ajax
$indigo = new indigo\main($parameter);

// 実行する
echo $indigo->ajax_run();
```



### 9. indigoのクーロン実行

6.で作成したパラメータを引数にlib-indigoのmainクラスを呼び出し初期化を行います。

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

// 6.1で作成したパラメタ記載ファイル
require __DIR__ . '/parameter.php';

// parameter.phpのcall_parameterメソッド
$parameter = call_parameter();

// load indigo\main
$indigo = new indigo\main($parameter);

// 実行する
echo $indigo->cron_run();
```


#### 9-3. indigo(cron)をサーバから一定の間隔で呼び出すようクーロン登録を行う

apache権限でクーロン登録用コマンドを実行（root権限だとindigo内の一部動作時にエラーとなる）

```
$ crontab -u apache -e
```

何分間隔で呼び出すのかを設定する。クーロン用のログも出力させる場合は、以下のようにログディレクトリ・ログファイル名を記載する。

```
$ */1 * * * * /usr/bin/php /var/www/html/sample-lib-indigo/htdocs/cron.php >>/var/www/html/sample-lib-indigo/indigo_dir/log/cron.log 2>>/var/www/html/sample-lib-indigo/indigo_dir/log/cron-err.log
```


## 更新履歴 - Change log

### lib-indigo 0.2.0 (リリース日未定)

- Gitリモートの `username` と `password` オプションを省略可能になった。
- 設定項目名 `realpath_ajax_call` を `url_ajax_call` に名称変更。
- 新しい設定項目 `realpath_git_master_dir` を追加。
- 新しい設定項目 `space_name` を追加。
- `indigo\ajax::ajax_run()` を廃止し、`indigo\main::ajax_run()` に統一した。
- データベース接続設定の項目名を変更。
- データベース接続設定に `prefix` を追加。
- データベース接続先に `mysql` を追加。
- 出力先のパスの設定名を `real_path` から `dist` に変更。
- 細かい不具合の修正。

### lib-indigo 0.1.4 (2019年7月1日)

- オプション `additional_params` を追加。
- オプション `_GET`, `_POST` を省略可能とした。
- Ajaxの実行メソッド名を `ajax_run()` に変更。
- フロントエンドの初期化スクリプト仕様を変更。
- 配信予約日時の時制チェックに関する不具合を修正。
- 配信予約の更新に関する不具合を修正。
- その他いくつかの細かい修正。

### lib-indigo 0.1.3 (2018年8月31日)

- エラーハンドラ登録処理の削除
- indigo内で生成するディレクトリ名を一部修正
- 不具合修正：グローバル関数にバックスラッシュ付与

### lib-indigo 0.1.2 (2018年8月22日)

- パラメタ不足パターンの対策

### lib-indigo 0.1.1 (2018年8月21日)

- SQLインジェクション対策実装
- htmlspecialchars実装
- 複数のエンドポイントファイルのパラメタ部分を一元管理
- 関数戻り値受け渡し時のjson変換を廃止
- docコメント修正

### lib-indigo 0.1.0 (2018年8月6日)

- Initial Release.


## ライセンス - License

MIT License

## 作者 - Author

- (C)Natsuki Gushikawa natsuki.gushikawa@imjp.co.jp
