@extends('newadmin.app')
@section('content')
    <div class="user">
        <div class="head">
            <h3>Payment列表</h3>
        </div>
        <div class="close"></div>
        {{--<div class="search">--}}
            {{--<div class="input-append date" id="dp3" data-date="12-02-2012" data-date-format="yyyy-mm-dd">--}}
                {{--<input class="add-on" readonly="readonly" size="16" type="text"  value="@if(isset($date)){{$date}}@else @endif">--}}
            {{--</div>--}}
        {{--</div>--}}
        <table>
            <thead>
            <tr>
                <th>项目</th>
                <th>付款方</th>
                <th>支付方式</th>
                <th>金额</th>
                <th>支付时间</th>
                <th>订单号</th>
            </tr>
            </thead>
            <tbody>
            @foreach($paie as $pay)
                <tr>
                    <td>{{$pay->project->prj_name}}</td>
                    <td>{{$pay->user->user_name}}&nbsp;{{$pay->user->user_lastname}}</td>
                    <td>@if($pay->pay_method==2) PayPal @else @endif </td>
                    <td>{{$pay->price}}</td>
                    <td>{{date('Y-m-d H:i:s',$pay->pay_time)}}</td>
                    <td>{{$pay->receipt}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="page">
            {!! $paies->render() !!}
        </div>
    </div>
@endsection