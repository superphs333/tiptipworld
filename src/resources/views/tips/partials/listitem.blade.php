@php
    $style = (string) data_get($item, 'style', 'list');
    $showCategory = (bool) ($showCategory ?? data_get($item, 'show_category', false));
    $showTags = (bool) ($showTags ?? data_get($item, 'show_tags', false));
@endphp

@if ($style === 'feed')
    <article class="home-popular__card">
        @if (data_get($item, 'detail_url'))
            <a class="home-popular__thumb" href="{{ data_get($item, 'detail_url') }}">
                <img src="{{ data_get($item, 'thumbnail_url') }}" alt="{{ data_get($item, 'title') }}" loading="lazy">
            </a>
        @else
            <span class="home-popular__thumb">
                <img src="{{ data_get($item, 'thumbnail_url') }}" alt="{{ data_get($item, 'title') }}" loading="lazy">
            </span>
        @endif

        <div class="home-popular__body">
            @if ($showCategory && data_get($item, 'category_url'))
                <a class="home-popular__category" href="{{ data_get($item, 'category_url') }}">{{ data_get($item, 'category_name') }}</a>
            @elseif ($showCategory)
                <span class="home-popular__category">{{ data_get($item, 'category_name') }}</span>
            @endif

            @if (data_get($item, 'detail_url'))
                <a class="home-popular__card-title" href="{{ data_get($item, 'detail_url') }}">{{ data_get($item, 'title') }}</a>
            @else
                <span class="home-popular__card-title">{{ data_get($item, 'title') }}</span>
            @endif

            @if (data_get($item, 'show_author', true) || data_get($item, 'show_views', true))
                <div class="home-popular__author-row">
                    @if (data_get($item, 'show_author', true))
                        <x-author-inline
                            :name="data_get($item, 'author_name', '작성자 미상')"
                            :avatar="data_get($item, 'author_image_url', asset('images/avatar-default.svg'))"
                            :author-id="(int) data_get($item, 'author_id', 0)"
                            variant="card"
                            class="home-popular__author"
                        />
                    @endif

                    @if (data_get($item, 'show_views', true))
                        <span class="home-popular__views" title="조회수">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M2.4 12s3.6-6 9.6-6 9.6 6 9.6 6-3.6 6-9.6 6-9.6-6-9.6-6Z" stroke="currentColor" stroke-width="1.6" />
                                <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.6" />
                            </svg>
                            {{ number_format((int) data_get($item, 'view_count', 0)) }}
                        </span>
                    @endif
                </div>
            @endif

            @if (data_get($item, 'show_stats', true))
                <div class="home-popular__bottom">
                    <span class="home-popular__stats">
                        <span class="home-popular__stat" title="좋아요">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke="currentColor" stroke-width="1.6" />
                            </svg>
                            {{ number_format((int) data_get($item, 'like_count', 0)) }}
                        </span>
                        <span class="home-popular__stat" title="댓글">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4.75 6.5a2.25 2.25 0 0 1 2.25-2.25h10a2.25 2.25 0 0 1 2.25 2.25v7.25A2.25 2.25 0 0 1 17 16h-6.2l-3.95 3.35a.55.55 0 0 1-.9-.42V16H7A2.25 2.25 0 0 1 4.75 13.75V6.5Z" stroke="currentColor" stroke-width="1.6" />
                            </svg>
                            {{ number_format((int) data_get($item, 'comment_count', 0)) }}
                        </span>
                        <span class="home-popular__stat" title="북마크">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke="currentColor" stroke-width="1.6" />
                            </svg>
                            {{ number_format((int) data_get($item, 'bookmark_count', 0)) }}
                        </span>
                    </span>
                </div>
            @endif
        </div>
    </article>
@else
    <article class="tip-list-wireframe__item">
        <a class="tip-list-wireframe__thumb" href="{{ data_get($item, 'detail_url') }}">
            <img src="{{ data_get($item, 'thumbnail_url') }}" alt="{{ data_get($item, 'title') }}" loading="lazy">
        </a>

        <div class="tip-list-wireframe__item-body">
            @if ($showCategory && (int) data_get($item, 'category_id', 0) > 0)
                <a class="tip-list-wireframe__category tip-search-minimal__result-category" href="{{ data_get($item, 'category_url') }}">
                    {{ data_get($item, 'category_name') }}
                </a>
            @endif

            <div class="tip-list-wireframe__headline">
                <a class="tip-list-wireframe__item-title" href="{{ data_get($item, 'detail_url') }}">{{ data_get($item, 'title') }}</a>
            </div>

            <div class="tip-list-wireframe__meta">
                <x-author-inline
                    :name="data_get($item, 'author_name', '작성자 미상')"
                    :avatar="data_get($item, 'author_image_url', asset('images/avatar-default.svg'))"
                    :author-id="(int) data_get($item, 'author_id', 0)"
                    variant="list"
                    class="tip-list-wireframe__author"
                />
                <span>댓글 {{ number_format((int) data_get($item, 'comment_count', 0)) }}</span>
                <span>{{ data_get($item, 'created_label') }}</span>
            </div>

            <p class="tip-list-wireframe__summary">{{ data_get($item, 'summary') }}</p>

            @if ($showTags && !empty(data_get($item, 'tags', [])))
                <div class="tip-wireframe__tags tip-search-minimal__result-tags" aria-label="게시글 태그">
                    @foreach ((array) data_get($item, 'tags', []) as $tag)
                        @if (data_get($tag, 'url'))
                            <a class="tip-wireframe__tag tip-search-minimal__result-tag" href="{{ data_get($tag, 'url') }}">#{{ data_get($tag, 'name') }}</a>
                        @else
                            <span class="tip-wireframe__tag tip-search-minimal__result-tag">#{{ data_get($tag, 'name') }}</span>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="tip-list-wireframe__engagement" aria-label="좋아요 및 북마크">
                <button
                    type="button"
                    class="tip-list-wireframe__engagement-btn {{ data_get($item, 'is_liked') ? 'is-liked' : '' }}"
                    aria-label="좋아요"
                    title="좋아요"
                    data-tip-action="like"
                    aria-pressed="{{ data_get($item, 'is_liked') ? 'true' : 'false' }}"
                    data-tip-id="{{ data_get($item, 'id') }}"
                >
                    <span class="tip-list-wireframe__engagement-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" focusable="false">
                            <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="tip-list-wireframe__engagement-label">좋아요</span>
                    <span class="tip-list-wireframe__engagement-count" data-like-count>{{ number_format((int) data_get($item, 'like_count', 0)) }}</span>
                </button>
                <button
                    type="button"
                    class="tip-list-wireframe__engagement-btn {{ data_get($item, 'is_bookmarked') ? 'is-bookmarked' : '' }}"
                    aria-label="북마크"
                    title="북마크"
                    data-tip-action="bookmark"
                    aria-pressed="{{ data_get($item, 'is_bookmarked') ? 'true' : 'false' }}"
                    data-tip-id="{{ data_get($item, 'id') }}"
                >
                    <span class="tip-list-wireframe__engagement-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" focusable="false">
                            <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="tip-list-wireframe__engagement-label">북마크</span>
                    <span class="tip-list-wireframe__engagement-count" data-bookmark-count>{{ number_format((int) data_get($item, 'bookmark_count', 0)) }}</span>
                </button>
            </div>
        </div>
    </article>
@endif
