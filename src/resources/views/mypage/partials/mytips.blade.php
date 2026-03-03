@php
    $selectedTagIds = collect((array) request()->query('tags', []))
        ->map(static fn ($tagId) => (int) $tagId)
        ->filter(static fn ($tagId) => $tagId > 0)
        ->unique()
        ->values()
        ->all();
    $sortOptions = [
        'latest' => '최신순',
        'popular' => '조회순',
        'likes' => '좋아요순',
        'bookmarks' => '북마크순',
    ];
    $currentSort = (string) request()->query('sort', 'latest');
    $currentPerPage = method_exists($tips, 'perPage')
        ? (int) $tips->perPage()
        : max(1, min((int) request()->query('per_page', 20), 100));
@endphp

<section class="mytips-showcase">
    <div class="mytips-shell">
        <div class="mytips-panel">
            <form class="mytips-search-body" method="GET" action="{{ route('mypage', ['tab' => 'mytips']) }}">
                <div class="mytips-search-grid">
                    <label class="mytips-field">
                        <span class="mytips-label">카테고리</span>
                        <select class="mytips-control" name="category_id">
                            <option value="" @selected(blank(request()->query('category_id')))>전체 카테고리</option>
                                @foreach ($myTipcategories as $myTipcategory)
                                    <option
                                        value="{{ data_get($myTipcategory, 'id') }}"
                                        @selected((string) request()->query('category_id') === (string) data_get($myTipcategory, 'id'))
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
                            value="{{ (string) request()->query('query', '') }}"
                            placeholder="제목 검색"
                        />
                    </label>
                    <label class="mytips-field">
                        <span class="mytips-label">정렬</span>
                        <select class="mytips-control" name="sort">
                            @foreach ($sortOptions as $sortValue => $sortLabel)
                                <option value="{{ $sortValue }}" @selected($currentSort === $sortValue)>{{ $sortLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <input type="hidden" name="per_page" value="{{ $currentPerPage }}" />
                @if (filled(request()->query('status')))
                    <input type="hidden" name="status" value="{{ request()->query('status') }}" />
                @endif
                @if (filled(request()->query('visibility')))
                    <input type="hidden" name="visibility" value="{{ request()->query('visibility') }}" />
                @endif
                <div data-mytips-tag-inputs>
                    @foreach ($selectedTagIds as $selectedTagId)
                        <input type="hidden" name="tags[]" value="{{ $selectedTagId }}" />
                    @endforeach
                </div>

                <div class="mytips-tags-block">
                    <div class="mytips-label">검색 태그</div>
                    <p class="mytips-tags-note">내 글에 등록된 태그를 클릭해 선택하세요. 선택한 태그를 모두 포함한 게시글만 검색됩니다.</p>
                    <div class="mytips-tag-pool" data-mytips-tag-pool>
                        @php
                            $selectedTagIdMap = array_flip($selectedTagIds);
                        @endphp
                        @forelse ($myTipTags as $tag)
                            @php
                                $tagId = (int) data_get($tag, 'id', 0);
                                $isSelectedTag = array_key_exists($tagId, $selectedTagIdMap);
                            @endphp
                            <button
                                class="mytips-tag-option {{ $isSelectedTag ? 'is-selected' : '' }}"
                                type="button"
                                data-mytips-tag-option
                                data-tag-id="{{ $tagId }}"
                                data-tag-name="{{ data_get($tag, 'name', '') }}"
                                aria-pressed="{{ $isSelectedTag ? 'true' : 'false' }}"
                            >
                                <span class="mytips-tag-option-name">#{{ data_get($tag, 'name', '') }}</span>
                                <span class="mytips-tag-option-count">{{ data_get($tag, 'tips_count', 0) }}</span>
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
                    @if (filled(request()->query('category_id')))
                        <input type="hidden" name="category_id" value="{{ request()->query('category_id') }}" />
                    @endif
                    @if (filled(request()->query('query')))
                        <input type="hidden" name="query" value="{{ request()->query('query') }}" />
                    @endif
                    <input type="hidden" name="sort" value="{{ $currentSort }}" />
                    @if (filled(request()->query('status')))
                        <input type="hidden" name="status" value="{{ request()->query('status') }}" />
                    @endif
                    @if (filled(request()->query('visibility')))
                        <input type="hidden" name="visibility" value="{{ request()->query('visibility') }}" />
                    @endif
                    @foreach ($selectedTagIds as $selectedTagId)
                        <input type="hidden" name="tags[]" value="{{ $selectedTagId }}" />
                    @endforeach
                    <span class="mytips-toolbar-label">표시설정</span>
                    <span class="mytips-toolbar-label">페이지당</span>
                    <input class="mytips-control mytips-per-page" type="number" name="per_page" min="1" max="100" step="1" value="{{ $currentPerPage }}" />
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
                            @php
                                $tipId = (int) data_get($tip, 'id', 0);
                                $tipThumbnail = (string) data_get($tip, 'thumbnail_url', '');
                                $tipCategory = (string) data_get($tip, 'category', data_get($tip, 'category_name', '미분류'));
                                $tipTitle = (string) data_get($tip, 'title', '');
                                $tipTags = collect(data_get($tip, 'tags', []))
                                    ->map(static fn ($tag) => is_string($tag) ? $tag : (string) data_get($tag, 'name', ''))
                                    ->filter()
                                    ->values();
                                $tipViews = (int) data_get($tip, 'view_count', 0);
                                $tipLikes = (int) data_get($tip, 'like_count', 0);
                                $tipBookmarks = (int) data_get($tip, 'bookmark_count', 0);
                                $tipVisibility = (string) data_get($tip, 'visibility', '공개');
                                $tipStatus = (string) data_get($tip, 'status', '-');
                                $tipDate = (string) data_get($tip, 'date', '-');
                                $detailUrl = $tipId > 0 ? route('tip.show', ['tip_id' => $tipId]) : null;

                                $visibilityClass = match ($tipVisibility) {
                                    '비공개' => 'mytips-badge mytips-badge--gray',
                                    '일부공개' => 'mytips-badge mytips-badge--rose',
                                    default => 'mytips-badge mytips-badge--mint',
                                };

                                $statusClass = match ($tipStatus) {
                                    '게시' => 'mytips-badge mytips-badge--mint',
                                    '삭제' => 'mytips-badge mytips-badge--rose',
                                    default => 'mytips-badge mytips-badge--gray',
                                };
                            @endphp
                            <tr
                                @class(['mytips-row-link' => $detailUrl])
                                @if ($detailUrl)
                                    data-mytips-row-link
                                    data-href="{{ $detailUrl }}"
                                    tabindex="0"
                                    role="link"
                                    aria-label="팁 상세 보기: {{ $tipTitle }}"
                                @endif
                            >
                                <td class="mytips-id">{{ $tipId }}</td>
                                <td>
                                    <div class="mytips-thumb">
                                        @if ($tipThumbnail !== '')
                                            <img src="{{ $tipThumbnail }}" alt="" />
                                        @else
                                            <span>TT</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="mytips-title-stack">
                                        <div class="mytips-chip">{{ $tipCategory }}</div>
                                        <div class="mytips-title">"{{ $tipTitle }}"</div>

                                        @if ($tipTags->isNotEmpty())
                                            <div class="mytips-tag-row">
                                                @foreach ($tipTags as $tag)
                                                    <span class="mytips-chip {{ $tag === '삭제' ? 'mytips-chip--alert' : '' }}">#{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="mytips-metric">{{ number_format($tipViews) }}</td>
                                <td class="mytips-metric">{{ number_format($tipLikes) }}</td>
                                <td class="mytips-metric">{{ number_format($tipBookmarks) }}</td>
                                <td><span class="{{ $visibilityClass }}">{{ $tipVisibility }}</span></td>
                                <td><span class="{{ $statusClass }}">{{ $tipStatus }}</span></td>
                                <td class="mytips-date">{{ $tipDate }}</td>
                            </tr>
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
