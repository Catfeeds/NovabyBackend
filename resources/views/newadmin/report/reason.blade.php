@extends('newadmin.app')
@section('content')
    <span  class="reason-click">Create</span>
    <div class="reason">
        <div class="head">
            <h3>Reason列表</h3>
        </div>
        <table>
            <thead>
            <tr>
                <th></th>
                <th>原因</th>
                <th></th>
                <th>原因中文</th>
                <th></th>
                <th>分类</th>
                <th>显示</th>

            </tr>
            </thead>
            <tbody>
            @foreach($reasons as $reason)
                <tr>
                    <td></td>
                    <td>{{$reason->content}}</td>
                    <td></td>
                    <td>{{$reason->content_cn}}</td>
                    <td></td>
                    <td>@if($reason->type==1)模型 @else 评论 @endif</td>
                    <td>
                        <input class="reason_active" type="radio" name="active{{$reason->id}}" value="1" @if($reason->display ==1) checked="checked" @else @endif/>是
                        <input class="reason_active" type="radio" name="active{{$reason->id}}" value="0" @if($reason->display ==0) checked="checked" @else @endif/>否
                        <input class="id" type="hidden" value="{{$reason->id}}" />
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="page">
            {!! $reasons->render() !!}
        </div>
    </div>
    <div class="reason-template">
        <h2>Reason Template</h2>
        <form action="/admin/report/reason/template" method="post">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="group">
                <label>type:</label>
                <select name="type" class="text">
                    <option value="1">模型</option>
                    <option value =2>评论</option>
                </select>
            </div>
            <div class="group">
                <label>原因:</label>
                <textarea  name="content"></textarea>
            </div>
            <div class="group">
                <label>中文:</label>
                <textarea  name="content_cn"></textarea>
            </div>
            <button type="submit">save</button>
        </form>
    </div>
@endsection