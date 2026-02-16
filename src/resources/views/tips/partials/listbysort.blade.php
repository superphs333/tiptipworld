@php
    $totalCount = method_exists($tipItems, 'total') ? $tipItems->total() : $tipItems->count();
    $firstItem = method_exists($tipItems, 'firstItem') ? $tipItems->firstItem() : null;
    $lastItem = method_exists($tipItems, 'lastItem') ? $tipItems->lastItem() : null;
    $currentSort = request('sort', 'latest');
@endphp

<section class="tip-wireframe tip-list-wireframe" data-tip-list-wireframe>
    <div class="tip-wireframe__topbar">        
        <div class="tip-wireframe__topbar-right">
            <span class="tip-list-wireframe__mode">{{ mb_strtoupper($sort ?? 'category', 'UTF-8') }} </span>
        </div>
    </div>

    <div class="tip-list-wireframe__hero">
        <article class="tip-list-wireframe__panel tip-list-wireframe__panel--intro">
            <div class="tip-list-wireframe__eyebrow">{{ $sort }}</div>
            <h1 class="tip-list-wireframe__title">{{ $site_title }}</h1>
            <p class="tip-list-wireframe__description">
               {{ $description }}
            </p>
        </article>

        <article class="tip-list-wireframe__panel tip-list-wireframe__panel--snapshot">
            <div class="tip-list-wireframe__count-line">콘텐츠 수 : {{ number_format($totalCount) }}</div>
            <dl class="tip-list-wireframe__snapshot-list">
                <div class="tip-list-wireframe__snapshot-item">
                    <dt>오늘 올라온 글</dt>
                    <dd>{{ $todayTipCount ?? 0 }}</dd>
                </div>
                <div class="tip-list-wireframe__snapshot-item">
                    <dt>평균 좋아요</dt>
                    <dd>{{ $avgLikeCount }}</dd>
                </div>
                <div class="tip-list-wireframe__snapshot-item">
                    <dt>평균 북마크</dt>
                    <dd>{{ $avgBookmarkCount }}</dd>
                </div>
            </dl>
        </article>
    </div>

    <section class="tip-list-wireframe__list" aria-label="팁 리스트">
        <header class="tip-list-wireframe__list-head">
            <div class="tip-list-wireframe__list-heading">
                <h2 class="tip-list-wireframe__section-title">리스트</h2>
                <p>{{ $allCount }}개의 게시글</p>
            </div>

            <form class="tip-list-wireframe__sort-form" method="GET">
                <label for="tips-sort-key">정렬</label>
                <select id="tips-sort-key" name="sort" onchange="this.form.submit()">
                    <option value="latest" @selected($currentSort === 'latest')>최신순</option>
                    <option value="popular" @selected($currentSort === 'popular')>인기순</option>
                    <option value="likes" @selected($currentSort === 'likes')>좋아요순</option>
                    <option value="bookmarks" @selected($currentSort === 'bookmarks')>북마크순</option>
                </select>
            </form>

        </header>

        <div class="tip-list-wireframe__items">           
            @foreach ($tipItems as $item)
            <article class="tip-list-wireframe__item">
                <a class="tip-list-wireframe__thumb" href="#">
                    <img src="{{ $item->thumbnailUrl }}" alt="신입도 바로 쓰는 업무 정리 체크리스트" loading="lazy">
                </a>
                <div class="tip-list-wireframe__item-body">
                    <div class="tip-list-wireframe__headline">
                        {{-- <span class="tip-list-wireframe__category">운영</span> --}}
                        <a class="tip-list-wireframe__item-title" href="{{ route('tip.show',['tip_id'=>$item->id]) }}">{{ $item->title }}</a>
                    </div>
                    <div class="tip-list-wireframe__meta">
                        @php
                            $authorName = data_get($item, 'user.name', '작성자 미상');
                            $authorImage = data_get($item, 'user.profile_image_url', asset('images/avatar-default.svg'));
                            $likeCount = (int) data_get($item, 'like_count', data_get($item, 'likes_count', 0));
                            $bookmarkCount = (int) data_get($item, 'bookmark_count', data_get($item, 'bookmarks_count', 0));
                            $isLiked = (int) data_get($item, 'is_liked', 0) > 0;
                            $isBookmarked = (int) data_get($item, 'is_bookmarked', 0) > 0;
                        @endphp
                        <span class="tip-list-wireframe__author">
                            <img
                                class="tip-list-wireframe__author-avatar"
                                src="{{ $authorImage }}"
                                alt="{{ $authorName }} 프로필"
                                loading="lazy"
                            >
                            <span class="tip-list-wireframe__author-name">{{ $authorName }}</span>
                        </span>
                        <span>댓글 {{ data_get($item, 'comments_count', 0) }}</span>
                        <span>{{ data_get($item,'createdDate') }}</span>
                    </div>
                    <p class="tip-list-wireframe__summary">
                            {{ \Illuminate\Support\Str::limit(strip_tags(data_get($item, 'content', '')), 80, '...') }}
                    </p>
                    <div class="tip-list-wireframe__engagement" aria-label="좋아요 및 북마크 자리">
                        <button
                            type="button"
                            class="tip-list-wireframe__engagement-btn {{ $isLiked ? 'is-liked' : '' }}"
                            aria-label="좋아요"
                            title="좋아요"
                            data-tip-action="like"
                            aria-pressed="{{ $isLiked ? 'true' : 'false' }}"
                            data-tip-id="{{ $item->id }}"
                        >
                            <span class="tip-list-wireframe__engagement-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" focusable="false">
                                    <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke-width="1.6" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="tip-list-wireframe__engagement-label">좋아요</span>
                            <span class="tip-list-wireframe__engagement-count" data-like-count>{{ number_format($likeCount) }}</span>
                        </button>
                        <button
                            type="button"
                            class="tip-list-wireframe__engagement-btn {{ $isBookmarked ? 'is-bookmarked' : '' }}"
                            aria-label="북마크"
                            title="북마크"
                            data-tip-action="bookmark"
                            aria-pressed="{{ $isBookmarked ? 'true' : 'false' }}"
                            data-tip-id="{{ $item->id }}"
                        >
                            <span class="tip-list-wireframe__engagement-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" focusable="false">
                                    <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke-width="1.6" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="tip-list-wireframe__engagement-label">북마크</span>
                            <span class="tip-list-wireframe__engagement-count" data-bookmark-count>{{ number_format($bookmarkCount) }}</span>
                        </button>
                    </div>
                </div>
            </article>
            @endforeach
        </div>
        <footer class="tip-list-wireframe__pagination">
            <span class="tip-list-wireframe__page-meta">
                @if ($firstItem !== null && $lastItem !== null)
                    {{ $firstItem }}-{{ $lastItem }} / 총 {{ number_format($totalCount) }}개
                @else
                    총 {{ number_format($totalCount) }}개
                @endif
            </span>

            @if (method_exists($tipItems, 'hasPages') && $tipItems->hasPages())
                <div class="app-pagination app-pagination--tip">
                    {{ $tipItems->onEachSide(1)->links('vendor.pagination.app') }}
                </div>
            @endif
        </footer>

    </section>
</section>
