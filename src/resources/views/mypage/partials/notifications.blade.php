@php
    $notificationSummary = $notificationSummary ?? [
        'unread_count' => 0,
        'total_count' => 0,
        'digest' => null,
    ];

    $notificationFilters = $notificationFilters ?? [
        'status' => 'all',
        'type' => 'all',
    ];

    $notificationGroups = $notificationGroups ?? [];

    $statusFilters = [
        'all' => '전체',
        'unread' => '안 읽음',
        'read' => '읽음',
    ];

    $typeFilters = [
        'all' => '전체',
        'comment' => '댓글',
        'reply' => '답글',
        'follow' => '팔로우',
        'like' => '좋아요',
        'bookmark' => '북마크',
        'system' => '시스템',
    ];

    $currentStatus = (string) data_get($notificationFilters, 'status', 'all');
    $currentType = (string) data_get($notificationFilters, 'type', 'all');

    $buildFilterUrl = static function (string $status, string $type): string {
        return route('mypage', [
            'tab' => 'notifications',
            'status' => $status,
            'type' => $type,
        ]);
    };
@endphp

<section class="notifications-board">
    <header class="notifications-board__summary">
        <div class="notifications-board__summary-main">
            <div class="notifications-board__count-list">
                <span class="notifications-board__count notifications-board__count--unread">
                    읽지 않음 {{ number_format((int) data_get($notificationSummary, 'unread_count', 0)) }}개
                </span>
                <span class="notifications-board__count">
                    전체 {{ number_format((int) data_get($notificationSummary, 'total_count', 0)) }}개
                </span>
            </div>

            <div class="notifications-board__summary-copy">
                <h3 class="notifications-board__title">알림함</h3>
                <p class="notifications-board__digest">
                    {{ (string) data_get($notificationSummary, 'digest', '새 알림이 없습니다.') }}
                </p>
            </div>
        </div>

        <div class="notifications-board__summary-actions">
            <a href="{{ route('mypage', ['tab' => 'notifications']) }}" class="notify-btn notify-btn--ghost">
                필터 초기화
            </a>
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="notify-btn notify-btn--solid">모두 읽음 처리</button>
            </form>
        </div>
    </header>

    <div class="notifications-board__filters">
        <div class="notifications-board__status-list">
            @foreach ($statusFilters as $filterKey => $filterLabel)
                <a
                    href="{{ $buildFilterUrl($filterKey, $currentType) }}"
                    @class([
                        'notifications-board__status-chip',
                        'is-active' => $currentStatus === $filterKey,
                    ])
                >
                    {{ $filterLabel }}
                </a>
            @endforeach
        </div>

        <div class="notifications-board__type-row">
            <div class="notifications-board__type-list">
                @foreach ($typeFilters as $filterKey => $filterLabel)
                    <a
                        href="{{ $buildFilterUrl($currentStatus, $filterKey) }}"
                        @class([
                            'notifications-board__type-chip',
                            'is-active' => $currentType === $filterKey,
                        ])
                    >
                        {{ $filterLabel }}
                    </a>
                @endforeach
            </div>
            <button type="button" class="notifications-board__sort-btn">최신순</button>
        </div>
    </div>

    <div class="notifications-board__groups">
        @forelse ($notificationGroups as $group)
            <section class="notifications-board__group">
                <div class="notifications-board__group-head">
                    <h4 class="notifications-board__group-title">{{ (string) data_get($group, 'label', '') }}</h4>
                    <span class="notifications-board__group-count">
                        {{ number_format(count((array) data_get($group, 'items', []))) }}개
                    </span>
                </div>

                <div class="notifications-board__list">
                    @foreach ((array) data_get($group, 'items', []) as $item)
                        @php
                            $type = (string) data_get($item, 'type', 'system');
                            $typeKey = in_array($type, ['comment', 'reply', 'follow', 'like', 'bookmark', 'system'], true)
                                ? $type
                                : 'system';
                            $isUnread = (bool) data_get($item, 'is_unread', false);
                            $notificationId = (string) data_get($item, 'id', '');
                            $actionUrl = (string) data_get($item, 'action_url', route('mypage', ['tab' => 'notifications']));
                        @endphp

                        <article @class(['notification-card', 'is-unread' => $isUnread])>
                            <div class="notification-card__layout">
                                <div class="notification-card__body">
                                    <span @class(['notification-card__indicator', 'is-visible' => $isUnread])></span>

                                    <div class="notification-card__badge notification-card__badge--{{ $typeKey }}">
                                        {{ (string) data_get($item, 'badge', 'SYS') }}
                                    </div>

                                    <div class="notification-card__content">
                                        <div class="notification-card__head">
                                            <span class="notification-card__actor">
                                                {{ (string) data_get($item, 'actor_name', '시스템') }}
                                            </span>
                                            <span class="notification-card__meta-chip notification-card__meta-chip--{{ $typeKey }}">
                                                {{ (string) data_get($item, 'meta', '알림') }}
                                            </span>
                                            @if ($isUnread)
                                                <span class="notification-card__new">NEW</span>
                                            @endif
                                        </div>

                                        <div class="notification-card__copy">
                                            <p class="notification-card__message">
                                                {{ (string) data_get($item, 'message', '') }}
                                            </p>
                                            @if (filled(data_get($item, 'target')))
                                                <p class="notification-card__target">
                                                    {{ (string) data_get($item, 'target', '') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="notification-card__aside">
                                    <time class="notification-card__time">
                                        {{ (string) data_get($item, 'created_at_human', '-') }}
                                    </time>

                                    <div class="notification-card__actions">
                                        @if ($isUnread && $notificationId !== '')
                                            <form method="POST" action="{{ route('notifications.read', ['notificationId' => $notificationId]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="notify-btn notify-btn--ghost notify-btn--small">
                                                    읽음 처리
                                                </button>
                                            </form>
                                        @else
                                            <span class="notify-btn notify-btn--ghost notify-btn--small">읽음</span>
                                        @endif

                                        <a href="{{ $actionUrl }}" class="notify-btn notify-btn--solid notify-btn--small">
                                            {{ (string) data_get($item, 'action_label', '보기') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="notifications-board__empty">
                <div class="notifications-board__empty-badge">0</div>
                <h3 class="notifications-board__empty-title">새 알림이 없습니다.</h3>
                <p class="notifications-board__empty-copy">
                    댓글, 답글, 팔로우, 좋아요, 북마크 알림이 도착하면 이곳에 표시됩니다.
                </p>
            </div>
        @endforelse
    </div>
</section>
