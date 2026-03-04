@php
    $notificationSummary = $notificationSummary ?? [
        'unread_count' => 5,
        'total_count' => 24,
        'digest' => '새 댓글 2건, 답글 1건, 새 팔로워 1명',
    ];

    $notificationFilters = $notificationFilters ?? [
        'status' => 'all',
        'type' => 'all',
    ];

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

    $notificationGroups = $notificationGroups ?? [
        [
            'label' => '오늘',
            'items' => [
                [
                    'type' => 'comment',
                    'badge' => 'HG',
                    'actor_name' => '홍길동',
                    'message' => '회원님의 글에 댓글을 남겼습니다.',
                    'target' => '"Laravel 정리"',
                    'meta' => '새 댓글 1개',
                    'created_at_human' => '5분 전',
                    'is_unread' => true,
                    'action_label' => '댓글 보기',
                ],
                [
                    'type' => 'reply',
                    'badge' => 'KC',
                    'actor_name' => '김코드',
                    'message' => '회원님의 댓글에 답글을 남겼습니다.',
                    'target' => '"그 부분은 Service에서 처리하는 게 좋아요"',
                    'meta' => '답글 스레드',
                    'created_at_human' => '18분 전',
                    'is_unread' => true,
                    'action_label' => '답글 보기',
                ],
                [
                    'type' => 'like',
                    'badge' => 'PS',
                    'actor_name' => '박설계 외 2명',
                    'message' => '회원님의 글을 좋아합니다.',
                    'target' => '"Blade 컴포넌트 가이드"',
                    'meta' => '좋아요 3개',
                    'created_at_human' => '42분 전',
                    'is_unread' => false,
                    'action_label' => '글 보기',
                ],
            ],
        ],
        [
            'label' => '최근 7일',
            'items' => [
                [
                    'type' => 'follow',
                    'badge' => 'LM',
                    'actor_name' => '이메이커',
                    'message' => '회원님을 새로 팔로우했습니다.',
                    'target' => '프로필 피드에서 새 글을 확인해보세요.',
                    'meta' => '팔로워 +1',
                    'created_at_human' => '어제 14:20',
                    'is_unread' => true,
                    'action_label' => '프로필 보기',
                ],
                [
                    'type' => 'bookmark',
                    'badge' => 'JH',
                    'actor_name' => '정한빛',
                    'message' => '회원님의 글을 북마크했습니다.',
                    'target' => '"Tailwind 팁 모음"',
                    'meta' => '북마크 1개',
                    'created_at_human' => '2일 전',
                    'is_unread' => false,
                    'action_label' => '글 보기',
                ],
                [
                    'type' => 'system',
                    'badge' => 'SYS',
                    'actor_name' => '시스템',
                    'message' => '작성한 글이 정상적으로 게시되었습니다.',
                    'target' => '"알림 페이지 구성안"',
                    'meta' => '게시 완료',
                    'created_at_human' => '3일 전',
                    'is_unread' => false,
                    'action_label' => '글 보기',
                ],
            ],
        ],
        [
            'label' => '이전',
            'items' => [
                [
                    'type' => 'system',
                    'badge' => 'ADM',
                    'actor_name' => '운영팀',
                    'message' => '신고한 댓글의 검토가 완료되었습니다.',
                    'target' => '처리 결과를 확인해 주세요.',
                    'meta' => '신고 처리',
                    'created_at_human' => '8일 전',
                    'is_unread' => false,
                    'action_label' => '결과 보기',
                ],
            ],
        ],
    ];
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
            <button type="button" class="notify-btn notify-btn--ghost">알림 설정</button>
            <button type="button" class="notify-btn notify-btn--solid">모두 읽음 처리</button>
        </div>
    </header>

    <div class="notifications-board__filters">
        <div class="notifications-board__status-list">
            @foreach ($statusFilters as $filterKey => $filterLabel)
                <button
                    type="button"
                    @class([
                        'notifications-board__status-chip',
                        'is-active' => (string) data_get($notificationFilters, 'status', 'all') === $filterKey,
                    ])
                >
                    {{ $filterLabel }}
                </button>
            @endforeach
        </div>

        <div class="notifications-board__type-row">
            <div class="notifications-board__type-list">
                @foreach ($typeFilters as $filterKey => $filterLabel)
                    <button
                        type="button"
                        @class([
                            'notifications-board__type-chip',
                            'is-active' => (string) data_get($notificationFilters, 'type', 'all') === $filterKey,
                        ])
                    >
                        {{ $filterLabel }}
                    </button>
                @endforeach
            </div>

            <button type="button" class="notifications-board__sort-btn">최신순</button>
        </div>
    </div>

    <div class="notifications-board__groups">
        @forelse ($notificationGroups as $group)
            <section class="notifications-board__group">
                <div class="notifications-board__group-head">
                    <h4 class="notifications-board__group-title">{{ data_get($group, 'label') }}</h4>
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
                                            <p class="notification-card__target">
                                                {{ (string) data_get($item, 'target', '') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="notification-card__aside">
                                    <time class="notification-card__time">
                                        {{ (string) data_get($item, 'created_at_human', '-') }}
                                    </time>

                                    <div class="notification-card__actions">
                                        <button type="button" class="notify-btn notify-btn--ghost notify-btn--small">
                                            {{ $isUnread ? '읽음 처리' : '보관' }}
                                        </button>
                                        <button type="button" class="notify-btn notify-btn--solid notify-btn--small">
                                            {{ (string) data_get($item, 'action_label', '보기') }}
                                        </button>
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
                    댓글, 답글, 팔로우, 시스템 알림이 도착하면 이곳에 표시됩니다.
                </p>
            </div>
        @endforelse
    </div>
</section>
