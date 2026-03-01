@php
    $initialCategory = (string) ($initialCategory ?? 'all');
    $initialSort = (string) ($initialSort ?? 'latest');
    $initialQuery = trim((string) ($initialQuery ?? ''));
    $initialTags = array_values(array_filter(
        array_map(static fn ($tag) => trim((string) $tag), (array) ($initialTags ?? [])),
        static fn ($tag) => $tag !== ''
    ));
    $totalCount = isset($tipItems) && method_exists($tipItems, 'total')
        ? (int) $tipItems->total()
        : (isset($tipItems) ? (int) $tipItems->count() : 0);
    $firstItem = isset($tipItems) && method_exists($tipItems, 'firstItem') ? $tipItems->firstItem() : null;
    $lastItem = isset($tipItems) && method_exists($tipItems, 'lastItem') ? $tipItems->lastItem() : null;
@endphp

<section class="tip-wireframe tip-list-wireframe tip-search-minimal" data-tip-search-ui>
    <form
        id="tip-search-form"
        class="tip-search-minimal__form"
        method="GET"
        action="{{ route('tips.search') }}"
        data-search-form
        aria-label="검색 조건"
    >
        <section class="tip-search-minimal__search-box" aria-label="통합 검색 영역">
            <div class="tip-search-minimal__search-row">
                <label class="tip-search-minimal__field" for="tip-search-category">
                    <span>카테고리</span>
                    <select class="tip-search-minimal__select" id="tip-search-category" name="category" data-search-category>
                        <option value="all" @selected($initialCategory === '' || $initialCategory === 'all')>전체 카테고리</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $initialCategory === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="tip-search-minimal__field" for="tip-search-query">
                    <span>검색어(제목/작성자)</span>
                    <input
                        class="tip-search-minimal__input"
                        id="tip-search-query"
                        type="search"
                        name="query"
                        value="{{ $initialQuery }}"
                        placeholder="제목/작성자 통합 검색"
                        autocomplete="off"
                        data-search-query
                    >
                </label>

                <label class="tip-search-minimal__field tip-search-minimal__field--sort" for="tip-search-sort">
                    <span>정렬</span>
                    <select class="tip-search-minimal__select" id="tip-search-sort" name="sort" data-search-sort>
                        <option value="latest" @selected($initialSort === 'latest')>최신순</option>
                        <option value="popular" @selected($initialSort === 'popular')>조회순</option>
                        <option value="likes" @selected($initialSort === 'likes')>좋아요순</option>
                        <option value="bookmarks" @selected($initialSort === 'bookmarks')>북마크순</option>
                    </select>
                </label>
            </div>

            <section class="tip-search-minimal__tag-panel" aria-label="검색에 포함할 태그">
                <header class="tip-search-minimal__tag-head">
                    <h2>검색 태그</h2>
                </header>
                <p class="tip-search-minimal__tag-rule">선택한 태그를 모두 포함한 게시글만 검색됩니다.</p>

                <div class="tip-search-minimal__tag-input-wrap">
                    <input
                        class="tip-search-minimal__input"
                        type="search"
                        placeholder="태그 입력"
                        autocomplete="off"
                        data-tag-input
                    >
                    <button class="tip-search-minimal__btn" type="button" data-tag-add>추가</button>
                </div>

                <div class="tip-search-minimal__tags" data-tag-list>
                    <span class="tip-search-minimal__empty" data-tag-empty>선택된 태그 없음</span>
                </div>
            </section>
        </section>

        <div class="tip-search-minimal__actions">
            <button class="tip-search-minimal__btn tip-search-minimal__btn--primary" type="submit">검색</button>
        </div>

        <div class="tip-search-minimal__tag-hidden" data-tag-hidden-inputs></div>
    </form>

    <section class="tip-list-wireframe__list" aria-label="검색 결과">
        <header class="tip-list-wireframe__list-head">
            <div class="tip-list-wireframe__list-heading">
                <h2 class="tip-list-wireframe__section-title">리스트</h2>
                <p>{{ number_format($totalCount) }}개의 게시글</p>
            </div>
        </header>

        <div class="tip-list-wireframe__items">
            @forelse (($tipItems ?? collect()) as $item)
                @php
                    $authorName = data_get($item, 'user.name', '작성자 미상');
                    $authorImage = data_get($item, 'user.profile_image_url', asset('images/avatar-default.svg'));
                    $authorId = (int) data_get($item, 'user.id', 0);
                    $categoryId = (int) data_get($item, 'category.id', 0);
                    $categoryName = trim((string) data_get($item, 'category.name', ''));
                    if ($categoryId > 0 && $categoryName === '') {
                        $categoryName = '카테고리';
                    }
                    $commentCount = (int) data_get($item, 'comment_count', data_get($item, 'comments_count', 0));
                    $likeCount = (int) data_get($item, 'like_count', data_get($item, 'likes_count', 0));
                    $bookmarkCount = (int) data_get($item, 'bookmark_count', data_get($item, 'bookmarks_count', 0));
                    $isLiked = (int) data_get($item, 'is_liked', 0) > 0;
                    $isBookmarked = (int) data_get($item, 'is_bookmarked', 0) > 0;
                    $summarySource = (string) data_get($item, 'excerpt', data_get($item, 'content', ''));
                    $summary = \Illuminate\Support\Str::limit(trim(strip_tags($summarySource)), 110, '...');
                    $tagItems = collect(data_get($item, 'tags', []))
                        ->map(static function ($tag) {
                            return [
                                'id' => (int) data_get($tag, 'id', 0),
                                'name' => trim((string) data_get($tag, 'name', '')),
                            ];
                        })
                        ->filter(static fn ($tag) => $tag['name'] !== '')
                        ->values();
                @endphp
                <article class="tip-list-wireframe__item">
                    <a class="tip-list-wireframe__thumb" href="{{ route('tip.show', ['tip_id' => $item->id]) }}">
                        <img src="{{ data_get($item, 'thumbnailUrl', asset('images/no-thumbnail.png')) }}" alt="{{ $item->title }}" loading="lazy">
                    </a>

                    <div class="tip-list-wireframe__item-body">
                        @if ($categoryId > 0)
                            <a class="tip-list-wireframe__category tip-search-minimal__result-category" href="{{ route('tips.category', ['category_id' => $categoryId]) }}">
                                {{ $categoryName }}
                            </a>
                        @endif

                        <div class="tip-list-wireframe__headline">
                            <a class="tip-list-wireframe__item-title" href="{{ route('tip.show', ['tip_id' => $item->id]) }}">{{ $item->title }}</a>
                        </div>

                        <div class="tip-list-wireframe__meta">
                            <x-author-inline
                                :name="$authorName"
                                :avatar="$authorImage"
                                :author-id="$authorId"
                                variant="list"
                                class="tip-list-wireframe__author"
                            />
                            <span>댓글 {{ number_format($commentCount) }}</span>
                            <span>{{ data_get($item, 'createdDate') }}</span>
                        </div>

                        <p class="tip-list-wireframe__summary">{{ $summary }}</p>

                        @if ($tagItems->isNotEmpty())
                            <div class="tip-wireframe__tags tip-search-minimal__result-tags" aria-label="게시글 태그">
                                @foreach ($tagItems as $tag)
                                    @if ($tag['id'] > 0)
                                        <a class="tip-wireframe__tag tip-search-minimal__result-tag" href="{{ route('tips.tag', ['tag_id' => $tag['id']]) }}">#{{ $tag['name'] }}</a>
                                    @else
                                        <span class="tip-wireframe__tag tip-search-minimal__result-tag">#{{ $tag['name'] }}</span>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <div class="tip-list-wireframe__engagement" aria-label="좋아요 및 북마크">
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
            @empty
                <article class="tip-list-wireframe__item tip-list-wireframe__item--empty">
                    <p>검색 결과가 없습니다.</p>
                </article>
            @endforelse
        </div>

        <footer class="tip-list-wireframe__pagination">
            <span class="tip-list-wireframe__page-meta">
                @if ($firstItem !== null && $lastItem !== null)
                    {{ $firstItem }}-{{ $lastItem }} / 총 {{ number_format($totalCount) }}개
                @else
                    총 {{ number_format($totalCount) }}개
                @endif
            </span>
            @if (isset($tipItems) && method_exists($tipItems, 'hasPages') && $tipItems->hasPages())
                <div class="app-pagination app-pagination--tip">
                    {{ $tipItems->onEachSide(1)->links('vendor.pagination.app') }}
                </div>
            @endif
        </footer>
    </section>
