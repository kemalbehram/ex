<!DOCTYPE html>
<html lang="en" style="font-size: 100px;">
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
	<title>Invitation Registration</title>
    <script type="text/javascript" src="/resource/frontend/js/jquery.min.js"></script>
	<style type="text/css">
		*{
			margin: 0;
			padding: 0;
		}
		html,body{
			height: 100%;
		}
		body{
			background-color: #2e3349;
		}
		.auto_image{
			width: auto;
			height: auto;
			max-width: 100%;
			max-height: 100%;
		}
		.content{
			margin: 0.44rem 0.4rem 0;
		}
		.logo{
			width: 150px;
			margin: 0 auto 0.22rem;
		}
		.logo img{
			border-radius: 0.3rem;
		}
		.input-area .shurukuang{
			position: relative;
			width: 100%;
			height: 0.50rem;
			border-bottom: 1px solid #7a87b0;
			display: flex;
			align-items: center;
		}
		.shurukuang input{
			margin-left: 0.05rem;
			display: inline-block;
			height: 100%;
			width: 85%;
			border: none;
			outline: none;
			color: #cfd3e9;
			background-color: transparent;
		}

		button.varcodebutton{
			position: absolute;
		    right: 0;
		    top: 0;
		    z-index: 1;
		    height: 100%;
		    padding: 0 4px;
		    color: #21A9ED;
		    background: none;
		    border: none;
		    outline: none;
		}
		.reg{
			margin-top: 0.5rem;
			width: 100%;
			height: 0.4rem;
			background-color: #21A9ED;
			border: none;
			color: white;
		}
	</style>
</head>
<body style="font-size: 15px;">

	<div class="content">
		<div class="logo">
			<img class="auto_image" src="<?= Yii::$app->config->info('WEB_SITE_LOGO') ?>">
		</div>

		<div class="input-area">
			<div class="shurukuang">
				<input id="area_code" type="text" placeholder="Please enter the area code" value="91">
			</div>
			<div class="shurukuang">
				<input id="mobile_phone" type="text" placeholder="Please enter your phone number">
			</div>
			<div class="shurukuang">
				<input id="password" type="password" placeholder="Please enter your password">
			</div>
			<div class="shurukuang">
				<input id="repassword" type="password" placeholder="Please enter your password again">
			</div>
			<div class="shurukuang">
				<input id="varcode" type="text" placeholder="Please enter CAPTCHA">
				<button id="getVar" class="varcodebutton">Get Code</button>
			</div>
			<div class="shurukuang">
				<input id="code" type="text" placeholder="Invitation code (optional)">
			</div>
		</div>
		<button class="reg" id="reg">Register</button>
        <div style="text-align: right;margin-top: 5px;"><a href="?os=<?php echo $os;?>&code=<?php echo $code;?>&type=email" style="color: white;">E-mail registration</a></div>

		<a href="<?= Yii::$app->config->info('APP_DOWNLOAD_URL') ?>" style="color: #fff; padding: 10px 0; width: 100%; margin: 0 auto; display: block; text-align: center; text-decoration: none;">Download App</a>	
	</div>


<script type="text/javascript">
	var api = "/api/";
	var down = "<?= Yii::$app->config->info('APP_DOWNLOAD_URL') ?>";

	$(document).ready(function(){
	
		let invite_code = GetQueryString("code")
		if(invite_code !=null && invite_code.toString().length>1){
			$('#code').val(invite_code);
			$('#code').attr("readonly",true);
		}

		$('#getVar').click(function(){

			let phone = $("#mobile_phone").val();
			let area_code = $("#area_code").val();
			if (area_code.length == 0) {
				alert("Area code error")
				return;
			}
			if (phone.length == 0) {
				alert("Phone number error")
				return;
			}

			isDisableVarCode(true)

			$.post(
	         	api + "register/mobile-varcode",
	         	{
	         		"mobile_phone":phone,
	         		"area_code":area_code,
	         		"type":"1",
	         		"language": 'en_us'
	         	},
	         	function(data, textStatus){
	         		if (data.code != 200) {
	         			isDisableVarCode(false)
	         			alert(data.message)
	         		}else{
	         			varCodeCutDown()
	         		}
	         	},
	         	"json")
		});

		$('#reg').click(function(){
			let area_code = $("#area_code").val();
			let phone = $("#mobile_phone").val()
			let password = $("#password").val()
			let repassword = $("#repassword").val()
			let varcode = $("#varcode").val()

			if (phone.length == 0 || password.length == 0 || repassword.length == 0 || varcode.length == 0) {
				alert("Incomplete information!")
				return;
			}

			$.post(
	         	api + "register/register",
	         	{
	         		"area_code":area_code,
	         		"mobile_phone":phone,
	         		"password":password,
	         		"repassword":repassword,
	         		"varcode": varcode,
	         		"code":$("#code").val(),
	         		"language": 'en_us'
	         	},
	         	function(data, textStatus){
	         		if (data.code == 200) {
	         			alert("Registration successful");
	         			let os = GetQueryString("os")
	         			if (os === "ios") {
	         				$(location).attr('href', down);
	         			}else{
	         				$(location).attr('href', down);
	         			}
	         		}else{
	         			alert(data.message)
	         		}
	         	},
	         	"json")
		});

	});

	//发送验证码 倒计时
	function varCodeCutDown(){
		$('#getVar').html("60s");
		var second = 60;
		var timer = null;
		timer = setInterval(function(){
			second -= 1;
			if(second >0 ){
				$('#getVar').html(second + "s");
			}else{
				clearInterval(timer);
				$('#getVar').html("Get Code");
				isDisableVarCode(false)
			}
		},1000);
	}

	function isDisableVarCode(isDisable){
		if (isDisable) {
			$("#getVar").attr("disabled", "true");
		}else{
			$("#getVar").removeAttr("disabled");//启用按钮
		}
	}

	function GetQueryString(name){
		var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
	    var r = window.location.search.substr(1).match(reg);//search,查询？后面的参数，并匹配正则
	    if(r!=null)return  unescape(r[2]); return null;
	}

</script>
</body>
</html>