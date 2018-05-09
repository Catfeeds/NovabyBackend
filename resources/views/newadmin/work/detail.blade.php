@extends('newadmin.app')
@section('content')

<div class="head">模型详情</div>
<div class="detail">
    <div class="group">
        <label>名称:</label>
        {{$work->work_title}}
    </div>

    <div class="group">
        <label>作者:</label>
        {{$work->user->user_name}}
    </div>

    <div class="group">
        <label>描述:</label>
        {{$work->work_description}}
    </div>

    <div class="group">
        <label>作品类型:</label>
        {{$work->cate->cate_name}}
    </div>

    <div class="group">
        <label>标签:</label>

        @if($work->work_tags)
        @if(is_array($work->work_tags))
            @foreach($work->work_tags as $key => $value)
                <span class="tag" id="tag">{{$value->tag_name}}</span>
            @endforeach
        @else
            <span class="tag">{{$work->tag->tag_name}}</span>
        @endif
            @endif
    </div>
    <div class="group">
        <label>texture:</label>
        @if($work->work_texture ==0)no
        @else yes
        @endif
    </div>
    <div class="group">
        <label>animation:</label>
        @if($work->work_animation ==0)no
        @else yes
        @endif</div>
    <div class="group">
        <label>rigged:</label>
        @if($work->work_rigged ==0)no
        @else yes
        @endif</div>
    <div class="group">
        <label>lowpoly:</label>
        @if($work->work_lowpoly ==0)no
        @else yes
        @endif</div>
    <div class="group">
        <label>状态:</label>
        @if($work->work_status=='')<span>未审核</span>
        @elseif($work->work_status==1)<span class="status-yes">已审核,通过</span>
        @else<span class="status-no">已审核,未通过</span>
        @endif
    </div>
    <div class="group">
        <label>缩略图:</label>
        <img src="{{$work->work_cover}}" />
    </div>
    <div class="group">
        <label>图集:</label>
        @if(is_array($work->work_photos))
            @foreach($work->work_photos as $key => $value)
                <img src="{{$value}}" />
            @endforeach
        @else
            <img src="{{$work->work_photos}}" />
        @endif

    </div>
</div>
@endsection