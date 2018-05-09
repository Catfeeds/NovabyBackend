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

        <p>Congratulations on posting your new project, {{$project}} !</p>

        <p>It's an exciting time! We've notified hundreds of artists about your project and you should start to receive bids from them in just a few moments.</p>


        <p><strong>What's next?</strong></p>

        <p><strong>1. Receive bids from artists interested in your project. You can view their bids by visiting your project page.</strong></p>


        <p><strong>2. Compare their proposals, profiles, previous work history and chat with them in real-time to discuss your project.</strong></p>


        <p><strong>3. Award the project to your preferred freelancer and get your job done!</strong></p>


        <p>Don't wait - see your first bids rolling in!</p>

        <p><a href="{{$url}}">View your bids now</a></p>

        <p>Best regards,</p>
    </div>
    <div style="float: left;width: 100%;padding-left: 20px;">Novaby</div>
</div>
</body>
</html>