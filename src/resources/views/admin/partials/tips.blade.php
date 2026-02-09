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
    $normalizeDateTimeLocal = static function ($value) {
        if (!filled($value)) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i');
        } catch (\Throwable $e) {
            return '';
        }
    };
    $startDateInput = $normalizeDateTimeLocal(request('start_date'));
    $endDateInput = $normalizeDateTimeLocal(request('end_date'));
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

            <div
                class="tip-panel__alerts"
                x-data="{
                    showErrors: {{ $errors->any() ? 'true' : 'false' }},
                    showError: {{ session('error') ? 'true' : 'false' }},
                    showSuccess: {{ session('success') ? 'true' : 'false' }},
                }"
            >
                @if ($errors->any())
                    <div class="tip-panel__alert tip-panel__alert--error" x-show="showErrors">
                        <button class="tip-panel__alert-close" type="button" aria-label="닫기" @click="showErrors = false">×</button>
                        <ul>
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="tip-panel__alert tip-panel__alert--error" x-show="showError">
                        <button class="tip-panel__alert-close" type="button" aria-label="닫기" @click="showError = false">×</button>
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="tip-panel__alert tip-panel__alert--success" x-show="showSuccess">
                        <button class="tip-panel__alert-close" type="button" aria-label="닫기" @click="showSuccess = false">×</button>
                        {{ session('success') }}
                    </div>
                @endif
            </div>

            <div class="category-panel__filter tip-panel__filter">
                <form class="category-panel__form tip-panel__form" action="" method="GET">
                    @if (request()->has('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}" />
                    @endif
                    <div class="tip-panel__filters">
                        <div class="tip-panel__filters-main">
                            <div class="user-panel__filter-row tip-panel__filter-row tip-panel__filter-row--top">
                                <div class="tip-panel__field">
                                    <span class="tip-panel__field-label">카테고리</span>
                                    <div
                                        class="category-panel__select-wrap tip-panel__select"
                                        x-data="selectBox()"
                                        :class="{ 'is-open': open }"
                                        @click.outside="close()"
                                        @keydown.escape.stop="close()"
                                    >
                                        <select class="category-panel__select-native" name="category_id" x-ref="select" x-model="value">
                                            <option value="all" @selected(blank(request('category_id')) || request('category_id') === 'all')>전체</option>
                                            <option value="uncategorized" @selected(request('category_id') === 'uncategorized')>미분류</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                            <span class="category-panel__select-label" x-text="label">카테고리</span>
                                            <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                        <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                            <li class="category-panel__select-option" role="option" @click="choose('all')" :class="{ 'is-active': value === 'all' }" :aria-selected="value === 'all'">전체</li>
                                            <li class="category-panel__select-option" role="option" @click="choose('uncategorized')" :class="{ 'is-active': value === 'uncategorized' }" :aria-selected="value === 'uncategorized'">미분류</li>
                                            @foreach($categories as $category)
                                                <li class="category-panel__select-option" role="option" @click="choose('{{ $category->id }}')" :class="{ 'is-active': value === '{{ $category->id }}' }" :aria-selected="value === '{{ $category->id }}'">{{ $category->name }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                <div class="tip-panel__field">
                                    <span class="tip-panel__field-label">노출</span>
                                    <div
                                        class="category-panel__select-wrap tip-panel__select"
                                        x-data="selectBox()"
                                        :class="{ 'is-open': open }"
                                        @click.outside="close()"
                                        @keydown.escape.stop="close()"
                                    >
                                        <select class="category-panel__select-native" name="visibility" x-ref="select" x-model="value">
                                            <option value="" @selected(blank(request('visibility')))>노출</option>
                                            @foreach(config('app.tip_visibility', []) as $visibility)
                                                <option value="{{ $visibility }}" @selected(request('visibility') === $visibility)>{{ $visibility }}</option>
                                            @endforeach
                                        </select>
                                        <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                            <span class="category-panel__select-label" x-text="label">노출</span>
                                            <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                        <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                            <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">노출</li>
                                            @foreach(config('app.tip_visibility', []) as $visibility)
                                                <li class="category-panel__select-option" role="option" @click="choose('{{ $visibility }}')" :class="{ 'is-active': value === '{{ $visibility }}' }" :aria-selected="value === '{{ $visibility }}'">{{ $visibility }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                <div class="tip-panel__field">
                                    <span class="tip-panel__field-label">상태</span>
                                    <div
                                        class="category-panel__select-wrap tip-panel__select"
                                        x-data="selectBox()"
                                        :class="{ 'is-open': open }"
                                        @click.outside="close()"
                                        @keydown.escape.stop="close()"
                                    >
                                        <select class="category-panel__select-native" name="status" x-ref="select" x-model="value">
                                            <option value="" @selected(blank(request('status')))>상태</option>
                                            @foreach(config('app.tip_status', []) as $status)
                                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                        <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                            <span class="category-panel__select-label" x-text="label">상태</span>
                                            <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                        <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                            <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">상태</li>
                                            @foreach(config('app.tip_status', []) as $status)
                                                <li class="category-panel__select-option" role="option" @click="choose('{{ $status }}')" :class="{ 'is-active': value === '{{ $status }}' }" :aria-selected="value === '{{ $status }}'">{{ $status }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="user-panel__filter-row tip-panel__filter-row tip-panel__filter-row--period">
                                <div class="tip-panel__field tip-panel__field--period">
                                    <span class="tip-panel__field-label">기간</span>
                                    <div class="tip-panel__date-range">
                                        <input
                                            class="category-panel__input tip-panel__date-input"
                                            type="datetime-local"
                                            name="start_date"
                                            value="{{ $startDateInput }}"
                                            step="60"
                                        />
                                        <span class="tip-panel__date-separator">~</span>
                                        <input
                                            class="category-panel__input tip-panel__date-input"
                                            type="datetime-local"
                                            name="end_date"
                                            value="{{ $endDateInput }}"
                                            step="60"
                                            @change="onEndDateChange($event)"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="user-panel__filter-row tip-panel__filter-row tip-panel__filter-row--search">
                                <div class="tip-panel__field tip-panel__field--search">
                                    <span class="tip-panel__field-label">검색어</span>
                                    <input
                                        class="category-panel__input tip-panel__search-input"
                                        type="text"
                                        name="query"
                                        placeholder="검색어 입력(제목/작성자)"
                                        value="{{ request('query') }}"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="tip-panel__actions-col">
                            <a class="category-panel__bulk-btn category-panel__bulk-btn--ghost" href="{{ url()->current() }}">초기화</a>
                            <button class="category-panel__bulk-btn category-panel__bulk-btn--accent" type="submit">검색</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="user-panel__list-header tip-panel__list-header">
                <div class="user-panel__list-title">목록</div>
                <form class="user-panel__display-form" action="" method="GET">
                    @php
                        $displayParams = ['tab', 'query', 'category_id', 'status', 'visibility', 'start_date', 'end_date'];
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
                            <th>카테고리/제목</th>
                            <th>작성자</th>
                            <th>상태</th>
                            {{-- <th>조회/좋아요</th> --}}
                            <th>날짜</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tipItems as $tip)
                            @php
                                $tipId = data_get($tip, 'id', '-');
                                $title = data_get($tip, 'title', '-');
                                $summary = data_get($tip, 'summary', data_get($tip, 'excerpt', ''));
                                $author = data_get($tip, 'user.name', data_get($tip, 'user', '-'));
                                $category = data_get($tip, 'category.name', data_get($tip, 'category', '미분류'));
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
                                $dateLabel = $dateRaw ? \Illuminate\Support\Carbon::parse($dateRaw)->format('y-m-d A h:i') : '-';
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
                                    <div class="tip-panel__meta-line">
                                        <span class="tip-panel__category">{{ $category }}</span>
                                    </div>
                                    <div class="tip-panel__title-line">
                                        “{{ $title }}”
                                    </div>
                                    {{-- <div class="tip-panel__summary-line">{{ $summary ?: '요약이 없습니다.' }}</div> --}}
                                    <div class="tip-panel__meta-line">
                                        
                                        @if ($tagItems->isNotEmpty())
                                            <span class="tip-panel__tags">
                                                @foreach ($tagItems as $tag)
                                                    <span class="tip-panel__tag">#{{ $tag }}</span>
                                                @endforeach
                                            </span>
                                        @endif
                                    </div>
                                    <div class="tip-panel__actions">
                                        {{-- <button class="tip-panel__action" type="button">미리보기</button> --}}
                                        <button class="tip-panel__action" type="button">편집</button>
                                        {{-- <button class="tip-panel__action" type="button">복제</button>
                                        <button class="tip-panel__action" type="button">⋯</button> --}}
                                    </div>
                                </td>
                                <td>{{ $author }}</td>
                                <td>
                                    <span class="tip-panel__status tip-panel__status--{{ $statusKey }}">{{ $statusLabel }}</span>
                                </td>
                                {{-- <td class="tip-panel__metrics">{{ number_format($views) }} / {{ number_format($likes) }}</td> --}}
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
