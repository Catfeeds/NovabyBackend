<html>
<head>
    <title>novaby后台</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="/newadmin/css/admin.css" rel="stylesheet"/>
    <link href="/newadmin/css/menu.css" rel="stylesheet"/>
    <link href="/newadmin/date/datepicker.css" rel="stylesheet"/>
    <link href="/newadmin/css/bootstrap.css" rel="stylesheet"/>
    <script src="/newadmin/js/jquery-3.2.1.min.js"></script>
    <script src="/newadmin/date/bootstrap-datepicker.js"></script>
    <script src="/newadmin/js/admin.js"></script>
</head>
<body>
<div class="top">
    <div class="logout">
        {{session('admin')}}&nbsp;|<a href="/admin/logout" style="color: white;">退出</a>
    </div>
</div>
<div class="menu">
    @section('menu')
        @include('newadmin.menu')
    @show
</div>
<div class="content">
    @yield('content')
</div>
{{--<div class="footer">2017©Novaby CMS</div>--}}
</body>
</html>