var gulp = require('gulp');
var sass = require('gulp-sass');//CSSコンパイラ
var plumber = require("gulp-plumber");//コンパイルエラーが起きても watch を抜けないようになる
var rename = require("gulp-rename");//ファイル名の置き換えを行う
var browserify = require("gulp-browserify");//NodeJSのコードをブラウザ向けコードに変換
var _tasks = [
	'client-libs',
	'.js',
	'.css',
	'.css.scss'
];

// client-libs (frontend) を処理
gulp.task("client-libs", function() {
});


// src 中の *.js を処理
gulp.task('.js', function(){
	gulp.src(["src/**/*.js","!src/**/*.ignore*","!src/**/*.ignore*/*"])
		.pipe(plumber())
		.pipe(browserify({}))
		.pipe(gulp.dest( './res/' ))
	;
});

// src 中の *.css.scss を処理
gulp.task('.css.scss', function(){
	gulp.src(["src/**/*.css.scss","!src/**/*.ignore*","!src/**/*.ignore*/*"])
		.pipe(plumber())
		.pipe(sass())
		.pipe(rename({extname: ''}))
		.pipe(gulp.dest( './res/' ))
	;
});

// src 中の *.css を処理
gulp.task('.css', function(){
	gulp.src(["src/**/*.css","!src/**/*.ignore*","!src/**/*.ignore*/*"])
		.pipe(plumber())
		.pipe(gulp.dest( './res/' ))
	;
});


// src 中のすべての拡張子を監視して処理
gulp.task("watch", function() {
	gulp.watch(["src/**/*"], _tasks);
});


// src 中のすべての拡張子を処理(default)
gulp.task("default", _tasks);
