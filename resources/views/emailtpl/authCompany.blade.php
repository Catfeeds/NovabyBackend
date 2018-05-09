@extends('layouts-mail2')
@section('content')
        @if($status==1)
            <span>Congratulations! Your company application was successful. </span>
            <span>Please click on the link below.</span>
        @else
            <span>We received your application. Unfortunately your company application was not successful. </span>
        @endif
@endsection