<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Tip;
use App\Models\Tag;
use App\Models\User;

class TipService
{
    public static function getUserFeed(int $target_id, ?string $sortKey = null){
        $sortKey = $sortKey ?? (string) request()->query('sort', 'latest');
        $viewerId = (int) Auth::id();
        $isOwner = $viewerId >0 && $viewerId === (int) $target_id;
        $baseQuery = Tip::query()
            ->where('tips.user_id', $target_id)
            ->when(!$isOwner, function ($q){
                $q->where('tips.status', 'published')
                    ->where('tips.visibility', 'public');
            })
            ->select('tips.*')
            ->with([
                'user:id,name,profile_image_path',
                'category:id,name',
            ]);
        $listQuery = match ($sortKey) {
            'popular'   => (clone $baseQuery)->orderByDesc('tips.view_count')->orderByDesc('tips.id'),
            'likes'     => (clone $baseQuery)->orderByDesc('tips.like_count')->orderByDesc('tips.id'),
            'bookmarks' => (clone $baseQuery)->orderByDesc('tips.bookmark_count')->orderByDesc('tips.id'),
            default     => (clone $baseQuery)->orderByDesc('tips.created_at')->orderByDesc('tips.id'), 
        };

        return $listQuery->get()->map(static function ($item) {
            return [
                'id' => (int) data_get($item, 'id', 0),
                'title' => (string) data_get($item, 'title', ''),
                'thumbnail_url' => (string) data_get($item, 'thumbnail_url', data_get($item, 'thumbnailUrl', asset('images/no-thumbnail.png'))),
                'category_id' => (int) data_get($item, 'category.id', 0),
                'category_name' => (string) data_get($item, 'category.name', '미분류'),
                'view_count' => (int) data_get($item, 'view_count', 0),
                'like_count' => (int) data_get($item, 'like_count', 0),
                'comment_count' => (int) data_get($item, 'comment_count', 0),
                'bookmark_count' => (int) data_get($item, 'bookmark_count', 0),
                'author' => [
                    'id' => (int) data_get($item, 'user.id', 0),
                    'name' => (string) data_get($item, 'user.name', '작성자 미상'),
                    'profile_image_url' => (string) data_get($item, 'user.profile_image_url', asset('images/avatar-default.svg')),
                ],
            ];
        })->values();
    }

    public static function getMyTips(Request $request) : mixed{

        // 조건 : 카테고리, 노출, 상태, 검색어
        $query = trim($request->query('query'));
        $category = $request->query('category_id') ?? null;
        $status = $request->query('status');
        $visibility = $request->query('visibility');

        // 정렬
        $sortKey = $request->query('sort', 'latest');

        $tips = Tip::query()
            ->where('user_id', Auth::id())
            ->with('category')
            ->with('tags:id,name');
        
        // 쿼리 (검색어)
        if(isset($query) && $query !== ''){
            $tips->where(function ($searchQ) use ($query){
                $searchQ->where('title', 'like' , "%{$query}%");
            });
        }
        // 카테고리
        if(isset($category) && $category !== ''){
            if($category === 'uncategorized'){
                $tips->whereNull('category_id');
            }else{
                $tips->where('category_id',$category);
            }
        }
        // 상태
        if($status !== null && $status !== ''){
            $tips->where('status',$status);
        }
        // 노출
        if($visibility !== null && $visibility !== ''){
            $tips->where('visibility', $visibility);
        }
        // 정렬
        $resultTips = match($sortKey){
            'popular' => (clone $tips)->orderByDesc('tips.view_count')->orderByDesc('tips.id'),
            'likes' => (clone $tips)->orderByDesc('tips.like_count')->orderByDesc('tips.id'),
            'bookmarks' => (clone $tips)->orderByDesc('tips.bookmark_count')->orderByDesc('tips.id'),
            default => (clone $tips)->orderByDesc('tips.created_at')->orderByDesc('tips.id')
        };

        return $resultTips->get()->map(static function ($item){
            return[
                'id' => (int) data_get($item, 'id', 0),
                'title' => (string) data_get($item, 'title'. ''),
                'thumbnail_url' => (string) data_get($item, 'thumbnailUrl'),
                'category_id' => (int) data_get($item, 'category.id'),
                'category_name' => (string) data_get($item, 'category.name'),
                'view_count' => (int) data_get($item, 'view_count', 0),
                'like_count' => (int) data_get($item, 'like_count', 0),
                'comment_count' => (int) data_get($item, 'comment_count', 0),
                'bookmark_count' => (int) data_get($item, 'bookmark_count', 0),
            ];
        })->values();
        
    }

    // 유저 글의 카테고리 가져오기
    public function userTipsCategory($user_id, ?int $limit = null)
    {
        $user = User::findOrFail($user_id);

        return $user->tips()
            ->selectRaw('category_id, COUNT(*) as tips_count')
            ->groupBy('category_id')
            ->orderByDesc('tips_count')
            ->with('category:id,name')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get()
            ->map(static function ($item) {
                $isUncategorized = $item->category_id === null;

                return [
                    'id' => $isUncategorized ? 'uncategorized' : (int) $item->category_id,
                    'name' => $isUncategorized
                        ? '미분류'
                        : (string) data_get($item, 'category.name', '미분류'),
                    'tips_count' => (int) data_get($item, 'tips_count', 0),
                ];
            })
            ->values();
    }

    // 유저 글의 태그 가져오기 
    public function userTipTags($user_id, ?int $limit=null){
        $user = User::findOrFail($user_id);
        $userTipTags = Tag::query()
            ->whereHas('tips', fn ($q) => $q->where('tips.user_id', $user->id))
            ->withCount([
                'tips as tips_count' => fn ($q) => $q->where('tips.user_id', $user->id),
            ])
            ->having('tips_count','>',0)
            ->orderByDesc('tips_count')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get(['id', 'name'])
            ->map(static function ($item){
                return [
                    'id' => (int) $item->id,
                    'name' => (string) $item->name, 
                    'tips_count' => (int) data_get($item, 'tips_count', 0)
                ];
            });
        return $userTipTags;
    }
}
