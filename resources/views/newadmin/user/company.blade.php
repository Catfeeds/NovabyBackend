@extends('newadmin.app')
@section('content')
    <span  class="user-click">推荐</span>
    <div class="user">
        <div class="head">
            <h3>Company列表</h3>
        </div>
        <div class="close"></div>
        <div class="search">
            <div class="input-append date" id="dp3" data-date="12-02-2012" data-date-format="yyyy-mm-dd">
                <input type="hidden" id="user" value="4"/>
                <input class="add-on" readonly="readonly" size="16" type="text"  value="@if(isset($date)){{$date}}@else @endif">
            </div>
        </div>
        @if(count($users)>0)
        <table>
            <thead>
            <tr>
                <th>头像</th>
                <th>企业名</th>
                <th>邮箱</th>
                <th>注册时间</th>
                <th>国家</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td><img src="{{$user->user_avatar}}"  style="width: 50%;"/></td>
                    <td>{{$user->company_name}}</td>
                    <td>{{$user->user_email}}</td>
                    <td>{{date('Y-m-d',strtotime($user->user_register_time))}}</td>
                    <td>@if($user->user_country ==0)未知@else{{$user->country->name}}@endif</td>
                    <td>
                        <a href="/admin/user/default/{{$user->user_id}}/3">重置头像</a>
                        <a href="/admin/user/partner/{{$user->user_id}}/3">合作伙伴</a>
                    </td>

                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="page">
            总人数:<span class="count">{{$count}}</span> {!! $users->render() !!}
        </div>
        @else

        @endif
    </div>
    <div class="user-recommend">
        <h2>Recommend Modeler</h2>
        <form action="/admin/user/recommend" method="post">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="group">
                <label>user:</label>
                <input type="text" class="text" name="id">
            </div>
            <div class="group">
                <label>explain:</label>
                <textarea  name="explain"></textarea>
            </div>
            <button type="submit">save</button>
        </form>
    </div>

@endsection