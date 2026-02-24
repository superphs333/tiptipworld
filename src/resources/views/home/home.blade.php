@extends('layouts.community')

{{-- 타이틀 --}}
@section('title', 'TipTipWorld') 

@section('content')

    {{-- 최근 인기글 리스트 --}}
    <x-home.popular-tips :tips="$popular_tips" ></x-home.popular-tips>

    {{-- 인기 태그 --}}
    <x-home.popular-tags :tags="$popular_tags"></x-home.popular-tags>


    {{-- 인기 태그 별 게시글 (3개씩) --}}

    {{-- 모든 카테고리 --}}
    <x-home.all-category :categories="$categories"></x-home.all-category>

    {{-- 카테고리 별 게시글 3개씩  --}}
@endsection
