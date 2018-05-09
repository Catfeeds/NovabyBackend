@extends('newadmin.app')
@section('content')
    <span  class="mail-click">Create Mail Template</span>
    <div class="mail">
        <div class="head" >
            <h3>邮件</h3>
            <a class="create" href="/admin/mail/create">Send Mail</a>
        </div>
        @if(!empty($mails))
        <div class="mail-box">
            <ul>
                @foreach($mails as $mail)
                    <li class="mail_id">
                        {{$mail->title}}
                        <input type="hidden" name="id" value="{{$mail->id}}"  id="id"/>
                        <input type="hidden" value="{{$mail->content}}" id="content" name="content" />
                    </li>
                @endforeach
            </ul>
            <div class="mail-content">
                <textarea>{!! $first->content !!}</textarea>
                <input type="hidden" name="mail_id" value="{{$first-> id}}" id="mail_id"/>
            </div>
            <div class="error"></div>
        </div>
        @else
        暂时没有邮件模版
        @endif
    </div>
    <div class="mail-template">
        <h2>Mail Template</h2>
        <form action="/admin/mail/template" method="post">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="group">
                <label>title:</label>
                <input type="text" class="text" name="title">
            </div>
            <div class="group">
                <label>content:</label>
                <textarea  name="content"></textarea>
            </div>
            <button type="submit">save</button>
        </form>
    </div>
@endsection