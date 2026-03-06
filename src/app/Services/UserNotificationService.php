<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Tip;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserNotificationService
{
    public const TYPE_COMMENT = 'comment';
    public const TYPE_REPLY = 'reply';
    public const TYPE_FOLLOW = 'follow';
    public const TYPE_LIKE = 'like';
    public const TYPE_BOOKMARK = 'bookmark';
    public const TYPE_SYSTEM = 'system';

    private const TYPES = [
        self::TYPE_COMMENT,
        self::TYPE_REPLY,
        self::TYPE_FOLLOW,
        self::TYPE_LIKE,
        self::TYPE_BOOKMARK,
        self::TYPE_SYSTEM,
    ];

    public function send(
        User $recipient,
        User $actor,
        string $type,
        string $message,
        array $extra = []
    ): bool {
        if (! in_array($type, self::TYPES, true)) {
            return false;
        }

        // 자기 자신에게는 알림을 생성하지 않는다.
        if ((int) $recipient->id === (int) $actor->id) {
            return false;
        }

        $payload = array_merge([
            'type' => $type,
            'actor_id' => (int) $actor->id,
            'actor_name' => $this->displayName($actor),
            'actor_profile_image_url' => (string) $actor->profile_image_url,
            'badge' => $this->makeBadge($this->displayName($actor), (int) $actor->id),
            'meta' => $this->metaByType($type),
            'message' => $message,
            'action_label' => $this->actionLabelByType($type),
            'action_url' => route('mypage', ['tab' => 'notifications']),
            'created_at' => now()->toIso8601String(),
        ], $extra);

        $recipient->notify(new ActivityNotification($type, $payload));

        return true;
    }

    public function notifyComment(Tip $tip, Comment $comment, User $actor): bool
    {
        $tip->loadMissing('user:id,name,profile_image_path');
        if (! $tip->user instanceof User) {
            return false;
        }

        $title = $this->limitText((string) $tip->title, 60);
        $message = $this->displayName($actor).'님이 회원님의 글 "'.$title.'"에 댓글을 남겼습니다.';

        return $this->send($tip->user, $actor, self::TYPE_COMMENT, $message, [
            'target_type' => 'tip',
            'target_id' => (int) $tip->id,
            'target_text' => '"'.$title.'"',
            'action_label' => '댓글 보기',
            'action_url' => route('tip.show', ['tip_id' => $tip->id]).'#comment-'.$comment->id,
        ]);
    }

    public function notifyReply(Tip $tip, Comment $replyComment, Comment $targetComment, User $actor): bool
    {
        $targetComment->loadMissing('user:id,name,profile_image_path');
        if (! $targetComment->user instanceof User) {
            return false;
        }

        $targetBody = $targetComment->status === 'deleted'
            ? '삭제된 댓글입니다.'
            : (string) $targetComment->body;

        $targetBody = $this->limitText($targetBody, 60);
        $message = $this->displayName($actor).'님이 회원님의 댓글에 답글을 남겼습니다.';

        return $this->send($targetComment->user, $actor, self::TYPE_REPLY, $message, [
            'target_type' => 'comment',
            'target_id' => (int) $targetComment->id,
            'target_text' => '"'.$targetBody.'"',
            'action_label' => '답글 보기',
            'action_url' => route('tip.show', ['tip_id' => $tip->id]).'#comment-'.$replyComment->id,
        ]);
    }

    public function notifyLike(Tip $tip, User $actor): bool
    {
        $tip->loadMissing('user:id,name,profile_image_path');
        if (! $tip->user instanceof User) {
            return false;
        }

        $title = $this->limitText((string) $tip->title, 60);
        $message = $this->displayName($actor).'님이 회원님의 글 "'.$title.'"을 좋아했습니다.';

        return $this->send($tip->user, $actor, self::TYPE_LIKE, $message, [
            'target_type' => 'tip',
            'target_id' => (int) $tip->id,
            'target_text' => '"'.$title.'"',
            'action_label' => '글 보기',
            'action_url' => route('tip.show', ['tip_id' => $tip->id]),
        ]);
    }

    public function notifyBookmark(Tip $tip, User $actor): bool
    {
        $tip->loadMissing('user:id,name,profile_image_path');
        if (! $tip->user instanceof User) {
            return false;
        }

        $title = $this->limitText((string) $tip->title, 60);
        $message = $this->displayName($actor).'님이 회원님의 글 "'.$title.'"을 북마크했습니다.';

        return $this->send($tip->user, $actor, self::TYPE_BOOKMARK, $message, [
            'target_type' => 'tip',
            'target_id' => (int) $tip->id,
            'target_text' => '"'.$title.'"',
            'action_label' => '글 보기',
            'action_url' => route('tip.show', ['tip_id' => $tip->id]),
        ]);
    }

    public function notifyFollow(User $targetUser, User $actor): bool
    {
        $message = $this->displayName($actor).'님이 회원님을 팔로우했습니다.';

        return $this->send($targetUser, $actor, self::TYPE_FOLLOW, $message, [
            'target_type' => 'user',
            'target_id' => (int) $targetUser->id,
            'target_text' => '',
            'action_label' => '프로필 보기',
            'action_url' => route('tips.userFeed', ['user_id' => $actor->id]),
        ]);
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->whereKey($notificationId)->first();
        if (! $notification instanceof DatabaseNotification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(User $user): int
    {
        return (int) $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function getBoardData(
        User $user,
        string $status = 'all',
        string $type = 'all',
        int $limit = 80
    ): array {
        $status = $this->normalizeStatus($status);
        $type = $this->normalizeType($type);
        $size = max(1, min($limit, 200));

        $query = $user->notifications()->latest();

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        /** @var Collection<int, DatabaseNotification> $notifications */
        $notifications = $query->limit($size)->get();

        return [
            'notificationSummary' => [
                'unread_count' => (int) $user->unreadNotifications()->count(),
                'total_count' => (int) $user->notifications()->count(),
                'digest' => null,
            ],
            'notificationFilters' => [
                'status' => $status,
                'type' => $type,
            ],
            'notificationGroups' => $this->groupByPeriod($notifications),
        ];
    }

    private function metaByType(string $type): string
    {
        return match ($type) {
            self::TYPE_COMMENT => '댓글',
            self::TYPE_REPLY => '답글',
            self::TYPE_FOLLOW => '팔로우',
            self::TYPE_LIKE => '좋아요',
            self::TYPE_BOOKMARK => '북마크',
            default => '시스템',
        };
    }

    private function actionLabelByType(string $type): string
    {
        return match ($type) {
            self::TYPE_COMMENT => '댓글 보기',
            self::TYPE_REPLY => '답글 보기',
            self::TYPE_FOLLOW => '프로필 보기',
            self::TYPE_LIKE, self::TYPE_BOOKMARK => '글 보기',
            default => '보기',
        };
    }

    private function normalizeStatus(string $value): string
    {
        $v = strtolower(trim($value));

        return in_array($v, ['all', 'unread', 'read'], true) ? $v : 'all';
    }

    private function normalizeType(string $value): string
    {
        $v = strtolower(trim($value));
        if ($v === 'all') {
            return 'all';
        }

        return in_array($v, self::TYPES, true) ? $v : 'all';
    }

    private function displayName(User $user): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) $user->name));

        return $name !== '' ? $name : "사용자 {$user->id}";
    }

    private function makeBadge(string $name, ?int $userId = null): string
    {
        $collapsed = preg_replace('/\s+/u', '', trim($name));
        $collapsed = is_string($collapsed) ? $collapsed : '';

        if ($collapsed === '') {
            return $userId !== null ? "U{$userId}" : 'SYS';
        }

        return mb_substr(mb_strtoupper($collapsed), 0, 2);
    }

    private function limitText(string $text, int $length): string
    {
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $text));

        return Str::limit($normalized, $length, '...');
    }

    /**
     * @param Collection<int, DatabaseNotification> $notifications
     * @return array<int, array{label:string, items:array<int, array<string,mixed>>}>
     */
    private function groupByPeriod(Collection $notifications): array
    {
        $today = [];
        $recent = [];
        $older = [];

        $todayStart = now()->startOfDay();
        $recentStart = now()->subDays(7)->startOfDay();

        foreach ($notifications as $notification) {
            $item = $this->toViewItem($notification);
            $createdAt = $notification->created_at;

            if ($createdAt instanceof Carbon && $createdAt->gte($todayStart)) {
                $today[] = $item;
                continue;
            }

            if ($createdAt instanceof Carbon && $createdAt->gte($recentStart)) {
                $recent[] = $item;
                continue;
            }

            $older[] = $item;
        }

        $groups = [];

        if ($today !== []) {
            $groups[] = ['label' => '오늘', 'items' => $today];
        }
        if ($recent !== []) {
            $groups[] = ['label' => '최근 7일', 'items' => $recent];
        }
        if ($older !== []) {
            $groups[] = ['label' => '이전', 'items' => $older];
        }

        return $groups;
    }

    /**
     * @return array<string,mixed>
     */
    private function toViewItem(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        $type = $this->normalizeType((string) ($data['type'] ?? $notification->type ?? self::TYPE_SYSTEM));
        if ($type === 'all') {
            $type = self::TYPE_SYSTEM;
        }

        $actorName = trim((string) ($data['actor_name'] ?? ''));
        if ($actorName === '') {
            $actorName = '시스템';
        }

        $badge = trim((string) ($data['badge'] ?? ''));
        if ($badge === '') {
            $badge = $this->makeBadge($actorName);
        }

        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '') {
            $message = '새 알림이 도착했습니다.';
        }

        $createdAtHuman = $notification->created_at instanceof Carbon
            ? $notification->created_at->locale('ko')->diffForHumans()
            : '-';

        return [
            'id' => (string) $notification->id,
            'type' => $type,
            'badge' => $badge,
            'actor_name' => $actorName,
            'message' => $message,
            'target' => (string) ($data['target_text'] ?? ''),
            'meta' => (string) ($data['meta'] ?? $this->metaByType($type)),
            'created_at_human' => $createdAtHuman,
            'is_unread' => $notification->read_at === null,
            'action_label' => (string) ($data['action_label'] ?? $this->actionLabelByType($type)),
            'action_url' => (string) ($data['action_url'] ?? route('mypage', ['tab' => 'notifications'])),
        ];
    }
}