</section>

@once
    <script>
        (() => {
            const root = document.querySelector('[data-tip-search-ui]');
            if (!root) {
                return;
            }

            const form = root.querySelector('[data-search-form]');
            const sortSelect = root.querySelector('[data-search-sort]');
            const tagInput = root.querySelector('[data-tag-input]');
            const tagAddButton = root.querySelector('[data-tag-add]');
            const tagList = root.querySelector('[data-tag-list]');
            const tagEmpty = root.querySelector('[data-tag-empty]');
            const tagHiddenInputs = root.querySelector('[data-tag-hidden-inputs]');
            const initialTags = @json($initialTags);

            if (!form || !tagInput || !tagAddButton || !tagList || !tagEmpty || !tagHiddenInputs) {
                return;
            }

            const state = {
                tags: [],
            };

            const syncTagHiddenInputs = () => {
                tagHiddenInputs.innerHTML = '';
                state.tags.forEach((tag) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'tags[]';
                    hidden.value = tag;
                    tagHiddenInputs.appendChild(hidden);
                });
            };

            const setTagStates = () => {
                const hasTags = tagList.querySelector('[data-tag-chip]') !== null;
                tagEmpty.hidden = hasTags;
                syncTagHiddenInputs();
            };

            const createTagChip = (label) => {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'tip-search-minimal__tag';
                chip.dataset.tagChip = label.toLowerCase();
                chip.textContent = `#${label} ×`;
                tagList.appendChild(chip);
            };

            const addTag = () => {
                const raw = String(tagInput.value || '').trim();
                if (!raw) {
                    return;
                }

                const key = raw.toLowerCase();
                if (state.tags.some((tag) => tag.toLowerCase() === key)) {
                    tagInput.value = '';
                    return;
                }

                state.tags.push(raw);
                createTagChip(raw);
                tagInput.value = '';
                setTagStates();
            };

            form.addEventListener('submit', () => {
                syncTagHiddenInputs();
            });

            if (sortSelect) {
                sortSelect.addEventListener('change', () => {
                    syncTagHiddenInputs();
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                        return;
                    }
                    form.submit();
                });
            }

            tagAddButton.addEventListener('click', addTag);
            tagInput.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') {
                    return;
                }
                event.preventDefault();
                addTag();
            });

            tagList.addEventListener('click', (event) => {
                const chip = event.target.closest('[data-tag-chip]');
                if (!chip) {
                    return;
                }

                const target = String(chip.dataset.tagChip || '').trim();
                state.tags = state.tags.filter((tag) => tag.toLowerCase() !== target);
                chip.remove();
                setTagStates();
            });

            initialTags.forEach((tag) => {
                const label = String(tag || '').trim();
                if (!label) {
                    return;
                }

                if (state.tags.some((current) => current.toLowerCase() === label.toLowerCase())) {
                    return;
                }

                state.tags.push(label);
                createTagChip(label);
            });

            setTagStates();
        })();
    </script>
@endonce
