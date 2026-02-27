import $ from 'jquery';

window.$ = window.$ || $;
window.jQuery = window.jQuery || $;

const FOLLOWERS_TAB = 'followers';
const FOLLOWING_TAB = 'following';

const normalizeTab = (value) => (value === FOLLOWING_TAB ? FOLLOWING_TAB : FOLLOWERS_TAB);
const formatCount = (value) => Number(value || 0).toLocaleString('ko-KR');
const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

$(() => {
    const $root = $('[data-tip-userfeed]').first();
    if (!$root.length) {
        return;
    }

    // 정렬: 기존 기능 유지(AJAX로 피드 영역만 교체)
    let sortXhr = null;
    $(document).on('change', '#tip-userfeed-sort', function () {
        const $select = $(this);
        const $scope = $select.closest('[data-tip-userfeed]');

        if ($scope.get(0) !== $root.get(0)) {
            return;
        }

        const $form = $select.closest('form');
        const $targetFeed = $scope.find('.tip-userfeed__feed').first();
        const sortKey = String($select.val() || 'latest');

        if (!$targetFeed.length) {
            return;
        }

        const requestUrl = new URL($form.attr('action') || window.location.href, window.location.origin);
        requestUrl.searchParams.set('sort', sortKey);

        if (sortXhr) {
            sortXhr.abort();
            sortXhr = null;
        }

        $select.prop('disabled', true);
        $scope.attr('aria-busy', 'true');

        sortXhr = $.ajax({
            url: requestUrl.toString(),
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .done((html) => {
                const $doc = $('<div>').append($.parseHTML(html, document, true));
                const $nextFeed = $doc.find('[data-tip-userfeed] .tip-userfeed__feed').first();

                if (!$nextFeed.length) {
                    window.location.assign(requestUrl.toString());
                    return;
                }

                $targetFeed.replaceWith($nextFeed);
                window.history.replaceState({}, '', requestUrl.toString());
            })
            .fail(() => {
                window.location.assign(requestUrl.toString());
            })
            .always(() => {
                $select.prop('disabled', false);
                $scope.removeAttr('aria-busy');
                sortXhr = null;
            });
    });

    // 모달: 핵심 기능만 유지
    const $modal = $root.find('[data-follow-modal]').first();
    if (!$modal.length) {
        return;
    }

    const $list = $modal.find('[data-follow-modal-list]').first();
    const $empty = $modal.find('[data-follow-modal-empty]').first();
    const $search = $modal.find('[data-follow-modal-search]').first();
    const $followersCount = $root.find('[data-followers-count]').first();
    const $followingCount = $root.find('[data-following-count]').first();

    const listUrl = String($root.attr('data-follow-list-url') || '');
    const followToggleUrlBase = String($root.attr('data-follow-toggle-url-base') || '/user/follow').replace(/\/$/, '');
    const csrfToken = String($('meta[name="csrf-token"]').attr('content') || '');

    const state = {
        activeTab: FOLLOWERS_TAB,
        query: '',
        users: [],
        isLoading: false,
        errorMessage: '',
        lastFocusedElement: null,
    };

    const normalizeUsers = (items) => (Array.isArray(items) ? items : []).map((item, index) => {
        const user = item && typeof item === 'object' ? item : {};
        const id = Number(user.id);
        const rawName = String(user.name || '').trim();

        return {
            id: Number.isInteger(id) && id > 0 ? id : index + 1,
            name: rawName !== '' ? rawName : `사용자 ${index + 1}`,
            avatar: String(user.avatar || '/images/avatar-default.svg'),
            isFollowing: Boolean(user.is_following),
            isSelf: Boolean(user.is_self),
        };
    });

    const syncTabs = () => {
        $modal.find('[data-follow-modal-tab]').each(function () {
            const $tab = $(this);
            const isActive = normalizeTab($tab.attr('data-follow-modal-tab')) === state.activeTab;

            $tab.toggleClass('is-active', isActive);
            $tab.attr('aria-selected', isActive ? 'true' : 'false');
        });
    };

    const setModalVisible = (visible) => {
        $modal.prop('hidden', !visible);
        $modal.attr('aria-hidden', visible ? 'false' : 'true');
        $('body').toggleClass('tip-userfeed-modal-open', visible);
    };

    const updateCounts = (followers, following) => {
        const followersCount = Number(followers || 0);
        const followingCount = Number(following || 0);

        $modal.find('[data-follow-modal-tab-count="followers"]').text(formatCount(followersCount));
        $modal.find('[data-follow-modal-tab-count="following"]').text(formatCount(followingCount));

        $followersCount
            .attr('data-count', String(followersCount))
            .data('count', followersCount)
            .text(formatCount(followersCount));

        $followingCount
            .attr('data-count', String(followingCount))
            .data('count', followingCount)
            .text(formatCount(followingCount));
    };

    const render = () => {
        if (state.isLoading) {
            $list.empty();
            $empty.text('목록을 불러오는 중입니다...').prop('hidden', false);
            return;
        }

        if (state.errorMessage) {
            $list.empty();
            $empty.text(state.errorMessage).prop('hidden', false);
            return;
        }

        const keyword = state.query.toLowerCase();
        const visibleUsers = keyword
            ? state.users.filter((user) => user.name.toLowerCase().includes(keyword))
            : state.users;

        if (!visibleUsers.length) {
            $list.empty();
            $empty.text(state.query ? '검색 결과가 없습니다.' : '표시할 사용자가 없습니다.').prop('hidden', false);
            return;
        }

        $empty.prop('hidden', true);

        const html = visibleUsers.map((user) => {
            const name = escapeHtml(user.name);
            const avatar = escapeHtml(user.avatar);
            const actionButton = user.isSelf
                ? ''
                : `
                    <button
                        type="button"
                        class="tip-userfeed__follow-action ${user.isFollowing ? 'is-following' : ''}"
                        data-follow-user-id="${user.id}"
                        aria-pressed="${user.isFollowing ? 'true' : 'false'}"
                    >
                        ${user.isFollowing ? '팔로잉' : '팔로우'}
                    </button>
                `;

            return `
                <li class="tip-userfeed__follow-item">
                    <div class="tip-userfeed__follow-user">
                        <img class="tip-userfeed__follow-avatar" src="${avatar}" alt="${name} 프로필" loading="lazy">
                        <div class="tip-userfeed__follow-meta">
                            <p class="tip-userfeed__follow-name">${name}</p>
                        </div>
                    </div>
                    ${actionButton}
                </li>
            `;
        }).join('');

        $list.html(html);
    };

    const fetchFollowList = () => {
        if (!listUrl) {
            state.users = [];
            state.errorMessage = '팔로우 목록 경로가 설정되지 않았습니다.';
            state.isLoading = false;
            render();
            return;
        }

        state.isLoading = true;
        state.errorMessage = '';
        render();

        $.ajax({
            url: listUrl,
            method: 'GET',
            data: {
                type: state.activeTab,
            },
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .done((res) => {
                if (!res || !res.success) {
                    state.users = [];
                    state.errorMessage = '목록을 불러오지 못했습니다.';
                    return;
                }

                state.users = normalizeUsers(res.users);
                updateCounts(res.followers_count, res.following_count);
            })
            .fail((xhr) => {
                state.users = [];
                state.errorMessage = xhr.status === 401
                    ? '로그인 후 다시 시도해주세요.'
                    : '목록을 불러오지 못했습니다. 잠시 후 다시 시도해주세요.';
            })
            .always(() => {
                state.isLoading = false;
                syncTabs();
                render();
            });
    };

    const openModal = (tab, triggerElement) => {
        state.activeTab = normalizeTab(tab);
        state.query = '';
        state.lastFocusedElement = triggerElement || document.activeElement;

        $search.val('');
        syncTabs();
        setModalVisible(true);
        fetchFollowList();

        window.requestAnimationFrame(() => {
            $search.trigger('focus');
        });
    };

    const closeModal = () => {
        if ($modal.prop('hidden')) {
            return;
        }

        setModalVisible(false);

        if (state.lastFocusedElement && typeof state.lastFocusedElement.focus === 'function') {
            state.lastFocusedElement.focus();
        }
    };

    const toggleFollowUser = (targetUserId, $button) => {
        if (!csrfToken || !Number.isInteger(targetUserId) || targetUserId <= 0) {
            return;
        }

        $button.prop('disabled', true);

        $.ajax({
            url: `${followToggleUrlBase}/${targetUserId}`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
            .done((res) => {
                if (!res || !res.success) {
                    return;
                }

                const isFollowing = Boolean(res.following);
                state.users = state.users.map((user) => (
                    user.id === targetUserId
                        ? { ...user, isFollowing }
                        : user
                ));
                render();
            })
            .always(() => {
                $button.prop('disabled', false);
            });
    };

    $root.on('click', '[data-follow-modal-open]', function () {
        openModal(String($(this).attr('data-follow-modal-open') || FOLLOWERS_TAB), this);
    });

    $root.on('click', '[data-follow-modal-close]', () => {
        closeModal();
    });

    $root.on('click', '[data-follow-modal-tab]', function () {
        state.activeTab = normalizeTab($(this).attr('data-follow-modal-tab'));
        state.query = '';
        $search.val('');
        fetchFollowList();
    });

    $root.on('input', '[data-follow-modal-search]', () => {
        state.query = String($search.val() || '').trim();
        render();
    });

    $root.on('click', '[data-follow-user-id]', function () {
        const $button = $(this);
        const targetUserId = Number($button.attr('data-follow-user-id'));
        toggleFollowUser(targetUserId, $button);
    });

    $(document).on('keydown', (event) => {
        if (event.key !== 'Escape' || $modal.prop('hidden')) {
            return;
        }

        closeModal();
    });
});
