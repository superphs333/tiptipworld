@props(['item' => []])

<a
    href="{{ data_get($item, 'detail_url') }}"
    class="bookmark-archive__card"
    data-bookmark-item
    data-category="{{ data_get($item, 'filter.category_value', 'uncategorized') }}"
    data-tags="{{ data_get($item, 'filter.tag_values_text', '') }}"
    aria-label="{{ data_get($item, 'title') }} 상세 보기"
>
    <div class="bookmark-archive__thumb bookmark-archive__thumb--cool" aria-hidden="true">
        <div class="bookmark-archive__thumb-glow"></div>
        <div class="bookmark-archive__thumb-icon">
            <svg viewBox="0 0 120 120" fill="none" focusable="false">
                <circle cx="60" cy="48" r="23" stroke="currentColor" stroke-width="6"/>
                <path d="M42 74c4-9 14-15 18-15s14 6 18 15" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                <path d="M51 93h18" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

    <div class="bookmark-archive__card-body">
        <div class="bookmark-archive__card-top">
            <span class="bookmark-archive__category">{{ data_get($item, 'category.name', '미분류') }}</span>
        </div>

        <h4 class="bookmark-archive__card-title">{{ data_get($item, 'title') }}</h4>

        @if (!empty(data_get($item, 'tags', [])))
            <div class="bookmark-archive__tag-row">
                @foreach (data_get($item, 'tags', []) as $tag)
                    <span class="bookmark-archive__tag">{{ data_get($tag, 'label') }}</span>
                @endforeach
            </div>
        @endif

        <div class="bookmark-archive__meta">
            <span class="bookmark-archive__author">
                <span class="bookmark-archive__author-avatar" aria-hidden="true"></span>
                {{ data_get($item, 'author.name', '작성자 미상') }}
            </span>

            <span class="bookmark-archive__views">
                <svg viewBox="0 0 24 24" fill="none" focusable="false" aria-hidden="true">
                    <path d="M2.4 12s3.6-6 9.6-6 9.6 6 9.6 6-3.6 6-9.6 6-9.6-6-9.6-6Z" stroke="currentColor" stroke-width="1.6"/>
                    <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.6"/>
                </svg>
                {{ data_get($item, 'metrics.views_text', '0') }}
            </span>
        </div>

        <div class="bookmark-archive__reactions">
            <span class="bookmark-archive__reaction {{ data_get($item, 'reaction.is_liked') ? 'is-liked' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" focusable="false" aria-hidden="true">
                    <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke="currentColor" stroke-width="1.6"/>
                </svg>
                {{ data_get($item, 'metrics.likes_text', '0') }}
            </span>
            <span class="bookmark-archive__reaction">
                <svg viewBox="0 0 24 24" fill="none" focusable="false" aria-hidden="true">
                    <path d="M4.75 6.5A2.25 2.25 0 0 1 7 4.25h10A2.25 2.25 0 0 1 19.25 6.5v7.25A2.25 2.25 0 0 1 17 16h-6.2l-3.95 3.35a.55.55 0 0 1-.9-.42V16H7a2.25 2.25 0 0 1-2.25-2.25V6.5Z" stroke="currentColor" stroke-width="1.6"/>
                </svg>
                {{ data_get($item, 'metrics.comments_text', '0') }}
            </span>
            <span class="bookmark-archive__reaction {{ data_get($item, 'reaction.is_bookmarked') ? 'is-bookmarked' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" focusable="false" aria-hidden="true">
                    <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke="currentColor" stroke-width="1.6"/>
                </svg>
                {{ data_get($item, 'metrics.bookmarks_text', '0') }}
            </span>
        </div>
    </div>
</a>
