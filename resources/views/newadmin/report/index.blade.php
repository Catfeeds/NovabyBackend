@extends('newadmin.app')
@section('content')
    <div class="work">
        <div class="head">
            <h3>@if($id==0) 评论举报 @else 模型举报 @endif</h3>
        </div>
        <table>
            <thead>
            <tr>
                <th>@if($id==0) 评论 @else 模型id @endif</th>
                <th>举报人</th>
                <th>举报原因</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($reports as $report)
                <tr>
                    <td>@if($id==0) {{$report->comment->comment_content}} @else <a href="/admin/work/detail/{{$report->work_id}}">{{$report->work_id}}</a> @endif</td>
                    <td>{{$report->user->user_name}}&nbsp;{{$report->user->user_lastname}}</td>
                    <td>@if($report->reason_id==0){{$report->content}}@else {{$report->reason->content}} @endif</td>
                    <td>@if($report->status==0) 待处理 @else <span class="status-no">已处理</span> @endif</td>
                    <td>
                        @if($report->status==0)
                        <a href="/admin/report/del/{{$report->id}}/@if($id==0)0 @else 1 @endif" class="del">删除</a>
                        <a href="/admin/report/ignore/{{$report->id}}/@if($id==0)0 @else 1 @endif" class="ignore">忽略</a>
                        {{--<a href="/admin/work/detail/{{$report->id}}" class="look">查看</a>--}}
                        @else

                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="page">
            {!! $reports->render() !!}
        </div>
    </div>
@endsection