@extends('newadmin.app')
@section('content')
    <div class="head">邮件创建</div>

    <div class="mail-create create">
        <form action="/admin/mail/postMail" method="post" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="group">
                <div class="label">邮件对象:</div>
                <input type="radio" name="user_id" class="radio" id="all"><span>all</span>
                <input type="radio" name="user_id" class="radio" id="one"><span>user:</span>
                <input type="text" name="user" value="" class="text" autocomplete="off"/>
                <input type="hidden" name="user_id" value="" id="user_id"/>
            </div>
            <div class="group">
                <div class="label">邮件内容:</div>
                <textarea name="content"></textarea>
            </div>
            <input type="hidden" name="type" value="5" />
            <div class="group">
                <button type="submit">发送</button>
            </div>
        </form>
        <div class="user-search">
            <ul>
            </ul>
        </div>
    </div>
@endsection