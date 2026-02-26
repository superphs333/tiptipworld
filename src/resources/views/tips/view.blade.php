@extends('layouts.community')
@once
    @vite('resources/js/components/tip-actions.js')
    @vite('resources/js/components/tip-comments.js')
    @vite('resources/js/components/profile.js')
    @vite('resources/js/components/userfeed.js')

@endonce
@php
    $title = null;
    $partial = null;
    $containerClass = 'w-full max-w-none px-0 py-10';

    switch ($viewMode ?? null) {
        case 'detailView':
            $title = isset($tip) ? $tip->title : null;
            $partial = 'tips.partials.detail';
            break;
        case 'tipListBySort':
            $title = $site_title;
            $partial = 'tips.partials.listbysort';
            break;
        case 'frontForm' :
            $title = $site_title;
            $partial = 'tips.partials.frontform';
            $containerClass = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10';
            break;
        case 'tipUserFeed' :
            $title = $site_title;
            $partial = 'tips.partials.userfeed';
            break;
    }
@endphp

@if (!empty($title))
    @section('title', $title)
@endif

@section('container_class', $containerClass)

@section('content')
    @if (!empty($partial))
        @include($partial)
    @endif
@endsection
