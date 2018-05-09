@extends('newadmin.app')
@section('content')
    <div class="head">banner创建</div>

    <div class="create">
        <form action="/admin/banner/save" method="post" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="group">
                <div class="label">id:</div>
                <input name="model_id" type="text" class="text"/><small>关联模型的id</small>
            </div>
            <div class="group">
                <div class="label">Banner图:</div>
                <input name="file" type="file" class="file"/>
            </div>
            <div class="group" style="display: none;">
                <div class="label">文字:</div>
                <textarea name="content"></textarea>
            </div>
            <div class="group">
                <button type="submit">保存</button>
            </div>
        </form>
    </div>
@endsection