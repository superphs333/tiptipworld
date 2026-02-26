@php
    $currentSort = (string) request('sort', 'latest');

    $profileUser = [
        'id' => 3333,
        'name' => 'hihi3333',
        'profile_image_url' => asset('images/avatar-default.svg'),
        'joined' => '2026.02.01',
    ];

    $followersCount = 128;
    $followingCount = 47;
    $isFollowing = false;

    $topCategories = collect([
        ['name' => '기획', 'tips_count' => 12],
        ['name' => '업무 자동화', 'tips_count' => 9],
        ['name' => '개발', 'tips_count' => 7],
    ]);

    $topTags = collect([
        ['name' => '업무팁', 'tips_count' => 15],
        ['name' => '생산성', 'tips_count' => 11],
        ['name' => '노션', 'tips_count' => 8],
    ]);

    $recentTips = collect([
        ['title' => '회의록 템플릿 5분 세팅법'],
        ['title' => '반복 업무를 자동화하는 체크리스트'],
        ['title' => '신입 온보딩 문서 구조 예시'],
        ['title' => '주간 회고를 빠르게 쓰는 프롬프트'],
        ['title' => '팀 위키를 오래 유지하는 규칙'],
    ]);

    $tipItems = collect([
        [
            'id' => 501,
            'title' => '[SEED-C20] 050 카테고리20 테스트 카드',
            'thumbnail_url' => asset('images/no-thumbnail.png'),
            'category_name' => '추가',
            'view_count' => 5953,
            'like_count' => 0,
            'comment_count' => 0,
            'bookmark_count' => 0,
            'author' => [
                'id' => 3333,
                'name' => 'hihi3333',
                'profile_image_url' => asset('images/avatar-default.svg'),
            ],
        ],
        [
            'id' => 502,
            'title' => '업무 체크리스트를 템플릿으로 고정하는 방법',
            'thumbnail_url' => asset('images/no-thumbnail.png'),
            'category_name' => '기획',
            'view_count' => 4210,
            'like_count' => 18,
            'comment_count' => 4,
            'bookmark_count' => 13,
            'author' => [
                'id' => 3333,
                'name' => 'hihi3333',
                'profile_image_url' => asset('images/avatar-default.svg'),
            ],
        ],
        [
            'id' => 503,
            'title' => '회의 전에 공유하면 좋은 사전질문 7개',
            'thumbnail_url' => asset('images/no-thumbnail.png'),
            'category_name' => '업무 자동화',
            'view_count' => 3892,
            'like_count' => 25,
            'comment_count' => 7,
            'bookmark_count' => 16,
            'author' => [
                'id' => 3333,
                'name' => 'hihi3333',
                'profile_image_url' => asset('images/avatar-default.svg'),
            ],
        ],
        [
            'id' => 504,
            'title' => '프로젝트 킥오프 문서 기본 뼈대',
            'thumbnail_url' => asset('images/no-thumbnail.png'),
            'category_name' => '개발',
            'view_count' => 2675,
            'like_count' => 12,
            'comment_count' => 3,
            'bookmark_count' => 9,
            'author' => [
                'id' => 3333,
                'name' => 'hihi3333',
                'profile_image_url' => asset('images/avatar-default.svg'),
            ],
        ],
        [
            'id' => 505,
            'title' => '회고 문서를 꾸준히 쓰게 만드는 루틴',
            'thumbnail_url' => asset('images/no-thumbnail.png'),
            'category_name' => '기획',
            'view_count' => 1951,
            'like_count' => 9,
            'comment_count' => 1,
            'bookmark_count' => 5,
            'author' => [
                'id' => 3333,
                'name' => 'hihi3333',
                'profile_image_url' => asset('images/avatar-default.svg'),
            ],
        ],
        [
            'id' => 506,
            'title' => '협업 요청 메시지를 짧게 쓰는 공식',
            'thumbnail_url' => asset('images/no-thumbnail.png'),
            'category_name' => '커뮤니케이션',
            'view_count' => 1634,
            'like_count' => 6,
            'comment_count' => 2,
            'bookmark_count' => 4,
            'author' => [
                'id' => 3333,
                'name' => 'hihi3333',
                'profile_image_url' => asset('images/avatar-default.svg'),
            ],
        ],
    ]);

    $tipItems = match ($currentSort) {
        'popular' => $tipItems->sortByDesc('view_count')->values(),
        'likes' => $tipItems->sortByDesc('like_count')->values(),
        'bookmarks' => $tipItems->sortByDesc('bookmark_count')->values(),
        default => $tipItems->sortByDesc('id')->values(),
    };

    $totalCount = $tipItems->count();
@endphp

