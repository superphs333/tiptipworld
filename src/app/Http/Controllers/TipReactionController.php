<?php

namespace App\Http\Controllers;

use App\Models\Tip;
use App\Models\User;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\Auth;

/**
 * 팁에 대한 사용자 반응(좋아요, 북마크)을 처리하는 컨트롤러
 */
class TipReactionController extends Controller
{
    public function __construct(
        private UserNotificationService $userNotificationService,
    ) {
    }

    /**
     * 특정 팁에 대한 현재 로그인 사용자의 좋아요 상태를 토글
     * 
     * [처리흐름]
     * 1. tip_id로 대상 팁을 조회
     * 2. 현재 로그인한 사용자 ID를 구함
     * 3. likedUsers 관계에서 toggle()을 수행
     * 4. 최종 좋아요 수를 다시 계산해 like_count 캐시 컬럼에 반영
     * 5. 새로 좋아요가 추가된 경우에만 작성자 ㅏㄹ림 보냄
     * 6. json 응답으로 현재 상태와 개수를 반환 
     */
    public function like(int $tip_id)
    {
        
        $tip = Tip::findOrFail($tip_id);
        $userId = Auth::id();

        $changed = $tip->likedUsers()->toggle($userId);

        // $attached 배열이 비어 있지 않으면 "이번 요청으로 새로 좋아요가 붙은 상태" 
        $liked = ! empty($changed['attached']);

        $likeCount = $tip->likedUsers()->count(); // 최종 좋아요 수
        $tip->update(['like_count' => $likeCount]); // tips.like_count 캐시 컬럼 갱신

        $actor = Auth::user();

        // 좋아요가 새로 추가된 경우에만 알림 발송 
        if ($liked && $actor instanceof User) {
            $this->userNotificationService->notifyLike($tip, $actor);
        }

        return response()->json([
            'success' => true,
            'tip_id' => $tip->id,
            'liked' => $liked,
            'like_count' => $likeCount,
        ]);
    }

    /**
     * 특정 팁에 대한 현재 로그인 사용자의 북마크 상태를 토글
     * 
     * [처리흐름]
     * 1. tip_id로 대상 팁을 조회
     * 2. 현재 로그인한 사용자 ID를 구함
     * 3. bookmarkedUsers 관계에서 toggle()을 수행
     * 4. 최종 북마크 수를 다시 계산해 bookmark_count 캐시 컬럼에 반영
     * 5. 새로 북마크가 추가된 경우에만 작성자 알림을 보냄
     * 6. json 응답으로 현재 상태와 개수를 반환 
     */
    public function bookmark(int $tip_id)
    {
        $tip = Tip::findOrFail($tip_id);
        $userId = Auth::id();

        $changed = $tip->bookmarkedUsers()->toggle($userId);
        $bookmarked = ! empty($changed['attached']);

        $bookmarkCount = $tip->bookmarkedUsers()->count();
        $tip->update(['bookmark_count' => $bookmarkCount]);

        $actor = Auth::user();
        if ($bookmarked && $actor instanceof User) {
            $this->userNotificationService->notifyBookmark($tip, $actor);
        }

        return response()->json([
            'success' => true,
            'tip_id' => $tip->id,
            'bookmarked' => $bookmarked,
            'bookmark_count' => $bookmarkCount,
        ]);
    }
}
