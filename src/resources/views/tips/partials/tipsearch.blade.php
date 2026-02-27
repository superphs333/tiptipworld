@php
    $initialCategory = (string) request()->query('category', 'all');
    $initialSort = (string) request()->query('sort', 'latest');
    $initialQuery = trim((string) request()->query('query', ''));

    $requestTags = request()->query('tags', []);
    if (is_string($requestTags)) {
        $requestTags = array_filter(array_map('trim', explode(',', $requestTags)));
    } elseif (!is_array($requestTags)) {
        $requestTags = [];
    }

    $initialTags = array_values(array_filter(
        array_map(static fn ($tag) => trim((string) $tag), $requestTags),
        static fn ($tag) => $tag !== ''
    ));
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
                    <span>검색어(제목/작성자/태그)</span>
                    <input
                        class="tip-search-minimal__input"
                        id="tip-search-query"
                        type="search"
                        name="query"
                        value="{{ $initialQuery }}"
                        placeholder="제목/작성자/태그 통합 검색"
                        autocomplete="off"
                        data-search-query
                    >
                </label>
            </div>

            <section class="tip-search-minimal__tag-panel" aria-label="검색에 포함할 태그">
                <header class="tip-search-minimal__tag-head">
                    <h2>검색 태그</h2>
                </header>

                <div class="tip-search-minimal__tag-input-wrap">
                    <input
                        class="tip-search-minimal__input"
                        type="search"
                        placeholder=""
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
                <p><span data-result-count>0</span>개의 게시글</p>
            </div>
            <div class="tip-list-wireframe__sort-form">
                <label for="tip-search-sort">정렬</label>
                <select id="tip-search-sort" name="sort" form="tip-search-form" data-search-sort>
                    <option value="latest" @selected($initialSort === 'latest')>최신순</option>
                    <option value="popular" @selected($initialSort === 'popular')>조회순</option>
                    <option value="likes" @selected($initialSort === 'likes')>좋아요순</option>
                    <option value="bookmarks" @selected($initialSort === 'bookmarks')>북마크순</option>
                </select>
            </div>
        </header>

        <div class="tip-list-wireframe__items" data-result-list></div>

        <footer class="tip-list-wireframe__pagination">
            <span class="tip-list-wireframe__page-meta" data-result-meta>총 0개</span>
        </footer>
    </section>
</section>

<template id="tip-search-row-template">
    <article class="tip-list-wireframe__item">
        <a class="tip-list-wireframe__thumb" href="#" data-row-link>
            <img src="/images/no-thumbnail.png" alt="" loading="lazy" data-row-thumb>
        </a>

        <div class="tip-list-wireframe__item-body">
            <div class="tip-list-wireframe__headline">
                <a class="tip-list-wireframe__item-title" href="#" data-row-title-link>
                    <span data-row-title></span>
                </a>
            </div>

            <div class="tip-list-wireframe__meta">
                <span class="author-inline author-inline--list tip-list-wireframe__author">
                    <span class="author-inline__profile author-inline__profile--static">
                        <img class="author-inline__avatar" src="/images/avatar-default.svg" alt="" loading="lazy" data-row-author-avatar>
                        <span class="author-inline__name" data-row-author></span>
                    </span>
                </span>
                <span data-row-comments></span>
                <span data-row-date></span>
            </div>

            <p class="tip-list-wireframe__summary" data-row-summary></p>

            <div class="tip-list-wireframe__engagement" aria-label="좋아요 및 북마크">
                <button type="button" class="tip-list-wireframe__engagement-btn" aria-label="좋아요" title="좋아요" disabled>
                    <span class="tip-list-wireframe__engagement-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" focusable="false">
                            <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="tip-list-wireframe__engagement-label">좋아요</span>
                    <span class="tip-list-wireframe__engagement-count" data-row-likes>0</span>
                </button>
                <button type="button" class="tip-list-wireframe__engagement-btn" aria-label="북마크" title="북마크" disabled>
                    <span class="tip-list-wireframe__engagement-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" focusable="false">
                            <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="tip-list-wireframe__engagement-label">북마크</span>
                    <span class="tip-list-wireframe__engagement-count" data-row-bookmarks>0</span>
                </button>
            </div>
        </div>
    </article>
