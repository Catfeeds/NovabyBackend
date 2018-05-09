<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html" charset=utf-8" />
    <title>后台登录</title>
    <link href="/newadmin/css/login.css" type="text/css" rel="stylesheet">
</head>
<body>

<div class="login">
    <div class="message"><img src="/newadmin/image/logo2.png" style="padding-right:20px">后台管理</div>
    <form method="post">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input name="uname"  type="text" placeholder="Enter Name" autocomplete="off">
        <hr class="hr15">
        <input name="password" type="password" placeholder="Enter Password">
        <hr class="hr15">
        <input value="登录" type="submit">
        <hr class="hr20">
    </form>
</div>

<div class="copyright">© 2018 Novaby</div>

</body>
</html>