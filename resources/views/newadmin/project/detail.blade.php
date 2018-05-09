@extends('newadmin.app')
@section('content')
    <div class="head">项目详情</div>
    <div class="project-detail">
        <table border="1">
            <tr>
                <th colspan="10" style="font-size: 17px;">项目需求</th>
            </tr>
            <tr>
                <th>项目名称</th>
                <th colspan="2">{{$project->prj_name}}</th>
                <th>模型数量</th>
                <th style="color:red;">{{$project->prj_models_tot}}</th>
                <th>发布者</th>
                <th>
                    {{$project->user->user_name}}
                </th>
                <th>联系邮箱</th>
                <th colspan="2">{{$project->user->user_email}}</th>
            </tr>
            <tr>
                <th>项目类别</th>
                <th>{{$project->industry->cate_name}}</th>
                <th>发布时间</th>
                <th>{{$project->created_at}}</th>
                <th>报价时间</th>
                <th style="color:red;">{{$project->prj_time_day}}天</th>
                <th>个人主页</th>
                <th colspan="3">
                    <a href="{{$project->user->homepage}}" target="_blank">{{$project->user->homepage}}</a>
                </th>
            </tr>
            <tr>
                <th rowspan="2">项目描述</th>
                <th colspan="9" rowspan="2">{!! $project->prj_desc!!}</th>
            </tr>
            <tr></tr>
            <tr>
                <th colspan="10" style="font-size: 17px;">支付信息</th>
            </tr>
                @if($project->modeler!=null)
                    <tr>
                        <th>支付金额(USD)</th>
                        <th style="color:red;">{!! $project->price !!}.00</th>
                        <th>税收(USD)</th>
                        <th style="color:red;">-</th>
                        <th>支付方式</th>
                        <th>
                            @if($project->pay->pay_method==2)
                                PayPal
                            @else

                            @endif
                        </th>
                        <th>收款方</th>
                        <th>Novaby</th>
                        <th>支付时间</th>
                        <th>{{date('Y-m-d H:i:s',$project->pay->pay_time)}}</th>
                    </tr>
                    <tr>
                        <th>订单号</th>
                        <th colspan="9" style="color: red;">{{$project->pay->receipt}}</th>
                    </tr>
                @else
                <tr>
                    <th>支付金额(USD)</th>
                    <th style="color:red;"></th>
                    <th>税收(USD)</th>
                    <th style="color:red;">-</th>
                    <th>支付方式</th>
                    <th></th>
                    <th>收款方</th>
                    <th>Novaby</th>
                    <th>支付时间</th>
                    <th></th>
                </tr>
                <tr>
                    <th>订单号</th>
                    <th colspan="4" style="color: red;"></th>
                </tr>
                @endif
            <tr>
                <th colspan="10" style="font-size: 17px;">报价列表</th>
            </tr>
            <tr>
                <th>报价者</th>
                <th colspan="2">邮箱</th>
                <th colspan="3">个人主页</th>
                <th>报价金额</th>
                <th>周期</th>
                <th>报价时间</th>
                <th>状态</th>
            </tr>
            @if($project->prj_users)
                @foreach($project->prj_users as $apply)
                    <tr>
                        <td>{{$apply->user->user_name}}</td>
                        <td colspan="2">{{$apply->user->user_email}}</td>
                        <td colspan="3"><a href="{{$apply->user->homepage}}">{{$apply->user->homepage}}</a></td>
                        <td style="color:red;">{{$apply->apply_price}}</td>
                        <td style="color: red;">{{$apply->apply_cost_time}}</td>
                        <td>{{date('Y-m-d H:i:s',$apply->apply_time)}}</td>
                        <td style="color: green;">
                            @if($project->modeler!=null && $project->prj_modeler==$apply->user->user_id)被选中
                            @else
                            @endif
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td></td>
                    <td colspan="2"></td>
                    <td colspan="3"><a href=""></a> </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endif
            @if($project->modeler!=null)
                <tr>
                    <th colspan="10" style="font-size: 17px;">项目监控</th>
                </tr>
                <tr>
                    <th>开始时间</th>
                    <th colspan="2" style="color: green;">{{date('Y-m-d H:i:s',$project->pay->pay_time)}}</th>
                    <th>问题数</th>
                    <th style="color: red;">{{count($project->mark)}}</th>
                    <th>已解决</th>
                    <th style="color:green;">{{count($project->resolvedMark)}}</th>
                    <th>结束时间</th>
                    <th colspan="2" style="color:red;">{{date('Y-m-d H:i:s',($project->pay->pay_time+$project->modeler->apply_time+$project->endtime))}}</th>
                </tr>
                <tr>
                    <th>提交日期</th>
                    <th colspan="7">提交内容</th>
                    <th>转换</th>
                    <th>最终上传</th>
                </tr>
            @if($project->day_attachment!=null)
                @foreach($project->day_attachment as $day)
                    <tr>
                        <td>{{date('Y-m-d H:i:s',$day->bd_pubtime)}}</td>
                        <td colspan="7">{{$day->bd_attachment}}</td>
                        <td>
                        </td>
                        <td>
                            @if($day->bd_final==1)是
                            @else
                            @endif
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td></td>
                    <td colspan="7"></td>
                    <td></td>
                    <td></td>
                </tr>
            @endif
                <tr>
                    <th colspan="10" style="font-size: 17px;">项目结果</th>
                @if($project->rate!=null)
                    <tr>
                        <th>甲方审核</th>
                        <th>
                            @if($project->rate->r_result==1) <span style="color: lime;">Fail</span>
                            @else <span style="color: red;">Pass</span>
                            @endif
                        </th>
                        <th>审核时间</th>
                        <th>{{date('Y-m-d H:i:s',$project->rate->r_catetime)}}</th>
                        <th>效率</th>
                        <th>{{$project->rate->r_time}}</th>
                        <th>质量</th>
                        <th>{{$project->rate->r_quality}}</th>
                        <th>沟通</th>
                        <th>{{$project->rate->r_other}}</th>
                    </tr>
                    <tr>
                        <th>审核意见</th>
                        <th colspan="9">{!! $project->rate->r_comnent !!}</th>
                    </tr>
                    @else
                    <tr>
                        <th>甲方审核</th>
                        <th></th>
                        <th>审核时间</th>
                        <th></th>
                        <th>效率</th>
                        <th></th>
                        <th>质量</th>
                        <th></th>
                        <th>沟通</th>
                        <th></th>
                    </tr>
                    <tr>
                        <th>审核意见</th>
                        <th colspan="9"></th>
                    </tr>
                    @endif

            @else
                <tr>
                    <th colspan="10" style="font-size: 17px;">项目监控</th>
                </tr>
                <tr>
                    <th>开始时间</th>
                    <th colspan="2"></th>
                    <th>问题数</th>
                    <th></th>
                    <th>已解决</th>
                    <th></th>
                    <th>结束时间</th>
                    <th colspan="2"></th>
                </tr>
                <tr>
                    <th>提交日期</th>
                    <th colspan="7">提交内容</th>
                    <th>转换</th>
                    <th>最终上传</th>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="7"></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th colspan="10" style="font-size: 17px;">项目结果</th>
                </tr>
                <tr>
                    <th>甲方审核</th>
                    <th></th>
                    <th>审核时间</th>
                    <th></th>
                    <th>效率</th>
                    <th></th>
                    <th>质量</th>
                    <th></th>
                    <th>沟通</th>
                    <th></th>
                </tr>
                <tr>
                    <th>审核意见</th>
                    <th colspan="9"></th>
                </tr>
            @endif
        </table>
    </div>
@endsection