</template>

@once
    <script>
        (() => {
            const root = document.querySelector('[data-tip-search-ui]');
            if (!root) {
                return;
            }

            const mockTips = [
                {
                    title: 'Laravel 배포 전 체크리스트',
                    summary: '배포 전에 꼭 확인할 항목을 체크리스트 형식으로 정리했습니다.',
                    author: '민수',
                    category: 'backend',
                    categoryLabel: '백엔드',
                    tags: ['Laravel', 'Nginx', 'Docker'],
                    comments: 12,
                    likes: 48,
                    bookmarks: 31,
                    views: 1480,
                    date: '2026-02-20',
                    thumb: '/images/no-thumbnail.png',
                },
                {
                    title: 'Blade 템플릿 재사용 패턴 정리',
                    summary: '컴포넌트와 파셜을 분리할 때 실무에서 자주 쓰는 규칙을 모았습니다.',
                    author: '지연',
                    category: 'frontend',
                    categoryLabel: '프론트엔드',
                    tags: ['Blade', 'Laravel'],
                    comments: 8,
                    likes: 29,
                    bookmarks: 17,
                    views: 980,
                    date: '2026-02-18',
                    thumb: '/images/no-thumbnail.png',
                },
                {
                    title: 'MySQL 인덱스 튜닝 기초',
                    summary: '느린 쿼리를 빠르게 만들기 위한 인덱스 설계 포인트를 설명합니다.',
                    author: '준호',
                    category: 'database',
                    categoryLabel: '데이터베이스',
                    tags: ['MySQL', '성능'],
                    comments: 5,
                    likes: 40,
                    bookmarks: 26,
                    views: 1250,
                    date: '2026-02-14',
                    thumb: '/images/no-thumbnail.png',
                },
                {
                    title: 'Redis 캐시 무효화 전략',
                    summary: '캐시 일관성을 지키면서 성능을 얻는 패턴을 사례 중심으로 정리했습니다.',
                    author: '서연',
                    category: 'backend',
                    categoryLabel: '백엔드',
                    tags: ['Redis', '캐시'],
                    comments: 9,
                    likes: 36,
                    bookmarks: 22,
                    views: 1100,
                    date: '2026-02-11',
                    thumb: '/images/no-thumbnail.png',
                },
                {
                    title: 'AWS + Docker 최소 배포 파이프라인',
                    summary: '작은 팀에서 빠르게 운영 가능한 배포 흐름을 단계별로 소개합니다.',
                    author: '하늘',
                    category: 'infra',
                    categoryLabel: '인프라',
                    tags: ['AWS', 'Docker', 'CI/CD'],
                    comments: 14,
                    likes: 57,
                    bookmarks: 44,
                    views: 1820,
                    date: '2026-02-07',
                    thumb: '/images/no-thumbnail.png',
                },
            ];

            const form = root.querySelector('[data-search-form]');
            const queryInput = root.querySelector('[data-search-query]');
            const categorySelect = root.querySelector('[data-search-category]');
            const sortSelect = root.querySelector('[data-search-sort]');

            const tagInput = root.querySelector('[data-tag-input]');
            const tagAddButton = root.querySelector('[data-tag-add]');
            const tagList = root.querySelector('[data-tag-list]');
            const tagEmpty = root.querySelector('[data-tag-empty]');
            const tagHiddenInputs = root.querySelector('[data-tag-hidden-inputs]');

            const resultList = root.querySelector('[data-result-list]');
            const resultCount = root.querySelector('[data-result-count]');
            const resultMeta = root.querySelector('[data-result-meta]');
            const template = document.getElementById('tip-search-row-template');
            const initialTags = @json($initialTags);

            const state = {
                query: String(queryInput.value || '').trim(),
                category: categorySelect.value || 'all',
                sort: sortSelect.value || 'latest',
                tags: [],
            };

            const numberFormat = (value) => new Intl.NumberFormat('ko-KR').format(Number(value) || 0);

            const syncTagHiddenInputs = () => {
                if (!tagHiddenInputs) {
                    return;
                }

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
                renderResults();
            };

            const sortResults = (items) => {
                const list = [...items];
                if (state.sort === 'popular') {
                    return list.sort((a, b) => b.views - a.views);
                }
                if (state.sort === 'likes') {
                    return list.sort((a, b) => b.likes - a.likes);
                }
                if (state.sort === 'bookmarks') {
                    return list.sort((a, b) => b.bookmarks - a.bookmarks);
                }
                return list.sort((a, b) => new Date(b.date) - new Date(a.date));
            };

            const getFilteredResults = () => {
                const keyword = state.query.toLowerCase();
                return sortResults(
                    mockTips.filter((tip) => {
                        const keywordMatch = keyword === ''
                            || tip.title.toLowerCase().includes(keyword)
                            || tip.author.toLowerCase().includes(keyword)
                            || tip.tags.some((tag) => tag.toLowerCase().includes(keyword));

                        const hasMockCategory = ['backend', 'frontend', 'database', 'infra'].includes(state.category);
                        const categoryMatch = state.category === 'all' || !hasMockCategory || tip.category === state.category;

                        const lowerTags = tip.tags.map((tag) => tag.toLowerCase());
                        const tagMatch = state.tags.length === 0
                            || state.tags.some((tag) => lowerTags.includes(tag.toLowerCase()));

                        return keywordMatch && categoryMatch && tagMatch;
                    }),
                );
            };

            const renderRow = (tip) => {
                const fragment = template.content.cloneNode(true);
                const thumb = fragment.querySelector('[data-row-thumb]');
                const rowLink = fragment.querySelector('[data-row-link]');
                const titleLink = fragment.querySelector('[data-row-title-link]');

                thumb.src = tip.thumb;
                thumb.alt = tip.title;
                rowLink.setAttribute('aria-label', tip.title);
                fragment.querySelector('[data-row-title]').textContent = tip.title;
                titleLink.setAttribute('aria-label', tip.title);

                fragment.querySelector('[data-row-author]').textContent = tip.author;
                fragment.querySelector('[data-row-comments]').textContent = `댓글 ${numberFormat(tip.comments)}`;
                fragment.querySelector('[data-row-date]').textContent = tip.date;
                fragment.querySelector('[data-row-summary]').textContent = tip.summary;
                fragment.querySelector('[data-row-likes]').textContent = numberFormat(tip.likes);
                fragment.querySelector('[data-row-bookmarks]').textContent = numberFormat(tip.bookmarks);

                return fragment;
            };

            const renderResults = () => {
                const results = getFilteredResults();
                resultList.innerHTML = '';

                if (!results.length) {
                    const empty = document.createElement('article');
                    empty.className = 'tip-list-wireframe__item tip-list-wireframe__item--empty';
                    empty.innerHTML = '<p>검색 결과가 없습니다.</p>';
                    resultList.appendChild(empty);
                } else {
                    results.forEach((tip) => {
                        resultList.appendChild(renderRow(tip));
                    });
                }

                resultCount.textContent = numberFormat(results.length);
                resultMeta.textContent = `총 ${numberFormat(results.length)}개`;
            };

            form.addEventListener('submit', () => {
                state.query = String(queryInput.value || '').trim();
                state.category = categorySelect.value || 'all';
                state.sort = sortSelect.value || 'latest';
                syncTagHiddenInputs();
            });

            sortSelect.addEventListener('change', () => {
                state.sort = sortSelect.value;
                renderResults();
            });

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
                renderResults();
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
            renderResults();
        })();
    </script>
@endonce
