@extends('layouts-mail2')
@section('content')
        <span>please use the pin code below to reset your password!</span>
        <span>Here is your pin code:</span>
        <span style="color:#e86367;font-size: 24px;">{{$code}}</span>
@endsection
