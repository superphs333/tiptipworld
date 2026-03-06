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

/**
 * 1. 알림 생성 : 댓글, 답글, 좋아요, 북마크, 팔로우
 * 2. 알림 조회/가공 : 읽음/안읽음 필터, 타입 필터, 오늘/최근 7일 /이전 그룹화, 프론트에서 바로 쓰기 쉬운 배열 형태로 변환 
 */
class UserNotificationService{

    public const TYPE_COMMENT = 'comment';
    public const TYPE_REPLY = 'reply';
    public const TYPE_FOLLOW = 'follow';
    public const TYPE_LIKE = 'like';
    public const TYPE_BOOKMARK = 'bookmark';
    public const TYPE_SYSTEM = 'system';

    // 허용 가능한 알림 타입 목록 
    private const TYPES = [
        self::TYPE_COMMENT,
        self::TYPE_REPLY,
        self::TYPE_FOLLOW,
        self::TYPE_LIKE,
        self::TYPE_BOOKMARK,
        self::TYPE_SYSTEM,
    ];

    private function makeBadge(string $name, ?int $userId = null) : string{
        $collapsed = preg_replace('/\s+/u', '', trim($name));
        $collapsed = is_string($collapsed) ? $collapsed : '';
    }

    /**
     * 공통 전송 메서드
     */
    public function send(
        User $recipient, // 알림을 받을 사용자
        User $actor,    // 행동을 한 사용자
        string $type,   // 알림 타입
        string $message, // 최종 표시 문구
        array $extra = [] // 상황별 추가 payload
    ) : bool{
        // 타입 검증 
        if(! in_array($type, self::TYPES, true)) return false;

        // 자기 자신에게는 알림 생성 x
        if((int) $recipient->id === (int) $actor->id){
            return false;
        }

        // 공통 payload 생성 
        $payload = array_merge([
            'type' => $type, // 알림 타입 

            // 행동 주체 사용자 정부 (누가 이 행동을 했는지 프론트에 표시할 때 사용)
            'actor_id' => (int) $actor->id,
            'actor_name' => $this->displayName($actor),
            'actor_profile_image_url' => (string) $actor->profile_image_url,

            // 프로필 이미지가 없거나 단순 텍스트 뱃지가 필요할 때 사용할 값
            'badge' => $this->makeBadge($this->displayName($actor), (int) $actor->id),

            // 알림 타입을 화면용 짧은 라벨로 변환한 값 ex)comment -> 댓글 
            'meta' => $this->metaByType($type),

            // 실제 사용자에게 보여줄 메세지 
            'message' => $message,

            //클릭 버튼/링크 라벨 기본값
            'action_label' => $this->actionLabelByType($type),
            // 기본 이동 url (개발 알림 탭에서 별도 url없으면 알림 탭으로 이동하게 둠)
            'action_url' => route('mypage', ['tab' => 'notifications']),

            // 알림 생성 시각
            'created_at' => now()->toIso8601String(),
        ], $extra);

        // 알림 전송 
        $recipient->notify(new ActivityNotification($type, $payload));

        return true;
    }


    /**
     * 댓글 알림 생성
     * 
     * @param Tip       $tip        댓글이 달린 글
     * @param Comment   $comment    새로 작성된 댓글
     * @param User      $actor      댓글을 작성한 사용자
     */
    public function notifyComment(Tip $tip, Comment $comment, User $actor) :bool {
        // 글 작성자(user) 관계가 아직 로딩되지 않았다면 최소 컬럼만 로딩
        $tip->loadMissing('user"id, name, profile_image_path');

        // 글 작성자가 정상적으로 존재하지 않으면 중단
        if(! $tip->user instanceof User){
            return false;
        }

        // 알림 문구에 글 제목
        $title = Str::limit(trim((string) $tip->title), 60, '...');

        // 사용자에게 노출할 최종 메세지 
        $message = $this->displayName($actor) . '님이 회원님의 글 "' . $title . '"에 댓글을 남겼습니다.';

        /**
         * 공통 send()를 호출하여 실제 알림 생성
         *
         * - recipient: 글 작성자
         * - actor: 댓글 작성자
         * - target_type: 이 알림이 무엇을 대상으로 하는지 표시
         * - action_url: 클릭 시 해당 글의 특정 댓글 위치로 이동
         */
        return $this->send($tip->user, $actor, self::TYPE_COMMENT, $message, [
            'target_type' => 'tip',
            'target_id' => (int) $tip->id,
            'target_text' => '"' . $title . '"',
            'action_label' => '댓글 보기',
            'action_url' => route('tip.show', ['tip_id' => $tip->id]) . '#comment-' . $comment->id,
        ]);

    }


