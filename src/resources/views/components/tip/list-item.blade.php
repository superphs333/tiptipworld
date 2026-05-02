@props([
    'item' => [],
    'showCategory' => false,
    'showTags' => false,
])

<article class="tip-list-wireframe__item">
    <a class="tip-list-wireframe__thumb" href="{{ data_get($item, 'detail_url') }}">
        <img src="{{ data_get($item, 'thumbnail_url') }}" alt="{{ data_get($item, 'thumbnail_alt', data_get($item, 'title')) }}" loading="lazy">
    </a>

    <div class="tip-list-wireframe__item-body">
        @if ($showCategory && data_get($item, 'category.url'))
            <a class="tip-list-wireframe__category tip-search-minimal__result-category" href="{{ data_get($item, 'category.url') }}">
                {{ data_get($item, 'category.name', '미분류') }}
            </a>
        @elseif ($showCategory)
            <span class="tip-list-wireframe__category tip-search-minimal__result-category">
                {{ data_get($item, 'category.name', '미분류') }}
            </span>
        @endif

        <div class="tip-list-wireframe__headline">
            <a class="tip-list-wireframe__item-title" href="{{ data_get($item, 'detail_url') }}">{{ data_get($item, 'title') }}</a>
        </div>

        <div class="tip-list-wireframe__meta">
            <x-author-inline
                :name="data_get($item, 'author.name', '작성자 미상')"
                :avatar="data_get($item, 'author.avatar_url', asset('images/avatar-default.svg'))"
                :author-id="(int) data_get($item, 'author.id', 0)"
                variant="list"
                class="tip-list-wireframe__author"
            />
            <span>댓글 {{ data_get($item, 'metrics.comments_text', '0') }}</span>
            <span>{{ data_get($item, 'created_text', '-') }}</span>
        </div>

        <p class="tip-list-wireframe__summary">{{ data_get($item, 'summary', '') }}</p>

        @if ($showTags && !empty(data_get($item, 'tags', [])))
            <div class="tip-wireframe__tags tip-search-minimal__result-tags" aria-label="게시글 태그">
                @foreach (data_get($item, 'tags', []) as $tag)
                    @if (data_get($tag, 'url'))
                        <a class="tip-wireframe__tag tip-search-minimal__result-tag" href="{{ data_get($tag, 'url') }}">{{ data_get($tag, 'label') }}</a>
                    @else
                        <span class="tip-wireframe__tag tip-search-minimal__result-tag">{{ data_get($tag, 'label') }}</span>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="tip-list-wireframe__engagement" aria-label="좋아요 및 북마크">
            <button
                type="button"
                class="tip-list-wireframe__engagement-btn {{ data_get($item, 'reaction.is_liked') ? 'is-liked' : '' }}"
                aria-label="좋아요"
                title="좋아요"
                data-tip-action="like"
                aria-pressed="{{ data_get($item, 'reaction.is_liked') ? 'true' : 'false' }}"
                data-tip-id="{{ data_get($item, 'id') }}"
            >
                <span class="tip-list-wireframe__engagement-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" focusable="false">
                        <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke-width="1.6" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="tip-list-wireframe__engagement-label">좋아요</span>
                <span class="tip-list-wireframe__engagement-count" data-like-count>{{ data_get($item, 'metrics.likes_text', '0') }}</span>
            </button>
            <button
                type="button"
                class="tip-list-wireframe__engagement-btn {{ data_get($item, 'reaction.is_bookmarked') ? 'is-bookmarked' : '' }}"
                aria-label="북마크"
                title="북마크"
                data-tip-action="bookmark"
                aria-pressed="{{ data_get($item, 'reaction.is_bookmarked') ? 'true' : 'false' }}"
                data-tip-id="{{ data_get($item, 'id') }}"
            >
                <span class="tip-list-wireframe__engagement-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" focusable="false">
                        <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke-width="1.6" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="tip-list-wireframe__engagement-label">북마크</span>
                <span class="tip-list-wireframe__engagement-count" data-bookmark-count>{{ data_get($item, 'metrics.bookmarks_text', '0') }}</span>
            </button>
        </div>
    </div>
</article>
