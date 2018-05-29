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
        "pickles2/lib-indigo": "dev-develop"
    },
	〜 中略 〜
}
```

#### 1-2. composer update を実行する

追加したら、`composer update` を実行して変更を反映することを忘れずに。

```
$ composer update
```

### 2. Resourceファイルの取込
indigoを動作させる上で必要となるResrouceファイルをプロジェクトに取込みます。
#### 2-1. Resourceファイル取込用スクリプトをプロジェクトへコピーする
```
$ cp yourProject\vendor\pickles2\lib-indigo\res_install_script.php yourProject\
```

#### 2-2. スクリプトをコマンドラインで実行する
```
$ php res_install_script.php [resourceInstallPath(ex. ./res)]
```

#### 2-3. Resourceを読込む
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
```

### 4. indigoの実行
#### 4-1. 初期化する

各種パラメータを設定し、lib-indigoのmainクラスを呼び出し初期化を行います。

```
<?php

$indigo = new indigo\main(
	array(
		// POST
		'_POST' => $_POST,

		// GET
		'_GET' => $_GET,

		// Git情報定義
		'git' => array(
			
			// リポジトリのパス
			// ウェブプロジェクトのリポジトリパスを設定。
			'repository' => './repos/master/',

			// プロトコル
			// ※現在はhttpsのみ対応
			'protocol' => 'https',

			// ホスト
			// Gitリポジトリのhostを設定。
			'host' => 'github.com',

			// url
			// Gitリポジトリのhostを設定。
			'url' => 'github.com/gushikawa/indigo-test-project.git',

			// ユーザ名
			// Gitリポジトリのユーザ名を設定。
			'username' => 'hoge',

			// パスワード
			// Gitリポジトリのパスワードを設定。
			'password' => 'fuga'
		)
	)
);
```

#### 4-2. indigoを実行する

`run()` を実行します。

```
// return: 結果表示用HTML
echo $indigo->run();
```

## 更新履歴 - Change log
### lib-indigo x.x (yyyy年mm月dd日)
- Initial Release.

## ライセンス - License
MIT License

## 作者 - Author
- (C)Kyota Hiyoshi hiyoshi-kyota@imjp.co.jp