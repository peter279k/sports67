$(function() {
	alertify.set({
		labels:{
			ok: "確定",
			cancel: "取消"
		}
	});
	
	var res = "";
	$("#main-page").show();
	
	$.post("/sports/67/handle/Route/route?action=check_chief_login", function(response) {
		res = $.parseJSON(response);
		res = res["result"];
		if(!res) {
			alertify.alert("尚未登入!", function() {
				location.href = "/sports/67/chief/login";
			});
		}
		else {
			$("#main-page").show();
		}
	});
	
	$.post("/sports/67/handle/Route/route?action=get_contest_list", function(response) {
		res = $.parseJSON(response);
		res = res["result"];
		if(res[0]["a_b"]=="甲組") {
			var contest1 = '<optgroup class="mytext" label="單項徑賽(男子甲組)">';
			var contest2 = '<optgroup class="mytext2" label="單項田賽(男子甲組)">';
			var contest3 = '<optgroup class="mytext3" label="接力項目(男子甲組)">';	
			var contest4 = '<optgroup class="mytext4" label="單項徑賽(女子甲組)">';
			var contest5 = '<optgroup class="mytext5" label="單項田賽(女子甲組)">';
			var contest6 = '<optgroup class="mytext6" label="接力項目(女子甲組)">';
		}
		else {
			var contest1 = '<optgroup class="mytext" label="單項徑賽(男子乙組)">';
			var contest2 = '<optgroup class="mytext2" label="單項田賽(男子乙組)">';
			var contest3 = '<optgroup class="mytext3" label="接力項目(男子乙組)">';	
			var contest4 = '<optgroup class="mytext4" label="單項徑賽(女子乙組)">';
			var contest5 = '<optgroup class="mytext5" label="單項田賽(女子乙組)">';
			var contest6 = '<optgroup class="mytext6" label="接力項目(女子乙組)">';
		}
		var other_contest = '<optgroup label="不分組">';
		/*
			$contest_row[$len]["item"] = $contest_item["item"];
			$contest_row[$len]["category"] = $contest_item["category"];
			$contest_row[$len]["least_number"] = $contest_item["least_number"];
			$contest_row[$len]["max_number"] = $contest_item["max_number"];
			$contest_row[$len]["a_b"] = $contest_item["a_b"];
		*/
		var contest_count = 0;
		for(;contest_count<res.length;contest_count++) {
			if(res[contest_count]["male_female"].indexOf("男子")!=-1) {
				switch(res[contest_count]["item"]) {
					case "單項徑賽":
						contest1 += '<option value="'+res[contest_count]["ID"]+'">'+res[contest_count]["category"]+'</option>';
						break;
					case "單項田賽":
						contest2 += '<option value="'+res[contest_count]["ID"]+'">'+res[contest_count]["category"]+'</option>';
						break;
					case "接力項目":
						contest3 += '<option value="'+res[contest_count]["ID"]+'">'+res[contest_count]["category"]+'</option>';
						break;
				}
			}
			else if(res[contest_count]["male_female"].indexOf("女子")!=-1) {
				switch(res[contest_count]["item"]) {
					case "單項徑賽":
						contest4 += '<option value="'+res[contest_count]["ID"]+'">'+res[contest_count]["category"]+'</option>';
						break;
					case "單項田賽":
						contest5 += '<option value="'+res[contest_count]["ID"]+'">'+res[contest_count]["category"]+'</option>';
						break;
					case "接力項目":
						contest6 += '<option value="'+res[contest_count]["ID"]+'">'+res[contest_count]["category"]+'</option>';
						break;
				}
			}
			else {
				other_contest += '<option value="'+res[contest_count]["ID"]+'">'+res[contest_count]["category"]+'</option>';
			}
		}
		
		contest1 += "</optgroup>";
		contest2 += "</optgroup>";
		contest3 += "</optgroup>";
		contest4 += "</optgroup>";
		contest5 += "</optgroup>";
		contest6 += "</optgroup>";
		other_contest += "</optgroup>";
		
		$("#contest-list").html('');
		$("#contest-list").append("<option value='0'>請選擇賽事名稱</option>"+contest1+contest2+contest3+contest4+contest5+contest6+other_contest);
		$("#contest-list").selectmenu( "refresh");
	});
	
	$("#produce-signup-form").click(function() {
		$("#signup-form").html('');
		$("#search-result-table").html('');
		var contest_count = 0;
		var form_str = "";
		var contest_id = $("#contest-list").val();
		if(contest_id=="0") {
			alertify.alert("請選擇賽事名稱");
		}
		else {
			$.post("/sports/67/handle/Route/route?action=get_contest_form",{"data": [{"contest-id": contest_id}]} , function(response) {
				res = $.parseJSON(response);
				res = res["result"];
				
				if(res.length==0) {
					alertify.alert("查無選擇的賽事報名表");
				}
				else if(res=="is-deadline") {
					alertify.alert("報名截止!");
				}
				else if(res[contest_count]["max_number"]=="participate") {
					form_str += '<fieldset data-role="controlgroup">'+
						'<h2>要參加請勾選</h2>'+
						'<label for="checkbox-signup">參加</label>'+
						'<input data-theme="b" type="checkbox" id="checkbox-signup">'+
						'</fieldset>'+
						'<input value="送出" type="button" id="post-contest-action" onclick="javascript:post_contest('+contest_id+')">';
					$("#signup-form").html(form_str);
					$("#search-result-table").html('');
					$("input[type='checkbox']").checkboxradio();
					$("input[type='checkbox']").checkboxradio('refresh');
				}
				else if(res[contest_count]["max_number"]=="10") {
					form_str += '<h2>最多可以報名兩組，一組四個人第五個為候補。</h2>'+
						'<label for="people1">第一位(請輸入學號)</label>'+
						'<input id="people1" type="text">'+
						'<label for="people2">第二位(請輸入學號)</label>'+
						'<input id="people2" type="text">'+
						'<label for="people3">第三位(請輸入學號)</label>'+
						'<input id="people3" type="text">'+
						'<label for="people4">第四位(請輸入學號)</label>'+
						'<input id="people4" type="text">'+
						'<label for="people5">第五位(候補，選填，請輸入學號)</label>'+
						'<input id="people5" type="text">'+
						'<input value="送出" type="button" id="post-contest-action" onclick="javascript:post_contest2('+contest_id+')">';		
						//$("input[type='checkbox']").checkboxradio('refresh');
					$("#signup-form").html(form_str);
					$("input[type='text']").textinput();
					$("input[type='text']").textinput('refresh');
				}
				else {
					form_str += '<h2>最多可以報名五個人。</h2>'+
						'<label for="people-one">請輸入學號</label>'+
						'<input id="people-one" type="text">'+
						'<input value="送出" type="button" id="post-contest-action" onclick="javascript:post_contest3('+contest_id+')">';
					$("#signup-form").html(form_str);
					$("input[type='text']").textinput();
					$("input[type='text']").textinput('refresh');
				}
			
				$("input[type='button']").button();
				$("input[type='button']").button('refresh');
			});
		}
	});
	
	/*
	$("#print-signup-form").click(function() {
		$.post("/sports/67/handle/Route/route?action=print_signup_form", function(response) {
			res = $.parseJSON(response);
			res = res['result'];
			location.href = "/sports/67/";
		});
	});*/
	
	/*
	$("#content-result")click(function() {
		$.post("/sports/67/handle/Route/route?action=", function(response) {
			
		});
	});*/
	
	$("#logon-action").click(function() {
		$.post("/sports/67/handle/Route/route?action=handle_chief_logon", function(response) {
			res = $.parseJSON(response);
			res = res["result"];
			location.href = "/sports/67/chief/login";
		});
	});
	
	$("#delete-form").click(delete_form);
	
});

