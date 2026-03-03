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
                <h3 class="bookmark-archive__section-title">Insight</h3>

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

                    <div class="bookmark-archive__sort-shell" aria-hidden="true">
                        <span>정렬</span>
                        <div class="bookmark-archive__sort-value">최신순</div>
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
    <style>
        .bookmark-archive {
            --archive-border: #dfdfdf;
            --archive-ink: #181818;
            --archive-muted: #666f7d;
            --archive-soft: #f6f6f6;
            --archive-soft-strong: #eeeeee;
            display: grid;
            gap: 1.75rem;
            color: var(--archive-ink);
            font-family: "Pretendard", "Noto Sans KR", sans-serif;
        }

        .bookmark-archive__profile,
        .bookmark-archive__insight-card,
        .bookmark-archive__card,
        .bookmark-archive__empty {
            border: 1px solid var(--archive-border);
            border-radius: 20px;
            background: #ffffff;
        }

        .bookmark-archive__profile {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.35rem 1.5rem;
        }

        .bookmark-archive__identity {
            display: flex;
            align-items: center;
            gap: 1.1rem;
            min-width: 0;
        }

        .bookmark-archive__avatar {
            display: grid;
            flex-shrink: 0;
            place-items: center;
            width: 5.2rem;
            height: 5.2rem;
            border: 1px solid var(--archive-border);
            border-radius: 999px;
            background: linear-gradient(180deg, #e9eef9 0%, #d9e0f0 100%);
            box-shadow: inset 0 0 0 4px rgba(255, 255, 255, 0.8);
        }

        .bookmark-archive__avatar span {
            font-size: 2rem;
            font-weight: 800;
            color: #8592ad;
        }

        .bookmark-archive__identity-body {
            min-width: 0;
        }

        .bookmark-archive__kicker {
            margin: 0 0 0.3rem;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            color: var(--archive-muted);
        }

        .bookmark-archive__name {
            margin: 0;
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            line-height: 1.15;
        }

        .bookmark-archive__summary {
            margin: 0.45rem 0 0;
            font-size: 1rem;
            color: var(--archive-muted);
        }

        .bookmark-archive__stats {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .bookmark-archive__stat-box {
            display: grid;
            gap: 0.2rem;
            min-width: 5.5rem;
            text-align: center;
        }

        .bookmark-archive__stat-box strong {
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1;
        }

        .bookmark-archive__stat-box span {
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--archive-muted);
        }

        .bookmark-archive__pill {
            display: inline-flex;
            align-items: center;
            min-height: 2.3rem;
            padding: 0.55rem 1rem;
            border: 1px solid var(--archive-border);
            border-radius: 999px;
            background: var(--archive-soft);
            font-size: 0.88rem;
            font-weight: 700;
            color: #444;
        }

        .bookmark-archive__tabs {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            width: fit-content;
            padding: 0.3rem;
            border: 1px solid var(--archive-border);
            border-radius: 16px;
            background: #ffffff;
        }

        .bookmark-archive__tab {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-height: 2.8rem;
            padding: 0.6rem 1rem;
            border: 0;
            border-radius: 12px;
            background: transparent;
            font: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            color: #5c6675;
            cursor: pointer;
        }

        .bookmark-archive__tab em {
            font-style: normal;
            color: #7b8594;
        }

        .bookmark-archive__tab.is-active {
            background: #1e2430;
            color: #ffffff;
        }

        .bookmark-archive__tab.is-active em {
            color: rgba(255, 255, 255, 0.82);
        }

        .bookmark-archive__panel {
            display: grid;
            gap: 1.75rem;
        }

        .bookmark-archive__panel[hidden] {
            display: none;
        }

        .bookmark-archive__section-title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .bookmark-archive__insight {
            display: grid;
            gap: 1rem;
        }

        .bookmark-archive__insight-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .bookmark-archive__insight-card {
            display: grid;
            gap: 1rem;
            padding: 1.15rem 1.25rem;
        }

        .bookmark-archive__insight-card h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #3b4351;
        }

        .bookmark-archive__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .bookmark-archive__chip,
        .bookmark-archive__empty-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            min-height: 2.2rem;
            padding: 0.45rem 0.8rem;
            border: 1px solid var(--archive-border);
            border-radius: 999px;
            background: #fff;
            font-size: 0.92rem;
            font-weight: 700;
            color: #242a33;
        }

        .bookmark-archive__chip em {
            font-style: normal;
            font-weight: 700;
            color: var(--archive-muted);
        }

        .bookmark-archive__empty-chip {
            color: var(--archive-muted);
        }

        .bookmark-archive__feed {
            display: grid;
            gap: 1.25rem;
        }

        .bookmark-archive__feed-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
        }

        .bookmark-archive__feed-heading {
            display: grid;
            gap: 0.35rem;
        }

        .bookmark-archive__feed-heading p {
            margin: 0;
            font-size: 1rem;
            color: var(--archive-muted);
        }

        .bookmark-archive__sort-shell {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-size: 0.92rem;
            font-weight: 700;
            color: #4c5461;
        }

        .bookmark-archive__sort-value {
            display: inline-flex;
            align-items: center;
            min-height: 2.5rem;
            padding: 0.55rem 1rem;
            border: 1px solid var(--archive-border);
            border-radius: 12px;
            background: #fff;
            color: #191f28;
        }

        .bookmark-archive__grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.35rem;
        }

        .bookmark-archive__card {
            overflow: hidden;
        }

        .bookmark-archive__thumb {
            position: relative;
            height: 18rem;
            border-bottom: 1px solid var(--archive-border);
            overflow: hidden;
        }

        .bookmark-archive__thumb--cool {
            background: linear-gradient(180deg, #f1f4fb 0%, #dbe5fb 100%);
        }

        .bookmark-archive__thumb--warm {
            background:
                radial-gradient(circle at 24% 62%, rgba(255, 240, 219, 0.78), rgba(255, 240, 219, 0) 24%),
                linear-gradient(135deg, #c68c41 0%, #a55d28 28%, #714522 58%, #ba7c2b 100%);
        }

        .bookmark-archive__thumb--cool::after,
        .bookmark-archive__thumb--warm::after {
            content: "";
            position: absolute;
            left: -8%;
            right: -8%;
            bottom: -26%;
            height: 44%;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.88);
        }

        .bookmark-archive__thumb-glow {
            position: absolute;
            inset: 1.4rem 24% auto;
            height: 4.6rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0) 74%);
        }

        .bookmark-archive__thumb-icon {
            position: absolute;
            inset: 50% auto auto 50%;
            z-index: 1;
            transform: translate(-50%, -52%);
            color: rgba(64, 95, 161, 0.92);
        }

        .bookmark-archive__thumb--warm .bookmark-archive__thumb-icon {
            color: rgba(255, 249, 238, 0.9);
        }

        .bookmark-archive__thumb-icon svg {
            width: 7rem;
            height: 7rem;
        }

        .bookmark-archive__card-body {
            display: grid;
            gap: 0.85rem;
            padding: 0.9rem 1rem 1rem;
        }

        .bookmark-archive__card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.7rem;
        }

        .bookmark-archive__category,
        .bookmark-archive__saved {
            display: inline-flex;
            align-items: center;
            min-height: 1.9rem;
            padding: 0.3rem 0.68rem;
            border: 1px solid var(--archive-border);
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #4a5568;
            background: #fff;
        }

        .bookmark-archive__saved {
            background: var(--archive-soft);
        }

        .bookmark-archive__card-title {
            margin: 0;
            font-size: 1.06rem;
            line-height: 1.45;
            font-weight: 800;
            color: #151b24;
        }

        .bookmark-archive__tag-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .bookmark-archive__tag {
            display: inline-flex;
            align-items: center;
            min-height: 1.8rem;
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
            background: #f8f9fb;
            font-size: 0.76rem;
            font-weight: 700;
            color: #657083;
        }

        .bookmark-archive__meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding-bottom: 0.7rem;
            border-bottom: 1px solid var(--archive-soft-strong);
        }

        .bookmark-archive__author {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
            font-size: 0.96rem;
            font-weight: 700;
            color: #1e2430;
        }

        .bookmark-archive__author-avatar {
            width: 1.55rem;
            height: 1.55rem;
            flex-shrink: 0;
            border-radius: 999px;
            border: 1px solid #cfd6e3;
            background: linear-gradient(180deg, #c8d2e4 0%, #b2bfd6 100%);
        }

        .bookmark-archive__views {
            display: inline-flex;
            align-items: center;
            gap: 0.32rem;
            flex-shrink: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: #657083;
        }

        .bookmark-archive__views svg,
        .bookmark-archive__reaction svg {
            width: 0.95rem;
            height: 0.95rem;
        }

        .bookmark-archive__reactions {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            color: #616b7b;
        }

        .bookmark-archive__reaction {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.94rem;
            font-weight: 700;
        }

        .bookmark-archive__empty {
            padding: 2.4rem 1.25rem;
            font-size: 1rem;
            font-weight: 700;
            color: var(--archive-muted);
            text-align: center;
        }

        @media (max-width: 1100px) {
            .bookmark-archive__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 820px) {
            .bookmark-archive__profile,
            .bookmark-archive__feed-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .bookmark-archive__stats {
                justify-content: flex-start;
            }

            .bookmark-archive__insight-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .bookmark-archive {
                gap: 1.2rem;
            }

            .bookmark-archive__profile,
            .bookmark-archive__insight-card {
                padding: 1rem;
            }

            .bookmark-archive__identity {
                align-items: flex-start;
            }

            .bookmark-archive__avatar {
                width: 4.2rem;
                height: 4.2rem;
            }

            .bookmark-archive__tabs {
                width: 100%;
            }

            .bookmark-archive__tab {
                flex: 1 1 auto;
                justify-content: center;
            }

            .bookmark-archive__grid {
                grid-template-columns: 1fr;
            }

            .bookmark-archive__thumb {
                height: 15rem;
            }

            .bookmark-archive__card-body {
                padding: 0.85rem;
            }

            .bookmark-archive__meta {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endonce

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
