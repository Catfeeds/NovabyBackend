@extends('newadmin.app')
@section('content')
    <div class="cash">
        <div class="head">
            <h3>提现列表</h3>
        </div>
        <div class="search">
            <form action="/admin/cash/search" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="text" value="{{$email}}" name="email" class="text" placeholder="Search email"/>
                <button type="submit">搜索</button>
            </form>
        </div>
        <table>
            <thead>
            <tr>
                <th>申请人</th>
                <th>金额(USD)</th>
                <th>paypal账户</th>
                <th>paypal账户名</th>
                <th>订单号</th>
                <th>状态</th>
                <th>申请时间</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($cashes as $cash)
                <tr>
                    <td>{{$cash->user->user_name}}</td>
                    <td><span style="color:red;">{{$cash->amount}}</span></td>
                    <td>{{$cash->paypal_email}}</td>
                    <td>{{$cash->paypal_name}}</td>
                    <td>{{$cash->transaction_no}}</td>
                    <td>@if($cash->status==0)<span>待处理</span>
                        @elseif($cash->status==1)<span class="status-yes">成功</span>
                        @else <span class="status-no">失败</span>
                        @endif
                    </td>
                    <td>{{date('Y-m-d H:i:s',$cash->apply_time)}}</td>
                    <td>
                        @if($cash->status==0)
                        <a href="/admin/cash/status/{{$cash->id}}/1" class="success">成功</a>
                        <a href="/admin/cash/status/{{$cash->id}}/2" class="del">失败</a>
                        @else
                        已处理
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="page">
            {!! $cashes->render() !!}
        </div>
    </div>

@endsection