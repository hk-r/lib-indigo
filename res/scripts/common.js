$(function($){
	$(window).load(function(){

		/*
		 * 削除ボタン
		 */
		$('#delete_btn').on('click', function() {

			var selected_flg = false;
				
			var element = document.getElementsByName('target');
				
			var str = "";

			for (i = 0; i < element.length; i++) {

				if (element[i].checked) {
					selected_flg = true;
					str = element[i].value;
					break;
				}
			}

			if (!selected_flg) {
				
				alert('選択されていません');
				return false;
			}

			// 「OK」時の処理開始 ＋ 確認ダイアログの表示
			if(window.confirm('本当に予定を削除してよろしいですか？')) {

				$("#form_table").submit(function(){
					$('<input />').attr('type', 'hidden')
					 .attr('name', 'selected_id')
					 .attr('value', str)
					 .appendTo('#form_table');
					});

			}

			// 「キャンセル」時の処理開始
			else {
				var $dialog = $('.dialog');
				$dialog.remove();
				return false;
			}

			// 画面ロック
			display_lock();
		});	

		/*
		 * 変更ボタン
		 */
		$('#update_btn').on('click', function() {

			var selected_flg = false;
				
			var element = document.getElementsByName('target');
				
			var str = "";

			for (var i = 0; i < element.length; i++) {

				if (element[i].checked) {
					selected_flg = true;
					str = element[i].value;
					break;
				}
			}

			if (!selected_flg) {
				
				alert('選択されていません');
				return false;
			}

			$("#form_table").submit(function(){
				$('<input />').attr('type', 'hidden')
				 .attr('name', 'selected_id')
				 .attr('value', str)
				 .appendTo('#form_table');
			});

			// 画面ロック
			display_lock();
		});	

		/*
		 * 新規変更ダイアログ[確認]ボタン
		 */
		$('#add_check_btn, #update_check_btn').on('click', function() {

			/// 入力チェック
			if (!check_validation()) {
				return false;
			}

			// ダイアログ画面ロック
			display_lock();
		});	

		/*
		 * [新規][履歴]戻る][確定]ボタン
		 */
		$('#add_btn, #confirm_btn, #back_btn, #history_btn').on('click', function() {

			// ダイアログ画面ロック
			display_lock();
		});	

		/*
		 * 即時公開ボタン
		 */
		$('#release_btn').on('click', function() {

			var selected_flg = false;
			
			var element = document.getElementsByName('target');
			
			var str = "";

			for (var i = 0; i < element.length; i++) {

				if (element[i].checked) {
					selected_flg = true;
					str = element[i].value;
					break;
				}
			}

			if (!selected_flg) {
				
				alert('選択されていません');
				return false;
			}

			$("#form_table").submit(function(){
				$('<input />').attr('type', 'hidden')
				 .attr('name', 'selected_id')
				 .attr('value', str)
				 .appendTo('#form_table');
			});

			// 画面ロック
			display_lock();
		});	

		/*
		 * 状態ダイアログ[閉じる]ボタン
		 */
		$('#close_btn').on('click', function() {

			var dialog = document.getElementById('modal_dialog');
			dialog.remove();

			// // 画面ロック
			// display_lock();
		});	
	});

	/*
	 * 入力チェック
	 */
	function check_validation () {

		var date = document.getElementById('datepicker').value;
		var time = document.getElementById('reserve_time').value;

	    if(date == "" || time == ""){
	      
	        alert("日付と時間を入力して下さい。");

	        var now = new Date();

			return false;
	    }

		if(!date.match(/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/)){

		    alert("日付の形式が正しくありません。YYYY-MM-DDの形式で入力してください。");

		    return false;
		 }
		 return true;
	}

	/*
	 * 画面ロック
	 */
	function display_lock() {

		var h = window.innerHeight;

		var loader_bg = document.getElementById('loader-bg');
		loader_bg.style.height = h + "px";
		loader_bg.style.display = 'block';

		var loading = document.getElementById('loading');
		loading.style.height = h + "px";
		loading.style.display = 'block';
	}

});
