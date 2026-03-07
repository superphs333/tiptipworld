@extends('layouts.community')

@once
    @vite('resources/js/components/profile.js')
@endonce

{{-- 타이틀 --}}
@section('title', 'TipTipWorld')

@section('content')
    @php
        $totalTips = (int) $categories->sum('tips_count');
        $topCategory = $categories->sortByDesc('tips_count')->first();
        $topTagCategory = $top_tag_category ?? null;
        $topTagCount = (int) data_get($topTagCategory, 'tags_count', 0);
        $topTagCategoryName = $topTagCount > 0 ? data_get($topTagCategory, 'name', '집계 중') : '집계 중';
    @endphp

    <div class="home-shell" data-home-shell>
        <section class="home-hero" aria-label="홈 소개">
            <div class="home-hero__left">
                <p class="home-hero__kicker">TIP DISCOVERY HUB</p>
                <h1 class="home-hero__title">
                    지금 뜨는 팁과 태그를
                    <span>한 번에</span>
                    확인하세요.
                </h1>
                <p class="home-hero__desc">
                    실시간 반응이 높은 글부터 카테고리별 큐레이션까지,
                    TipTipWorld 홈에서 탐색을 시작하세요.
                </p>

                <div class="home-hero__actions">
                    <a class="home-hero__btn home-hero__btn--primary" href="#popular-tips">인기글 보기</a>
                    <a class="home-hero__btn home-hero__btn--ghost" href="#all-categories">카테고리 탐색</a>
                </div>
            </div>

            <aside class="home-hero__stats" aria-label="홈 요약 통계">
                <article class="home-hero__stat-card home-hero__stat-card--summary">
                    <p class="home-hero__stat-label">팁</p>
                    <p class="home-hero__stat-value">{{ number_format($totalTips) }}</p>
                </article>
                <article class="home-hero__stat-card home-hero__stat-card--highlight">
                    <p class="home-hero__stat-label">최다 팁 카테고리</p>
                    <p class="home-hero__stat-strong">{{ data_get($topCategory, 'name', '집계 중') }}</p>
                    <p class="home-hero__stat-sub">
                        {{ number_format((int) data_get($topCategory, 'tips_count', 0)) }}개 팁
                    </p>
                </article>
                <article class="home-hero__stat-card">
                    <p class="home-hero__stat-label">최다 태그 카테고리</p>
                    <p class="home-hero__stat-strong">{{ $topTagCategoryName }}</p>
                    <p class="home-hero__stat-sub">{{ number_format($topTagCount) }}개 태그</p>
                </article>
            </aside>
        </section>

        {{-- 최근 인기글 리스트 --}}
        <div id="popular-tips">
            <x-home.popular-tips :tips="$popular_tips"></x-home.popular-tips>
        </div>

        {{-- 인기 태그 --}}
        <x-home.popular-tags :tags="$popular_tags"></x-home.popular-tags>

        {{-- 인기 태그 별 게시글 (3개씩) --}}

        {{-- 모든 카테고리 --}}
        <x-home.all-category :categories="$categories"></x-home.all-category>

        {{-- 카테고리 별 게시글 3개씩 --}}
    </div>
@endsection
