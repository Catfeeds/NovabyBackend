@extends('layouts-mail2')
@section('content')
        @if($status ==1)
            <span>Congratulations! Your withdrawal is successful.</span>
        @else
            <span>We are sorry to inform you that something wrong with your withdrawal.</span>
        @endif
        <span>Click to view your withdrawal history for more information.</span>
@endsection