<?php

namespace App\Http\Controllers;

use App\Models\Tip;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // 댓글 추가
    public function commentAdd($tip_id, Request $request){
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:500'],
        ]);

        Tip::findOrFail($tip_id);

        $comment = Comment::create([
            'tip_id' => $tip_id, 
            'user_id' => Auth::id(),
            'body' => $validated['comment']
        ]);

        $comment->load('user:id,name');

        return response()->json([
            'success' => true,
            'message' => '댓글이 등록되었습니다.',
            'comment'=> [
                'id' => $comment->id,
                'tip_id' => $comment->tip_id,
                'user_id' => $comment->user_id,
                'user_name' => $comment->user->name,
                'user_profile_image_url' => $comment->user->profile_image_url,
                'body' => $comment->body,
                'created_at' =>$comment->created_at?->toISOString(),
                'like_count' => $comment->like_count,
                'reply_count' => $comment->reply_count,
                'depth' => $comment->depth,
                'parent_id' => $comment->parent_id,
            ]
        ],201);


          
    }
}
