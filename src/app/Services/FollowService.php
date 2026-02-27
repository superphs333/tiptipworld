<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class FollowService
{

    /**
     *  상세용 : 단건 팔로우 여부
     */
    public function isFollowing(?int $authUserId, ?int $targetUserId): bool
    {
        // 로그인 안 했거나, 대상이 없거나, 자기 자신이면 팔로우 관계 -> false 
        if (!$authUserId || !$targetUserId || $authUserId === $targetUserId) {
            return false;
        }

        // user_follows 테이블에서 (나->대상) 레코드 확인
        return DB::table('user_follows')
            ->where('follower_user_id', $authUserId)
            ->where('followed_user_id', $targetUserId)
            ->exists();
    }

    /**
     * 토글 후 현재 팔로우 상태 반환
     */
    public function toggleFollow(int $authUserId, int $targetUserId): bool
    {
        // 잘못된 입력/자기 자신 팔로우 방지 
        if ($authUserId <= 0 || $targetUserId <= 0 || $authUserId === $targetUserId) {
            return false;
        }

        User::query()->findOrFail($targetUserId);
        $me = User::query()->findOrFail($authUserId);

        // belongsToMany 토글
            // 기존 관계 있으면 detach, 없으면 attach 
        $changed = $me->followingUsers()->toggle($targetUserId);
        $following = !empty($changed['attached']);

        return $following;
    }

    /**
     * 팔로우 갯수
     */
    public function getFollowerCount(int $user_id): int
    {
        $user = User::query()->findOrFail($user_id);
        return $user->followerUsers()->count();
    }

    /**
     * 팔로우/팔로잉 목록 조회
     *
     * @return array{
     *     type: string,
     *     users: array<int, array{id: int, name: string, avatar: string, is_following: bool, is_self: bool}>,
     *     followers_count: int,
     *     following_count: int
     * }
     */
    public function getFollowList(
        int $targetUserId, // 프로필 주인
        string $type = 'followers', // followers | following 
        ?int $authUserId = null, // 로그인 유저 id
        string $query = '', // 검색어(이름)
        int $limit = 40 // 최대 조회 수 
    ): array {
        $targetUser = User::query()->findOrFail($targetUserId);
        $normalizedType = $type === 'following' ? 'following' : 'followers'; // 허용타입 
        $keyword = trim($query); // 검색어
        $size = max(1, min($limit, 80));

        $relation = $normalizedType === 'following'
            ? $targetUser->followingUsers() // 나를 팔로우한 사람
            : $targetUser->followerUsers(); // 내가 팔로우한 사람

        // 목록 
        $listQuery = $relation
            ->select('users.id', 'users.name', 'users.profile_image_path')
            ->orderByPivot('created_at', 'desc') // 최신순 
            ->limit($size);

        // 이름 검색 
        if ($keyword !== '') {
            $listQuery->where('users.name', 'like', "%{$keyword}%");
        }

        /** @var \Illuminate\Support\Collection<int, User> $users */
        $users = $listQuery->get();

        // 현재 목록에 있는 사용자 id만 뽑아둠 
        $userIds = $users->pluck('id')->filter()->map(static fn ($id) => (int) $id)->values()->all();

        // 목록 중 팔로우 중인 사람
        $followingIdMap = [];
        if (($authUserId ?? 0) > 0 && !empty($userIds)) {
            $followingIdMap = DB::table('user_follows')
                ->where('follower_user_id', $authUserId)
                ->whereIn('followed_user_id', $userIds)
                ->pluck('followed_user_id')
                ->mapWithKeys(static fn ($id) => [(int) $id => true])
                ->all();
        }

        $rows = $users->map(function (User $user) use ($followingIdMap, $authUserId): array {
            $id = (int) $user->id;
            $name = trim((string) $user->name);
            $isSelf = ($authUserId ?? 0) > 0 && $id === (int) $authUserId;

            return [
                'id' => $id,
                'name' => $name !== '' ? $name : "사용자 {$id}",
                'avatar' => (string) $user->profile_image_url,
                'is_following' => $isSelf ? false : isset($followingIdMap[$id]),
                'is_self' => $isSelf,
            ];
        })->values()->all();

        return [
            'type' => $normalizedType,
            'users' => $rows,
            'followers_count' => (int) $targetUser->followerUsers()->count(),
            'following_count' => (int) $targetUser->followingUsers()->count(),
        ];
    }
}
