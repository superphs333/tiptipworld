@php
    $currentSort = (string) ($currentSort ?? request('sort', 'latest'));

    $profileUser = $profileUser ?? [
        'id' => 0,
        'name' => '작성자',
        'profile_image_url' => asset('images/avatar-default.svg'),
        'joined' => '집계 중',
    ];

    $followersCount = (int) ($followersCount ?? 0);
    $followingCount = (int) ($followingCount ?? 0);
    $isFollowing = (bool) ($isFollowing ?? false);

    $topCategories = collect($topCategories ?? []);
    $topTags = collect($topTags ?? []);
    $tipItems = collect($tipItems ?? []);

    $totalCount = (int) ($totalCount ?? $tipItems->count());
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
            @if (!$myFeed)
            <span class="author-inline tip-userfeed__follow-wrap" data-author-id="{{ $profileUser['id'] }}">
                <button
                    type="button"
                    class="author-inline__follow tip-userfeed__follow-btn {{ $isFollowing ? 'is-following' : '' }}"
                    aria-pressed="{{ $isFollowing ? 'true' : 'false' }}"
                >
                    {{ $isFollowing ? '팔로잉' : '팔로우' }}
                </button>
            </span>
            @endif
        </div>
    </header>

    <section class="tip-userfeed__insight" aria-label="사용자 인사이트">
        <h2 class="tip-userfeed__section-title">Insight</h2>
        <div class="tip-userfeed__insight-grid">
            <article class="tip-userfeed__insight-card">
                <h3>카테고리</h3>
                <div class="tip-userfeed__chips">
                    @foreach ($topCategories as $category)
                        @php $categoryId = (int) data_get($category, 'id', 0); @endphp
                        @if ($categoryId > 0)
                            <a
                                class="tip-userfeed__chip tip-userfeed__chip--link"
                                href="{{ route('tips.category', ['category_id' => $categoryId]) }}"
                            >
                                {{ data_get($category, 'name', '미분류') }}
                                <em>{{ number_format((int) data_get($category, 'tips_count', 0)) }}</em>
                            </a>
                        @else
                            <span class="tip-userfeed__chip">
                                {{ data_get($category, 'name', '미분류') }}
                                <em>{{ number_format((int) data_get($category, 'tips_count', 0)) }}</em>
                            </span>
                        @endif
                    @endforeach
                </div>
            </article>

            <article class="tip-userfeed__insight-card">
                <h3>태그</h3>
                <div class="tip-userfeed__chips">
                    @foreach ($topTags as $tag)
                        @php $tagId = (int) data_get($tag, 'id', 0); @endphp
                        @if ($tagId > 0)
                            <a
                                class="tip-userfeed__chip tip-userfeed__chip--link"
                                href="{{ route('tips.tag', ['tag_id' => $tagId]) }}"
                            >
                                #{{ data_get($tag, 'name', '태그') }}
                                <em>{{ number_format((int) data_get($tag, 'tips_count', 0)) }}</em>
                            </a>
                        @else
                            <span class="tip-userfeed__chip">
                                #{{ data_get($tag, 'name', '태그') }}
                                <em>{{ number_format((int) data_get($tag, 'tips_count', 0)) }}</em>
                            </span>
                        @endif
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
                <select id="tip-userfeed-sort" name="sort">
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
                    $tipId = (int) data_get($item, 'id', 0);
                    $categoryName = (string) data_get($item, 'category_name', '미분류');
                    $categoryId = (int) data_get($item, 'category_id', 0);
                    $viewCount = (int) data_get($item, 'view_count', 0);
                    $likeCount = (int) data_get($item, 'like_count', 0);
                    $commentCount = (int) data_get($item, 'comment_count', 0);
                    $bookmarkCount = (int) data_get($item, 'bookmark_count', 0);
                @endphp

                <article class="home-popular__card">
                    @if ($tipId > 0)
                        <a class="home-popular__thumb" href="{{ route('tip.show', ['tip_id' => $tipId]) }}">
                            <img src="{{ data_get($item, 'thumbnail_url') }}" alt="{{ data_get($item, 'title') }}" loading="lazy">
                        </a>
                    @else
                        <span class="home-popular__thumb">
                            <img src="{{ data_get($item, 'thumbnail_url') }}" alt="{{ data_get($item, 'title') }}" loading="lazy">
                        </span>
                    @endif

                    <div class="home-popular__body">
                        @if ($categoryId > 0)
                            <a class="home-popular__category" href="{{ route('tips.category', ['category_id' => $categoryId]) }}">{{ $categoryName }}</a>
                        @else
                            <span class="home-popular__category">{{ $categoryName }}</span>
                        @endif
                        @if ($tipId > 0)
                            <a class="home-popular__card-title" href="{{ route('tip.show', ['tip_id' => $tipId]) }}">
                                {{ data_get($item, 'title') }}
                            </a>
                        @else
                            <span class="home-popular__card-title">
                                {{ data_get($item, 'title') }}
                            </span>
                        @endif

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
