<!DOCTYPE html>
<head>
    <style>
        a {  color:#18408F;  }
    </style>
</head>
<body style="width: 820px;font-size: 14px;">
<div style="width:100%;padding:10px;height:500px;">
    <div style="width: 20%;float:left;padding-top: 10px;padding-left: 20px;">
        <div><img src="https://api.novaby.com/images/logo-n.png" /></div>
    </div>
    <div style="width:80%;float:left;padding-left: 20px;">
        <p>您好 {{$user}},</p>
        @if($status==1)
            <p>祝贺! 您的模型师认证申请成功. </p>

            <p>请点击下方链接查看.</p>

            <p><a href="{{$url}}">{{$url}}</a> </p>
        @else
            <p>我们收到您的申请.不幸的是您的模型师认证申请未成功. </p>

            <p><a href="{{$url}}">请点击链接查看并且再次尝试. </a></p>
        @endif
    </div>
    <div style="float: left;width: 100%;padding-left: 20px;">Novaby</div>
</div>
</body>
</html>