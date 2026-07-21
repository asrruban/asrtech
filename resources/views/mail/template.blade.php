@extends('mail.layout')

@section('badge', $badge ?? '')

@section('content')
    {!! $body !!}
@endsection
