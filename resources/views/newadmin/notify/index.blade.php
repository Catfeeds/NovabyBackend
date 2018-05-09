@extends('newadmin.app')
@section('content')
    {{--<span  class="notify-click">Create Notify Template</span>--}}
    <div class="notify">
        <div class="head" >
            <h3>消息</h3>
            <a class="create" href="/admin/notify/create">发消息</a>
        </div>
        @if(!empty($notifies))
            <div class="notify-box">
                <ul>
                    @foreach($notifies as $notify)
                        <li class="notify_id {{$notify->id}}">
                            {{$notify->title}}
                            <input type="hidden" name="id" value="{{$notify->id}}"  id="id"/>
                            <input type="hidden" value="{{$notify->content}}" id="content" name="content" />
                        </li>
                    @endforeach
                </ul>
                <div class="notify-content">
                    <textarea>{!! $first->content !!}</textarea>
                    <input type="hidden" name="notify_id" value="{{$first-> id}}" id="notify_id"/>
                </div>
                <div class="error"></div>
            </div>
        @else
            暂时没有消息
        @endif
    </div>
    <div class="notify-template">
        <h2>Notify Template</h2>
        <form action="/admin/notify/template" method="post">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="group">
                <label>title:</label>
                <input type="text" class="text" name="title">
            </div>
            <div class="group">
                <label>content:</label>
                <textarea  name="content"></textarea>
            </div>
            <button type="submit">save</button>
        </form>
    </div>
@endsection