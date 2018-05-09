@extends('layouts-mail2')
@section('content')
    <span>Hi，as you submitted your time and price for project ({{$prj_name}}), you have been invited to have a trial on it and the trial fee is {{$price}} USD. Click <a href="{{$url}}">here</a> to start.</span>
@endsection
