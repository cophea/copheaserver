<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN" xml:lang="zh-CN">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Starter Template for Bootstrap</title>
</head>

<body>

登录
<form action="<?php echo U('Api/Passport/doLogin');?>" method="post">
<input type="text" name="login_email" value="admin@admin.com"/>
<input type="text" name="login_password" value="lj6912010"/>
<input type="text" name="login_remember" value="0"/>

<input type="submit"/>
</form>

测试修改密码
<form action="<?php echo U('Api/Account/doModifyPassword');?>" method="post">
<input type="text" name="oldpassword"/>
<input type="text" name="password"/>
<input type="text" name="repassword"/>

<input type="submit"/>
</form>
</body>
</html>