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
                        <select class="category-panel__select-native" name="status" id="status">
                            <option value="all">전체</option>
                            <option value="active">활성화</option>
                            <option value="inactive">비활성화</option>
                        </select>
                        <button class="category-panel__select-trigger" type="button" aria-haspopup="listbox" aria-expanded="false">
                            <span class="category-panel__select-label">전체</span>
                            <svg class="category-panel__select-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <ul class="category-panel__select-menu" role="listbox" tabindex="-1">
                            <li class="category-panel__select-option" role="option" data-value="all">전체</li>
                            <li class="category-panel__select-option" role="option" data-value="active">활성화</li>
                            <li class="category-panel__select-option" role="option" data-value="inactive">비활성화</li>
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

<script>
    document.querySelectorAll("[data-select]").forEach(function (wrap) {
        var select = wrap.querySelector("select");
        var trigger = wrap.querySelector(".category-panel__select-trigger");
        var label = wrap.querySelector(".category-panel__select-label");
        var menu = wrap.querySelector(".category-panel__select-menu");
        var options = Array.prototype.slice.call(
            wrap.querySelectorAll(".category-panel__select-option")
        );

        if (!select || !trigger || !label || !menu) {
            return;
        }

        var sync = function () {
            var selected = select.options[select.selectedIndex];
            label.textContent = selected ? selected.textContent : "";
            options.forEach(function (option) {
                var isActive = option.getAttribute("data-value") === select.value;
                option.classList.toggle("is-active", isActive);
                option.setAttribute("aria-selected", isActive ? "true" : "false");
            });
        };

        var closeMenu = function () {
            wrap.classList.remove("is-open");
            trigger.setAttribute("aria-expanded", "false");
        };

        sync();

        trigger.addEventListener("click", function () {
            var willOpen = !wrap.classList.contains("is-open");
            wrap.classList.toggle("is-open", willOpen);
            trigger.setAttribute("aria-expanded", willOpen ? "true" : "false");
            if (!willOpen) {
                return;
            }
            menu.focus();
        });

        options.forEach(function (option) {
            option.addEventListener("click", function () {
                var value = option.getAttribute("data-value");
                if (value !== null) {
                    select.value = value;
                    sync();
                    select.dispatchEvent(new Event("change", { bubbles: true }));
                }
                closeMenu();
            });
        });

        document.addEventListener("click", function (event) {
            if (!wrap.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (!wrap.classList.contains("is-open")) {
                return;
            }
            if (event.key === "Escape") {
                closeMenu();
                trigger.focus();
            }
        });
    });
</script>
