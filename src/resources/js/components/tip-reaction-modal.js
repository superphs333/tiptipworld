const LIKE_TAB = 'likes';
const BOOKMARK_TAB = 'bookmarks';
const OPEN_CLASS_NAME = 'tip-wireframe-reaction-modal-open';

const normalizeTab = (value) => (value === BOOKMARK_TAB ? BOOKMARK_TAB : LIKE_TAB);

const toSearchKey = (value) => String(value ?? '').trim().toLowerCase();

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-tip-wireframe]');
    if (!root) {
        return;
    }

    const modal = root.querySelector('[data-reaction-modal]');
    if (!modal) {
        return;
    }

    const searchInput = modal.querySelector('[data-reaction-modal-search]');
    const emptyText = modal.querySelector('[data-reaction-modal-empty]');
    const tabButtons = Array.from(modal.querySelectorAll('[data-reaction-modal-tab]'));

    const listByTab = {
        [LIKE_TAB]: modal.querySelector('[data-reaction-modal-list="likes"]'),
        [BOOKMARK_TAB]: modal.querySelector('[data-reaction-modal-list="bookmarks"]'),
    };

    const state = {
        activeTab: LIKE_TAB,
        lastFocusedElement: null,
    };

    const setModalVisible = (visible) => {
        modal.hidden = !visible;
        modal.setAttribute('aria-hidden', visible ? 'false' : 'true');
        document.body.classList.toggle(OPEN_CLASS_NAME, visible);
    };

    const syncTabs = () => {
        tabButtons.forEach((button) => {
            const isActive = normalizeTab(button.getAttribute('data-reaction-modal-tab')) === state.activeTab;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    };

    const syncLists = () => {
        Object.entries(listByTab).forEach(([tab, list]) => {
            if (!list) {
                return;
            }

            list.hidden = tab !== state.activeTab;
        });
    };

    const renderEmptyMessage = (message, visible) => {
        if (!emptyText) {
            return;
        }

        emptyText.textContent = message;
        emptyText.hidden = !visible;
    };

    const filterActiveList = () => {
        const activeList = listByTab[state.activeTab];
        if (!activeList) {
            renderEmptyMessage('표시할 사용자가 없습니다.', true);
            return;
        }

        const keyword = toSearchKey(searchInput?.value ?? '');
        const items = Array.from(activeList.querySelectorAll('[data-reaction-item]'));
        let visibleCount = 0;

        items.forEach((item) => {
            const name = toSearchKey(item.getAttribute('data-reaction-name') ?? '');
            const isMatched = keyword === '' || name.includes(keyword);
            item.hidden = !isMatched;
            if (isMatched) {
                visibleCount += 1;
            }
        });

        if (items.length === 0) {
            renderEmptyMessage('표시할 사용자가 없습니다.', true);
            return;
        }

        if (visibleCount === 0) {
            renderEmptyMessage(keyword === '' ? '표시할 사용자가 없습니다.' : '검색 결과가 없습니다.', true);
            return;
        }

        renderEmptyMessage('', false);
    };

    const openModal = (tab, triggerElement) => {
        state.activeTab = normalizeTab(tab);
        state.lastFocusedElement = triggerElement ?? document.activeElement;

        if (searchInput) {
            searchInput.value = '';
        }

        syncTabs();
        syncLists();
        setModalVisible(true);
        filterActiveList();

        window.requestAnimationFrame(() => {
            searchInput?.focus();
        });
    };

    const closeModal = () => {
        if (modal.hidden) {
            return;
        }

        setModalVisible(false);

        if (state.lastFocusedElement && typeof state.lastFocusedElement.focus === 'function') {
            state.lastFocusedElement.focus();
        }
    };

    root.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const openButton = target.closest('[data-reaction-modal-open]');
        if (openButton && root.contains(openButton)) {
            event.preventDefault();
            if (openButton instanceof HTMLButtonElement && openButton.disabled) {
                return;
            }
            openModal(openButton.getAttribute('data-reaction-modal-open'), openButton);
            return;
        }

        const closeButton = target.closest('[data-reaction-modal-close]');
        if (closeButton && modal.contains(closeButton)) {
            event.preventDefault();
            closeModal();
            return;
        }

        const tabButton = target.closest('[data-reaction-modal-tab]');
        if (tabButton && modal.contains(tabButton)) {
            event.preventDefault();
            state.activeTab = normalizeTab(tabButton.getAttribute('data-reaction-modal-tab'));

            if (searchInput) {
                searchInput.value = '';
            }

            syncTabs();
            syncLists();
            filterActiveList();
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            filterActiveList();
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || modal.hidden) {
            return;
        }

        closeModal();
    });
});
