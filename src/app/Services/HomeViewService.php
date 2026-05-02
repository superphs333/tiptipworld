<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class HomeViewService
{
    /**
     * 홈 화면용 인기 태그 목록 조회
     * (홈에서 지금 많이 쓰이는 태그를 보여주기 위함)
     * 
     * [기준]
     * - 차단되지 않은 태그만 대상
     * - 공개된(public) + 발행된(published) 팁에 연결된 태그만 대상
     * - 각 태그가 몇 개의 공개 팁에 연결되어 있는지  tips_count로 집계 
     * - 많이 사용된 태그 순으로 정렬 
     */
    public static function getPopularTags(int $limit = 30) : EloquentCollection{
        $limit = max(1, min($limit, 100));

        $popularTags = Tag::query()
            ->visible() // 차단되지 않은 태그만 조회 
            ->whereHas('tips', function ($q) { // 공개된 팁에 실제로 연결된 태그만 남김 
                $q->publicFeed();
            })
            ->withCount([
                'tips as tips_count' => function ($q) {
                    $q->publicFeed();
                }
            ])
            ->orderByDesc('tips_count')
            ->limit($limit)
            ->get();

        return $popularTags;
    }


    /**
     * 모든 카테고리 목록 
     * 카테고리(타이틀, 설명), 각 카테고리에 등록된 팁개수
     */
    public static function getAllCategories() : EloquentCollection{
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
     * 홈 상단 통계 데이터 조립
     * 
     * [목적]
     * - 뷰에서 직접 계산하지 않도록 총 팁수 / 팁 수 1위 카테고리 / 사용량 1위 태그를 출력 전용 배열로 만들어 줌
     */
    public static function getHeroStats(Collection $categories, Collection $popularTags): array
    {
        // 전제 공개 팁 수 
        $totalTips = (int) $categories->sum('tips_count');

        // 팁 수가 가장 많은 카테고리 1개 
        $topCategory = $categories->sortByDesc('tips_count')->first();
        // 없을 수도 있으므로 기본값 처리
        $topCategoryCount = (int) data_get($topCategory, 'tips_count', 0);
        // 카운트가 0보다 클 때만 실제 이름 사용, 아니면 집계 중
        $topCategoryName = $topCategoryCount > 0
            ? (string) data_get($topCategory, 'name', '집계 중')
            : '집계 중';
        // 인기 태그 목록은 이미 사용량 순으로 정렬되어 있으므로 첫 번재가 1위 태그
        $topTag = $popularTags->first();
        // tips_count 우선 사용, 없으면 usage_count fallback
        $topTagCount = (int) data_get($topTag, 'tips_count', data_get($topTag, 'usage_count', 0));
        $topTagName = $topTagCount > 0
            ? '#' . ltrim((string) data_get($topTag, 'name', '태그'), '#')
            : '집계 중';

        return [
            // 전체 팁 수
            'total_tips' => $totalTips, 
            'total_tips_text' => number_format($totalTips),

            // 팁 수 1위 카테고리 정보
            'top_category' => [
                'name' => $topCategoryName,
                'count' => $topCategoryCount,
                'count_text' => number_format($topCategoryCount),
            ],

            // 사용량 1위 태그 정보
            'top_tag' => [
                'name' => $topTagName,
                'count' => $topTagCount,
                'count_text' => number_format($topTagCount),
            ],
        ];
    }
}
