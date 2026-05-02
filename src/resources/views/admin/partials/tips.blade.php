<div x-data="{ selected: [] }">
    <div class="category-panel tip-panel">
        <div class="category-panel__content">
            <div class="tip-panel__summary">
                <div class="tip-panel__summary-left">
                    <div class="tip-panel__title">Tips 관리</div>
                    <div class="tip-panel__meta">
                        <span>총 {{ data_get($tipsView, 'total_count_text', '0') }}개</span>
                        <span>최근 수정: {{ data_get($tipsView, 'last_updated_text', '-') }}</span>
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
                    showWarning: {{ session('warning') ? 'true' : 'false' }},
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

                @if (session('warning'))
                    <div class="tip-panel__alert tip-panel__alert--error" x-show="showWarning">
                        <button class="tip-panel__alert-close" type="button" aria-label="닫기" @click="showWarning = false">×</button>
                        {{ session('warning') }}
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
                    @if (filled(data_get($tipsView, 'per_page')))
                        <input type="hidden" name="per_page" value="{{ data_get($tipsView, 'per_page') }}" />
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
                                            <option value="all" @selected(blank(data_get($tipsView, 'category')) || data_get($tipsView, 'category') === 'all')>전체</option>
                                            <option value="uncategorized" @selected(data_get($tipsView, 'category') === 'uncategorized')>미분류</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" @selected((string) data_get($tipsView, 'category', 'all') === (string) $category->id)>{{ $category->name }}</option>
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
                                            <option value="" @selected(blank(data_get($tipsView, 'visibility')))>노출</option>
                                            @foreach(data_get($tipsView, 'visibility_options', []) as $visibility)
                                                <option value="{{ $visibility }}" @selected(data_get($tipsView, 'visibility') === $visibility)>{{ $visibility }}</option>
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
                                            @foreach(data_get($tipsView, 'visibility_options', []) as $visibility)
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
                                            <option value="" @selected(blank(data_get($tipsView, 'status')))>상태</option>
                                            @foreach(data_get($tipsView, 'status_options', []) as $status)
                                                <option value="{{ $status }}" @selected(data_get($tipsView, 'status') === $status)>{{ $status }}</option>
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
                                            @foreach(data_get($tipsView, 'status_options', []) as $status)
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
                                            type="date"
                                            name="start_date"
                                            value="{{ data_get($tipsView, 'start_date_input', '') }}"
                                        />
                                        <span class="tip-panel__date-separator">~</span>
                                        <input
                                            class="category-panel__input tip-panel__date-input"
                                            type="date"
                                            name="end_date"
                                            value="{{ data_get($tipsView, 'end_date_input', '') }}"
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
                                        value="{{ data_get($tipsView, 'query', '') }}"
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
                    @foreach (data_get($tipsView, 'display_values', []) as $param => $value)
                        @if (filled($value))
                            <input type="hidden" name="{{ $param }}" value="{{ $value }}" />
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
                            value="{{ data_get($tipsView, 'per_page', 20) }}"
                        />
                    </label>
                    <button class="category-panel__bulk-btn" type="submit">적용</button>
                </form>
            </div>

            <div class="user-panel__list tip-panel__list">
                <table class="user-panel__table tip-panel__table">
                    <thead>
                        <tr>
                            {{-- <th>
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
                            </th> --}}
                            <th>ID</th>
                            <th>썸네일</th>
                            <th>카테고리/제목</th>
                            <th>작성자</th>
                            <th>노출</th>
                            <th>상태</th>
                            {{-- <th>조회/좋아요</th> --}}
                            <th>날짜</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (data_get($tipsView, 'tip_items', []) as $tip)
                            <x-tip.admin-row :item="$tip" />
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
                        @if (data_get($tipsView, 'first_item') !== null && data_get($tipsView, 'last_item') !== null)
                            {{ data_get($tipsView, 'first_item') }}-{{ data_get($tipsView, 'last_item') }} / 총 {{ data_get($tipsView, 'total_count_text', '0') }}개
                        @else
                            총 {{ data_get($tipsView, 'total_count_text', '0') }}개
                        @endif
                    </span>
                </div>
                @if (data_get($tipsView, 'show_pagination'))
                    <div class="app-pagination">
                        {{ $datas->onEachSide(1)->links('vendor.pagination.app') }}
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
