@php
    $sortOptions = [
        'latest' => '최신순',
        'popular' => '인기순',
        'likes' => '좋아요순',
    ];
    $requestedSort = (string) request('sort', 'latest');
    $sortKey = array_key_exists($requestedSort, $sortOptions) ? $requestedSort : 'latest';

    $siteTitle = filled($site_title ?? null) ? $site_title : '샘플 분야';
   //$description = '분류별 팁을 한 화면에서 빠르게 탐색할 수 있도록 정리된 더미 데이터 화면입니다.';
    $modeLabel = request()->routeIs('tips.tag') ? 'TAG VIEW' : 'CATEGORY VIEW';

    $dummyTitles = [
        '신입도 바로 쓰는 업무 정리 체크리스트',
        '회의 시간을 절반으로 줄이는 아젠다 템플릿',
        '작업 우선순위 충돌을 정리하는 기준 3가지',
        '읽기 쉬운 문서 제목 규칙과 예시 모음',
        '팀 위키 정착을 위한 첫 2주 운영 가이드',
        '요청사항 누락 없는 브리프 작성 방법',
        '협업툴 알림 피로를 줄이는 실전 설정값',
        '주간 회고를 데이터 중심으로 남기는 법',
        '재사용 가능한 QA 체크시트 구성 예시',
        '실무자가 자주 묻는 배포 전 확인 항목',
        '기획 변경 요청 대응 시 커뮤니케이션 문장',
        '짧은 튜토리얼 문서의 이상적인 문단 길이',
    ];
    $dummyCategories = ['운영', '기획', '문서화', '협업', '개발'];
    $dummyAuthors = ['Mina', 'Joon', 'Ari', 'Sora', 'Theo', 'Hana'];

    $dummyTips = collect(range(1, 12))->map(function ($index) use ($dummyTitles, $dummyCategories, $dummyAuthors) {
        $title = $dummyTitles[$index - 1] ?? ('샘플 팁 ' . $index);

        return [
            'id' => null,
            'title' => $title,
            'category' => ['name' => $dummyCategories[($index - 1) % count($dummyCategories)]],
            'user' => ['name' => $dummyAuthors[($index - 1) % count($dummyAuthors)]],
            'excerpt' => '샘플 데이터입니다. 실제 API/DB 연동 전 레이아웃과 인터랙션 확인용으로 고정된 문장을 사용합니다.',
            'view_count' => max(120, 2650 - ($index * 135)),
            'like_count' => max(10, 430 - ($index * 18)),
            'comments_count' => max(0, 35 - $index),
            'bookmark_count' => max(0, 28 - intdiv($index, 2)),
            'published_at' => now()->subDays($index)->format('Y-m-d'),
            'thumbnailUrl' => asset('images/no-thumbnail.png'),
            'tags' => [
                ['name' => '샘플'],
                ['name' => '더미데이터'],
                ['name' => '레이아웃검수'],
            ],
        ];
    });

    $tipItems = match ($sortKey) {
        'popular' => $dummyTips->sortByDesc('view_count')->values(),
        'likes' => $dummyTips->sortByDesc('like_count')->values(),
        default => $dummyTips->values(),
    };

    $topTips = $dummyTips
        ->sortByDesc(function ($tip) {
            $views = (int) data_get($tip, 'view_count', 0);
            $likes = (int) data_get($tip, 'like_count', 0);

            return ($views * 2) + $likes;
        })
        ->values()
        ->take(10);

    $winnerTip = $topTips->first();
    $totalCount = $tipItems->count();
    $showPagination = false;
    $firstItem = $tipItems->isNotEmpty() ? 1 : null;
    $lastItem = $tipItems->count() ?: null;

    $winnerTitle = data_get($winnerTip, 'title', '아직 선정된 winner가 없습니다.');
    $winnerUser = data_get($winnerTip, 'user.name', data_get($winnerTip, 'authorName', '작성자 미상'));
    $winnerViews = (int) data_get($winnerTip, 'view_count', 0);
    $winnerLikes = (int) data_get($winnerTip, 'like_count', 0);
    $winnerScore = ($winnerViews * 2) + $winnerLikes;
    $winnerTipId = data_get($winnerTip, 'id');
    $winnerUrl = is_numeric($winnerTipId)
        ? route('tip.show', ['tip_id' => $winnerTipId])
        : 'javascript:void(0)';
@endphp

