<?php

namespace App\Http\Controllers;

use App\Services\HomeViewService;
use App\Services\Tip\TipReadService;

class HomeController extends Controller
{
    public function __construct(
        private TipReadService $tipReadService,
    ) {
    }

    public function index()
    {
        // 홈 인기글 카드 데이터 조회 (좋아요/북마크 상태까지 함께 반영된 카드 데이터를 가져옴)
        $popular_tips = $this->tipReadService->getHomePopularCards(auth()->id());
        // 홈 인기 태그 목록 조회 (공개된 팁 기준 + 사용 가능한 태그 기준으로 정렬된 태그 컬렉션)
        $popular_tags = HomeViewService::getPopularTags();
        // 홈 카테고리 목록 조회 (활성 카테고리만 가져오고, 각 카테고리의 공개 팁 개수(tips_count)도 포함)
        $all_categories = HomeViewService::getAllCategories();
        // 홈 상단 통계용 데이터 조립 (이미 가져온 categoreis, popular_tags를 이용 => 총 팁 수 / 1위 카테고리 / 1위 태그 정보를 뷰용 배열로 만듦)
        $hero_stats = HomeViewService::getHeroStats($all_categories, $popular_tags);

        return view('home.home', [
            'popular_tips' => $popular_tips,
            'popular_tags' => $popular_tags,
            'categories' => $all_categories,
            'hero_stats' => $hero_stats,
        ]);
    }
}
