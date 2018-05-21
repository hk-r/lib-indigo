$(function($){
	$(window).load(function(){

		$('#init_btn').on('click', function() {

			// 画面ロック
			var h = $(window).height();
			$('#loader-bg ,#loader').height(h).css('display','block');

			var $form = $('<form>').attr({
				action : '',
				method: 'post'
			});
			$('body').append($form);

			var $input = $('<input>').attr({
				type : 'hidden',
				name : 'initialize',
				value: 'value'
			});
			
			$form.append($input);
			$form.submit();
		});

		$('.reflect').on('click', function() {
			

		});

		$('#close_btn').on('click', function() {
			var $dialog = $('.dialog');
			$dialog.remove();
		});

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
			if(window.confirm('本当に予定を削除してよろしいですか？')){

				$("#form_tbl").submit(function(){
					$('<input />').attr('type', 'hidden')
					 .attr('name', 'radio_selected_id')
					 .attr('value', str)
					 .appendTo('#form_tbl');
					});

			}

			// 「キャンセル」時の処理開始
			else{
				var $dialog = $('.dialog');
				$dialog.remove();
			}
			// 「キャンセル」時の処理終了
		});

		// 変更ボタン押下
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

			$("#form_tbl").submit(function(){
				$('<input />').attr('type', 'hidden')
				 .attr('name', 'radio_selected_id')
				 .attr('value', str)
				 .appendTo('#form_tbl');
			});

		});

		$('#add_check_btn').on('click', function() {

			if (!check_validation()) {
				return false;
			}
		});		

		$('#update_check_btn').on('click', function() {

			if (!check_validation()) {
				return false;
			}
		});		
	})

	function check_validation () {
		document.getElementById('datepicker').style.backgroundColor = '#FFFFFF';
        document.getElementById('time').style.backgroundColor = '#FFFFFF';

		var date = document.getElementById('datepicker').value;
		var time = document.getElementById('time').value;

	    if(date == "" || time == ""){
	      
	        alert("公開予定日時を入力して下さい。");
	        document.getElementById('datepicker').style.backgroundColor = 'mistyrose';
	        document.getElementById('time').style.backgroundColor = 'mistyrose';

	        var now = new Date();

			return false;
	    }

		if(!date.match(/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/)){

		    alert("日付の形式が正しくありません。YYYY-MM-DDの形式で入力してください。");

		    document.getElementById('datepicker').style.backgroundColor = 'mistyrose';

		    return false;
		 }
		 return true;
	}
});
