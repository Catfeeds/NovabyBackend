@extends('layouts-mail2')
@section('content')
        @if($status==1)
            <span>Congratulations! Your model:<strong>{{$model}}</strong> was converted successfully.  Please click the link below.</span>
        @else
            <span>We received your release. Unfortunately, your model :<strong>{{$model}}</strong> conversion failed.</span>
        @endif
@endsection