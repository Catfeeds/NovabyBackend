@extends('newadmin.app')
@section('content')
    <div class="user">
        <div class="head">
            <h3>企业申请</h3>
        </div>
        <div class="close"></div>
        <table>
            <thead>
            <tr>
                <th>company</th>
                <th>邮箱</th>
                <th>国家</th>
                <th>城市</th>
                <th>申请信息</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{$user->company_name}}</td>
                    <td>{{$user->user_email}}</td>
                    <td>{{$user->country->name}}</td>
                    <td>{{$user->city->name}}</td>
                    <td></td>
                    <td>
                        <a href="/admin/company/pass/{{$user->user_id}}" class="success">通过</a>
                        <a href="/admin/company/fail/{{$user->user_id}}" class="del">拒绝</a>
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