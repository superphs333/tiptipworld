<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FollowService;
use App\Models\User;

class UserFollowController extends Controller
{
    public function __construct(private FollowService $followService)
    {
        
    }
    
    public  function followUser(int $user_id){
        
        $authUserId = (int) Auth::id();

        if($authUserId <= 0 ){
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $following = $this->followService->toggleFollow($authUserId, $user_id);
        $following_count = $this->followService->getFollowerCount($user_id);


        return response()->json([
            'success' => true,
            'following' => $following,
            'following_count' => $following_count,
            'target_user_id' => $user_id,
        ]);

    }
}