    /**
     * 답글 알림 생성
     * 
     * @param   Tip     $tip            답글이 달린 글
     * @param   Comment $replyComment   새로 작성된 답글
     * @param   Comment $targetComment  답글의 대상이 된 원댓글
     * @param   User    $actor          답글을 작성한 사용자
     */
    public function notifyReply(Tip $tip, Comment $replyComment, Comment $targetComment, User $actor) : bool{
        // 원댓글의 작성자(user) 관계를 로딩
        $targetComment->loadMissing('user:id,name,profile_image_path');

        // 원댓글 작성자가 없으면 알림 대상이 없으므로 중단
        if(!$targetComment->user instanceof User){
            return false;
        }

        // 원댓글이 삭제된 상태라면 실제 본문 대신 안내 문구를 사용
        $targetBody = $targetComment->status === 'deleted'
            ? '삭제된 댓글입니다.'
            : (string) $targetComment->body;
        
        // 댓글 본문
        $targetBody = Str::limit(trim(preg_replace('/\s+/u', ' ', $targetBody) ?? ''), 60, '...');

        // 답글 알림 메세지
        $message = $this->displayName($actor) . '님이 회원님의 댓글에 답글을 남겼습니다.';

        /**
         * 클릭하면 새로 달린 답글 위치로 바로 이동하도록 URL을 구성한다.
         */
        return $this->send($targetComment->user, $actor, self::TYPE_REPLY, $message, [
            'target_type' => 'comment',
            'target_id' => (int) $targetComment->id,
            'target_text' => '"' . $targetBody . '"',
            'action_label' => '답글 보기',
            'action_url' => route('tip.show', ['tip_id' => $tip->id]) . '#comment-' . $replyComment->id,
        ]);
    }


    /**
     * 좋아요 알림 생성
     * 
     */
    public function notifyLike(Tip $tip, User $actor) : bool {
        // 글 작성자 관계 로딩
        $tip->loadMissing('user:id,name,profile_image_path');

        // 글 작성자가 없으면 알림 전송 불가
        if(!$tip->user instanceof User){
            return false;
        }

        // 글제목
        $title = Str::limit(trim((string) $tip->title), 60, '...');

        // 좋아요 알림 문구
        $message = $this->displayName($actor) . '님이 회원님의 글 "' . $title . '"을 좋아했습니다.';

        // 글 상제 페이지로 이동하는 알림 
        return $this->send($tip->user, $actor, self::TYPE_LIKE, $message, [
            'target_type' => 'tip',
            'target_id' => (int) $tip->id,
            'target_text' => '"' . $title . '"',
            'action_label' => '글 보기',
            'action_url' => route('tip.show', ['tip_id' => $tip->id]),
        ]);

    }

    /**
     * 북마크 알림 
     */
    public function notifyBookmark(Tip $tip, User $actor) : bool {
        // 글 작성자 관계 로딩
        $tip->loadMissing('user:id,name,profile_image_path');

        // 글 작성자가 없으면 중단
        if(! $tip->user instanceof User) return false;

        // 제목
        $title = Str::limit(trim((string) $tip->title), 60, '...');

        // 북마크 알림 문구
        $message = $this->displayName($actor) . '님이 회원님의 글 "' . $title . '"을 북마크했습니다.';

        return $this->send($tip->user, $actor, self::TYPE_BOOKMARK, $message, [
            'target_type' => 'tip',
            'target_id' => (int) $tip->id,
            'target_text' => '"' . $title . '"',
            'action_label' => '글 보기',
            'action_url' => route('tip.show', ['tip_id' => $tip->id]),
        ]);
    }

    /**
     * 팔로우 알림 생성
     */
    public function notifyFollow(User $targetUser, User $actor) : bool {
        // 팔로우 알림 문구
        $message = $this->displayName($actor) . '님이 회원님을 팔로우했습니다.';

        // 클릭하면 팔로우한 사람의 프로필/피드로 이동
        return $this->send($targetUser, $actor, self::TYPE_FOLLOW, $message, [
            'target_type' => 'user',
            'target_id' => (int) $targetUser->id,
            'target_text' => '',
            'action_label' => '프로필 보기',
            'action_url' => route('tips.userFeed', ['user_id' => $actor->id]),
        ]);
    }


