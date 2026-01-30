<div x-data="categoryPanel()" @keydown.escape.window="closeModal()">
    <div class="category-panel" x-data="{selected:[]}">
        <div class="category-panel__content">
        <div class="category-panel__top-actions">
            <button class="category-panel__add-btn" type="button" aria-label="카테고리 추가" @click="openModal()">
                <span class="category-panel__add-icon" aria-hidden="true">+</span>
            </button>
        </div>
        {{-- 카테고리 필터링 --}}
        <div class="category-panel__filter">
            <form class="category-panel__form" action="" method="GET">
                <div class="category-panel__search">
                    <div
                        class="category-panel__select-wrap"
                        x-data="selectBox()"
                        :class="{ 'is-open': open }"
                        @click.outside="close()"
                        @keydown.escape.stop="close()"
                    >
                        <select class="category-panel__select-native" name="is_active" id="is_active" x-ref="select" x-model="value">
                            <option value="" {{ request('is_active') === null || request('is_active') === '' ? 'selected' : '' }}>전체</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>활성화</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>비활성화</option>
                        </select>
                        <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                            <span class="category-panel__select-label" x-text="label">전체</span>
                            <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <ul class="category-panel__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                            <li class="category-panel__select-option" role="option" @click="choose('')" :class="{ 'is-active': value === '' }" :aria-selected="value === ''">전체</li>
                            <li class="category-panel__select-option" role="option" @click="choose('1')" :class="{ 'is-active': value === '1' }" :aria-selected="value === '1'">활성화</li>
                            <li class="category-panel__select-option" role="option" @click="choose('0')" :class="{ 'is-active': value === '0' }" :aria-selected="value === '0'">비활성화</li>
                        </ul>
                    </div>
                    <input class="category-panel__input" type="text" name="name" placeholder="이름/슬러그 검색" />
                    <button class="category-panel__search-btn" type="submit" aria-label="검색">
                        <svg class="category-panel__search-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M11 19a8 8 0 1 1 5.657-2.343L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        {{-- 카테고리 선택 조절 --}}
        <div class="category-panel__bulk-actions" x-show="selected.length > 0" x-cloak>
            <button class="category-panel__bulk-btn" type="button">삭제</button>
            <div class="category-panel__bulk-count" x-text="`${selected.length}개 선택됨`"></div>
        </div>

        {{-- 카테고리 목록 --}}
        <div>
            <table class="category-panel__table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox"
                            @change="
                                selected = $event.target.checked
                                ? [...$el.closest('table')
                                    .querySelectorAll('input[name=&quot;category_ids[]&quot;]')]
                                    .map(el => el.value)
                                : [];
                            "
                            />
                        </th>
                        <th>이름</th>
                        <th>상태</th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($datas ?? collect()) as $category)
                        <tr>    
                            <td>
                                <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" x-model="selected" />
                            </td>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->is_active ? '활성화' : '비활성화' }}</td>
                            <td>                                
                                <a href="" class="category-panel__edit-link">편집</a>                            
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">데이터가 없습니다.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td>합계</td>
                        <td>{{ $datas->count() }}개</td>
                    </tr>
                </tfoot>
            </table>
            <div class="category-panel__footer">
                목록은 선택 정렬순으로 표시됩니다.
            </div>
        </div>
        </div>
    </div>

    <div class="category-modal" :class="{ 'is-open': modalOpen }" :aria-hidden="(!modalOpen).toString()">
        <div class="category-modal__overlay" @click="closeModal()"></div>
        <div class="category-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
            <div class="category-modal__header">
                <h3 class="category-modal__title" id="category-modal-title">카테고리 추가</h3>
                <button class="category-modal__close" type="button" aria-label="닫기" @click="closeModal()">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form class="category-modal__form" action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="category-modal__grid">
                    <div class="category-modal__field category-modal__field--full">
                        <label class="category-modal__label" for="category-name">이름</label>
                        <input class="category-modal__input" type="text" id="category-name" name="name" required x-ref="modalFocus" />
                    </div>
                    <div class="category-modal__field category-modal__field--full">
                        <label class="category-modal__label" for="category-description">설명</label>
                        <textarea class="category-modal__textarea" id="category-description" name="description" rows="3"></textarea>
                    </div>
                    {{-- <div class="category-modal__field">
                        <label class="category-modal__label" for="category-slug">슬러그</label>
                        <input class="category-modal__input" type="text" id="category-slug" name="slug" placeholder="자동 생성" />
                    </div> --}}
                    <div class="category-modal__field">
                        <label class="category-modal__label" for="category-is-active">상태</label>
                        <div
                            class="category-modal__select-wrap"
                            x-data="selectBox()"
                            :class="{ 'is-open': open }"
                            @click.outside="close()"
                            @keydown.escape.stop="close()"
                        >
                            <select class="category-modal__select-native" id="category-is-active" name="is_active" x-ref="select" x-model="value">
                                <option value="1">활성화</option>
                                <option value="0">비활성화</option>
                            </select>
                            <button class="category-modal__select-trigger" type="button" aria-haspopup="listbox" :aria-expanded="open" @click="toggle()">
                                <span class="category-modal__select-label" x-text="label">활성화</span>
                                <svg class="category-modal__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul class="category-modal__select-menu" role="listbox" tabindex="-1" x-ref="menu">
                                <li class="category-modal__select-option" role="option" @click="choose('1')" :class="{ 'is-active': value === '1' }" :aria-selected="value === '1'">활성화</li>
                                <li class="category-modal__select-option" role="option" @click="choose('0')" :class="{ 'is-active': value === '0' }" :aria-selected="value === '0'">비활성화</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="category-modal__actions">
                    <button class="category-modal__btn" type="button" @click="closeModal()">취소</button>
                    <button  class="category-modal__btn category-modal__btn--primary" type="submit">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
