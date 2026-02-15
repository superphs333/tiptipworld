@extends('layouts.community')
@once
    @vite('resources/js/components/tip-actions.js')
    @vite('resources/js/components/tip-comments.js')
@endonce
@php
    $title = null;
    $partial = null;

    switch ($viewMode ?? null) {
        case 'detailView':
            $title = isset($tip) ? $tip->title : null;
            $partial = 'tips.partials.detail';
            break;
        case 'tipListBySort':
            $title = $site_title;
            $partial = 'tips.partials.listbysort';
            break;
    }
@endphp

@if (!empty($title))
    @section('title', $title)
@endif

@section('container_class', 'w-full max-w-none px-0 py-10')

@section('content')
    @if (!empty($partial))
        @include($partial)
    @endif
@endsection
