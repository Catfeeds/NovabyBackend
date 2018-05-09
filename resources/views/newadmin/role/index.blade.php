@extends('newadmin.app')
@section('content')
    <span  class="work-click">首页推荐</span>
    <div class="work">
        <div class="head">
            <h3>角色列表</h3>
        </div>
        <table>
            <thead>
            <tr>
                <th>id</th>
                <th></th>
                <th></th>
                <th>角色</th>
                <th></th>
                <th></th>
                <th>中文</th>
                <th></th>
                <th></th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($roles as $role)
                <tr>
                    <td>{{$role->id}}</td>
                    <td></td>
                    <td></td>
                    <td>{{$role->name}}</td>
                    <td></td>
                    <td></td>
                    <td>{{$role->name_cn}}</td>
                    <td></td>
                    <td></td>
                    <td>
                        <a href="/admin/role/edit/{{$role->id}}" class="market">编辑</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection