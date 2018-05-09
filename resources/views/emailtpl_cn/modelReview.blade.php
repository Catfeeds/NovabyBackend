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
        <p>Hello {{$user}},</p>
        @if($status==1)
            <p>Your model:<strong>{{$model}}</strong> verification was successful.</p>
        @else
            <p>Your model:<strong>{{$model}}</strong> verification was not successful.</p>
        @endif
        <p>Please click on the link below.</p>

        <p><a href="{{$url}}">{{$url}}</a> </p>
    </div>
    <div style="float: left;width: 100%;padding-left: 20px;">Novaby</div>
</div>
</body>
</html>
