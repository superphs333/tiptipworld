<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Tip;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;


class HomeViewService
{
    /**
     * 가중치를 계산해서 최근 인기글 가져오기
     *
     * engagement = views * 1 + likes * 3 + comments * 5 + bookmarks * 8
     */
    public static function getpopularList(int $limit = 10, int $days = 7): Collection
    {
        $limit = max(1, min($limit, 50));
        $days = max(1, min($days, 30));
        $authUserId = Auth::id();

        $query = Tip::query()
            ->where('tips.status', 'published')
            ->where('tips.visibility', 'public')
            //->where('tips.created_at', '>=', now()->subDays($days))
            ->select('tips.*')
            ->selectRaw("
                (tips.view_count * 1)
                + (tips.like_count * 3)
                + (tips.comment_count * 5)
                + (tips.bookmark_count * 8)
                as engagement
            ")
            ->with([
                'user:id,name,profile_image_path',
                'category:id,name',
            ]);

        if ($authUserId) {
            $query->withCount([
                'likedUsers as is_liked' => function ($countQuery) use ($authUserId) {
                    $countQuery->where('users.id', $authUserId);
                },
                'bookmarkedUsers as is_bookmarked' => function ($countQuery) use ($authUserId) {
                    $countQuery->where('users.id', $authUserId);
                },
            ]);
        }

        $result = $query
            ->orderByDesc('engagement')
            ->orderByDesc('tips.id')
            ->limit($limit)
            ->get();

        return $result;
    }

    /**
     * 인기 태그 가져오기 
     * tip_tag에서 가장 많이 
     */
    public static function getPopularTags(int $limit = 30) : Collection{
        $limit = max(1, min($limit, 100));

        $popularTags = Tag::query()
            ->visible()
            ->withCount('tips')
            ->orderByDesc('tips_count')
            ->limit($limit)
            ->get();

        return $popularTags;
    }


    /**
     * 모든 카테고리 목록 
     * 카테고리(타이틀, 설명), 각 카테고리에 등록된 팁개수
     */
    public static function getAllCategories() : Collection{
        $categories = Category::query()
           ->where('is_active', true)
           ->withCount([
                'tips as tips_count' => function($q){
                    $q->where('status', 'published')
                    ->where('visibility', 'public');
                }
           ])
           ->orderBy('sort_order')
           ->orderBy('id')
           ->get();

        return $categories;
    }

    /**
     * 공개된 팁 기준으로 태그가 가장 많이 사용된 카테고리 1개
     */
    public static function getTopTagCategory() : ?Category
    {
        $topCategory = Category::query()
            ->where('categories.is_active', true)
            ->leftJoin('tips', function ($join) {
                $join->on('tips.category_id', '=', 'categories.id')
                    ->where('tips.status', 'published')
                    ->where('tips.visibility', 'public');
            })
            ->leftJoin('tip_tag', 'tip_tag.tip_id', '=', 'tips.id')
            ->leftJoin('tags', function ($join) {
                $join->on('tags.id', '=', 'tip_tag.tag_id')
                    ->where('tags.is_blocked', false);
            })
            ->select('categories.id', 'categories.name')
            ->selectRaw('COUNT(tags.id) as tags_count')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('tags_count')
            ->orderBy('categories.id')
            ->first();

        return $topCategory;
    }
}