function post_contest(contest_id) {
	if($("#checkbox-signup").prop('checked')) {
		$.post("/sports/67/handle/Route/route?action=post_contest_form", {data: [{"contest_id": contest_id}]}, function(response) {
			res = $.parseJSON(response);
			res = res["result"];
			alertify.alert(res);
		});
	}
}

function post_contest2(contest_id) {
	for(var i=1;i<=4;i++) {
		if($("#people"+i).val()=="") {
			alertify.alert("第"+i+"位未填寫!");
			return false;
		}
	}
	
	$.post("/sports/67/handle/Route/route?action=post_contest_form2", {data: [{"contest_id": contest_id,"people1": $("#people1").val(),"people2": $("#people2").val(),"people3": $("#people3").val(),"people4": $("#people4").val(),"people5": $("#people5").val()}]}, function(response) {
		res = $.parseJSON(response);
		res = res["result"];
		if(res=="is-limit")
			alertify.alert("單項徑賽,單項田賽以及400公尺接力等項目最多報名三項!");
		else
			alertify.alert(res);
	});
}

function post_contest3(contest_id) {
	if($("#people-one").val()=="") {
		alertify.alert("未輸入學號!");
	}
	else {
		$.post("/sports/67/handle/Route/route?action=post_contest_form3", {data: [{"contest_id": contest_id,"people-one": $("#people-one").val()}]}, function(response) {
			res = $.parseJSON(response);
			res = res["result"];
			if(res=="is-limit")
				alertify.alert("單項徑賽,單項田賽以及400公尺接力等項目最多報名三項!");
			else
				alertify.alert(res);
		});
	}
}

