<?php if (!defined('THINK_PATH')) exit();?><html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>

<body>

登录
<form action="<?php echo U('api/Passport/doLogin');?>" method="post">
<input type="text" name="login_email" value="admin@admin.com"/>
<input type="text" name="login_password" value="lj6912010"/>
<input type="text" name="login_remember" value="0"/>

<input type="submit"/>
</form>

测试
<form action="<?php echo U('api/Passport/doLogin');?>" method="post">
<input type="text" name="login_email"/>
<input type="text" name="login_password"/>
<input type="text" name="login_remember" value="0"/>

<input type="submit"/>
</form>
</body>
</html>