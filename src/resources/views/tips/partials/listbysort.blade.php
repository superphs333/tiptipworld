<section class="tip-wireframe tip-list-wireframe" data-tip-list-wireframe>
    <div class="tip-wireframe__topbar">        
        <div class="tip-wireframe__topbar-right">
            <span class="tip-list-wireframe__mode">{{ data_get($listView, 'sort_mode_text', strtoupper((string) ($sort ?? 'category'))) }} </span>
        </div>
    </div>

    <div class="tip-list-wireframe__hero">
        <article class="tip-list-wireframe__panel tip-list-wireframe__panel--intro">
            <div class="tip-list-wireframe__eyebrow">{{ $sort }}</div>
            <h1 class="tip-list-wireframe__title">{{ $site_title }}</h1>
            <p class="tip-list-wireframe__description">
               {{ $description }}
            </p>
        </article>

        <article class="tip-list-wireframe__panel tip-list-wireframe__panel--snapshot">
            <div class="tip-list-wireframe__count-line">콘텐츠 수 : {{ data_get($listView, 'total_count_text', '0') }}</div>
            <dl class="tip-list-wireframe__snapshot-list">
                <div class="tip-list-wireframe__snapshot-item">
                    <dt>오늘 올라온 글</dt>
                    <dd>{{ data_get($listView, 'today_tip_count_text', '0') }}</dd>
                </div>
                <div class="tip-list-wireframe__snapshot-item">
                    <dt>평균 좋아요</dt>
                    <dd>{{ data_get($listView, 'avg_like_count', 0) }}</dd>
                </div>
                <div class="tip-list-wireframe__snapshot-item">
                    <dt>평균 북마크</dt>
                    <dd>{{ data_get($listView, 'avg_bookmark_count', 0) }}</dd>
                </div>
            </dl>
        </article>
    </div>

    <section class="tip-list-wireframe__list" aria-label="팁 리스트">
        <header class="tip-list-wireframe__list-head">
            <div class="tip-list-wireframe__list-heading">
                <h2 class="tip-list-wireframe__section-title">리스트</h2>
                <p>{{ data_get($listView, 'total_count_text', '0') }}개의 게시글</p>
            </div>

            <form class="tip-list-wireframe__sort-form" method="GET">
                <label for="tips-sort-key">정렬</label>
                <select id="tips-sort-key" name="sort" onchange="this.form.submit()">
                    <option value="latest" @selected(data_get($listView, 'current_sort') === 'latest')>최신순</option>
                    <option value="popular" @selected(data_get($listView, 'current_sort') === 'popular')>인기순</option>
                    <option value="likes" @selected(data_get($listView, 'current_sort') === 'likes')>좋아요순</option>
                    <option value="bookmarks" @selected(data_get($listView, 'current_sort') === 'bookmarks')>북마크순</option>
                </select>
            </form>

        </header>

        <div class="tip-list-wireframe__items">           
            @foreach ($tipItems as $item)
                <x-tip.list-item :item="$item" />
            @endforeach
        </div>
        <footer class="tip-list-wireframe__pagination">
            <span class="tip-list-wireframe__page-meta">
                @if (data_get($listView, 'first_item') !== null && data_get($listView, 'last_item') !== null)
                    {{ data_get($listView, 'first_item') }}-{{ data_get($listView, 'last_item') }} / 총 {{ data_get($listView, 'total_count_text', '0') }}개
                @else
                    총 {{ data_get($listView, 'total_count_text', '0') }}개
                @endif
            </span>

            @if (method_exists($tipItems, 'hasPages') && $tipItems->hasPages())
                <div class="app-pagination app-pagination--tip">
                    {{ $tipItems->onEachSide(1)->links('vendor.pagination.app') }}
                </div>
            @endif
        </footer>

    </section>
</section>
