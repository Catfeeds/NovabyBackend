@extends('newadmin.app')
@section('content')
    <span  class="auth-click">创建</span>
    <div class="auth">
        <div class="head">
            <h3>Config列表</h3>
        </div>
        <div style="padding: 10px;">
            <form action="/admin/auth/saveIntroduction" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <label class="introduction">介绍</label>
                    <textarea name="content" class="introduction-content">{!! $introduction->content !!}</textarea>
                <input type="submit" value="保存" class="submit">
            </form>
        </div>
        <div>
            <table>
                <thead>
                <tr>
                    <th>序号</th>
                    <th>功能</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($functions as $function)
                    <tr>
                        <td>{{$function->id}}</td>
                        <td>{!! $function->content !!}</td>
                        <td>
                            <a href="/admin/banner/destroy/{{$function->id}}" class="look">删除</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="auth-template">
        <h2>Function</h2>
        <form action="/admin/auth/saveFunction" method="post">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="group">
                <label>function:</label>
                <textarea  name="content"></textarea>
            </div>
            <button type="submit">save</button>
        </form>
    </div>
@endsection