@extends('newadmin.app')
@section('content')
    <div>
        <div class="head">
            <h3>Project列表</h3>
        </div>
        <table>
            <thead>
            <tr>
                <th>项目名</th>
                <th >项目状态</th>
                <th>发布者</th>
                <th>报价天数</th>
                <th>发布时间</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($projects as $project)
                <tr>
                    <td>{{$project->prj_name}}</td>
                    <td>
                        @if($project->prj_progress==1) <span class="proposal">proposal</span>
                        @elseif($project->prj_progress==2) <span class="contract">building</span>
                        @else <span class="building">submission</span>
                        @endif
                    </td>
                    <td>{{$project->user->user_name}}</td>
                    <td>{{$project->prj_time_day}}</td>
                    <td>{{$project->created_at}}</td>
                    <td>
                        {{--<a href="/admin/project/trans/{{$project->prj_id}}" class="del">转换</a>--}}
                        <a href="/admin/project/detail/{{$project->prj_id}}" class="look">查看</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="page">
        {!! $projects->render() !!}
    </div>
@endsection