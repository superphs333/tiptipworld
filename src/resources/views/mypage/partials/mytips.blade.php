@php
    $mockTips = [
        [
            'id' => 214,
            'category' => '유지보수',
            'title' => '카테고리 유지보수 시 체크해야 할 5가지',
            'tags' => ['편집', '삭제'],
            'author' => 'jojjoo33 hi',
            'visibility' => '공개',
            'status' => '임시저장',
            'date' => '26-02-08 PM 12:54',
        ],
        [
            'id' => 215,
            'category' => '태그',
            'title' => '태그를 추가할 때 중복을 피하는 방법',
            'tags' => ['추천', '초보'],
            'author' => 'jojjoo33 hi',
            'visibility' => '공개',
            'status' => '게시',
            'date' => '26-02-09 AM 03:15',
        ],
        [
            'id' => 216,
            'category' => '미분류',
            'title' => '임시 작성 글 정리 루틴',
            'tags' => ['편집', '삭제'],
            'author' => 'jojjoo33 hi',
            'visibility' => '비공개',
            'status' => '임시저장',
            'date' => '26-02-09 AM 07:47',
        ],
        [
            'id' => 218,
            'category' => '추가',
            'title' => '[SEED-C20] 카테고리20 테스트 팁 001',
            'tags' => ['추가', '카테고리20'],
            'author' => 'hihi3333',
            'visibility' => '공개',
            'status' => '게시',
            'date' => '26-01-09 AM 12:46',
        ],
        [
            'id' => 219,
            'category' => '추가',
            'title' => '[SEED-C20] 카테고리20 테스트 팁 002',
            'tags' => ['추가', '카테고리20'],
            'author' => 'hihi3333',
            'visibility' => '공개',
            'status' => '게시',
            'date' => '25-08-27 PM 01:08',
        ],
        [
            'id' => 220,
            'category' => '추가',
            'title' => '[SEED-C20] 카테고리20 테스트 팁 003',
            'tags' => ['추가', '카테고리20'],
            'author' => 'hihi3333',
            'visibility' => '일부공개',
            'status' => '보관',
            'date' => '26-02-06 PM 10:46',
        ],
        [
            'id' => 221,
            'category' => '추가',
            'title' => '[SEED-C20] 카테고리20 테스트 팁 004',
            'tags' => ['추가', '카테고리20'],
            'author' => 'hihi3333',
            'visibility' => '공개',
            'status' => '게시',
            'date' => '25-11-19 AM 05:18',
        ],
        [
            'id' => 222,
            'category' => '추가',
            'title' => '[SEED-C20] 카테고리20 테스트 팁 005',
            'tags' => ['추가', '카테고리20'],
            'author' => 'hihi3333',
            'visibility' => '공개',
            'status' => '삭제',
            'date' => '25-10-25 PM 03:07',
        ],
    ];
    $pageTokens = ['First', 'Prev', 1, 2, 3, 4, 5, '...', 10, 11, 'Next', 'Last'];
@endphp