<section class="tip-wireframe tip-list-wireframe" data-tip-list-wireframe>
    <div class="tip-wireframe__topbar">
        <a class="tip-wireframe__back-link" href="{{ route('home') }}">← 홈</a>
        <div class="tip-wireframe__topbar-right">
            <span class="tip-list-wireframe__mode">
                {{ $modeLabel }}
            </span>
        </div>
    </div>

    <div class="tip-list-wireframe__hero">
        <article class="tip-list-wireframe__panel tip-list-wireframe__panel--intro">
            <div class="tip-list-wireframe__eyebrow">Category</div>
            <h1 class="tip-list-wireframe__title">{{ $siteTitle }}</h1>
            <p class="tip-list-wireframe__description">{{ $description }}</p>
        </article>

        <article class="tip-list-wireframe__panel tip-list-wireframe__panel--winner">
            <div class="tip-list-wireframe__count-line">콘텐츠 수 : {{ str_pad((string) $totalCount, 3, '0', STR_PAD_LEFT) }}</div>
            <h2 class="tip-list-wireframe__winner-title">이 분야 winner</h2>
            <a class="tip-list-wireframe__winner-link" href="{{ $winnerUrl }}">{{ $winnerTitle }}</a>
            <div class="tip-list-wireframe__winner-meta">
                <span>{{ $winnerUser }}</span>
                <span>Score {{ number_format($winnerScore) }}</span>
                <span>조회 {{ number_format($winnerViews) }} · 좋아요 {{ number_format($winnerLikes) }}</span>
            </div>
        </article>
    </div>

    <section class="tip-list-wireframe__top-feed" aria-label="인기글 피드">
        <div class="tip-list-wireframe__top-head">
            <h2 class="tip-list-wireframe__section-title">이 분야 인기글 10</h2>
            <div class="tip-list-wireframe__top-controls">
                <button type="button" class="tip-list-wireframe__slide-btn" data-top-prev aria-label="이전 인기글">‹</button>
                <button type="button" class="tip-list-wireframe__slide-btn" data-top-next aria-label="다음 인기글">›</button>
            </div>
        </div>

        <div class="tip-list-wireframe__top-rail" data-top-rail>
            @forelse ($topTips as $topTip)
                @php
                    $topTipId = data_get($topTip, 'id');
                    $topUrl = is_numeric($topTipId)
                        ? route('tip.show', ['tip_id' => $topTipId])
                        : 'javascript:void(0)';
                    $topTitle = data_get($topTip, 'title', '제목 없음');
                    $topCategory = data_get($topTip, 'category.name', data_get($topTip, 'categoryName', '미분류'));
                    $topViews = (int) data_get($topTip, 'view_count', 0);
                    $topLikes = (int) data_get($topTip, 'like_count', 0);
                    $topBookmarks = (int) data_get($topTip, 'bookmark_count', 0);
                @endphp
                <article class="tip-list-wireframe__top-card">
                    <span class="tip-list-wireframe__top-category">{{ $topCategory }}</span>
                    <a class="tip-list-wireframe__top-title" href="{{ $topUrl }}">{{ $topTitle }}</a>
                    <div class="tip-list-wireframe__top-metrics">
                        <span>북마크 {{ number_format($topBookmarks) }}</span>
                        <span>좋아요 {{ number_format($topLikes) }}</span>
                        <span>조회 {{ number_format($topViews) }}</span>
                    </div>
                </article>
            @empty
                <article class="tip-list-wireframe__top-card tip-list-wireframe__top-card--empty">
                    인기글 데이터가 없습니다.
                </article>
            @endforelse
        </div>
    </section>

    <section class="tip-list-wireframe__list" aria-label="팁 리스트">
        <header class="tip-list-wireframe__list-head">
            <div class="tip-list-wireframe__list-heading">
                <h2 class="tip-list-wireframe__section-title">리스트</h2>
                <p>{{ number_format($totalCount) }}개의 게시글</p>
            </div>

            <form class="tip-list-wireframe__sort-form" action="" method="GET">
                @foreach (request()->except(['sort', 'page']) as $name => $value)
                    @if (is_array($value))
                        @foreach ($value as $item)
                            <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="tips-sort-key">정렬</label>
                <select id="tips-sort-key" name="sort" onchange="this.form.submit()">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sortKey === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </header>

        <div class="tip-list-wireframe__items">
            @forelse ($tipItems as $tip)
                @php
                    $tipId = data_get($tip, 'id');
                    $tipUrl = is_numeric($tipId)
                        ? route('tip.show', ['tip_id' => $tipId])
                        : 'javascript:void(0)';
                    $thumb = data_get($tip, 'thumbnailUrl', data_get($tip, 'thumbnail_url'));
                    if (!filled($thumb)) {
                        $thumb = asset('images/no-thumbnail.png');
                    }

                    $category = data_get($tip, 'category.name', data_get($tip, 'categoryName', '미분류'));
                    $title = data_get($tip, 'title', '제목 없음');
                    $author = data_get($tip, 'user.name', data_get($tip, 'authorName', '작성자 미상'));
                    $commentCount = (int) data_get($tip, 'comments_count', 0);
                    $dateRaw = data_get($tip, 'published_at', data_get($tip, 'created_at'));
                    $dateLabel = '-';
                    if (filled($dateRaw)) {
                        try {
                            $dateLabel = \Illuminate\Support\Carbon::parse($dateRaw)->format('Y.m.d');
                        } catch (\Throwable $e) {
                            $dateLabel = '-';
                        }
                    }

                    $summary = trim((string) data_get($tip, 'excerpt', ''));
                    $tagItems = collect(data_get($tip, 'displayTags', data_get($tip, 'tags', [])))
                        ->map(fn ($tag) => is_string($tag) ? $tag : (data_get($tag, 'name') ?? data_get($tag, 'label')))
                        ->filter()
                        ->values();
                    if ($summary === '') {
                        $summary = $tagItems->isNotEmpty()
                            ? $tagItems->take(5)->map(fn ($tag) => '#' . $tag)->implode(' ')
                            : '요약 정보가 없습니다.';
                    }
                @endphp
                <article class="tip-list-wireframe__item">
                    <a class="tip-list-wireframe__thumb" href="{{ $tipUrl }}">
                        <img src="{{ $thumb }}" alt="{{ $title }}" loading="lazy">
                    </a>

                    <div class="tip-list-wireframe__item-body">
                        <div class="tip-list-wireframe__headline">
                            <span class="tip-list-wireframe__category">{{ $category }}</span>
                            <a class="tip-list-wireframe__item-title" href="{{ $tipUrl }}">{{ $title }}</a>
                        </div>
                        <div class="tip-list-wireframe__meta">
                            <span class="tip-list-wireframe__author">
                                <span class="tip-list-wireframe__dot" aria-hidden="true"></span>
                                {{ $author }}
                            </span>
                            <span>댓글 {{ number_format($commentCount) }}</span>
                            <span>{{ $dateLabel }}</span>
                        </div>
                        <p class="tip-list-wireframe__summary">{{ $summary }}</p>
                    </div>
                </article>
            @empty
                <article class="tip-list-wireframe__item tip-list-wireframe__item--empty">
                    <p>등록된 게시글이 없습니다.</p>
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

            @if ($showPagination)
                <div class="app-pagination">
                    {{ $tips->onEachSide(1)->links('vendor.pagination.app') }}
                </div>
            @endif
        </footer>
    </section>
</section>

@once
<script>
(() => {
    const root = document.querySelector('[data-tip-list-wireframe]');
    if (!root) {
        return;
    }

    const rail = root.querySelector('[data-top-rail]');
    const prevBtn = root.querySelector('[data-top-prev]');
    const nextBtn = root.querySelector('[data-top-next]');
    if (!rail || !prevBtn || !nextBtn) {
        return;
    }

    const getStep = () => {
        const card = rail.querySelector('.tip-list-wireframe__top-card');
        return card ? (card.getBoundingClientRect().width + 16) : 320;
    };

    const syncButtons = () => {
        const maxLeft = Math.max(0, rail.scrollWidth - rail.clientWidth - 2);
        prevBtn.disabled = rail.scrollLeft <= 2;
        nextBtn.disabled = rail.scrollLeft >= maxLeft;
    };

    prevBtn.addEventListener('click', () => {
        rail.scrollBy({ left: -getStep(), behavior: 'smooth' });
    });

    nextBtn.addEventListener('click', () => {
        rail.scrollBy({ left: getStep(), behavior: 'smooth' });
    });

    rail.addEventListener('scroll', syncButtons, { passive: true });
    window.addEventListener('resize', syncButtons);
    syncButtons();
})();
</script>
@endonce
