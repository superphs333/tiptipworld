<section class="bookmark-archive" data-bookmark-archive>
    <header class="bookmark-archive__profile">
        <div class="bookmark-archive__identity">
            <div class="bookmark-archive__avatar" aria-hidden="true">
                <span>B</span>
            </div>

            <div class="bookmark-archive__identity-body">
                <h2 class="bookmark-archive__name">보관한 게시글</h2>
                <p class="bookmark-archive__summary">
                    북마크 {{ $bookmarkCountText }}개 · 좋아요 {{ $likeCountText }}개 · 총 {{ $totalCountText }}개
                </p>
            </div>
        </div>

        <div class="bookmark-archive__stats">
            <div class="bookmark-archive__stat-box">
                <strong>{{ $bookmarkCountText }}</strong>
                <span>BOOKMARKS</span>
            </div>
            <div class="bookmark-archive__stat-box">
                <strong>{{ $likeCountText }}</strong>
                <span>LIKES</span>
            </div>
            <div class="bookmark-archive__pill">아카이브</div>
        </div>
    </header>

    <div class="bookmark-archive__tabs" role="tablist" aria-label="보관함 탭">
        @foreach ($tabSets as $tabKey => $tab)
            <button
                type="button"
                class="bookmark-archive__tab {{ $loop->first ? 'is-active' : '' }}"
                data-bookmark-tab-trigger="{{ $tabKey }}"
                role="tab"
                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
            >
                <span>{{ $tab['label'] }}</span>
                <em>{{ data_get($tab, 'meta.count_text', '0') }}</em>
            </button>
        @endforeach
    </div>

    @foreach ($tabSets as $tabKey => $tab)
        <section
            class="bookmark-archive__panel {{ $loop->first ? 'is-active' : '' }}"
            data-bookmark-tab-panel="{{ $tabKey }}"
            @if (! $loop->first) hidden @endif
        >
            <section class="bookmark-archive__insight" aria-label="{{ $tab['label'] }} 인사이트">
                

                <div class="bookmark-archive__insight-grid">
                    <article class="bookmark-archive__insight-card">
                        <h4>카테고리</h4>
                        <div class="bookmark-archive__chips">
                            @forelse ($tab['meta']['categories'] as $item)
                                <button
                                    type="button"
                                    class="bookmark-archive__chip bookmark-archive__chip--category bookmark-archive__filter-btn"
                                    data-bookmark-filter-trigger="category"
                                    data-filter-value="{{ (string) $item['id'] }}"
                                    aria-pressed="false"
                                >
                                    {{ $item['name'] }}
                                    <em>{{ data_get($item, 'count_text', '0') }}</em>
                                </button>
                            @empty
                                <span class="bookmark-archive__empty-chip">카테고리 없음</span>
                            @endforelse
                        </div>
                    </article>

                    <article class="bookmark-archive__insight-card">
                        <h4>태그</h4>
                        <div class="bookmark-archive__chips">
                            @forelse ($tab['meta']['tags'] as $item)
                                <button
                                    type="button"
                                    class="bookmark-archive__chip bookmark-archive__chip--tag bookmark-archive__filter-btn"
                                    data-bookmark-filter-trigger="tag"
                                    data-filter-value="{{ (string) $item['id'] }}"
                                    aria-pressed="false"
                                >
                                    {{ data_get($item, 'label', '#' . data_get($item, 'name', '태그')) }}
                                    <em>{{ data_get($item, 'count_text', '0') }}</em>
                                </button>
                            @empty
                                <span class="bookmark-archive__empty-chip">태그 없음</span>
                            @endforelse
                        </div>
                    </article>
                </div>
            </section>

            <section class="bookmark-archive__feed" aria-label="{{ $tab['label'] }} 피드">
                <header class="bookmark-archive__feed-head">
                    <div class="bookmark-archive__feed-heading">
                        <h3 class="bookmark-archive__section-title">Feed</h3>
                        <p>
                            <span data-bookmark-visible-count>{{ data_get($tab, 'meta.count_text', '0') }}</span>개의 게시글
                        </p>
                    </div>
                </header>

                @if ($tab['meta']['count'] > 0)
                    <div class="bookmark-archive__grid" data-bookmark-grid>
                        @foreach ($tab['items'] as $item)
                            <x-tip.archive-card :item="$item" />
                        @endforeach
                    </div>
                    <div class="bookmark-archive__empty" data-bookmark-filter-empty hidden>
                        선택한 조건에 맞는 게시글이 없습니다.
                    </div>
                @else
                    <div class="bookmark-archive__empty">
                        {{ $tab['label'] }}한 게시글이 없습니다.
                    </div>
                @endif
            </section>
        </section>
    @endforeach
</section>

