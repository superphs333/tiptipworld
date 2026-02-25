<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FollowService{


    /**
     *  상세용 : 단건 팔로우 여부
     */
    public function isFollowing(?int $authUserId, ?int $targetUserId) : bool {
        if (!$authUserId || !$targetUserId || $authUserId === $targetUserId) {
            return false;
        }
        return DB::table('user_follows')
            ->where('follower_user_id', $authUserId)
            ->where('followed_user_id', $targetUserId)
            ->exists();
    }

    /**
     * 토글 후 현재 팔로우 상태 반환
     */
    public function toggleFollow(int $authUserId, int $targetUserId) : bool{
        if($authUserId <= 0 || $targetUserId <= 0 || $authUserId === $targetUserId){
            return false;
        }

        User::query()->findOrFail($targetUserId);
        $me = User::query()->findOrFail($authUserId);

        $changed = $me->followingUsers()->toggle($targetUserId);
        $following = !empty($changed['attached']);

        return $following;
    }
}