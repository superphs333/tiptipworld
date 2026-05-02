<section class="tip-wireframe tip-list-wireframe tip-search-minimal" data-tip-search-ui>
    <form
        id="tip-search-form"
        class="tip-search-minimal__form"
        method="GET"
        action="{{ route('tips.search') }}"
        data-search-form
        aria-label="검색 조건"
    >
        <section class="tip-search-minimal__search-box" aria-label="통합 검색 영역">
            <div class="tip-search-minimal__search-row">
                <label class="tip-search-minimal__field" for="tip-search-category">
                    <span>카테고리</span>
                    <select class="tip-search-minimal__select" id="tip-search-category" name="category" data-search-category>
                        <option value="all" @selected(data_get($searchView, 'category') === '' || data_get($searchView, 'category') === 'all')>전체 카테고리</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) data_get($searchView, 'category', 'all') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="tip-search-minimal__field" for="tip-search-query">
                    <span>검색어(제목/작성자)</span>
                    <input
                        class="tip-search-minimal__input"
                        id="tip-search-query"
                        type="search"
                        name="query"
                        value="{{ data_get($searchView, 'query', '') }}"
                        placeholder="제목/작성자 통합 검색"
                        autocomplete="off"
                        data-search-query
                    >
                </label>

                <label class="tip-search-minimal__field tip-search-minimal__field--sort" for="tip-search-sort">
                    <span>정렬</span>
                    <select class="tip-search-minimal__select" id="tip-search-sort" name="sort" data-search-sort>
                        @foreach (data_get($searchView, 'sort_options', []) as $sortValue => $sortLabel)
                            <option value="{{ $sortValue }}" @selected(data_get($searchView, 'sort') === $sortValue)>{{ $sortLabel }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <section class="tip-search-minimal__tag-panel" aria-label="검색에 포함할 태그">
                <header class="tip-search-minimal__tag-head">
                    <h2>검색 태그</h2>
                </header>
                <p class="tip-search-minimal__tag-rule">선택한 태그를 모두 포함한 게시글만 검색됩니다.</p>

                <div class="tip-search-minimal__tag-input-wrap">
                    <input
                        class="tip-search-minimal__input"
                        type="search"
                        placeholder="태그 입력"
                        autocomplete="off"
                        data-tag-input
                    >
                    <button class="tip-search-minimal__btn" type="button" data-tag-add>추가</button>
                </div>

                <div class="tip-search-minimal__tags" data-tag-list>
                    <span class="tip-search-minimal__empty" data-tag-empty>선택된 태그 없음</span>
                </div>
            </section>
        </section>

        <div class="tip-search-minimal__actions">
            <button class="tip-search-minimal__btn tip-search-minimal__btn--primary" type="submit">검색</button>
        </div>

        <div class="tip-search-minimal__tag-hidden" data-tag-hidden-inputs></div>
    </form>

    <section class="tip-list-wireframe__list" aria-label="검색 결과">
        <header class="tip-list-wireframe__list-head">
            <div class="tip-list-wireframe__list-heading">
                <h2 class="tip-list-wireframe__section-title">리스트</h2>
                <p>{{ data_get($searchView, 'total_count_text', '0') }}개의 게시글</p>
            </div>
        </header>

        <div class="tip-list-wireframe__items">
            @forelse ($tipItems as $item)
                <x-tip.list-item :item="$item" :show-category="true" :show-tags="true" />
            @empty
                <article class="tip-list-wireframe__item tip-list-wireframe__item--empty">
                    <p>검색 결과가 없습니다.</p>
                </article>
            @endforelse
        </div>

        <footer class="tip-list-wireframe__pagination">
            <span class="tip-list-wireframe__page-meta">
                @if (data_get($searchView, 'first_item') !== null && data_get($searchView, 'last_item') !== null)
                    {{ data_get($searchView, 'first_item') }}-{{ data_get($searchView, 'last_item') }} / 총 {{ data_get($searchView, 'total_count_text', '0') }}개
                @else
                    총 {{ data_get($searchView, 'total_count_text', '0') }}개
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

@once
    <script>
        (() => {
            const root = document.querySelector('[data-tip-search-ui]');
            if (!root) {
                return;
            }

            const form = root.querySelector('[data-search-form]');
            const sortSelect = root.querySelector('[data-search-sort]');
            const tagInput = root.querySelector('[data-tag-input]');
            const tagAddButton = root.querySelector('[data-tag-add]');
            const tagList = root.querySelector('[data-tag-list]');
            const tagEmpty = root.querySelector('[data-tag-empty]');
            const tagHiddenInputs = root.querySelector('[data-tag-hidden-inputs]');
            const initialTags = @json(data_get($searchView, 'tags', []));

            if (!form || !tagInput || !tagAddButton || !tagList || !tagEmpty || !tagHiddenInputs) {
                return;
            }

            const state = {
                tags: [],
            };

            const syncTagHiddenInputs = () => {
                tagHiddenInputs.innerHTML = '';
                state.tags.forEach((tag) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'tags[]';
                    hidden.value = tag;
                    tagHiddenInputs.appendChild(hidden);
                });
            };

            const setTagStates = () => {
                const hasTags = tagList.querySelector('[data-tag-chip]') !== null;
                tagEmpty.hidden = hasTags;
                syncTagHiddenInputs();
            };

            const createTagChip = (label) => {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'tip-search-minimal__tag';
                chip.dataset.tagChip = label.toLowerCase();
                chip.textContent = `#${label} ×`;
                tagList.appendChild(chip);
            };

            const addTag = () => {
                const raw = String(tagInput.value || '').trim();
                if (!raw) {
                    return;
                }

                const key = raw.toLowerCase();
                if (state.tags.some((tag) => tag.toLowerCase() === key)) {
                    tagInput.value = '';
                    return;
                }

                state.tags.push(raw);
                createTagChip(raw);
                tagInput.value = '';
                setTagStates();
            };

            form.addEventListener('submit', () => {
                syncTagHiddenInputs();
            });

            if (sortSelect) {
                sortSelect.addEventListener('change', () => {
                    syncTagHiddenInputs();
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                        return;
                    }
                    form.submit();
                });
            }

            tagAddButton.addEventListener('click', addTag);
            tagInput.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') {
                    return;
                }
                event.preventDefault();
                addTag();
            });

            tagList.addEventListener('click', (event) => {
                const chip = event.target.closest('[data-tag-chip]');
                if (!chip) {
                    return;
                }

                const target = String(chip.dataset.tagChip || '').trim();
                state.tags = state.tags.filter((tag) => tag.toLowerCase() !== target);
                chip.remove();
                setTagStates();
            });

            initialTags.forEach((tag) => {
                const label = String(tag || '').trim();
                if (!label) {
                    return;
                }

                if (state.tags.some((current) => current.toLowerCase() === label.toLowerCase())) {
                    return;
                }

                state.tags.push(label);
                createTagChip(label);
            });

            setTagStates();
        })();
    </script>
@endonce
