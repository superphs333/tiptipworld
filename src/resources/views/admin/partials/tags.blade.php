@php
    $tags = $datas ?? collect();
    if (method_exists($tags, 'getCollection')) {
        $tagItems = $tags->getCollection();
    } else {
        $tagItems = collect($tags);
    }
    $totalCount = method_exists($tags, 'total') ? $tags->total() : $tagItems->count();
    $showPagination = method_exists($tags, 'links');
    $firstItem = method_exists($tags, 'firstItem') ? $tags->firstItem() : null;
    $lastItem = method_exists($tags, 'lastItem') ? $tags->lastItem() : null;
@endphp

<div x-data="tagPanel()" @keydown.escape.window="closeModal()">
    <div class="category-panel tag-panel">
        <div class="category-panel__content">
            <div class="category-panel__top-actions">
                <button class="category-panel__add-btn tag-panel__add-btn" type="button" aria-label="태그 추가" @click="openModal('add')">
                    <span class="category-panel__add-icon" aria-hidden="true">+</span>
                </button>
            </div>

            <div
                class="tag-panel__alerts"
                x-data="{
                    showErrors: {{ $errors->any() ? 'true' : 'false' }},
                    showError: {{ session('error') ? 'true' : 'false' }},
                    showSuccess: {{ session('success') ? 'true' : 'false' }},
                }"
            >
                @if ($errors->any())
                    <div class="tag-panel__alert tag-panel__alert--error" x-show="showErrors">
                        <button class="tag-panel__alert-close" type="button" aria-label="닫기" @click="showErrors = false">×</button>
                        <ul>
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="tag-panel__alert tag-panel__alert--error" x-show="showError">
                        <button class="tag-panel__alert-close" type="button" aria-label="닫기" @click="showError = false">×</button>
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="tag-panel__alert tag-panel__alert--success" x-show="showSuccess">
                        <button class="tag-panel__alert-close" type="button" aria-label="닫기" @click="showSuccess = false">×</button>
                        {{ session('success') }}
                    </div>
                @endif
            </div>
            <div class="tag-panel__filter">
                <form class="tag-panel__filter-form" action="" method="GET">
                    <div
                        class="category-panel__select-wrap tag-panel__filter-select"
                        x-data="selectBox()"
                        :class="{ 'is-open': open }"
                        @click.outside="close()"
                        @keydown.escape.stop="close()"
                    >
                        <select class="category-panel__select-native" name="is_blocked" x-ref="select" x-model="value">
                            <option value="" @selected(blank(request('is_blocked')))>전체</option>
                            <option value="0" @selected(request('is_blocked') === '0')>사용</option>
                            <option value="1" @selected(request('is_blocked') === '1')>금지</option>
                        </select>
                        <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                            <span class="category-panel__select-label" x-text="label">전체</span>
                            <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                            <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">전체</li>
                            <li class="category-panel__select-option" role="option" @click="choose('0')" :class="{ 'is-active': value === '0' }" :aria-selected="value === '0'">사용</li>
                            <li class="category-panel__select-option" role="option" @click="choose('1')" :class="{ 'is-active': value === '1' }" :aria-selected="value === '1'">금지</li>
                        </ul>
                    </div>
                    <div class="tag-panel__filter-search">
                        <input class="category-panel__input tag-panel__search-input" type="text" name="query" placeholder="이름 / ID 검색" value="{{ request('query') }}" />
                        <button class="category-panel__search-btn" type="submit" aria-label="검색">
                            <svg class="category-panel__search-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M11 19a8 8 0 1 1 5.657-2.343L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <div class="tag-panel__table-wrap">
                <table class="category-panel__table tag-panel__table">
                    <thead>
                        <tr>
                            <th>
                                <input
                                    type="checkbox"
                                    x-ref="selectAll"
                                    @change="
                                        selected = $event.target.checked
                                        ? [...$el.closest('table')
                                            .querySelectorAll('input[name=&quot;tag_ids[]&quot;]')]
                                            .map(el => el.value)
                                        : [];
                                    "
                                />
                            </th>
                            <th>ID</th>
                            <th>이름</th>
                            <th>사용량</th>
                            <th>금지</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tagItems as $tag)
                            @php
                                $tagId = data_get($tag, 'id', '-');
                                $tagName = data_get($tag, 'name', '-');
                                $usageCount = data_get($tag, 'usage_count', data_get($tag, 'tips_count', 0));
                                $isBlocked = (bool) data_get($tag, 'is_blocked', false);
                            @endphp
                            <tr>
                                <td><input type="checkbox" name="tag_ids[]" value="{{ $tagId }}" x-model="selected" /></td>
                                <td>{{ $tagId }}</td>
                                <td>{{ $tagName }}</td>
                                <td>{{ number_format((int) $usageCount) }}</td>
                                <td>
                                    <span class="tag-panel__badge {{ $isBlocked ? 'is-blocked' : 'is-normal' }}">{{ $isBlocked ? '금지' : '정상' }}</span>
                                </td>
                                <td>
                                    <button type="button" class="category-panel__action category-panel__action--delete">
                                        <svg class="category-panel__action-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 7h16" stroke="currentColor" stroke-linecap="round"/>
                                            <path d="M9 7V5h6v2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M7 7l1 12h8l1-12" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M10 11v5M14 11v5" stroke="currentColor" stroke-linecap="round"/>
                                        </svg>
                                        <span>삭제</span>
                                    </button>
                                    <button type="button" class="category-panel__action category-panel__action--edit" @click="openModal('edit', @js($tag))">
                                        <svg class="category-panel__action-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-linejoin="round"/>
                                            <path d="M14 6l4 4" stroke="currentColor" stroke-linecap="round"/>
                                        </svg>
                                        <span>편집</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="tag-panel__empty">데이터가 없습니다.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="tag-panel__pagination">
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
                        {{ $tags->onEachSide(1)->links('vendor.pagination.app') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="category-modal tag-modal" :class="{ 'is-open': modalOpen }" :aria-hidden="(!modalOpen).toString()">
        <div class="category-modal__overlay" @click="closeModal()"></div>
        <div class="category-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tag-modal-title">
            <div class="category-modal__header">
                <h3 class="category-modal__title" id="tag-modal-title">태그 <span x-text="modalTitle"></span></h3>
                <button class="category-modal__close" type="button" aria-label="닫기" @click="closeModal()">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form class="category-modal__form tag-modal__form" :action="modeAction" method="POST">
                @csrf
                <div class="category-modal__grid tag-modal__grid">
                    {{-- <div class="category-modal__field">
                        <label class="category-modal__label" for="tag-id">ID</label>
                        <input class="category-modal__input tag-modal__input--readonly" type="text" id="tag-id" x-model="data.id" readonly />
                    </div> --}}
                    <div class="category-modal__field">
                        <label class="category-modal__label" for="tag-name">이름*</label>
                        <input class="category-modal__input" type="text" id="tag-name" name="name" required x-ref="modalFocus" x-model="data.name" />
                    </div>
                    <div class="category-modal__field tag-modal__field--toggle">
                        <label class="category-modal__label" for="tag-blocked">금지 태그</label>
                        <label class="tag-modal__toggle">
                            <input type="checkbox" id="tag-blocked" name="is_blocked" value="1" x-model="data.is_blocked" />
                            {{-- <span>금지</span> --}}
                        </label>
                    </div>
                    <div class="category-modal__field">
                        <label class="category-modal__label" for="tag-usage">사용량(캐시)</label>
                        <input class="category-modal__input tag-modal__input--readonly" type="text" id="tag-usage" x-model="data.usage_count" readonly />
                    </div>
                    <div class="category-modal__field">
                        <label class="category-modal__label" for="tag-created">생성일</label>
                        <input class="category-modal__input tag-modal__input--readonly" type="text" id="tag-created" x-model="data.created_at" readonly />
                    </div>
                    <div class="category-modal__field">
                        <label class="category-modal__label" for="tag-updated">수정일</label>
                        <input class="category-modal__input tag-modal__input--readonly" type="text" id="tag-updated" x-model="data.updated_at" readonly />
                    </div>
                </div>
                <div class="category-modal__actions">
                    <button class="category-modal__btn" type="button" @click="closeModal()">취소</button>
                    <button class="category-modal__btn category-modal__btn--primary" type="submit">
                        <span x-text="submitLabel"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("tagPanel", () => ({
            modalOpen: false,
            modalMode: "add",
            selected: [],
            initData: {
                id: "",
                name: "",
                is_blocked: false,
                usage_count: 0,
                created_at: "",
                updated_at: ""
            },
            storeUrl : @js(route('admin.tag.store')),
            data: {},
            get isEdit() { return this.modalMode === "edit"; },
            get modalTitle() { return this.isEdit ? "편집" : "추가"; },
            get submitLabel() { return this.isEdit ? "저장" : "저장"; },
            init() {
                this.$watch("modalOpen", (value) => {
                    document.body.classList.toggle("is-modal-open", value);
                    if (value) {
                        this.$nextTick(() => this.$refs.modalFocus?.focus());
                    }
                });
                this.data = this.normalizeData();
            },
            normalizeData(data = null) {
                const normalized = { ...this.initData, ...(data ?? {}) };
                normalized.is_blocked = Boolean(normalized.is_blocked) ?? 0;
                normalized.usage_count = normalized.usage_count ?? 0;
                return normalized;
            },
            openModal(mode, data = null) {
                this.modalMode = mode;
                this.data = this.normalizeData(mode === "edit" ? data : null);
                this.modalOpen = true;
            },
            closeModal() {
                this.modalOpen = false;
            },
            clearSelection() {
                this.selected = [];
                if (this.$refs.selectAll) {
                    this.$refs.selectAll.checked = false;
                }
            },
            get modeAction(){
                return this.isEdit
                ? this.updateUrl.replace('__ID__',this.data?.id??'')
                : this.storeUrl
            }
        }));

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
            }
        }));
    });
</script>
@endonce
