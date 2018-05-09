@extends('newadmin.app')
@section('content')
    <div class="work">
        <div class="head">
            <h3>留言列表</h3>
        </div>
        <table>
            <thead>
            <tr>
                <th>邮箱</th>
                <th></th>
                <th>问题</th>
                <th>id</th>
                <th>内容</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($feedbacks as $feedback)
                <tr>
                    <td>{{$feedback->feed_email}}</td>
                    <td></td>
                    @if($feedback->feed_title)
                        <td>{!! substr($feedback->feed_title,0,50)!!}</td>
                    @else
                        <td></td>
                    @endif
                    @if($feedback->feed_wid)
                        <td>{{$feedback->feed_wid}}</td>
                    @else
                        <td></td>
                    @endif
                    <td>{!! substr($feedback->feed_content,0,50)!!}</td>
                    <td>
                        <a href="/admin/feedback/detail/{{$feedback->feed_id}}" class="look">查看</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="page">
            {!! $feedbacks->render() !!}
        </div>
    </div>
@endsection