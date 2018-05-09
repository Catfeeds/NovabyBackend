@extends('newadmin.app')
@section('content')
    <div class="user">
        <div class="head">
            <h3>申请认证</h3>
        </div>
        <div class="close"></div>
        <table>
            <thead>
            <tr>
                <th>firstname</th>
                <th>lastname</th>
                <th>邮箱</th>
                <th>国家</th>
                <th>城市</th>
                <th>申请模型</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{$user->user_name}}</td>
                    <td>{{$user->user_lastname}}</td>
                    <td>{{$user->user_email}}</td>
                    <td>@if($user->user_country ==0)未知@else{{$user->country->name}}@endif</td>
                    <td>@if($user->user_city ==0)未知@else{{$user->city->name}}@endif</td>
                    <td><a href="/admin/work/detail/{{$user->auth_model}}">{{$user->authModel->work_title}}</a></td>
                    <td>
                        <a href="/admin/modeler/pass/{{$user->user_id}}" class="success">通过</a>
                        <a href="/admin/modeler/fail/{{$user->user_id}}" class="del">拒绝</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="page">
             {!! $users->render() !!}
        </div>
    </div>

@endsection