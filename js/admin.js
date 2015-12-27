$(function(){
	
	alertify.set({
		labels:{
			ok: "確定",
			cancel: "取消"
		}
	});
	
	//facebook js sdk initial
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
				//var uid = response.authResponse.userID;
				//var accessToken = response.authResponse.accessToken;
				location.href = "main.html";
			}
			else if (response.status === 'not_authorized') {
				// the user is logged in to Facebook, 
				// but has not authenticated your app
			}
			else {
				// the user isn't logged in to Facebook.
				$("#acc-list").html('');
				$("#acc-list").append('<li><a href="javascript:Login()">使用Facebook帳號登入</a></li>');
				$("#acc-list").append('<li><a href="javascript:Signup()">使用Facebook帳號註冊</a></li>');
				$("#acc-list").listview('refresh');
			}
		});
 
    };
	
	$("#login-btn").bind("click", function(){
		var account = null;
		var pwd = null;
		var recaptcha = null;
		
		account = $("#user-acc").val();
		pwd = $("#user-pwd").val();
		recaptcha = $("#g-recaptcha-response").val();
		if($("#user-acc").val()==""){
			alertify.alert("未輸入帳號!");
		}
		else if($("#user-pwd").val()==""){
			alertify.alert("未輸入密碼!");
		}
		else if(recaptcha==""){
			alertify.alert("未通過驗證!");
		}
		else{
			$.post("/sports/67/handle/Route/route?action=handle_login_admin", {"data": [{"user-acc": account,"user-pwd": pwd,"recaptcha": recaptcha}]} ,function(response){
				console.log(response);
				res = $.parseJSON(response);
				if(res["result"]=="login-success"){
					location.reload();
				}
				else{
					alertify.alert("<h2>登入失敗!</h2>");
				}
			});
		}
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

function getUserInfo() {
	FB.api('/me', function(response) {
		console.log(response.id);
    });
}

function Login() {
	FB.login(function(response) {
		if (response.authResponse) {
			getUserInfo();
			//顯示logout button
			location.href = "main.html";
		}
		else {
			console.log('User cancelled login or did not fully authorize.');
		}
	},{scope: 'email,public_profile'});
}

function Signup() {
	FB.login(function(response) {
		if (response.authResponse) {
			$.post("/sports/67/handle/Route/route?action=post_user_id", {"data": [{"user_id": response.authResponse.userID}]}, function(response) {
				var res = $.parseJSON(response);
				res = res["result"];
				alertify.alert(res);
			});
		}
		else {
			console.log('User cancelled login or did not fully authorize.');
		}
	},{scope: 'email,public_profile'});
}
