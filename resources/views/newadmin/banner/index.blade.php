@extends('newadmin.app')
@section('content')
    <div class="head">
        <h3>Banner列表</h3>
        <a class="create" href="/admin/banner/create">新增</a>
    </div>
    <div style="padding: 10px;">
    <form action="/admin/banner/savewords" method="post">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <label>主标题
        <textarea name="title" style="width: 200px;height: 50px;">{{$title}}</textarea>
        </label>
        <label>副标题
        <textarea name="subtitle" style="width: 200px;height: 50px;">{{$subtitle}}</textarea>
        </label>
        <input type="submit" value="保存">
    </form>
    </div>
    <div>
        <table>
            <thead>
            <tr>
                <th>序号</th>
                <th>缩略图</th>
                <th >关联模型id</th>
                <th style="display: none;">文字</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($banner as $ban)
                <tr>
                    <td>{{$ban->id}}</td>
                    <td><img src="{{$ban->photo}}" /></td>
                    <td>{{$ban->model_id}}</td>
                    <td style="display: none;">{!! $ban->words !!}</td>
                    <td>
                        <a href="/admin/banner/destroy/{{$ban->id}}"class="look">删除</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="page">
        {!! $banners->render() !!}
    </div>
@endsection