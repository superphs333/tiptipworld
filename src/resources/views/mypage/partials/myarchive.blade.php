@php
    $dummyBookmarks = [
        [
            'category' => '추가',
            'title' => '[SEED-C20] 027 카테고리20 테스트 팁',
            'author' => 'hihi3333',
            'saved_type' => '북마크',
            'views' => 1215,
            'likes' => 0,
            'comments' => 0,
            'bookmarks' => 0,
            'tags' => ['seed', '카테고리20', '테스트'],
            'thumb_tone' => 'warm',
        ],
        [
            'category' => '추가',
            'title' => '[SEED-C20] 058 카테고리20 테스트 팁',
            'author' => 'hihi3333',
            'saved_type' => '북마크',
            'views' => 2047,
            'likes' => 0,
            'comments' => 0,
            'bookmarks' => 1,
            'tags' => ['seed', '온보딩', '모더라'],
            'thumb_tone' => 'cool',
        ],
        [
            'category' => '추가',
            'title' => '[SEED-C20] 024 카테고리20 테스트 팁',
            'author' => 'hihi3333',
            'saved_type' => '좋아요',
            'views' => 4083,
            'likes' => 2,
            'comments' => 0,
            'bookmarks' => 0,
            'tags' => ['seed', '카테고리20'],
            'thumb_tone' => 'cool',
        ],
        [
            'category' => '정리',
            'title' => '[SEED-C20] 049 카테고리20 테스트 팁',
            'author' => 'hihi3333',
            'saved_type' => '북마크',
            'views' => 4116,
            'likes' => 0,
            'comments' => 0,
            'bookmarks' => 0,
            'tags' => ['정리', '체크리스트'],
            'thumb_tone' => 'cool',
        ],
        [
            'category' => '브랜딩',
            'title' => '[SEED-C20] 003 카테고리20 테스트 팁',
            'author' => 'hihi3333',
            'saved_type' => '좋아요',
            'views' => 5678,
            'likes' => 3,
            'comments' => 0,
            'bookmarks' => 0,
            'tags' => ['브랜딩', '카피'],
            'thumb_tone' => 'cool',
        ],
        [
            'category' => '자동화',
            'title' => '[SEED-C20] 080 카테고리20 테스트 팁',
            'author' => 'hihi3333',
            'saved_type' => '북마크',
            'views' => 4857,
            'likes' => 0,
            'comments' => 0,
            'bookmarks' => 0,
            'tags' => ['자동화', '리포트', '운영'],
            'thumb_tone' => 'cool',
        ],
    ];

    $buildArchiveMeta = static function (array $items): array {
        $collection = collect($items);

        return [
            'count' => $collection->count(),
            'categories' => $collection
                ->groupBy('category')
                ->map(static fn ($group, $name) => [
                    'name' => $name,
                    'count' => $group->count(),
                ])
                ->sortByDesc('count')
                ->values()
                ->all(),
            'tags' => $collection
                ->flatMap(static fn ($item) => $item['tags'])
                ->countBy()
                ->map(static fn ($count, $name) => [
                    'name' => $name,
                    'count' => $count,
                ])
                ->sortByDesc('count')
                ->values()
                ->take(6)
                ->all(),
        ];
    };

    $bookmarkItems = collect($dummyBookmarks)
        ->where('saved_type', '북마크')
        ->values()
        ->all();

    $likeItems = collect($dummyBookmarks)
        ->where('saved_type', '좋아요')
        ->values()
        ->all();

    $tabSets = [
        'bookmarks' => [
            'label' => '북마크',
            'items' => $bookmarkItems,
            'meta' => $buildArchiveMeta($bookmarkItems),
        ],
        'likes' => [
            'label' => '좋아요',
            'items' => $likeItems,
            'meta' => $buildArchiveMeta($likeItems),
        ],
    ];

    $bookmarkCount = count($bookmarkItems);
    $likeCount = count($likeItems);
@endphp