<section class="mytips-showcase">
    <div class="mytips-shell">
        <div class="mytips-panel">
            <div class="mytips-search-body">
                <div class="mytips-search-grid">
                    <label class="mytips-field">
                        <span class="mytips-label">카테고리</span>
                        <select class="mytips-control">
                            <option selected>전체 카테고리</option>
                                @foreach ($myTipcategories as $myTipcategory)
                                    <option value="{{ data_get($myTipcategory, 'id') }}">
                                        {{ data_get($myTipcategory, 'name') }}
                                    </option>
                                @endforeach
                        </select>
                    </label>
                    <label class="mytips-field mytips-field--query">
                        <span class="mytips-label">검색어(제목/작성자)</span>
                        <input class="mytips-control" type="text" value="" placeholder="제목/작성자 통합 검색" />
                    </label>
                    <label class="mytips-field">
                        <span class="mytips-label">정렬</span>
                        <select class="mytips-control">
                            <option selected>최신순</option>
                            <option>조회순</option>
                            <option>좋아요순</option>
                            <option>북마크순</option>
                        </select>
                    </label>
                </div>

                <div class="mytips-tags-block">
                    <div class="mytips-label">검색 태그</div>
                    <p class="mytips-tags-note">내 글에 등록된 태그를 클릭해 선택하세요. 선택한 태그를 모두 포함한 게시글만 검색됩니다.</p>
                    <div class="mytips-tag-pool" data-mytips-tag-pool>
                        @forelse ($myTipTags as $tag)
                            <button
                                class="mytips-tag-option"
                                type="button"
                                data-mytips-tag-option
                                data-tag-name="{{ data_get($tag, 'name', '') }}"
                                aria-pressed="false"
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
                    <button class="mytips-button mytips-button--accent mytips-button--search" type="button">검색</button>
                </div>
            </div>
        </div>

        <div class="mytips-panel">
            <div class="mytips-toolbar">
                <div class="mytips-panel-title">목록</div>
                <div class="mytips-toolbar-controls">
                    <span class="mytips-toolbar-label">표시설정</span>
                    <span class="mytips-toolbar-label">페이지당</span>
                    <input class="mytips-control mytips-per-page" type="text" value="20" />
                    <button class="mytips-button mytips-button--ghost" type="button">적용</button>
                </div>
            </div>

            <div class="mytips-table-wrap">
                <table class="mytips-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>썸네일</th>
                            <th>카테고리/제목</th>
                            <th>작성자</th>
                            <th>노출</th>
                            <th>상태</th>
                            <th>날짜</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mockTips as $tip)
                            @php
                                $visibilityClass = match ($tip['visibility']) {
                                    '비공개' => 'mytips-badge mytips-badge--gray',
                                    '일부공개' => 'mytips-badge mytips-badge--rose',
                                    default => 'mytips-badge mytips-badge--mint',
                                };
                                $statusClass = match ($tip['status']) {
                                    '게시' => 'mytips-badge mytips-badge--mint',
                                    '삭제' => 'mytips-badge mytips-badge--rose',
                                    default => 'mytips-badge mytips-badge--gray',
                                };
                            @endphp
                            <tr>
                                <td class="mytips-id">{{ $tip['id'] }}</td>
                                <td>
                                    <div class="mytips-thumb">TT</div>
                                </td>
                                <td>
                                    <div class="mytips-title-stack">
                                        <div class="mytips-chip">{{ $tip['category'] }}</div>
                                        <div class="mytips-title">"{{ $tip['title'] }}"</div>
                                        <div class="mytips-tag-row">
                                            @foreach ($tip['tags'] as $tag)
                                                <span class="mytips-chip {{ $tag === '삭제' ? 'mytips-chip--alert' : '' }}">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                                <td class="mytips-author">{{ $tip['author'] }}</td>
                                <td><span class="{{ $visibilityClass }}">{{ $tip['visibility'] }}</span></td>
                                <td><span class="{{ $statusClass }}">{{ $tip['status'] }}</span></td>
                                <td class="mytips-date">{{ $tip['date'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mytips-footer">
            <div class="mytips-count">1-20 / 총 214개</div>
            <div class="mytips-pagination">
                @foreach ($pageTokens as $token)
                    <button
                        class="mytips-page {{ $token === 1 ? 'is-active' : '' }} {{ in_array($token, ['First', 'Prev'], true) ? 'is-muted' : '' }}"
                        type="button"
                    >
                        {{ $token }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</section>

@once
    <script>
        (function () {
            var tagPools = document.querySelectorAll('[data-mytips-tag-pool]');

            if (tagPools.length === 0) {
                return;
            }

            tagPools.forEach(function (pool) {
                var selectedTagsView = pool.parentElement.querySelector('[data-mytips-selected-tags]');
                var tagButtons = Array.from(pool.querySelectorAll('[data-mytips-tag-option]'));

                if (!selectedTagsView || tagButtons.length === 0) {
                    return;
                }

                var renderSelectedTags = function () {
                    var selectedButtons = tagButtons.filter(function (button) {
                        return button.classList.contains('is-selected');
                    });

                    selectedTagsView.innerHTML = '';

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
        }());
    </script>
@endonce
