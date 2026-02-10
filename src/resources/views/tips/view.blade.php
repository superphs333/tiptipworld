@extends('layouts.community')

@section('title',  $tip->title)
@section('container_class', 'w-full max-w-none px-0 py-10')

@section('content')
    @include('tips.partials.detail')
@endsection
