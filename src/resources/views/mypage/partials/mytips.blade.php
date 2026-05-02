<section class="mytips-showcase">
    <div class="mytips-shell">
        <div class="mytips-panel">
            <form class="mytips-search-body" method="GET" action="{{ route('mypage', ['tab' => 'mytips']) }}">
                <div class="mytips-search-grid">
                    <label class="mytips-field">
                        <span class="mytips-label">카테고리</span>
                        <select class="mytips-control" name="category_id">
                            <option value="" @selected(blank(data_get($myTipsFilters, 'category')))>전체 카테고리</option>
                                @foreach ($myTipcategories as $myTipcategory)
                                    <option
                                        value="{{ data_get($myTipcategory, 'id') }}"
                                        @selected((string) data_get($myTipsFilters, 'category', '') === (string) data_get($myTipcategory, 'id'))
                                    >
                                        {{ data_get($myTipcategory, 'name') }}
                                    </option>
                                @endforeach
                        </select>
                    </label>
                    <label class="mytips-field mytips-field--query">
                        <span class="mytips-label">검색어(제목)</span>
                        <input
                            class="mytips-control"
                            type="text"
                            name="query"
                            value="{{ (string) data_get($myTipsFilters, 'query', '') }}"
                            placeholder="제목 검색"
                        />
                    </label>
                    <label class="mytips-field">
                        <span class="mytips-label">정렬</span>
                        <select class="mytips-control" name="sort">
                            @foreach (data_get($myTipsFilters, 'sort_options', []) as $sortValue => $sortLabel)
                                <option value="{{ $sortValue }}" @selected(data_get($myTipsFilters, 'sort') === $sortValue)>{{ $sortLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <input type="hidden" name="per_page" value="{{ data_get($myTipsFilters, 'per_page', 20) }}" />
                @if (filled(data_get($myTipsFilters, 'status')))
                    <input type="hidden" name="status" value="{{ data_get($myTipsFilters, 'status') }}" />
                @endif
                @if (filled(data_get($myTipsFilters, 'visibility')))
                    <input type="hidden" name="visibility" value="{{ data_get($myTipsFilters, 'visibility') }}" />
                @endif
                <div data-mytips-tag-inputs>
                    @foreach (data_get($myTipsFilters, 'selected_tag_ids', []) as $selectedTagId)
                        <input type="hidden" name="tags[]" value="{{ $selectedTagId }}" />
                    @endforeach
                </div>

                <div class="mytips-tags-block">
                    <div class="mytips-label">검색 태그</div>
                    <p class="mytips-tags-note">내 글에 등록된 태그를 클릭해 선택하세요. 선택한 태그를 모두 포함한 게시글만 검색됩니다.</p>
                    <div class="mytips-tag-pool" data-mytips-tag-pool>
                        @forelse ($myTipTags as $tag)
                            <button
                                class="mytips-tag-option {{ array_key_exists((int) data_get($tag, 'id', 0), data_get($myTipsFilters, 'selected_tag_ids_map', [])) ? 'is-selected' : '' }}"
                                type="button"
                                data-mytips-tag-option
                                data-tag-id="{{ data_get($tag, 'id', 0) }}"
                                data-tag-name="{{ data_get($tag, 'name', '') }}"
                                aria-pressed="{{ array_key_exists((int) data_get($tag, 'id', 0), data_get($myTipsFilters, 'selected_tag_ids_map', [])) ? 'true' : 'false' }}"
                            >
                                <span class="mytips-tag-option-name">{{ data_get($tag, 'label', '#' . data_get($tag, 'name', '')) }}</span>
                                <span class="mytips-tag-option-count">{{ data_get($tag, 'tips_count_text', '0') }}</span>
                            </button>
                        @empty
                            <p class="mytips-tags-empty">내 글에 등록된 태그가 없습니다.</p>
                        @endforelse
                    </div>
                    <div class="mytips-selected-tags is-empty" data-mytips-selected-tags>선택된 태그 없음</div>
                </div>

                <div class="mytips-search-submit-row">
                    <button class="mytips-button mytips-button--accent mytips-button--search" type="submit">검색</button>
                </div>
            </form>
        </div>

            <div class="mytips-panel">
                <div class="mytips-toolbar">
                    <div class="mytips-panel-title">목록</div>
                    <form class="mytips-toolbar-controls" method="GET" action="{{ route('mypage', ['tab' => 'mytips']) }}">
                    @if (filled(data_get($myTipsFilters, 'category')))
                        <input type="hidden" name="category_id" value="{{ data_get($myTipsFilters, 'category') }}" />
                    @endif
                    @if (filled(data_get($myTipsFilters, 'query')))
                        <input type="hidden" name="query" value="{{ data_get($myTipsFilters, 'query') }}" />
                    @endif
                    <input type="hidden" name="sort" value="{{ data_get($myTipsFilters, 'sort', 'latest') }}" />
                    @if (filled(data_get($myTipsFilters, 'status')))
                        <input type="hidden" name="status" value="{{ data_get($myTipsFilters, 'status') }}" />
                    @endif
                    @if (filled(data_get($myTipsFilters, 'visibility')))
                        <input type="hidden" name="visibility" value="{{ data_get($myTipsFilters, 'visibility') }}" />
                    @endif
                    @foreach (data_get($myTipsFilters, 'selected_tag_ids', []) as $selectedTagId)
                        <input type="hidden" name="tags[]" value="{{ $selectedTagId }}" />
                    @endforeach
                    <span class="mytips-toolbar-label">표시설정</span>
                    <span class="mytips-toolbar-label">페이지당</span>
                    <input class="mytips-control mytips-per-page" type="number" name="per_page" min="1" max="100" step="1" value="{{ data_get($myTipsFilters, 'per_page', 20) }}" />
                    <button class="mytips-button mytips-button--ghost" type="submit">적용</button>
                </form>
            </div>

            <div class="mytips-table-wrap">
                <table class="mytips-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>썸네일</th>
                            <th>카테고리/제목</th>
                            <th class="mytips-metric-head">조회수</th>
                            <th class="mytips-metric-head">좋아요</th>
                            <th class="mytips-metric-head">북마크</th>
                            <th>노출</th>
                            <th>상태</th>
                            <th>날짜</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tips as $tip)
                            <x-tip.owner-row :item="$tip" />
                        @empty
                            <tr>
                                <td class="mytips-empty" colspan="9">내가 작성한 팁이 없습니다.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mytips-footer">
            <div class="mytips-count">
                {{ $tips->firstItem() ?? 0 }}-{{ $tips->lastItem() ?? 0 }}
                / 총 {{ $tips->total() }}개
            </div>
            @if ($tips->hasPages())
                <div class="app-pagination">
                    {{ $tips->onEachSide(1)->links('vendor.pagination.app') }}
                </div>
            @endif
        </div>
    </div>
</section>

@once
    <script>
        (function () {
            var tagPools = document.querySelectorAll('[data-mytips-tag-pool]');

            tagPools.forEach(function (pool) {
                var form = pool.closest('form');
                var selectedTagsView = pool.parentElement.querySelector('[data-mytips-selected-tags]');
                var tagInputsHost = form ? form.querySelector('[data-mytips-tag-inputs]') : null;
                var tagButtons = Array.from(pool.querySelectorAll('[data-mytips-tag-option]'));

                if (!selectedTagsView || tagButtons.length === 0) {
                    return;
                }

                var renderSelectedTags = function () {
                    var selectedButtons = tagButtons.filter(function (button) {
                        return button.classList.contains('is-selected');
                    });

                    selectedTagsView.innerHTML = '';
                    if (tagInputsHost) {
                        tagInputsHost.innerHTML = '';
                    }

                    if (selectedButtons.length === 0) {
                        selectedTagsView.classList.add('is-empty');
                        selectedTagsView.textContent = '선택된 태그 없음';
                        return;
                    }

                    selectedTagsView.classList.remove('is-empty');

                    selectedButtons.forEach(function (button) {
                        var selectedChip = document.createElement('span');

                        selectedChip.className = 'mytips-selected-tag';
                        selectedChip.textContent = '#' + button.dataset.tagName;
                        selectedTagsView.appendChild(selectedChip);

                        if (tagInputsHost && button.dataset.tagId) {
                            var hiddenInput = document.createElement('input');

                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'tags[]';
                            hiddenInput.value = button.dataset.tagId;
                            tagInputsHost.appendChild(hiddenInput);
                        }
                    });
                };

                tagButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        var isSelected = button.classList.toggle('is-selected');

                        button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                        renderSelectedTags();
                    });
                });

                renderSelectedTags();
            });

            var tipRows = Array.from(document.querySelectorAll('[data-mytips-row-link]'));

            tipRows.forEach(function (row) {
                var href = row.dataset.href;

                if (!href) {
                    return;
                }

                row.addEventListener('click', function (event) {
                    if (event.target.closest('a, button, input, select, textarea, form, label')) {
                        return;
                    }

                    window.location.href = href;
                });

                row.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    event.preventDefault();
                    window.location.href = href;
                });
            });
        }());
    </script>
@endonce
