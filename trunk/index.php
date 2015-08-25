<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Zimid Login</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="css/global.css" type="text/css" />
	<script type="text/javascript">
	function focusit() {
		document.getElementById('log').focus();
	}
	window.onload = focusit;
	</script>
</head>
<body>

<div id="login">

<div class="logo"><a href="http://zhangdi.name/" alt="Zimid Home"><img src="img/logo1.png" /></a></div>

<form name="loginform" id="loginform" action="openid_login.php" method="post">
<p><label class="login-label" for="log">OpenID:&nbsp;</label><input type="text" class='sexy' name="log" id="log" value="" size="32" tabindex="1" />
<img class="login-button" src="img/right_arrow.gif" onclick="document.forms[0].submit()"></img>
</p>
</form>
</div>

</body>
</html>