    /**
     * 마이페이지 알람 탭에 필요한 데이터 전체 
     * 
     * 필터 정규화 + 알림 목록 조회 + 요약 정보 + 기간별 그룹화
     * 
     * @param   User    $user       알림 소유자
     * @param   string  $status     all / unread / read
     * @param   string  $type       all / comment / reply / like ...
     * @param   int     $limit      최대 조회 개수
     * 
     * @return  array   프론트에서 바로 쓰기 좋은 구조의 배열       
     */
    public function getBoardData(
        User $user,
        string $status = 'all',
        string $type = 'all',
        int $limit = 80
    ): array{
        // 외부입력값 그대로 x, 허용된 값만 남기도록 정규화
        $status = $this->nomalizeStatus($status);
        $type = $this->nomalizeType($type);

        // limit 값 범위 제한
        $size = max(1, min($limit, 200));

        // 현재 사용자의 알림을 최신순으로 조회
        $query = $user->notifications()->latest();

        // 읽음/안읽음 필터 적용
        if($status === 'unread'){
            $query->whereNull('read_at');
        }elseif($status === 'read'){
            $query->whereNotNull('read_at');
        }

        // 타입 필터 (notifications 테이블의 type 컬럼에 실제 어떤 값이 저장되는지 구조 확인)
        if($type !== 'all'){
            $query->where('type', $type)
        }

        // 최종 조회 결과
        $notifications = $query->limit($size)->get();

        // 프론트에서 사용하기 쉬운 구조로 정리하여 반환
        return [
            'notificationsSummary' => [
                // 안읽음 알림 개수
                'unread_count' => (int) $user->unreadNotifications()->count(),

                // 전체 알림 개수
                'total_count' => (int) $user->notifications()->count(),

                // 향후 요약/다이제스트 기능 확장용 자리
                'digest' => null,
            ],

            // 현재 적용된 필터 상태 그대로 반환 
            'notificationFilters' => [
                'status' => $status,
                'type' => $type,
            ],

            // 오늘 / 최근7일 / 이전 기준으로 그룹화된 알림 목록
            'notificationGroups' => $this->groupByPeriod($notifications)
        ];
    }

