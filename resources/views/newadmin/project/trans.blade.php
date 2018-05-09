@extends('newadmin.app')
@section('content')
    <div class="head">模型转换</div>
    <div class="detail">
        @if (count($errors) > 0)
            <div class="group-error">
                error:
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif
        <div class="group">
            <label>项目名称:</label>
            {{$project->prj_name}}
        </div>
        <div class="group">
            <label>甲方:</label>
            {{$project->user->user_name}}&nbsp;{{$project->user->user_lastname}}
        </div>
        <div class="group">
            <label>乙方:</label>
            @if($project->pro_modeler == null)
                暂无
            @else
                {{$project->modeler->user_name}}{{$project->user->user_lastname}}
            @endif
        </div>
        <div class="group">
            <label>项目周期:</label>
            {{$project->prj_time_day}}天{{$project->prj_time_hour}}小时
        </div>
        <div class="group">
            <label>投标者:</label>
            @if(isset($project->prj_user))
                {{$project->prj_user->user_name}}{{$project->prj_user->user_lastname}}
            @endif
            @if(isset($project->prj_users))
                @foreach($project->prj_users as $user)
                    {{$user->user_name}}{{$user->user_lastname}}
                @endforeach
            @endif
            @if(!isset($project->prj_user) && !isset($project->prj_users))暂无
            @endif
        </div>
        <div class="group">
            <label>虚拟投标:</label>
            <form method="post" action="/admin/project/addUser">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="id" value="{{$project->prj_id}}">
                <input type="text" placeholder="投标者id" name="user"/>
                <input type="text" placeholder="项目天数" name="day"/>
                <input type="text" placeholder="项目小时" name="hour"/>
                <input type="text" placeholder="金额" name="price"/>
                <button type="submit">提交</button>
            </form>
        </div>
        <div class="group">
            <label>金额:</label>
            {{$project->prj_models_tot}}
        </div>
        <div class="group">
            <label>创建时间:</label>
            {{$project->prj_created_at}}
        </div>
        <div class="group">
            <label>图集:</label>
            @if(is_array($project->prj_photos))
                @foreach($project->prj_photos as $key => $value)
                    <img src="{{$value}}" />
                @endforeach
            @else
                <img src="{{$project->prj_photos}}" />
            @endif

        </div>
        <div class="group">
            <label>文件:</label>
            @if(is_array($project->prj_files))
                @foreach($project->prj_files as $key => $value)
                    <a href="{{$project->prj_files}}">下载</a>
                @endforeach
            @elseif($project->prj_files!=null)
                <a href="{{$project->prj_files}}">下载</a>
            @else
            @endif

        </div>
    </div>
    @if ($project->bdid)
        <div class="detail">
            <h3>上传转换后的模型</h3>
            <div><a href="{{$project->day_attachment}}">下载</a></div>
            <form method="post" action="/admin/project/uploadAndTrans" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="pid" value="{{ $project->bdid }}">
                <input type="file" name="file"/>
                <button type="submit">提交</button>
            </form>
        </div>
    @endif
@endsection
