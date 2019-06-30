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
		var indigo = new window.Indigo();
		indigo.init();
	});
</script>
```


#### 4. indigo作業用のディレクトリを作成する。

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

		// indigo作業用ディレクトリ（絶対パス）
		'realpath_workdir' => '/var/www/html/sample-lib-indigo/', // directoryName (ex. indigo_dir)

		// リソースディレクトリ（ドキュメントルートからの相対パス）
		'relativepath_resourcedir'	=> './../res/', // directoryName (ex. res)

		// ajax呼出クラス（ドキュメントルートからの相対パス）
		'realpath_ajax_call' => './ajax.php',
		
		// 画面表示上のタイムゾーン
		'time_zone' => 'Asia/Tokyo',

		// ユーザID
		'user_id' => 'user01',

		// DB設定
		'db' => array(

			// 'mysql' or null（nullの場合はSQLite3を使用）　※バージョン0.1.0時点ではmysql未対応
			'db_type' => null,

			// 以下mysql用の設定項目
			'mysql_db_name' => '',
			'mysql_db_host' => '',
			'mysql_db_user' => '',
			'mysql_db_pass' => ''
		),

		// 予約最大件数
		'max_reserve_record' => 10,

		// バックアップ世代管理件数　※バージョン0.1.0時点では未対応
		'max_backup_generation' => 5,

		// 本番環境パス（同期先）※バージョン0.1.0時点では先頭の設定内容のみ有効
		'server' => array(
				array(
						// 任意の名前
						'name' => 'server1',
						// 同期先絶対パス
						'real_path' => '/var/www/html/indigo-test-project/'
				),
				array(
						// 任意の名前
						'name' => 'server2',
						// 同期先絶対パス
						'real_path' => '/var/www/html/indigo-test-project2/'
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
※先述の 6. 「ajax呼出クラス（絶対パス）：'realpath_ajax_call'」 のファイル名と一致するようにファイルを作成してください。

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

// 6.1で作成したパラメタ記載ファイル
require __DIR__ . '/parameter.php';

// parameter.phpのcall_parameterメソッド
$parameter = call_parameter();

// load indigo\ajax
$indigo = new indigo\ajax($parameter);

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

### lib-indigo 0.1.4 (リリース日未定)

- オプション `additional_params` を追加。
- オプション `_GET`, `_POST` を省略可能とした。
- 配信予約日時の時制チェックに関する不具合を修正。
- Ajaxの実行メソッド名を `ajax_run()` に変更。
- フロントエンドの初期化スクリプト仕様を変更。

### lib-indigo 0.1.3 (2018年08月31日)

- エラーハンドラ登録処理の削除
- indigo内で生成するディレクトリ名を一部修正
- 不具合修正：グローバル関数にバックスラッシュ付与

### lib-indigo 0.1.2 (2018年08月22日)

- パラメタ不足パターンの対策

### lib-indigo 0.1.1 (2018年08月21日)

- SQLインジェクション対策実装
- htmlspecialchars実装
- 複数のエンドポイントファイルのパラメタ部分を一元管理
- 関数戻り値受け渡し時のjson変換を廃止
- docコメント修正

### lib-indigo 0.1.0 (2018年08月06日)

- Initial Release.


## ライセンス - License

MIT License

## 作者 - Author

- (C)Natsuki Gushikawa natsuki.gushikawa@imjp.co.jp
