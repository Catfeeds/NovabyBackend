<!DOCTYPE html>
<head>
    <style>
        a {
            color:#18408F;
        }
    </style>
</head>
<body style="width: 820px;font-size: 14px;">
<div style="width:100%;padding:10px;height:500px;">
    <div style="width: 20%;float:left;padding-top: 10px;padding-left: 20px;">
        <div><img src="https://api.novaby.com/images/logo-n.png" /></div>
    </div>
    <div style="width:80%;float:left;padding-left: 20px;">
        <p>您好 {{$user}},</p>
        @if($status ==1)
            <p>祝贺! 您的提现成功了.</p>
        @else
            <p>我们很遗憾地通知您，您的提现失败了.</p>
        @endif
        <p><a href="{{$url}}">点击查看您的提现记录详情</a>.</p>
    </div>
    <div style="float: left;width: 100%;padding-left: 20px;">Novaby</div>
</div>
</body>
</html>