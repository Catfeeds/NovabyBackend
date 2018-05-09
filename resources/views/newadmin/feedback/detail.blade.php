@extends('newadmin.app')
@section('content')

    <div class="head">留言详情</div>
    <div class="detail">
        <div class="group">
            <label>邮箱:</label>
            {{$feedback->feed_email}}
        </div>
        <div class="group">
            <label>标题:</label>
            {{$feedback->feed_title}}
        </div>
        <div class="group">
            <label>内容:</label>
            {{$feedback->feed_content}}
        </div>
    </div>
@endsection