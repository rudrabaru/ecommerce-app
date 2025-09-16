@extends('provider::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('provider.name') !!}</p>
@endsection