@once
    <script>
        (function () {
            var archives = document.querySelectorAll('[data-bookmark-archive]');

            archives.forEach(function (archive) {
                var triggers = Array.from(archive.querySelectorAll('[data-bookmark-tab-trigger]'));
                var panels = Array.from(archive.querySelectorAll('[data-bookmark-tab-panel]'));

                if (triggers.length === 0 || panels.length === 0) {
                    return;
                }

                var normalizeValue = function (value) {
                    return (value || '').trim();
                };

                var parseSelectedValues = function (value) {
                    return normalizeValue(value)
                        .split('|')
                        .map(normalizeValue)
                        .filter(Boolean);
                };

                var serializeSelectedValues = function (values) {
                    return values
                        .map(normalizeValue)
                        .filter(Boolean)
                        .join('|');
                };

                var applyPanelFilters = function (panel) {
                    var selectedCategories = parseSelectedValues(panel.getAttribute('data-selected-category'));
                    var selectedTags = parseSelectedValues(panel.getAttribute('data-selected-tag'));
                    var cards = Array.from(panel.querySelectorAll('[data-bookmark-item]'));
                    var visibleCountNode = panel.querySelector('[data-bookmark-visible-count]');
                    var emptyNode = panel.querySelector('[data-bookmark-filter-empty]');
                    var grid = panel.querySelector('[data-bookmark-grid]');
                    var visibleCount = 0;

                    cards.forEach(function (card) {
                        var cardCategory = normalizeValue(card.getAttribute('data-category'));
                        var cardTags = normalizeValue(card.getAttribute('data-tags'))
                            .split('|')
                            .map(normalizeValue)
                            .filter(Boolean);
                        var matchesCategory = selectedCategories.length === 0
                            || selectedCategories.indexOf(cardCategory) > -1;
                        var matchesTag = selectedTags.length === 0
                            || selectedTags.some(function (tag) {
                                return cardTags.indexOf(tag) > -1;
                            });
                        var isVisible = matchesCategory && matchesTag;

                        card.hidden = !isVisible;

                        if (isVisible) {
                            visibleCount += 1;
                        }
                    });

                    if (visibleCountNode) {
                        visibleCountNode.textContent = visibleCount.toLocaleString();
                    }

                    if (grid) {
                        grid.hidden = visibleCount === 0;
                    }

                    if (emptyNode) {
                        emptyNode.hidden = visibleCount > 0;
                    }
                };

                var updateFilterButtons = function (panel) {
                    var selectedCategories = parseSelectedValues(panel.getAttribute('data-selected-category'));
                    var selectedTags = parseSelectedValues(panel.getAttribute('data-selected-tag'));
                    var filterButtons = Array.from(panel.querySelectorAll('[data-bookmark-filter-trigger]'));

                    filterButtons.forEach(function (button) {
                        var filterType = button.getAttribute('data-bookmark-filter-trigger');
                        var filterValue = normalizeValue(button.getAttribute('data-filter-value'));
                        var isActive = false;

                        if (filterType === 'category') {
                            isActive = selectedCategories.indexOf(filterValue) > -1;
                        }

                        if (filterType === 'tag') {
                            isActive = selectedTags.indexOf(filterValue) > -1;
                        }

                        button.classList.toggle('is-active', isActive);
                        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });
                };

                var activateTab = function (tabKey) {
                    triggers.forEach(function (trigger) {
                        var isActive = trigger.getAttribute('data-bookmark-tab-trigger') === tabKey;
                        trigger.classList.toggle('is-active', isActive);
                        trigger.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });

                    panels.forEach(function (panel) {
                        var isActive = panel.getAttribute('data-bookmark-tab-panel') === tabKey;
                        panel.classList.toggle('is-active', isActive);
                        panel.hidden = !isActive;

                        if (isActive) {
                            updateFilterButtons(panel);
                            applyPanelFilters(panel);
                        }
                    });
                };

                panels.forEach(function (panel) {
                    panel.setAttribute('data-selected-category', '');
                    panel.setAttribute('data-selected-tag', '');

                    var filterButtons = Array.from(panel.querySelectorAll('[data-bookmark-filter-trigger]'));

                    filterButtons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            var filterType = button.getAttribute('data-bookmark-filter-trigger');
                            var filterValue = normalizeValue(button.getAttribute('data-filter-value'));
                            var attrName = filterType === 'category'
                                ? 'data-selected-category'
                                : 'data-selected-tag';
                            var currentValues = parseSelectedValues(panel.getAttribute(attrName));
                            var valueIndex = currentValues.indexOf(filterValue);

                            if (valueIndex > -1) {
                                currentValues.splice(valueIndex, 1);
                            } else {
                                currentValues.push(filterValue);
                            }

                            panel.setAttribute(attrName, serializeSelectedValues(currentValues));

                            updateFilterButtons(panel);
                            applyPanelFilters(panel);
                        });
                    });

                    updateFilterButtons(panel);
                    applyPanelFilters(panel);
                });

                triggers.forEach(function (trigger) {
                    trigger.addEventListener('click', function () {
                        activateTab(trigger.getAttribute('data-bookmark-tab-trigger'));
                    });
                });
            });
        }());
    </script>
@endonce