<script>
    document.addEventListener("alpine:init", () => {
        // 화면 전체 상태 컨트롤러 (모달 열기/닫기 + 배경 스크롤 잠금).
        Alpine.data("categoryPanel", () => ({
            modalOpen: false,
            init() {
                // 모달이 열려 있는 동안 배경 스크롤을 막고, 첫 입력칸에 포커스를 준다.
                this.$watch("modalOpen", (value) => {
                    document.body.classList.toggle("is-modal-open", value);
                    if (value) {
                        this.$nextTick(() => this.$refs.modalFocus?.focus());
                    }
                });
            },
            openModal() {
                // 추가 버튼으로 모달을 연다.
                this.modalOpen = true;
            },
            closeModal() {
                // 오버레이/닫기 버튼/ESC로 모달을 닫는다.
                this.modalOpen = false;
            },
        }));

        // 커스텀 셀렉트 공용 컨트롤러 (필터/모달 셀렉트 공통).
        Alpine.data("selectBox", () => ({
            open: false,
            value: "",
            label: "",
            init() {
                // 네이티브 select 값으로 초기화한 뒤, 라벨과 값을 계속 동기화한다.
                this.value = this.$refs.select?.value ?? "";
                this.setLabel();
                this.$watch("value", () => this.setLabel());
            },
            setLabel() {
                // 선택된 옵션의 텍스트를 트리거에 표시한다.
                const options = Array.from(this.$refs.select?.options || []);
                const selected = options.find((option) => option.value === this.value);
                this.label = selected ? selected.textContent : "";
            },
            toggle() {
                // 드롭다운 목록을 열고 닫는다.
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => this.$refs.menu?.focus());
                }
            },
            choose(value) {
                // 옵션을 선택하고 네이티브 select 값도 갱신한 뒤 닫는다.
                this.value = value;
                this.open = false;
            },
            close() {
                // 바깥 클릭 또는 ESC 시 드롭다운을 닫는다.
                this.open = false;
            },
        }));
    });
</script>
@endonce
