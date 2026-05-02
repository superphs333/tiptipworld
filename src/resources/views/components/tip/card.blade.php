@props([
    'item' => [],
    'interactiveReactions' => false,
    'openInNewTab' => false,
    'reactionButtonClass' => '',
])

<article class="home-popular__card">
    @if (data_get($item, 'detail_url'))
        <a class="home-popular__thumb" href="{{ data_get($item, 'detail_url') }}" @if ($openInNewTab) target="_blank" rel="noopener noreferrer" @endif>
            <img src="{{ data_get($item, 'thumbnail_url') }}" alt="{{ data_get($item, 'thumbnail_alt', data_get($item, 'title')) }}" loading="lazy">
        </a>
    @else
        <span class="home-popular__thumb">
            <img src="{{ data_get($item, 'thumbnail_url') }}" alt="{{ data_get($item, 'thumbnail_alt', data_get($item, 'title')) }}" loading="lazy">
        </span>
    @endif

    <div class="home-popular__body">
        @if (data_get($item, 'category.url'))
            <a class="home-popular__category" href="{{ data_get($item, 'category.url') }}">
                {{ data_get($item, 'category.name', '미분류') }}
            </a>
        @else
            <span class="home-popular__category">{{ data_get($item, 'category.name', '미분류') }}</span>
        @endif

        @if (data_get($item, 'detail_url'))
            <a class="home-popular__card-title" href="{{ data_get($item, 'detail_url') }}" @if ($openInNewTab) target="_blank" rel="noopener noreferrer" @endif>
                {{ data_get($item, 'title') }}
            </a>
        @else
            <span class="home-popular__card-title">{{ data_get($item, 'title') }}</span>
        @endif

        <div class="home-popular__author-row">
            <x-author-inline
                :name="data_get($item, 'author.name', '작성자 미상')"
                :avatar="data_get($item, 'author.avatar_url', asset('images/avatar-default.svg'))"
                :author-id="(int) data_get($item, 'author.id', 0)"
                variant="card"
                class="home-popular__author"
            />
            <span class="home-popular__views" title="조회수">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M2.4 12s3.6-6 9.6-6 9.6 6 9.6 6-3.6 6-9.6 6-9.6-6-9.6-6Z" stroke="currentColor" stroke-width="1.6" />
                    <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.6" />
                </svg>
                {{ data_get($item, 'metrics.views_text', '0') }}
            </span>
        </div>

        <div class="home-popular__bottom">
            <span class="home-popular__stats">
                @if ($interactiveReactions)
                    <button
                        type="button"
                        class="home-popular__stat {{ $reactionButtonClass }} {{ data_get($item, 'reaction.is_liked') ? 'is-liked' : '' }}"
                        title="좋아요"
                        aria-label="좋아요"
                        data-tip-action="like"
                        aria-pressed="{{ data_get($item, 'reaction.is_liked') ? 'true' : 'false' }}"
                        data-tip-id="{{ data_get($item, 'id') }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke="currentColor" stroke-width="1.6" />
                        </svg>
                        <span data-like-count>{{ data_get($item, 'metrics.likes_text', '0') }}</span>
                    </button>
                @else
                    <span
                        class="home-popular__stat {{ data_get($item, 'reaction.is_liked') ? 'is-liked' : '' }}"
                        title="좋아요"
                        aria-label="{{ data_get($item, 'reaction.is_liked') ? '좋아요함' : '좋아요' }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke="currentColor" stroke-width="1.6" />
                        </svg>
                        {{ data_get($item, 'metrics.likes_text', '0') }}
                    </span>
                @endif

                <span class="home-popular__stat" title="댓글">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4.75 6.5a2.25 2.25 0 0 1 2.25-2.25h10a2.25 2.25 0 0 1 2.25 2.25v7.25A2.25 2.25 0 0 1 17 16h-6.2l-3.95 3.35a.55.55 0 0 1-.9-.42V16H7A2.25 2.25 0 0 1 4.75 13.75V6.5Z" stroke="currentColor" stroke-width="1.6" />
                    </svg>
                    {{ data_get($item, 'metrics.comments_text', '0') }}
                </span>

                @if ($interactiveReactions)
                    <button
                        type="button"
                        class="home-popular__stat {{ $reactionButtonClass }} {{ data_get($item, 'reaction.is_bookmarked') ? 'is-bookmarked' : '' }}"
                        title="북마크"
                        aria-label="북마크"
                        data-tip-action="bookmark"
                        aria-pressed="{{ data_get($item, 'reaction.is_bookmarked') ? 'true' : 'false' }}"
                        data-tip-id="{{ data_get($item, 'id') }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke="currentColor" stroke-width="1.6" />
                        </svg>
                        <span data-bookmark-count>{{ data_get($item, 'metrics.bookmarks_text', '0') }}</span>
                    </button>
                @else
                    <span
                        class="home-popular__stat {{ data_get($item, 'reaction.is_bookmarked') ? 'is-bookmarked' : '' }}"
                        title="북마크"
                        aria-label="{{ data_get($item, 'reaction.is_bookmarked') ? '북마크함' : '북마크' }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke="currentColor" stroke-width="1.6" />
                        </svg>
                        {{ data_get($item, 'metrics.bookmarks_text', '0') }}
                    </span>
                @endif
            </span>
        </div>
    </div>
</article>
