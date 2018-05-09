@extends('layouts-mail2')
@section('content')
        @if($status==1)
            <span>Your model:<strong>{{$model}}</strong> verification was successful.</span>
        @else
            <span>Your model:<strong>{{$model}}</strong> verification was not successful.</span>
        @endif
@endsection
