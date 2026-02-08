@php
    $tips = $datas ?? collect();
    if (method_exists($tips, 'getCollection')) {
        $tipItems = $tips->getCollection();
    } else {
        $tipItems = collect($tips);
    }
    $totalCount = method_exists($tips, 'total') ? $tips->total() : $tipItems->count();
    $showPagination = method_exists($tips, 'links');
    $firstItem = method_exists($tips, 'firstItem') ? $tips->firstItem() : null;
    $lastItem = method_exists($tips, 'lastItem') ? $tips->lastItem() : null;
    $lastUpdatedRaw = $tipItems
        ->map(fn ($tip) => data_get($tip, 'updated_at') ?? data_get($tip, 'updatedAt'))
        ->filter()
        ->max();
    $lastUpdated = $lastUpdatedRaw
        ? \Illuminate\Support\Carbon::parse($lastUpdatedRaw)->format('Y-m-d')
        : '-';
@endphp

<div x-data="{ selected: [] }">
    <div class="category-panel tip-panel">
        <div class="category-panel__content">
            <div class="tip-panel__summary">
                <div class="tip-panel__summary-left">
                    <div class="tip-panel__title">Tips 관리</div>
                    <div class="tip-panel__meta">
                        <span>총 {{ number_format($totalCount) }}개</span>
                        <span>최근 수정: {{ $lastUpdated }}</span>
                    </div>
                </div>
                <div class="tip-panel__summary-actions">
                    <a class="category-panel__bulk-btn category-panel__bulk-btn--accent tip-panel__add-btn" href="{{ route('admin.tip.form') }}">+ Tip 추가</a>
                </div>
            </div>

            <div class="category-panel__filter tip-panel__filter">
                <form class="category-panel__form tip-panel__form" action="" method="GET">
                    @if (request()->has('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}" />
                    @endif
                    <div class="tip-panel__filters">
                        <div class="user-panel__filter-row tip-panel__filter-row tip-panel__filter-row--primary">
                            <input
                                class="category-panel__input tip-panel__search-input"
                                type="text"
                                name="query"
                                placeholder="검색어(제목/본문/작성자)"
                                value="{{ request('query') }}"
                            />
                            <div
                                class="category-panel__select-wrap tip-panel__select"
                                x-data="selectBox()"
                                :class="{ 'is-open': open }"
                                @click.outside="close()"
                                @keydown.escape.stop="close()"
                            >
                                <select class="category-panel__select-native" name="category" x-ref="select" x-model="value">
                                    <option value="" @selected(blank(request('category')))>카테고리</option>
                                    <option value="dev" @selected(request('category') === 'dev')>개발</option>
                                    <option value="design" @selected(request('category') === 'design')>디자인</option>
                                </select>
                                <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                    <span class="category-panel__select-label" x-text="label">카테고리</span>
                                    <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                    <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">카테고리</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('dev')" :class="{ 'is-active': value === 'dev' }" :aria-selected="value === 'dev'">개발</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('design')" :class="{ 'is-active': value === 'design' }" :aria-selected="value === 'design'">디자인</li>
                                </ul>
                            </div>
                            <div
                                class="category-panel__select-wrap tip-panel__select"
                                x-data="selectBox()"
                                :class="{ 'is-open': open }"
                                @click.outside="close()"
                                @keydown.escape.stop="close()"
                            >
                                <select class="category-panel__select-native" name="tag" x-ref="select" x-model="value">
                                    <option value="" @selected(blank(request('tag')))>태그</option>
                                    <option value="laravel" @selected(request('tag') === 'laravel')>#laravel</option>
                                    <option value="docker" @selected(request('tag') === 'docker')>#docker</option>
                                </select>
                                <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                    <span class="category-panel__select-label" x-text="label">태그</span>
                                    <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                    <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">태그</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('laravel')" :class="{ 'is-active': value === 'laravel' }" :aria-selected="value === 'laravel'">#laravel</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('docker')" :class="{ 'is-active': value === 'docker' }" :aria-selected="value === 'docker'">#docker</li>
                                </ul>
                            </div>
                            <div
                                class="category-panel__select-wrap tip-panel__select"
                                x-data="selectBox()"
                                :class="{ 'is-open': open }"
                                @click.outside="close()"
                                @keydown.escape.stop="close()"
                            >
                                <select class="category-panel__select-native" name="status" x-ref="select" x-model="value">
                                    <option value="" @selected(blank(request('status')))>상태</option>
                                    <option value="public" @selected(request('status') === 'public')>공개</option>
                                    <option value="private" @selected(request('status') === 'private')>비공개</option>
                                </select>
                                <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                    <span class="category-panel__select-label" x-text="label">상태</span>
                                    <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                    <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">상태</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('public')" :class="{ 'is-active': value === 'public' }" :aria-selected="value === 'public'">공개</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('private')" :class="{ 'is-active': value === 'private' }" :aria-selected="value === 'private'">비공개</li>
                                </ul>
                            </div>
                            <div
                                class="category-panel__select-wrap tip-panel__select"
                                x-data="selectBox()"
                                :class="{ 'is-open': open }"
                                @click.outside="close()"
                                @keydown.escape.stop="close()"
                            >
                                <select class="category-panel__select-native" name="sort" x-ref="select" x-model="value">
                                    <option value="" @selected(blank(request('sort')))>정렬</option>
                                    <option value="recent" @selected(request('sort') === 'recent')>최근순</option>
                                    <option value="popular" @selected(request('sort') === 'popular')>인기순</option>
                                </select>
                                <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                    <span class="category-panel__select-label" x-text="label">정렬</span>
                                    <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                    <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">정렬</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('recent')" :class="{ 'is-active': value === 'recent' }" :aria-selected="value === 'recent'">최근순</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('popular')" :class="{ 'is-active': value === 'popular' }" :aria-selected="value === 'popular'">인기순</li>
                                </ul>
                            </div>
                        </div>
                        <div class="user-panel__filter-row tip-panel__filter-row tip-panel__filter-row--secondary">
                            <div
                                class="category-panel__select-wrap tip-panel__select"
                                x-data="selectBox()"
                                :class="{ 'is-open': open }"
                                @click.outside="close()"
                                @keydown.escape.stop="close()"
                            >
                                <select class="category-panel__select-native" name="period" x-ref="select" x-model="value">
                                    <option value="" @selected(blank(request('period')))>기간</option>
                                    <option value="7" @selected(request('period') === '7')>최근 7일</option>
                                    <option value="30" @selected(request('period') === '30')>최근 30일</option>
                                    <option value="90" @selected(request('period') === '90')>최근 90일</option>
                                </select>
                                <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                    <span class="category-panel__select-label" x-text="label">기간</span>
                                    <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                    <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">기간</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('7')" :class="{ 'is-active': value === '7' }" :aria-selected="value === '7'">최근 7일</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('30')" :class="{ 'is-active': value === '30' }" :aria-selected="value === '30'">최근 30일</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('90')" :class="{ 'is-active': value === '90' }" :aria-selected="value === '90'">최근 90일</li>
                                </ul>
                            </div>
                            <div
                                class="category-panel__select-wrap tip-panel__select"
                                x-data="selectBox()"
                                :class="{ 'is-open': open }"
                                @click.outside="close()"
                                @keydown.escape.stop="close()"
                            >
                                <select class="category-panel__select-native" name="visible" x-ref="select" x-model="value">
                                    <option value="" @selected(blank(request('visible')))>노출여부</option>
                                    <option value="1" @selected(request('visible') === '1')>노출</option>
                                    <option value="0" @selected(request('visible') === '0')>미노출</option>
                                </select>
                                <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                    <span class="category-panel__select-label" x-text="label">노출여부</span>
                                    <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                    <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">노출여부</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('1')" :class="{ 'is-active': value === '1' }" :aria-selected="value === '1'">노출</li>
                                    <li class="category-panel__select-option" role="option" @click="choose('0')" :class="{ 'is-active': value === '0' }" :aria-selected="value === '0'">미노출</li>
                                </ul>
                            </div>
                            <div class="tip-panel__filter-actions">
                                <a class="category-panel__bulk-btn category-panel__bulk-btn--ghost" href="{{ url()->current() }}">초기화</a>
                                <button class="category-panel__bulk-btn category-panel__bulk-btn--accent" type="submit">검색</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="user-panel__list-header tip-panel__list-header">
                <div class="user-panel__list-title">목록</div>
                <form class="user-panel__display-form" action="" method="GET">
                    @php
                        $displayParams = ['tab', 'query', 'category', 'tag', 'status', 'sort', 'period', 'visible'];
                    @endphp
                    @foreach ($displayParams as $param)
                        @if (request()->has($param))
                            <input type="hidden" name="{{ $param }}" value="{{ request($param) }}" />
                        @endif
                    @endforeach
                    <span class="user-panel__display-label">표시설정</span>
                    <label class="user-panel__display-control" for="tips-per-page">
                        <span>페이지당</span>
                        <input
                            class="category-panel__input user-panel__per-page-input"
                            type="number"
                            id="tips-per-page"
                            name="per_page"
                            min="1"
                            max="100"
                            step="1"
                            value="{{ request('per_page', 20) }}"
                        />
                    </label>
                    <button class="category-panel__bulk-btn" type="submit">적용</button>
                </form>
            </div>

            <div class="user-panel__list tip-panel__list">
                <table class="user-panel__table tip-panel__table">
                    <thead>
                        <tr>
                            <th>
                                <input
                                    type="checkbox"
                                    x-ref="selectAll"
                                    @change="
                                        selected = $event.target.checked
                                        ? [...$el.closest('table')
                                            .querySelectorAll('input[name=&quot;tip_ids[]&quot;]')]
                                            .map(el => el.value)
                                        : [];
                                    "
                                />
                            </th>
                            <th>ID</th>
                            <th>썸네일</th>
                            <th>제목/요약</th>
                            <th>작성자</th>
                            <th>상태</th>
                            <th>조회/좋아요</th>
                            <th>날짜</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tipItems as $tip)
                            @php
                                $tipId = data_get($tip, 'id', '-');
                                $title = data_get($tip, 'title', '-');
                                $summary = data_get($tip, 'summary', data_get($tip, 'excerpt', ''));
                                $author = data_get($tip, 'author.name', data_get($tip, 'author', '-'));
                                $category = data_get($tip, 'category.name', data_get($tip, 'category', '-'));
                                $tags = data_get($tip, 'tags', []);
                                $tagItems = collect($tags)
                                    ->map(fn ($tag) => is_string($tag) ? $tag : (data_get($tag, 'name') ?? data_get($tag, 'label')))
                                    ->filter()
                                    ->values();
                                $thumb = data_get($tip, 'thumbnail_url', data_get($tip, 'thumbnail', ''));
                                $views = (int) data_get($tip, 'views', data_get($tip, 'view_count', 0));
                                $likes = (int) data_get($tip, 'likes', data_get($tip, 'like_count', 0));
                                $statusRaw = data_get($tip, 'status', data_get($tip, 'is_public', true));
                                $statusKey = $statusRaw === 'private' || $statusRaw === 0 || $statusRaw === false ? 'private' : 'public';
                                $statusLabel = $statusKey === 'public' ? '공개' : '비공개';
                                $dateRaw = data_get($tip, 'created_at', data_get($tip, 'updated_at'));
                                $dateLabel = $dateRaw ? \Illuminate\Support\Carbon::parse($dateRaw)->format('m-d') : '-';
                            @endphp
                            <tr>
                                <td><input type="checkbox" name="tip_ids[]" value="{{ $tipId }}" x-model="selected" /></td>
                                <td>{{ $tipId }}</td>
                                <td>
                                    <div class="tip-panel__thumb">
                                        @if ($thumb)
                                            <img src="{{ $thumb }}" alt="" />
                                        @else
                                            <span class="tip-panel__thumb-placeholder">img</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="tip-panel__title-line">“{{ $title }}”</div>
                                    <div class="tip-panel__summary-line">{{ $summary ?: '요약이 없습니다.' }}</div>
                                    <div class="tip-panel__meta-line">
                                        <span class="tip-panel__category">{{ $category }}</span>
                                        @if ($tagItems->isNotEmpty())
                                            <span class="tip-panel__tags">
                                                @foreach ($tagItems as $tag)
                                                    <span class="tip-panel__tag">#{{ $tag }}</span>
                                                @endforeach
                                            </span>
                                        @endif
                                    </div>
                                    <div class="tip-panel__actions">
                                        <button class="tip-panel__action" type="button">미리보기</button>
                                        <button class="tip-panel__action" type="button">편집</button>
                                        <button class="tip-panel__action" type="button">복제</button>
                                        <button class="tip-panel__action" type="button">⋯</button>
                                    </div>
                                </td>
                                <td>{{ $author }}</td>
                                <td>
                                    <span class="tip-panel__status tip-panel__status--{{ $statusKey }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="tip-panel__metrics">{{ number_format($views) }} / {{ number_format($likes) }}</td>
                                <td class="tip-panel__date">{{ $dateLabel }}</td>
                            </tr>
                        @empty
                            <tr class="user-panel__empty-row">
                                <td colspan="8" class="user-panel__empty">데이터가 없습니다.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="tag-panel__pagination tip-panel__pagination">
                <div class="tag-panel__page-meta">
                    <span class="tag-panel__page-range">
                        @if ($firstItem !== null && $lastItem !== null)
                            {{ $firstItem }}-{{ $lastItem }} / 총 {{ number_format($totalCount) }}개
                        @else
                            총 {{ number_format($totalCount) }}개
                        @endif
                    </span>
                </div>
                @if ($showPagination)
                    <div class="app-pagination">
                        {{ $tips->onEachSide(1)->links('vendor.pagination.app') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@once
<script>
    document.addEventListener("alpine:init", () => {
        // 커스텀 셀렉트 공용 컨트롤러 (필터 셀렉트 공통).
        Alpine.data("selectBox", () => ({
            open: false,
            value: "",
            label: "",
            init() {
                if (this.value === "" || this.value === null) {
                    this.value = this.$refs.select?.value ?? "";
                }
                this.setLabel();
                this.$watch("value", () => this.setLabel());
            },
            setLabel() {
                const options = Array.from(this.$refs.select?.options || []);
                const selected = options.find((option) => option.value === this.value);
                this.label = selected ? selected.textContent : "";
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => this.$refs.menu?.focus());
                }
            },
            choose(value) {
                this.value = value;
                this.open = false;
            },
            close() {
                this.open = false;
            },
        }));
    });
</script>
@endonce
