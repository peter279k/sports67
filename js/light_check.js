$(function(){
	var res = "";
	//facebook js sdk initial
	alertify.set({
		labels:{
			ok: "確定",
			cancel: "取消"
		}
	});
	
	$("#main-page").hide();
	var user_id = "";
	var token = "";
	window.fbAsyncInit = function() {
		FB.init({
			appId      : '439241719561429', // Set YOUR APP ID
			//channelUrl : 'http://hayageek.com/examples/oauth/facebook/oauth-javascript/channel.html', // Channel File
			status     : true, // check login status
			cookie     : true, // enable cookies to allow the server to access the session
			xfbml      : true  // parse XFBML
		});
		
		FB.getLoginStatus(function(response) {
			if (response.status === 'connected') {
				// the user is logged in and has authenticated your
				// app, and response.authResponse supplies
				// the user's ID, a valid access token, a signed
				// request, and the time the access token 
				// and signed request each expire
				$("#main-page").show();
				console.log("Connected to Facebook");
			}
			else if (response.status === 'not_authorized') {
				// the user is logged in to Facebook, 
				// but has not authenticated your app
				console.log('not_authorized');
				location.href = "admin.html";
			}
			else {
				// the user isn't logged in to Facebook.
				console.log('not_logged');
				location.href = "admin.html";
			}
		});
    };
    
    $("#search-action").click(function() {
    	res = check_login();
    	if(res === "not_authorized" || res === "not_logged") {
    		alertify.alert("尚未登入", function() {
    			location.href = "admin.html";
    		});
    	}
    	else if($("#lightrun-stu-number").val()=="") {
    		alertify.alert("未輸入學號!");
    	}
    	else {
    		var stu_number = $("#lightrun-stu-number").val();
    		var user_id = res.authResponse.userID;
    		$.post("/sports/67/handle/Route/route?action=handle_lightrun_search", {"data":[{"lightrun-stu-number": stu_number,"user-id":user_id}]}, function(response) {
				var res = $.parseJSON(response);
    			res = res['result'];
				
				if(res.length==0) {
					alertify.alert("查無此人的報名紀錄!");
				}
				else {
					var str2 = "<thead>"+
						"<tr><th>學號</th>"+"<th>衣服尺寸</th><th>報名時間</th><th>修改衣服尺寸</th><th>取消報名</th>"+"</tr></thead><tbody>";
					for(var len=0;len<res.length;len++) {
						str2 += "<tr><th>"+res[len]['student_number']+"</th>";
						str2 += "<td>"+res[len]['size']+"</td>";
						str2 += "<td>"+res[len]['signup_time']+"</td>";
			
						str2 += "<td><a href='javascript:edit_lightrun("+'"'+res[len]["student_number"]+'"'+")'>修改衣服尺寸</a></td>";
						str2 += "<td><a href='javascript:cancel_lightrun("+'"'+res[len]["student_number"]+'"'+")'>取消報名</a></td>";
						str2 += "</tr>";
					}
				
					str2 += "</tbody>";
					$("#lightrun-signup-table").html("");
					$("#lightrun-signup-table").append(str2);
					$("#lightrun-signup-table").table("refresh");
				}
    		});
			
    	}
    });
    
	$("#logon-action").click(function() {
		Logout();
	});
	
	// Load the SDK asynchronously
	(function(d){
		var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement('script'); js.id = id; js.async = true;
		js.src = "//connect.facebook.net/zh_TW/all.js";
		ref.parentNode.insertBefore(js, ref);
	}(document));
	
});

function check_login(){
	var result = "";
	FB.getLoginStatus(function(response) {
		if (response.status === 'connected') {
			// the user is logged in and has authenticated your
			// app, and response.authResponse supplies
			// the user's ID, a valid access token, a signed
			// request, and the time the access token 
			// and signed request each expire
			//var uid = response.authResponse.userID;
			//var accessToken = response.authResponse.accessToken;
			result = response;
		}
		else if (response.status === 'not_authorized') {
			// the user is logged in to Facebook, 
			// but has not authenticated your app
			result = 'not_authorized';
		}
		else {
			// the user isn't logged in to Facebook.
			result = 'not_logged';
		}
	});
	
	return result;
}

function cancel_lightrun(student_number) {
	alertify.confirm("確定要取消報名嗎?", function(e) {
		if(e) {
			$.post("/sports/67/handle/Route/route?action=cancel_lightrun_signup", {data: [{"student_number": student_number}]}, function(response) {
				res = $.parseJSON(response);
				res = res["result"];
				if(res=="cancel-signup-success") {
					alertify.alert("已經成功取消報名!");
				}
				else {
					console.log(res);
				}
			});
		}
	});
}

function edit_lightrun(student_number) {
	alertify.prompt("請輸入要修改衣服的size:", function(e,str) {
		if(e) {
			if(str=="") {
				alertify.alert("請輸入衣服size!");
			}
			else {
				$.post("/sports/67/handle/Route/route?action=edit_lightrun_size", {data: [{"student_number": student_number,"size": str}]}, function(response) {
					res = $.parseJSON(response);
					res = res['result'];
					if(res=="no-this-size") {
						alertify.alert("衣服尺寸輸入有誤!");
					}
					else if(res=="edit-size-success") {
						alertify.alert("更改衣服尺寸成功!");
					}
					else {
						console.log(res);
					}
				});
			}
		}
	}, "ex: XS");
}

function Logout() {
    FB.logout(function(){location.href="admin.html";});
	location.href="admin.html";
}