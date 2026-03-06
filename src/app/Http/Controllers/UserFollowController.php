<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\FollowService;
use App\Services\UserNotificationService;

class UserFollowController extends Controller
{
    public function __construct(
        private FollowService $followService,
        private UserNotificationService $userNotificationService,
    )
    {
    }

    public function followList(Request $request, int $user_id)
    {
        $type = (string) $request->query('type', 'followers');
        $query = (string) $request->query('query', '');
        $limit = (int) $request->query('limit', 40);
        $authUserId = Auth::id();

        $result = $this->followService->getFollowList(
            $user_id,
            $type,
            is_numeric($authUserId) ? (int) $authUserId : null,
            $query,
            $limit,
        );

        return response()->json([
            'success' => true,
            'type' => $result['type'],
            'users' => $result['users'],
            'followers_count' => $result['followers_count'],
            'following_count' => $result['following_count'],
        ]);
    }

    public function followUser(int $user_id)
    {
        $authUserId = (int) Auth::id();

        if ($authUserId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $following = $this->followService->toggleFollow($authUserId, $user_id);
        $followersCount = $this->followService->getFollowerCount($user_id);

        // 팔로우가 새로 생성된 경우에만 알림 전송
        if ($following) {
            $actor = Auth::user();
            $targetUser = User::query()->find($user_id);

            if ($actor instanceof User && $targetUser instanceof User) {
                $this->userNotificationService->notifyFollow($targetUser, $actor);
            }
        }

        return response()->json([
            'success' => true,
            'following' => $following,
            'followers_count' => $followersCount,
            'following_count' => $followersCount, // 하위 호환
            'target_user_id' => $user_id,
        ]);
    }
}