    /**
     * 알림을 기간별로 그룹화
     *
     * 기준:
     * - 오늘
     * - 최근 7일
     * - 이전
     *
     * @param Collection $notifications DatabaseNotification 컬렉션
     *
     * @return array 그룹화된 화면용 데이터
     */
    private function groupByPeriod(Collection $notifications): array
    {
        /**
         * 각 그룹별 아이템을 담을 배열
         */
        $today = [];
        $recent = [];
        $older = [];

        /**
         * 그룹 판별 기준 시점
         *
         * - todayStart: 오늘 00:00
         * - recentStart: 7일 전 00:00
         */
        $todayStart = now()->startOfDay();
        $recentStart = now()->subDays(7)->startOfDay();

        foreach ($notifications as $notification) {
            /**
             * DB 원본 알림을 화면용 단순 배열로 변환
             */
            $item = $this->toViewItem($notification);

            /**
             * 생성 시각 꺼내기
             */
            $createdAt = $notification->created_at;

            /**
             * 오늘 생성된 알림
             */
            if ($createdAt instanceof Carbon && $createdAt->gte($todayStart)) {
                $today[] = $item;
                continue;
            }

            /**
             * 오늘은 아니지만 최근 7일 이내 알림
             */
            if ($createdAt instanceof Carbon && $createdAt->gte($recentStart)) {
                $recent[] = $item;
                continue;
            }

            /**
             * 그보다 오래된 알림
             */
            $older[] = $item;
        }

        /**
         * 비어 있지 않은 그룹만 최종 결과에 포함
         */
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
     * DatabaseNotification 모델을 프론트 적용 배열 형태로 변환
     * 
     * DB에는 다양한 형식의 data가 들어올 수 있으므로, 
     * 화면에 필요한 키를 안전하게 보장하면서 추출.
     */
    private function toViewItem(DatabaseNotification $notification) : array{
        // data가 배열이 아니면 빈 배열로 처리
        $data = is_array($notification->data) ? $notification->data : [];

        /**
         * 타입 결정
         * 
         * 우선순위)
         * 1) payload data.type
         * 2) notification 테이블 type 컬럼
         * 3) system
         */
        $type = $this->normalizeType((string) ($data['type'] ?? $notification->type ?? self::TYPE_SYSTEM));
        if ($type === 'all') {
            $type = self::TYPE_SYSTEM;
        }

        /**
         * 행동 주체 이름 추출
         * 비어 있으면 시스템 알림으로 간주
         */
        $actorName = trim((string) ($data['actor_name'] ?? ''));
        if($actorName === '') $actorName = '시스템';

        /**
         * badge 추출 (없으면 actorName 기준으로 생성)
         */
        $badge = trim((string) ($data['badge'] ?? ''));
        if($badge === ''){ $badge = $this->makeBadge($actorName);}

        /**
         * 메세지 추출 (비어 있으면 기본 문구 사용)
         */
        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '') {
            $message = '새 알림이 도착했습니다.';
        }

        /**
         * 사람이 읽기 쉬운 상대 시간 문자열 생성 (3분 전, 2시간 전)
         */
        $createdAtHuman = $notification->created_at instanceof Carbon
            ? $notification->created_at->locale('ko')->diffForHumans()
            : '-';
        
        /**
         * 프론트에서 바로 쓰기 쉬운 키 구조로 반환
         */
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


    /**
     * 상태값 정규화
     * 
     * 허용값 : all | unread | read 
     */
    private function nomalizeStatus(string $value) : string {
        $v = strtolower(trim($value));
        return in_array($v, ['all', 'unread', 'read'], true) ? $v : 'all';
    }

    /**
     * 타입값 정규화
     * 
     * 허용값 : all
     * comment | reply | follow | like | bookmark | system (유효하지 않은 값은 all 로 처리)
     */
    private function nomalizeType(string $value) : string{
        $v = strtolower(trim($value));
        if($v === 'all') return 'all';
        return in_array($v, self::TYPES, true) ? $v : 'all';
    }

    /**
     * 타입별 메타 라벨 변환 (알림 리스트에서 짧은 구분 라벨로 쓸 수 있음)
     */
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

    /**
     * 타입별 기본 액션 라벨 반환
     * 
     * 버튼/링크 텍스트의 기본값 역할을 한다. 
     */
    private function actionLabelByType(string $type) : string{
        return match ($type) {
            self::TYPE_COMMENT => '댓글 보기',
            self::TYPE_REPLY => '답글 보기',
            self::TYPE_FOLLOW => '프로필 보기',
            self::TYPE_LIKE, self::TYPE_BOOKMARK => '글 보기',
            default => '보기',
        };
    }

    /**
     * 사용자 표시 이름 정리
     *
     * - 연속 공백 제거
     * - 앞뒤 공백 제거
     * - 이름이 비어 있으면 "사용자 {id}" 반환
     */
    private function displayName(User $user): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) $user->name));

        return $name !== '' ? $name : "사용자 {$user->id}";
    }

    /**
     * 텍스트 뱃지 생성
     *
     * 예:
     * - "홍 길동" -> "홍길동" -> 앞 2글자
     * - 빈 문자열이면 userId가 있으면 "U{id}", 없으면 "SYS"
     *
     * 프로필 이미지가 없을 때 대체 시각 요소로 사용할 수 있다.
     */
    private function makeBadge(string $name, ?int $userId = null): string
    {
        /**
         * 공백 제거한 이름 생성
         */
        $collapsed = preg_replace('/\s+/u', '', trim($name));
        $collapsed = is_string($collapsed) ? $collapsed : '';

        /**
         * 이름이 비어 있으면 fallback 값 반환
         */
        if ($collapsed === '') {
            return $userId !== null ? "U{$userId}" : 'SYS';
        }

        /**
         * 이름의 앞 2글자를 badge로 사용
         */
        return mb_substr(mb_strtoupper($collapsed), 0, 2);
    }




    /**
     * 특정 알림 1건 읽음 처리 
     */
    public function markAsRead(User $user, string $notificationId) : bool {
        // 현재 사용자의 알림 중 해당 id 찾기 (다른 사용자의 알림을 조작하지 못하게 함)
        $notification = $user->notifications()->whereKey($notificationId)->first();

        // 알림이 없거나 타입이 예상과 다르면 실패
        if (! $notification instanceof DatabaseNotification) {
            return false;
        }

        // 라라벨 제공 메서드로 읽음 처리
        $notification->markAsRead();

        return true;
    }

    /**
     * 현재 사용자의 안읽은 알림 전체 읽음 처리
     * 
     * @return int 실제로 업데이트된 알림 갯수
     */
    public function markAllAsRead(User $user) : int {
        return (int) $user->unreadNotifications()->update(['read_at'=>now()]);
    }

    
}