<section class="tip-userfeed" data-tip-userfeed>
    <header class="tip-userfeed__profile">
        <div class="tip-userfeed__identity">
            <img
                class="tip-userfeed__avatar"
                src="{{ $profileUser['profile_image_url'] }}"
                alt="{{ $profileUser['name'] }} 프로필"
                loading="lazy"
            >
            <div class="tip-userfeed__identity-body">
                <p class="tip-userfeed__kicker">USER FEED</p>
                <h1 class="tip-userfeed__name">{{ $profileUser['name'] }}</h1>
                <p class="tip-userfeed__summary">
                    공개 팁 {{ number_format($totalCount) }}개 · 가입일 {{ $profileUser['joined'] }}
                </p>
            </div>
        </div>

        <div class="tip-userfeed__relation">
            <p class="tip-userfeed__relation-item">
                <strong data-followers-count data-count="{{ $followersCount }}">{{ number_format($followersCount) }}</strong>
                <span>Followers</span>
            </p>
            <p class="tip-userfeed__relation-item">
                <strong>{{ number_format($followingCount) }}</strong>
                <span>Following</span>
            </p>
            <span class="author-inline tip-userfeed__follow-wrap" data-author-id="{{ $profileUser['id'] }}">
                <button
                    type="button"
                    class="author-inline__follow tip-userfeed__follow-btn {{ $isFollowing ? 'is-following' : '' }}"
                    aria-pressed="{{ $isFollowing ? 'true' : 'false' }}"
                >
                    {{ $isFollowing ? '팔로잉' : '팔로우' }}
                </button>
            </span>
        </div>
    </header>

    <section class="tip-userfeed__insight" aria-label="사용자 인사이트">
        <h2 class="tip-userfeed__section-title">Insight</h2>
        <div class="tip-userfeed__insight-grid">
            <article class="tip-userfeed__insight-card">
                <h3>카테고리</h3>
                <div class="tip-userfeed__chips">
                    @foreach ($topCategories as $category)
                        <span class="tip-userfeed__chip">
                            {{ data_get($category, 'name', '미분류') }}
                            <em>{{ number_format((int) data_get($category, 'tips_count', 0)) }}</em>
                        </span>
                    @endforeach
                </div>
            </article>

            <article class="tip-userfeed__insight-card">
                <h3>태그</h3>
                <div class="tip-userfeed__chips">
                    @foreach ($topTags as $tag)
                        <span class="tip-userfeed__chip">
                            #{{ data_get($tag, 'name', '태그') }}
                            <em>{{ number_format((int) data_get($tag, 'tips_count', 0)) }}</em>
                        </span>
                    @endforeach
                </div>
            </article>
        </div>
    </section>

    <section class="tip-userfeed__feed" aria-label="유저 피드">
        <header class="tip-userfeed__feed-head">
            <div class="tip-userfeed__feed-heading">
                <h2 class="tip-userfeed__section-title">Feed</h2>
                <p>{{ number_format($totalCount) }}개의 게시글</p>
            </div>

            <form class="tip-userfeed__sort-form" method="GET">
                <label for="tip-userfeed-sort">정렬</label>
                <select id="tip-userfeed-sort" name="sort" onchange="this.form.submit()">
                    <option value="latest" @selected($currentSort === 'latest')>최신순</option>
                    <option value="popular" @selected($currentSort === 'popular')>조회순</option>
                    <option value="likes" @selected($currentSort === 'likes')>좋아요순</option>
                    <option value="bookmarks" @selected($currentSort === 'bookmarks')>북마크순</option>
                </select>
            </form>
        </header>

        <div class="tip-userfeed__grid">
            @foreach ($tipItems as $item)
                @php
                    $authorName = (string) data_get($item, 'author.name', '작성자 미상');
                    $authorImage = (string) data_get($item, 'author.profile_image_url', asset('images/avatar-default.svg'));
                    $authorId = (int) data_get($item, 'author.id', 0);
                    $categoryName = (string) data_get($item, 'category_name', '미분류');
                    $viewCount = (int) data_get($item, 'view_count', 0);
                    $likeCount = (int) data_get($item, 'like_count', 0);
                    $commentCount = (int) data_get($item, 'comment_count', 0);
                    $bookmarkCount = (int) data_get($item, 'bookmark_count', 0);
                @endphp

                <article class="home-popular__card">
                    <a class="home-popular__thumb" href="#" onclick="return false;">
                        <img src="{{ data_get($item, 'thumbnail_url') }}" alt="{{ data_get($item, 'title') }}" loading="lazy">
                    </a>

                    <div class="home-popular__body">
                        <span class="home-popular__category">{{ $categoryName }}</span>
                        <a class="home-popular__card-title" href="#" onclick="return false;">
                            {{ data_get($item, 'title') }}
                        </a>

                        <div class="home-popular__author-row">
                            <x-author-inline
                                :name="$authorName"
                                :avatar="$authorImage"
                                :author-id="$authorId"
                                variant="card"
                                class="home-popular__author"
                            />
                            <span class="home-popular__views" title="조회수">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M2.4 12s3.6-6 9.6-6 9.6 6 9.6 6-3.6 6-9.6 6-9.6-6-9.6-6Z" stroke="currentColor" stroke-width="1.6" />
                                    <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.6" />
                                </svg>
                                {{ number_format($viewCount) }}
                            </span>
                        </div>

                        <div class="home-popular__bottom">
                            <span class="home-popular__stats">
                                <span class="home-popular__stat" title="좋아요">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                    {{ number_format($likeCount) }}
                                </span>
                                <span class="home-popular__stat" title="댓글">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4.75 6.5a2.25 2.25 0 0 1 2.25-2.25h10a2.25 2.25 0 0 1 2.25 2.25v7.25A2.25 2.25 0 0 1 17 16h-6.2l-3.95 3.35a.55.55 0 0 1-.9-.42V16H7A2.25 2.25 0 0 1 4.75 13.75V6.5Z" stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                    {{ number_format($commentCount) }}
                                </span>
                                <span class="home-popular__stat" title="북마크">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M7 4.75h10a.75.75 0 0 1 .75.75v14.6a.65.65 0 0 1-1.08.49L12 16.54l-4.67 4.05a.65.65 0 0 1-1.08-.49V5.5A.75.75 0 0 1 7 4.75Z" stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                    {{ number_format($bookmarkCount) }}
                                </span>
                            </span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</section>
