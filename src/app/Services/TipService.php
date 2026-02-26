<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Tip;
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
}
