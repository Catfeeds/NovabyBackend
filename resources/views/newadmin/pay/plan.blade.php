@extends('newadmin.app')
@section('content')
    <div class="user">
        <div class="head">
            <h3>Payment列表</h3>
        </div>
        <div class="close"></div>
        <table>
            <thead>
            <tr>
                <th style="width:1%;">服务</th>
                <th style="width:1%;">付款方</th>
                <th style="width:1%;">支付方式</th>
                <th style="width:1%;">金额</th>
                <th>开始时间</th>
                <th>结束时间</th>
                <th>支付时间</th>
                <th>订单号</th>
            </tr>
            </thead>
            <tbody>
            @foreach($paies as $pay)
                <tr>
                    <td>{{$pay->plan->name}}</td>
                    <td>@if($pay->user->company_name!=null){{$pay->user->company_name}}@else{{$pay->user->user_name}}&nbsp;{{$pay->user->user_lastname}}@endif</td>
                    <td>{{$pay->pay_method}}</td>
                    <td>{{$pay->pay_num}}</td>
                    <td class="pass">{{date('Y-m-d H:i:s',$pay->start_time)}}</td>
                    <td class="failed">{{date('Y-m-d H:i:s',$pay->end_time)}}</td>
                    <td>{{date('Y-m-d H:i:s',$pay->pay_time)}}</td>
                    <td>{{$pay->transaction_no}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="page">
            {!! $paies->render() !!}
        </div>
    </div>
@endsection