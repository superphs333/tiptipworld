<div class="category-panel">
    <div class="category-panel__content">
        <div class="category-panel__top-actions">
            <button class="category-panel__add-btn" type="button" aria-label="카테고리 추가">
                <span class="category-panel__add-icon" aria-hidden="true">+</span>
            </button>
        </div>
        {{-- 카테고리 필터링 --}}
        <div class="category-panel__filter">
            <form class="category-panel__form" action="" method="GET">
                <div class="category-panel__search">
                    <div class="category-panel__select-wrap" data-select>
                        <select class="category-panel__select-native" name="is_active" id="is_active">
                            <option value="">전체</option>
                            <option value="1">활성화</option>
                            <option value="0">비활성화</option>
                        </select>
                        <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" data-select-trigger>
                            <span class="category-panel__select-label" data-select-label>전체</span>
                            <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <ul class="category-panel__select-menu" role="listbox" tabindex="-1" data-select-menu>
                            <li class="category-panel__select-option" role="option" data-value="" data-select-option>전체</li>
                            <li class="category-panel__select-option" role="option" data-value="1" data-select-option>활성화</li>
                            <li class="category-panel__select-option" role="option" data-value="0" data-select-option>비활성화</li>
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

        {{-- 카테고리 목록 --}}
        <div>
            <table class="category-panel__table">
                <thead>
                    <tr>
                        <th>이름</th>
                        <th>슬러그</th>
                        <th>상태</th>
                        <th>정렬</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- 카테고리 데이터 --}}
                </tbody>
                <tfoot>
                    <tr>
                        <td>합계</td>
                        <td>개</td>
                    </tr>
                </tfoot>
            </table>
            <div class="category-panel__footer">
                목록은 최신순으로 정렬됩니다.
            </div>
        </div>
    </div>
</div>

@vite('resources/js/admin/category-panel.js')

<div class="category-modal" data-category-modal aria-hidden="true">
    <div class="category-modal__overlay" data-category-modal-close></div>
    <div class="category-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
        <div class="category-modal__header">
            <h3 class="category-modal__title" id="category-modal-title">카테고리 추가</h3>
            <button class="category-modal__close" type="button" data-category-modal-close aria-label="닫기">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <form class="category-modal__form" action="" method="POST">
            @csrf
            <div class="category-modal__grid">
                <div class="category-modal__field category-modal__field--full">
                    <label class="category-modal__label" for="category-name">이름</label>
                    <input class="category-modal__input" type="text" id="category-name" name="name" required data-category-modal-focus />
                </div>
                <div class="category-modal__field category-modal__field--full">
                    <label class="category-modal__label" for="category-description">설명</label>
                    <textarea class="category-modal__textarea" id="category-description" name="description" rows="3"></textarea>
                </div>
                <div class="category-modal__field">
                    <label class="category-modal__label" for="category-slug">슬러그</label>
                    <input class="category-modal__input" type="text" id="category-slug" name="slug" placeholder="자동 생성" />
                </div>
                <div class="category-modal__field">
                    <label class="category-modal__label" for="category-is-active">상태</label>
                    <div class="category-modal__select-wrap" data-select>
                        <select class="category-modal__select-native" id="category-is-active" name="is_active">
                            <option value="1">활성화</option>
                            <option value="0">비활성화</option>
                        </select>
                        <button class="category-modal__select-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" data-select-trigger>
                            <span class="category-modal__select-label" data-select-label>활성화</span>
                            <svg class="category-modal__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <ul class="category-modal__select-menu" role="listbox" tabindex="-1" data-select-menu>
                            <li class="category-modal__select-option" role="option" data-value="1" data-select-option>활성화</li>
                            <li class="category-modal__select-option" role="option" data-value="0" data-select-option>비활성화</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="category-modal__actions">
                <button class="category-modal__btn" type="button" data-category-modal-close>취소</button>
                <button class="category-modal__btn category-modal__btn--primary" type="submit">저장</button>
            </div>
        </form>
    </div>
</div>
