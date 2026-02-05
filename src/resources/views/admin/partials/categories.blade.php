<div x-data="categoryPanel()" @keydown.escape.window="closeModal()">
    <div class="category-panel" x-data="{selected:[]}">
        <div class="category-panel__content">
        <div class="category-panel__top-actions">
            <button class="category-panel__add-btn" type="button" aria-label="카테고리 추가" @click="openModal('add')">
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
                            <option value="" @selected(blank(request('is_active')))>전체</option>
                            <option value="1" @selected(request('is_active') === '1')>활성화</option>
                            <option value="0" @selected(request('is_active') === '0')>비활성화</option>
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
                    <input class="category-panel__input" type="text" name="name" placeholder="이름 검색"  value="{{ request('name') }}"/>
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
            <div class="category-panel__bulk-left">
                <span class="category-panel__bulk-dot" aria-hidden="true"></span>
                <span class="category-panel__bulk-count" x-text="`${selected.length}개 선택됨`"></span>
                <button class="category-panel__bulk-btn category-panel__bulk-btn--ghost" type="button" @click="selected = []; if ($refs.selectAll) { $refs.selectAll.checked = false; }">선택 해제</button>
            </div>
            <div class="category-panel__bulk-right">
                <form method="POST" :action="`{{ route('admin.categories.updateIsActive', ['category_ids' => '__IDS__']) }}`.replace('__IDS__', selected.join(','))" >
                    @csrf
                    @method('PATCH')
                    <button class="category-panel__bulk-btn" name="is_active_action" value="0" type="submit">비활성화</button>
                    <button class="category-panel__bulk-btn category-panel__bulk-btn--accent" name="is_active_action" value="1" type="submit">활성화</button>
                </form>

                <form method="POST" :action="`{{ route('admin.categories.delete', ['category_ids' => '__IDS__']) }}`.replace('__IDS__', selected.join(','))" onsubmit="return confirm('정말 삭제할까요?')">
                    @csrf
                    @method('DELETE')
                    <button class="category-panel__bulk-btn category-panel__bulk-btn--danger" type="submit">삭제</button>
                </form>                
            </div>
        </div>

        {{-- 카테고리 목록 --}}
        <div>
            <table class="category-panel__table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox"
                            x-ref="selectAll"
                            @change="
                                selected = $event.target.checked
                                ? [...$el.closest('table')
                                    .querySelectorAll('input[name=&quot;category_ids[]&quot;]')]
                                    .map(el => el.value)
                                : [];
                            "
                            />
                        </th>
                        <th x-show="{{ request('is_active') ? 'false' : 'true' }}"  class="w-10 text-center" aria-label="정렬">↕</th>
                        <th>이름</th>
                        <th>상태</th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody id="category_table_body">
                    @forelse (($datas ?? collect()) as $category)
                        <tr data-order={{ $category->sort_order }}  data-id={{ $category->id }}> 
                            <td>
                                <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" x-model="selected" />
                            </td>
                            <td x-show="{{ request('is_active') ? 'false' : 'true' }}" class="category-panel__drag-cell w-10 text-center">
                                <span class="category-panel__drag-handle" aria-hidden="true">≡</span>
                            </td>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->is_active ? '활성화' : '비활성화' }}</td>
                            <td>                                
                                <div class="category-panel__actions">
                                    <form action="{{ route('admin.categories.delete', ['category_ids' => $category->id]) }}" method="POST" onsubmit="return confirm('정말 삭제할까요?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="category-panel__action category-panel__action--delete category-panel__delete-link" type="submit">
                                            <svg class="category-panel__action-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M4 7h16" stroke="currentColor" stroke-linecap="round"/>
                                                <path d="M9 7V5h6v2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M7 7l1 12h8l1-12" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M10 11v5M14 11v5" stroke="currentColor" stroke-linecap="round"/>
                                            </svg>
                                            <span>삭제</span>
                                        </button>
                                    </form>
                                    {{-- 편집 클릭시 ->  category-modal 버튼이 열림 -> 선택된 항목에 대한거 모달에 넣기 / 카테고리 추가+저장 부분을 카테고리 수정+수정 으로 변경하기 -> 수정  --}}
                                    <button type="button" class="category-panel__action category-panel__action--edit category-panel__edit-link"  @click="openModal('edit',@js($category))">
                                        <svg class="category-panel__action-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-linejoin="round"/>
                                            <path d="M14 6l4 4" stroke="currentColor" stroke-linecap="round"/>
                                        </svg>
                                        <span>편집</span>
                                    </button>
                                </div>
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
                        <td></td>
                        <td>{{ $datas->count() }}개</td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <div class="category-panel__footer">
                <span class="category-panel__footer-note">목록은 선택 정렬순으로 표시됩니다.</span>
            </div>
            <!-- 정렬완료버튼 -->
            <div class="category-panel__footer-actions" style="display: none">
                <form method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" id="forSortOrderSubmit" name="ordered_ids">
                    <button class="category-panel__bulk-btn category-panel__bulk-btn--accent category-panel__footer-btn" type="submit" >정렬완료</button>
                </form>
            </div>
        </div>
        </div>
    </div>

    {{-- 모달 --}}
    <div class="category-modal" :class="{ 'is-open': modalOpen }" :aria-hidden="(!modalOpen).toString()">
        <div class="category-modal__overlay" @click="closeModal()"></div>
        <div class="category-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
            <div class="category-modal__header">
                <h3 class="category-modal__title" id="category-modal-title">카테고리
                    <span x-text="modalTitle"></span>
                </h3>
                <button class="category-modal__close" type="button" aria-label="닫기" @click="closeModal()">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form class="category-modal__form" :action="modeAction" 
                method="POST">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PATCH" />
                </template>
                <div class="category-modal__grid">
                    <div class="category-modal__field category-modal__field--full">
                        <label class="category-modal__label" for="category-name">이름</label>
                        <input class="category-modal__input" type="text" id="category-name" name="name" required x-ref="modalFocus" :value="data?.name ?? ''" />
                    </div>
                    <div class="category-modal__field category-modal__field--full">
                        <label class="category-modal__label" for="category-description">설명</label>
                        <textarea class="category-modal__textarea" id="category-description" name="description" rows="3" :value="data?.description ?? ''"></textarea>
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
                            x-modelable="value"
                            x-model="data.is_active"
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
                    <button  class="category-modal__btn category-modal__btn--primary" type="submit">
                        <span x-text="submitLabel"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener("alpine:init", () => {
        // 화면 전체 상태 컨트롤러 (모달 열기/닫기 + 배경 스크롤 잠금).
        Alpine.data("categoryPanel", () => ({
            modalOpen: false,
            modalMode : 'add',
            initData : {
                id : null,
                name : "",
                description : "",
                is_active : "1"
            },
            storeUrl : @js(route('admin.categories.store')),
            updateUrl: @js(route('admin.category.update', ['category_id' => '__ID__'])),
            get modeAction(){
                console.log('id->'+this.data?.id)
                
                return this.isEdit
                ? this.updateUrl.replace('__ID__',this.data?.id??'')
                : this.storeUrl
            },
            data : {},
            get isEdit() {return this.modalMode === 'edit';},
            get modalTitle() {return this.isEdit ? '수정' : '추가';},
            get submitLabel() {return this.isEdit ? '수정' : '저장';},
            init() {
                // 모달이 열려 있는 동안 배경 스크롤을 막고, 첫 입력칸에 포커스를 준다.
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
                const active = normalized.is_active;
                if (active === true || active === 1 || active === "1") {
                    normalized.is_active = "1";
                } else if (active === false || active === 0 || active === "0") {
                    normalized.is_active = "0";
                } else {
                    normalized.is_active = "1";
                }
                return normalized;
            },
            openModal(mode, data=null) {
                // 추가 버튼으로 모달을 연다.
                this.modalMode = mode; // add | edit
                this.data = this.normalizeData(mode === "edit" ? data : null);
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
                if (this.value === "" || this.value === null) {
                    this.value = this.$refs.select?.value ?? "";
                }
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

    /*
    드래그 앤 드롭
    */
    let dragCount = 0;
    const el = document.getElementById('category_table_body');
    new Sortable(el, {
        animation: 150,
        handle: '.category-panel__drag-handle',
        onEnd : function(evt){
            dragCount++;
            
            // category-panel__footer-actions의 display속성을 보이도록 변경
            if(document.querySelector('.category-panel__footer-actions').style.display == 'none'){
                document.querySelector('.category-panel__footer-actions').style.display = 'block';
            }
            
        }
    });

    
    /**
     * 정렬완료시 데이터 보내기
     */ 
    const sortForm = document.querySelector('.category-panel__footer-actions form');
    if(sortForm){
        sortForm.addEventListener('submit', function(e){
            // 기본 제출 동작 막기
            e.preventDefault();

            // 현재 화면에 보이는 테이블 행(tr)의 순서대로 ID를 추출 
            const rows = document.querySelectorAll("#category_table_body tr");
            const ids = Array.from(rows).map(row => {
                return row.querySelector('input[name="category_ids[]"]').value
            });

            // 추출한 ID 목록을 담을 hidden input 에 넣기 (#forSortOrderSubmit)
            let input = document.querySelector('#forSortOrderSubmit');
            input.value = ids.join(',');
            
            // 폼의 전송 주소 설정
            this.action = "{{ route('admin.category.updateSort') }}";

            // 폼 전송
            this.submit();

        });
    }

</script>
@endonce
