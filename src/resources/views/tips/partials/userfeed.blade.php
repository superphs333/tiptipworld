<section
    class="tip-userfeed"
    data-tip-userfeed
    data-follow-list-url="{{ route('user.follow.list', ['user_id' => data_get($profileUser, 'id', 0)]) }}"
    data-follow-toggle-url-base="{{ url('/user/follow') }}"
    data-user-feed-url-base="{{ url('/tips/user') }}"
>
    <header class="tip-userfeed__profile">
        <div class="tip-userfeed__identity">
            <img
                class="tip-userfeed__avatar"
                src="{{ data_get($profileUser, 'profile_image_url', asset('images/avatar-default.svg')) }}"
                alt="{{ data_get($profileUser, 'name', '작성자') }} 프로필"
                loading="lazy"
            >
            <div class="tip-userfeed__identity-body">
                <p class="tip-userfeed__kicker">USER FEED</p>
                <h1 class="tip-userfeed__name">{{ data_get($profileUser, 'name', '작성자') }}</h1>
                <p class="tip-userfeed__summary">
                    공개 팁 {{ $totalCountText ?? '0' }}개 · 가입일 {{ data_get($profileUser, 'joined', '집계 중') }}
                </p>
            </div>
        </div>

        <div class="tip-userfeed__relation">
            <button
                type="button"
                class="tip-userfeed__relation-item tip-userfeed__relation-trigger"
                data-follow-modal-open="followers"
                aria-controls="tip-userfeed-follow-modal"
                aria-haspopup="dialog"
            >
                <strong data-followers-count data-count="{{ $followersCount }}">{{ $followersCountText ?? '0' }}</strong>
                <span>Followers</span>
            </button>
            <button
                type="button"
                class="tip-userfeed__relation-item tip-userfeed__relation-trigger"
                data-follow-modal-open="following"
                aria-controls="tip-userfeed-follow-modal"
                aria-haspopup="dialog"
            >
                <strong data-following-count data-count="{{ $followingCount }}">{{ $followingCountText ?? '0' }}</strong>
                <span>Following</span>
            </button>
            @if (!$myFeed)
            <span class="author-inline tip-userfeed__follow-wrap" data-author-id="{{ data_get($profileUser, 'id', 0) }}">
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
                    @foreach (($topCategories ?? []) as $category)
                        @if (data_get($category, 'url'))
                            <a
                                class="tip-userfeed__chip tip-userfeed__chip--link"
                                href="{{ data_get($category, 'url') }}"
                            >
                                {{ data_get($category, 'label', data_get($category, 'name', '미분류')) }}
                                <em>{{ data_get($category, 'tips_count_text', '0') }}</em>
                            </a>
                        @else
                            <span class="tip-userfeed__chip">
                                {{ data_get($category, 'label', data_get($category, 'name', '미분류')) }}
                                <em>{{ data_get($category, 'tips_count_text', '0') }}</em>
                            </span>
                        @endif
                    @endforeach
                </div>
            </article>

            <article class="tip-userfeed__insight-card">
                <h3>태그</h3>
                <div class="tip-userfeed__chips">
                    @foreach (($topTags ?? []) as $tag)
                        @if (data_get($tag, 'url'))
                            <a
                                class="tip-userfeed__chip tip-userfeed__chip--link"
                                href="{{ data_get($tag, 'url') }}"
                            >
                                {{ data_get($tag, 'label', '#' . data_get($tag, 'name', '태그')) }}
                                <em>{{ data_get($tag, 'tips_count_text', '0') }}</em>
                            </a>
                        @else
                            <span class="tip-userfeed__chip">
                                {{ data_get($tag, 'label', '#' . data_get($tag, 'name', '태그')) }}
                                <em>{{ data_get($tag, 'tips_count_text', '0') }}</em>
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
                <p>{{ $totalCountText ?? '0' }}개의 게시글</p>
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
                <x-tip.card :item="$item" :interactive-reactions="true" reaction-button-class="tip-userfeed__stat-btn" />
            @endforeach
        </div>
    </section>

    <section
        class="tip-userfeed__follow-modal"
        id="tip-userfeed-follow-modal"
        data-follow-modal
        hidden
        aria-hidden="true"
    >
        <div class="tip-userfeed__follow-modal-backdrop" data-follow-modal-close></div>

        <div
            class="tip-userfeed__follow-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tip-userfeed-follow-modal-title"
        >
            <header class="tip-userfeed__follow-modal-head">
                <h2 id="tip-userfeed-follow-modal-title">팔로워 / 팔로잉</h2>
                <button
                    type="button"
                    class="tip-userfeed__follow-modal-close"
                    data-follow-modal-close
                    aria-label="팔로우 목록 닫기"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </header>

            <div class="tip-userfeed__follow-tabs" role="tablist" aria-label="팔로우 목록 탭">
                <button
                    type="button"
                    class="tip-userfeed__follow-tab is-active"
                    data-follow-modal-tab="followers"
                    role="tab"
                    aria-selected="true"
                >
                    팔로워
                    <em data-follow-modal-tab-count="followers">0</em>
                </button>
                <button
                    type="button"
                    class="tip-userfeed__follow-tab"
                    data-follow-modal-tab="following"
                    role="tab"
                    aria-selected="false"
                >
                    팔로잉
                    <em data-follow-modal-tab-count="following">0</em>
                </button>
            </div>

            <label class="tip-userfeed__follow-search">
                <span class="tip-userfeed__follow-search-label">검색</span>
                <input
                    type="search"
                    name="follow-search"
                    placeholder="이름 검색"
                    autocomplete="off"
                    data-follow-modal-search
                >
            </label>

            <div class="tip-userfeed__follow-body">
                <ul class="tip-userfeed__follow-list" data-follow-modal-list></ul>
                <p class="tip-userfeed__follow-empty" data-follow-modal-empty hidden>검색 결과가 없습니다.</p>
            </div>

            <footer class="tip-userfeed__follow-footer">
                <p>모달을 열 때마다 최신 팔로우 목록을 다시 불러옵니다.</p>
            </footer>
        </div>
    </section>
</section>
