@extends('newadmin.app')
@section('content')
    <span  class="category-click">新增</span>
    <div class="head">
        <h3>Category列表</h3>
    </div>
    <div class="category">
        <table>
            <thead>
            <tr>
                <th>id</th>
                <th>名称</th>
                <th>中文</th>
                <th>显示</th>
                <th>排序</th>
            </tr>
            </thead>
            <tbody>
            @foreach($categories as $category)
                <tr>
                    <td>{{$category->cate_id}}</td>
                    <td>
                        <input class="cate_name" type="text" value="{{$category->cate_name}}" style="border: 2px solid white;"/>
                        <input class="id" type="hidden" value="{{$category->cate_id}}" />
                    </td>
                    <td>
                        <input class="cate_name_cn" type="text" value="{{$category->cate_name_cn}}" style="border: 2px solid white;"/>
                        <input class="id" type="hidden" value="{{$category->cate_id}}" />
                    </td>
                    <td>
                        <input class="cate_active" type="radio" name="active{{$category->cate_id}}" value="0" @if($category->cate_active ==0) checked="checked" @else @endif/>是
                        <input class="cate_active" type="radio" name="active{{$category->cate_id}}" value="1" @if($category->cate_active ==1) checked="checked" @else @endif/>否
                        <input class="id" type="hidden" value="{{$category->cate_id}}" />
                    </td>
                    <td>
                        <input class="cate_order" type="text" value="{{$category->cate_order}}" style="border: 2px solid white;width: 40px;"/>
                        <input class="id" type="hidden" value="{{$category->cate_id}}" />
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="page">
        {!! $categories->render() !!}
    </div>
    <div class="category-create">
        <h3 style="float: right;" class="category-close">X</h3>
        <h2>Category Create</h2>
        <form action="/admin/category/createCategory" method="post">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="group">
                <label>名称:</label>
                <input type="text" class="text" name="name">
            </div>
            <div class="group">
                <label>中文:</label>
                <input type="text" class="text" name="name_cn">
            </div>
            <button type="submit">save</button>
        </form>
    </div>
@endsection