function delete_form() {
	var contest_id = $("#contest-list").val();
	if(contest_id=="0") {
		alertify.alert("請選擇賽事名稱");
		return false;
	}
	
	$.post("/sports/67/handle/Route/route?action=get_chief_forms", {"data": [{"contest_id": contest_id}]}, function(response) {
		res = $.parseJSON(response);
		res = res["result"];
		$("#signup-form").html("");
		var len = 0;
		if(res.length==0) {
			alertify.alert("查無選擇的賽事紀錄");
		}
		else {
			if(res[0]["team"]=="in-person") {
				var str2 = "<thead>"+
						"<tr><th>比賽項目</th><th>比賽類別</th><th>班級</th><th>學號</th><th>姓名</th>"+
						"<th>性別</th><th>取消報名</th>"+"</tr></thead><tbody>";
			}
			else if(res[0]["team"]=="participate") {
				var str2 = "<thead>"+
					"<tr><th>比賽項目</th>"+"<th>比賽類別</th><th>取消報名</th>"+"</tr></thead><tbody>";
			}
			else {
				var str2 = "<thead>"+
					"<tr><th>比賽項目</th><th>比賽類別</th><th>隊伍編號</th><th>班級</th><th>學號</th><th>姓名</th>"+
					"<th>性別</th><th>取消報名</th>"+"</tr><th>備註</th></thead><tbody>";
			}
		
			for(;len<res.length;len++) {
				if(res[len]["team"]=="participate") {
					str2 += "<tr><th>"+res[len]['item']+"</th>"+"<td>"+(res[len]['category'])+"</td>"+
						"<td>"+"<a href='javascript:post_delete_contest("+contest_id+','+'"'+res[len]["team"]+'"'+','+'"'+res[len]["student_number"]+'"'+")'>取消報名</a></td>"+"</tr>";
				}
				else if(res[len]["team"]=="in-person") {
					str2 += "<tr><th>"+res[len]['item']+"</th>"+"<td>"+(res[len]['category'])+"</td>"+
						"<td>"+res[len]['class']+"</td>"+"<td>"+res[len]['student_number']+"</td>"+"<td>"+res[len]['name']+"</td>"
						+"<td>"+res[len]['gender']+"</td>"+"<td>"+"<a href='javascript:post_delete_contest("+contest_id+','+'"'+res[len]["team"]+'"'+','+'"'+res[len]["student_number"]+'"'+")'>取消報名</a></td>"+"</tr>";
				}
				else {
					var temp = res[len]["team"].split('-');
					if((res.length-1)==len) {
						str2 += "<tr><th>"+res[len]['item']+"</th>"+"<td>"+(res[len]['category'])+"</td>"+
							"<td>"+("隊伍"+temp[1]+'(候補)')+"</td>"+"<td>"+res[len]['class']+"</td>"+
							"<td>"+res[len]['student_number']+"</td>"+"<td>"+res[len]['name']+"</td>"+"<td>"+res[len]['gender']+"</td>"
							+"<td>"+"<a href='javascript:post_delete_contest("+contest_id+','+'"'+res[len]["team"]+'"'+','+'"'+res[len]["student_number"]+'"'+")'>取消報名</a></td><td>按下隊伍裡其中一個取消報名即整個隊伍取消</td>"+"</tr>";
					
					}
					else {
						str2 += "<tr><th>"+res[len]['item']+"</th>"+"<td>"+(res[len]['category'])+"</td>"+
							"<td>"+("隊伍"+temp[1])+"</td>"+"<td>"+res[len]['class']+"</td>"+
							"<td>"+res[len]['student_number']+"</td>"+"<td>"+res[len]['name']+"</td>"+"<td>"+res[len]['gender']+"</td>"
							+"<td>"+"<a href='javascript:post_delete_contest("+contest_id+','+'"'+res[len]["team"]+'"'+','+'"'+res[len]["student_number"]+'"'+")'>取消報名</a></td><td>按下隊伍裡其中一個取消報名即整個隊伍取消</td>"+"</tr>";
					}
				}
			}
				
				$("#search-result-table").html('');
				$("#search-result-table").append(str2+"</tbody>");
				$("#search-result-table").table();
				$("#search-result-table").table("refresh");
		}
	});
}

function post_delete_contest(contest_id,team,student_number) {
	alertify.confirm("確定取消?", function(e) {
		if(e) {
			$.post("/sports/67/handle/Route/route?action=post_delete_contest", {"data": [{"contest_id": contest_id,"team":team,"student_number":student_number}]}, function(response) {
				res = $.parseJSON(response);
				res = res["result"];
				alertify.alert("<h2>"+res+"</h2>", function() {
					delete_form();
				});
			});
		}
	});
}