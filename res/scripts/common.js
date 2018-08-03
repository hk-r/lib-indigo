$(function($){
	$(window).load(function(){


		$('#branch_list').change(function() {
			
			// var str = $(this).val();
			// alert('セレクトボックスチェンジ');

			// var site_id1 = document.getElementsByName('branch_select_value');
			var branch_name = $(this).val();

			if (!branch_name) {

	    		$('#result').text("");
	    		$('#commit_hash').val("");

			} else {

				var realpath_ajax_call = document.getElementById('realpath_ajax_call').value;
				var realpath_workdir = document.getElementById('realpath_workdir').value;

				$.ajax ({
					type: 'GET',
					// "url": "./../vendor/pickles2/lib-indigo/php/jquery.php",
					// url: path + "php/ajax.php",
					url: realpath_ajax_call,
					
					data: { 'branch_name': branch_name, 
							'realpath_workdir': realpath_workdir
						},
					dataType: 'json',
				    success: function(data, dataType) {
				    	if (data) {
				    		$('#result').text(data.commit_hash);
				    		$('#commit_hash').val(data.commit_hash);
				    	}					
			    	},
			    	error: function(jqXHR, textStatus, errorThrown) {
				        // エラーメッセージの表示
				        alert('コミット取得に失敗しました。');
	                      $("#XMLHttpRequest").html("XMLHttpRequest : " + jqXHR.status);
	                      $("#textStatus").html("textStatus : " + textStatus);
	                      $("#errorThrown").html("errorThrown : " + errorThrown);
		      		}
				});
			}

	      	return false;
		});

		/*
		 * 削除ボタン
		 */
		$('#delete_btn').on('click', function() {

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
		 * 復元ボタン
		 */
		$('#restore_btn').on('click', function() {

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

			// 「OK」時の処理開始 ＋ 確認ダイアログの表示
			if(window.confirm('本当に復元してよろしいですか？')) {

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
		 * 新規、変更ダイアログの[確認][確定]ボタン
		 */
		$('#add_check_btn, #update_check_btn').on('click', function() {

			/// ブランチ入力チェック
			if (!check_branch_validation()) {
				return false;
			}
			/// 日付入力チェック
			if (!check_date_validation()) {
				return false;
			}
			// ダイアログ画面ロック
			display_lock();
		});	

		/*
		 * 即時公開ダイアログの[確認][確定]ボタン
		 */
		$('#immediate_check_btn').on('click', function() {

			/// ブランチ入力チェック
			if (!check_branch_validation()) {
				return false;
			}
			
			// ダイアログ画面ロック
			display_lock();
		});	
		/*
		 * 入力チェック不要
		 * [新規][履歴][戻る][確定][即時公開][バックアップ一覧]ボタン
		 */
		$('#add_btn, #back_btn, #history_btn, #backup_btn, #immediate_btn, #confirm_btn').on('click', function() {

			// ダイアログ画面ロック
			display_lock();
		});	


		/*
		 * ログボタン
		 */
		$('#log_btn').on('click', function() {

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
	 * ブランチ設定チェック
	 */
	function check_branch_validation () {

		var branch_select_value = document.getElementsByName('branch_select_value').item(0).value;

		if(branch_select_value === ""){
		  
			alert("ブランチを選択して下さい。");
			return false;
		}

		return true;
	}

	/*
	 * 日付入力チェック
	 */
	function check_date_validation () {

		var date = document.getElementById('datepicker').value;
		var time = document.getElementById('reserve_time').value;

		if(date === "" || time === ""){
		  
			alert("日付と時間を入力して下さい。");
			return false;
		}

		// if(!date.match(/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/)){

		// 	alert("日付の形式が正しくありません。YYYY-MM-DDの形式で入力してください。");
		// 	return false;
		// }

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
