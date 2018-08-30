pickles2/lib-indigo
======================

## 導入方法 - Setup
### 1. composerの設定
#### 1-1. `composer.json` に `pickles2/lib-indigo` を設定する

`require` の項目に、`pickles2/lib-indigo` を追加します。

```
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


### 2. Resourceファイルの取込
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

#### 2-4. Resourceを読込む
```
<link rel="stylesheet" href="/[resourceInstallPath]/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="/[resourceInstallPath]/styles/common.css">

<script src="/[resourceInstallPath]/bootstrap/js/bootstrap.min.js"></script>
<script src="/[resourceInstallPath]/scripts/common.js"></script>
```


### 3. jqueryのdatepickerを読込む
```
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js"></script>
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">

<script>
	$(function() {
		
		var dateFormat = 'yy-mm-dd';
		
		$.datepicker.setDefaults($.datepicker.regional["ja"]);
		
		$("#datepicker").datepicker({
			   dateFormat: dateFormat
		});
	});
</script>
```


#### 4. indigo作業用のディレクトリを作成する。
apache（その他）ユーザに書き込み権限を付与します。
```
$ mkdir -m 767 yourProject/[directoryName(ex. indigo_dir)]
```


#### 5. 同期先の本番環境ディレクトリのパーミッションを変更する。
apache（その他）ユーザに書き込み権限を付与します。
※-Rオプション・・・指定ディレクトリ以下に存在するディレクトリ・ファイルも全て再帰的に権限変更を行う。
```
$ chmod -R o+w honbanProject/[directoryName(ex. indigo-test-project)]
```



### 6. indigoの画面実行

各種パラメータを設定します。こちらに記載したパラメタが別ファイルから呼び出されます。

```
<?php

function call_parameter () {

	$parameter = 

	array(
		// POST
		'_POST' => $_POST,

		// GET
		'_GET' => $_GET,

		// indigo作業用ディレクトリ（絶対パス）
		'realpath_workdir' => '/var/www/html/sample-lib-indigo/[directoryName(ex. indigo_dir)]/',

		// リソースディレクトリ（ドキュメントルートからの相対パス）
		'relativepath_resourcedir'	=> './../[directoryName(ex. res)]/',

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
	)
);
```


### 7. indigoの画面実行
#### 7-1. 初期化する

6.で作成したパラメータを引数にlib-indigoのmainクラスを呼び出し初期化を行います。

```
<?php

require __DIR__ . '/../vendor/autoload.php';
// 6.1で作成したパラメタ記載ファイル
require __DIR__ . '/parameter.php';

// parameter.phpのcall_parameterメソッド
$parameter = call_parameter();

// load indigo\main
$indigo = new indigo\main($parameter);
```

#### 7-2. indigoを実行する

`run()` を実行します。

```
echo $indigo->run();
```


### 8. ajax呼び出しクラス
#### 8-1. 初期化する

6.で作成したパラメータを引数にlib-indigoのajaxクラスを呼び出し初期化を行います。
※6.で設定した 「ajax呼出クラス（絶対パス）：'realpath_ajax_call'」 のファイル名と一致するようにファイルを作成してください。

```
<?php

require __DIR__ . '/../vendor/autoload.php';

// 6.1で作成したパラメタ記載ファイル
require __DIR__ . '/parameter.php';

// parameter.phpのcall_parameterメソッド
$parameter = call_parameter();

// load indigo\ajax
$indigo = new indigo\ajax($parameter);
```

#### 8-2. ajax呼び出しを実行する

`get_commit_hash()` を実行します。

```
echo $indigo->get_commit_hash();
```



### 9. indigoのクーロン実行
#### 9-1. 初期化する

6.で作成したパラメータを引数にlib-indigoのmainクラスを呼び出し初期化を行います。

```
<?php

require __DIR__ . '/../vendor/autoload.php';

// 6.1で作成したパラメタ記載ファイル
require __DIR__ . '/parameter.php';

// parameter.phpのcall_parameterメソッド
$parameter = call_parameter();

// load indigo\main
$indigo = new indigo\main($parameter);
```

#### 9-2. indigo(cron)を実行する

`cron_run()` を実行します。

```
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