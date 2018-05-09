@extends('newadmin.app')
@section('content')
    <span  class="work-click">首页推荐</span>
<div class="work">
    <div class="head">
        <h3>Model列表</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>作品名称</th>
                <th>作者</th>
                <th>缩略图</th>
                <th></th>
                {{--<th>模型下载</th>--}}
                <th >状态</th>
                <th></th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        @foreach($work as $w)
            <tr>
                <td>{{$w->work_title}}</td>
                <td>{{$w->user->company_name!=null?$w->user->company_name:$w->user->user_name}}</td>
                <td><img src="{{$w->work_cover}}"></td>
                <td></td>
                {{--<td><a href="{{$w->work_model}}">下载</a></td>--}}
                <td>
                    @if($w->work_status!=1 && $w->work_status!=2)<span>待审核</span>
                    @elseif($w->work_status==1)<span class="status-yes">已审核，通过</span>
                    @elseif($w->work_status==2)<span class="status-no">已审核，未通过</span>
                    @endif
                </td>
                <td></td>
                <td>
                    <a href="/admin/work/review/{{$w->work_id}}/1/{{$id}}" class="success" style="font-size: 14px;">通过</a>
                    <span class="work-auth-click" style="font-size: 14px;"><input type="hidden" name="work_id" value="{{$w->work_id}}" class="work_id"/>不通过</span>
                    <a href="/admin/work/detail/{{$w->work_id}}" class="look">查看</a>
                    <a href="/admin/work/recommend/{{$w->work_id}}/{{$id}}" class="market">推荐</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="page">
        {!! $works->render() !!}
    </div>
</div>
<div class="work-recommend">
    <h3 style="float: right;" class="recommend-close">X</h3>
    <h2>Recommend Model</h2>
    <form action="/admin/work/homeRecommend" method="post">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <div class="group">
            <label>model:</label>
            <input type="text" class="text" name="id">
        </div>
        <button type="submit">save</button>
    </form>
</div>
{{--<div class="work-trans">--}}
    {{--<h3 style="float: right;" class="trans-close">X</h3>--}}
    {{--<h2>Model Trans</h2>--}}
    {{--<form action="/admin/work/trans" method="post" enctype="multipart/form-data">--}}
        {{--<input type="hidden" name="_token" value="{{ csrf_token() }}">--}}
        {{--<input type="hidden" name="type" value="{{$id}}"/>--}}
        {{--<input type="hidden" name="id" value="0" class="id"/>--}}
        {{--<div class="group" >--}}
            {{--转换:<input type="file"  name="file">--}}
        {{--</div>--}}
        {{--<button type="submit">save</button>--}}
    {{--</form>--}}
{{--</div>--}}
<div class="work-auth">
    <h3 style="float: right;" class="auth-close">X</h3>
    <h2>Review Faild</h2>
    <form action="/admin/work/reviewFaild" method="post">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="type" value="{{$id}}"/>
        <input type="hidden" name="auth_id" value="0" class="auth-id"/>
        <div class="group">
            <label>Name:</label>
            <input type="text" class="text" name="1">
        </div>
        <div class="group">
            <label>Category:</label>
            <input type="text" class="text" name="2">
        </div>
        <div class="group">
            <label>Tag:</label>
            <input type="text" class="text" name="3">
        </div>
        <div class="group">
            <label>License:</label>
            <input type="text" class="text" name="4">
        </div>
        <div class="group">
            <label>Pictures:</label>
            <input type="text" class="text" name="5">
        </div>
        <div class="group">
            <label>Model:</label>
            <input type="text" class="text" name="6">
        </div>
        <div class="group">
            <label>Video:</label>
            <input type="text" class="text" name="7">
        </div>
        <div class="group">
            <label>Description:</label>
            <input type="text" class="text" name="8">
        </div>
        <button type="submit">save</button>
    </form>
</div>
@endsection