<section class="bookmark-archive" data-bookmark-archive>
    <header class="bookmark-archive__profile">
        <div class="bookmark-archive__identity">
            <div class="bookmark-archive__avatar" aria-hidden="true">
                <span>B</span>
            </div>

            <div class="bookmark-archive__identity-body">
                <p class="bookmark-archive__kicker">MY BOOKMARKS</p>
                <h2 class="bookmark-archive__name">보관한 게시글</h2>
                <p class="bookmark-archive__summary">
                    북마크 {{ number_format($bookmarkCount) }}개 · 좋아요 {{ number_format($likeCount) }}개 · 총 {{ number_format(count($dummyBookmarks)) }}개
                </p>
            </div>
        </div>

        <div class="bookmark-archive__stats">
            <div class="bookmark-archive__stat-box">
                <strong>{{ number_format($bookmarkCount) }}</strong>
                <span>BOOKMARKS</span>
            </div>
            <div class="bookmark-archive__stat-box">
                <strong>{{ number_format($likeCount) }}</strong>
                <span>LIKES</span>
            </div>
            <div class="bookmark-archive__pill">아카이브</div>
        </div>
    </header>

    <div class="bookmark-archive__tabs" role="tablist" aria-label="보관함 탭">
        @foreach ($tabSets as $tabKey => $tab)
            <button
                type="button"
                class="bookmark-archive__tab {{ $loop->first ? 'is-active' : '' }}"
                data-bookmark-tab-trigger="{{ $tabKey }}"
                role="tab"
                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
            >
                <span>{{ $tab['label'] }}</span>
                <em>{{ number_format($tab['meta']['count']) }}</em>
            </button>
        @endforeach
    </div>

    @foreach ($tabSets as $tabKey => $tab)
        <section
            class="bookmark-archive__panel {{ $loop->first ? 'is-active' : '' }}"
            data-bookmark-tab-panel="{{ $tabKey }}"
            @if (! $loop->first) hidden @endif
        >
            <section class="bookmark-archive__insight" aria-label="{{ $tab['label'] }} 인사이트">
                

                <div class="bookmark-archive__insight-grid">
                    <article class="bookmark-archive__insight-card">
                        <h4>카테고리</h4>
                        <div class="bookmark-archive__chips">
                            @forelse ($tab['meta']['categories'] as $item)
                                <span class="bookmark-archive__chip">
                                    {{ $item['name'] }}
                                    <em>{{ number_format($item['count']) }}</em>
                                </span>
                            @empty
                                <span class="bookmark-archive__empty-chip">카테고리 없음</span>
                            @endforelse
                        </div>
                    </article>

                    <article class="bookmark-archive__insight-card">
                        <h4>태그</h4>
                        <div class="bookmark-archive__chips">
                            @forelse ($tab['meta']['tags'] as $item)
                                <span class="bookmark-archive__chip">
                                    #{{ $item['name'] }}
                                    <em>{{ number_format($item['count']) }}</em>
                                </span>
                            @empty
                                <span class="bookmark-archive__empty-chip">태그 없음</span>
                            @endforelse
                        </div>
                    </article>
                </div>
            </section>

            <section class="bookmark-archive__feed" aria-label="{{ $tab['label'] }} 피드">
                <header class="bookmark-archive__feed-head">
                    <div class="bookmark-archive__feed-heading">
                        <h3 class="bookmark-archive__section-title">Feed</h3>
                        <p>{{ number_format($tab['meta']['count']) }}개의 게시글</p>
                    </div>
                </header>

                @if ($tab['meta']['count'] > 0)
                    <div class="bookmark-archive__grid">
                        @foreach ($tab['items'] as $item)
                            @php
                                $toneClass = $item['thumb_tone'] === 'warm'
                                    ? 'bookmark-archive__thumb--warm'
                                    : 'bookmark-archive__thumb--cool';
                            @endphp

                            <article class="bookmark-archive__card">
                                <div class="bookmark-archive__thumb {{ $toneClass }}" aria-hidden="true">
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
                                        <span class="bookmark-archive__category">{{ $item['category'] }}</span>
                                        <span class="bookmark-archive__saved">{{ $item['saved_type'] }}</span>
                                    </div>

                                    <h4 class="bookmark-archive__card-title">{{ $item['title'] }}</h4>

                                    <div class="bookmark-archive__tag-row">
                                        @foreach ($item['tags'] as $tag)
                                            <span class="bookmark-archive__tag">#{{ $tag }}</span>
                                        @endforeach
                                    </div>

                                    <div class="bookmark-archive__meta">
                                        <span class="bookmark-archive__author">
                                            <span class="bookmark-archive__author-avatar" aria-hidden="true"></span>
                                            {{ $item['author'] }}
                                        </span>

                                        <span class="bookmark-archive__views">
                                            <svg viewBox="0 0 24 24" fill="none" focusable="false" aria-hidden="true">
                                                <path d="M2.4 12s3.6-6 9.6-6 9.6 6 9.6 6-3.6 6-9.6 6-9.6-6-9.6-6Z" stroke="currentColor" stroke-width="1.6"/>
                                                <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.6"/>
                                            </svg>
                                            {{ number_format($item['views']) }}
                                        </span>
                                    </div>

                                    <div class="bookmark-archive__reactions">
                                        <span class="bookmark-archive__reaction">
                                            <svg viewBox="0 0 24 24" fill="none" focusable="false" aria-hidden="true">
                                                <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke="currentColor" stroke-width="1.6"/>
                                            </svg>
                                            {{ number_format($item['likes']) }}
                                        </span>
                                        <span class="bookmark-archive__reaction">
                                            <svg viewBox="0 0 24 24" fill="none" focusable="false" aria-hidden="true">
                                                <path d="M4.75 6.5A2.25 2.25 0 0 1 7 4.25h10A2.25 2.25 0 0 1 19.25 6.5v7.25A2.25 2.25 0 0 1 17 16h-6.2l-3.95 3.35a.55.55 0 0 1-.9-.42V16H7a2.25 2.25 0 0 1-2.25-2.25V6.5Z" stroke="currentColor" stroke-width="1.6"/>
                                            </svg>
                                            {{ number_format($item['comments']) }}
                                        </span>
                                        <span class="bookmark-archive__reaction">
                                            <svg viewBox="0 0 24 24" fill="none" focusable="false" aria-hidden="true">
                                                <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke="currentColor" stroke-width="1.6"/>
                                            </svg>
                                            {{ number_format($item['bookmarks']) }}
                                        </span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="bookmark-archive__empty">
                        {{ $tab['label'] }}한 게시글이 없습니다.
                    </div>
                @endif
            </section>
        </section>
    @endforeach
</section>

@once
    <script>
        (function () {
            var archives = document.querySelectorAll('[data-bookmark-archive]');

            archives.forEach(function (archive) {
                var triggers = Array.from(archive.querySelectorAll('[data-bookmark-tab-trigger]'));
                var panels = Array.from(archive.querySelectorAll('[data-bookmark-tab-panel]'));

                if (triggers.length === 0 || panels.length === 0) {
                    return;
                }

                var activateTab = function (tabKey) {
                    triggers.forEach(function (trigger) {
                        var isActive = trigger.getAttribute('data-bookmark-tab-trigger') === tabKey;
                        trigger.classList.toggle('is-active', isActive);
                        trigger.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });

                    panels.forEach(function (panel) {
                        var isActive = panel.getAttribute('data-bookmark-tab-panel') === tabKey;
                        panel.classList.toggle('is-active', isActive);
                        panel.hidden = !isActive;
                    });
                };

                triggers.forEach(function (trigger) {
                    trigger.addEventListener('click', function () {
                        activateTab(trigger.getAttribute('data-bookmark-tab-trigger'));
                    });
                });
            });
        }());
    </script>
@endonce
