@extends('newadmin.app')
@section('content')
    <div class="head">消息创建</div>
    <div class="notify-create create">
        <form action="/admin/notify/postNotify" method="post" >
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            {{--<div class="group">--}}
                {{--<div class="label">消息title:</div>--}}
                {{--<input name="title" type="text" class="text"/>--}}
            {{--</div>--}}
            <div class="group">
                <div class="label">消息对象:</div>
                <input type="radio" name="user_id" class="radio" id="all"><span>all</span>
               <input type="radio" name="user_id" class="radio" id="one"><span>user:</span>
                <input type="text" name="user" value="" class="text" autocomplete="off"/>
                <input type="hidden" name="user_id" value="" id="user_id"/>
            </div>
            <div class="group">
                <div class="label">消息:</div>
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