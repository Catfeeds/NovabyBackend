@extends('newadmin.app')
@section('content')
    <div class="head">角色编辑</div>
    <div class="detail">
        <form method="post" action="/admin/role/save">
            <input type="hidden" name="id" value="{{$id}}">
            <div class="group">
                <label>角色名称:</label>
                <label>{{$role->name}}</label>
            </div>
            <div class="group">
                <label>中文名称:</label>
                <label>{{$role->name_cn}}</label>
            </div>
            <div class="group">
                <label>菜单权限:</label>
                {!! $menu !!}
            </div>
            <button>保存</button>
        </form>
    </div>
@endsection

