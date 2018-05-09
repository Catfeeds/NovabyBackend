<html>
<head>
</head>
<body>
<div class="main" style=" margin:0 auto;
            max-width: 600px;
            height: auto;
            background-color: #3E3E4D;
            border-radius: 6px;
            color: #FFFFFF;">
    <div class="header" style="height: 69px;
            max-width: 600px;
            background-color: #2A2A36;
            border-radius: 6px;">
        <img src="http://testapi.novaby.com/images/logo.fa32f48.png" style=" margin-left: 36px;
            margin-top: 18px;"/>
    </div>
    <div class="content" style="color: #fff;
            padding: 0 36px;
            max-width: 600px;
            font-size: 14px;
            line-height: 26px;">
        <p>Hello {{$user or 'nanwu'}}</p>
        @yield('content')
        {{--<p style="padding-bottom: 40px;">Thanks for confirming your email! To finalize the confirmation, click the link below. Once you're confirmed, you'll have access to all of our awesome features and can start your journey to becoming a Novaby rockstar. </p>--}}
        @if(isset($url))
        <div class="button" style="margin-top: 40px; width: 172px;
            height: 21px;
            background-color: #EA6264;
            font-size: 16px;
            color: #FFFFFF;
            padding: 6px 13px 12px 13px;
            border-radius: 100px;
            text-align: center;"><a href="{{$url}}" style="color: #FFFFFF;text-decoration:none;font-size: 16px;">Click to view</a></div>
        @endif
        <p style="padding-bottom: 40px;margin-top: 40px">As always, if you have any questions or issues, please contact us here: info@novaby.com or on our forum. We're here to help you get the most out of your Novaby experience, so don't hesitate to reach out.</p>
        <p style="padding-bottom: 60px;">The Novaby team</p>
    </div>
    <div class="footer" style="height: 69px;
            max-width: 600px;
            background-color: #2A2A36;
            border-radius: 6px;">
        <div class="desc">Follow us</div>

        <a href="https://www.facebook.com/NovabyCompany/"><img src="http://testapi.novaby.com/images/icon_facebook.png" style=" float: left;
            padding-top: 20px;
            padding-left: 16px;"/></a>
        <a href="https://twitter.com/novabycompany/"><img src="http://testapi.novaby.com/images/icon_twitter.png" style=" float: left;
            padding-top: 20px;
            padding-left: 12px;"/></a>
        <a href="http://www.linkedin.com/company/novaby/"><img src="http://testapi.novaby.com/images/icon_Linkedin.png" style=" float: left;
            padding-top: 20px;
            padding-left: 12px;"/></a>

        <div class="desc-right" style="float: right;
            color: #8D8D8D;
            font-size: 12px;
            padding-right: 36px;
            padding-top: 29px;">© Novaby 2017</div>

    </div>

</div>

</body>
</html>
