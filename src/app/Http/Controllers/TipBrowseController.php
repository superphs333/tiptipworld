<?php

namespace App\Http\Controllers;

use App\Data\Tip\TipListFilters;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\FollowService;
use App\Services\SearchKeywordService;
use App\Services\Tip\TipReadService;
use App\Services\TipViewCounterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 공개 팁 조회 화면 전용 컨트롤러
 * 
 * [역할]
 * - 팁 상세 페이지 진입
 * - 팁 목록/검색 결과 페이지 진입
 * - 카테고리/태그별 리스트 페이지 진입
 * - 특정 사용자의 팁 피드 페이지 진입
 * 
 */
class TipBrowseController extends Controller
{
    public function __construct(
        private FollowService $followService,   // 프로필 피드에서 현재 로그인 사용자의 팔로우 여부 확인
        private TipReadService $tipReadService, // 팁 조회/검색/목록/상세용 데이터 조립
        private TipViewCounterService $tipViewCounter,  // 상세 페이지 조회수 중복 증가 방지
        private SearchKeywordService $searchKeywordService, // 검색어 기록 및 인기 검색어 집계 
    ) {
    }

    /**
     * 개별 팁 상세 페이지
     * 
     * [흐름]
     * 1. 서비스에서 상세 페이지 표시용 데이터와 접근 가능 여부를 함께 받아옴
     * 2. 비공개/미발행 글 등 접근 불가능 한 경우 alert후 이전 페이지 또는 홈으로 보냄
     * 3. 접근 가능하면 조회수를 1회 조건부 증가시킴
     * 4. 증가된 조회수를 detail 배열에도 다시 반영해 뷰와 DB 값을 맞춤
     */
    public function showPost(Request $request, int $tip_id)
    {
        $detailPageData = $this->tipReadService->getDetailPageData($tip_id, $request->user());

        // 서비스가 접근 불가로 판단되는 경우 
        if (! $detailPageData['is_accessible']) {
            return response(
                "<script>alert('접근할 수 없는 게시글입니다.');" .
                "if (window.history.length > 1) { window.history.back(); }" .
                "else { window.location.href = '/'; }</script>"
            );
        }

        $tip = $detailPageData['model'];

        // 같은 사용자가 짧은 시간 내 반복 조회에도 조회수가 계속 오르지 않도록 서비스 내부에서 쿠키/사용자 기준으로 중복 증가 방지
        $this->tipViewCounter->increaseIfNeeded($request, $tip);
        $detailPageData['detail']['metrics']['views'] = (int) $tip->view_count;
        $detailPageData['detail']['metrics']['views_text'] = number_format((int) $tip->view_count);

        return view('tips.view', [
            'viewMode' => 'detailView',
            'detail' => $detailPageData['detail'],
        ]);
    }

    /**
     * 팁 목록 메인 페이지
     * : 실제 목록 데이터를 미리 조회하지 않고, 팁 목록 화면을 렌더링한다는 진입점 역할만 수행
     * 필요 데이터는 프론트/뷰 내부 로직에서 추가로 사용될 수 있음 
     */
    public function tipList(Request $request)
    {
        return view('tips.view', [
            'viewMode' => 'tipList',
            'title' => '팁 목록',
        ]);
    }

    /**
     * 공개 팁 검색 결과 페이지 
     * 
     * [역할]
     * - URL query string으로 들어온 검색 조건을 해석해 표준화된 필터 객체로 만든다
     * - 사용자가 입력한 검색어를 인기 검색어를 집계 대상으로 기록
     * - 현재 조건에 맞는 공개 팁 목록을 조회
     * - 검색 화면에서 필요한 부가 데이터(카테고리 목록, 정렬 상태, 건수 표시용 메타 정보)를 함께 뷰로 전달
     * 
     * [처리 흐름]
     * 1. 요청값(category, query, tags, sort, per_page 등)을 TipListFilters로 정규화
     * 2. 검색어가 비어 있지 않고 첫 페이지 요청일 때만 검색어 기록
     * 3. 정규화된 필터 기준으로 팁 목록 조회
     * 4. 검색 화면 필터 UI에 필요한 카테고리 목록 조회
     * 5. 뷰가 바로 사용할 수 있도록 searchView 상태 묶음으로 구성해 반환 
     */
    public function tipSearch(Request $request)
    {
        // 요청 query string을 검색 전용 필터 객체로 변환 
        $filters = TipListFilters::forPublicSearch($request);

        // 인기 검색어 집계 
        if ($filters->query !== '' && max((int) $request->query('page', 1), 1) === 1) {
            $this->searchKeywordService->record($request, $filters->query);
        }

        // 정규화된 검색 조건으로 공개 팁 목록을 조회 
        $tipItems = $this->tipReadService->searchPublicList($filters, Auth::id());

        // 검색 결과 자체와는 별개로, 사용자가 검색 조건을 다시 조정할 수 있도록 선택지 데이터를 함께 내려줌. 
        $categories = $this->tipReadService->getTipFormCategories();

        return view('tips.view', [            
            'viewMode' => 'tipSearch', // 하나의 공통 뷰에서 어떤 화면 모드로 렌더링할지 ㄱ구분하는 값
            'title' => '팁 검색 결과',  // 브라우저 제목 또는 화면 상단 타이틀용 텍스트
            'categories' => $categories, // 카테고리 필터 드롭다운/탭 등에 사용할 목록
            'tipItems' => $tipItems,    // 실제 검색 결과 목록 
            'searchView' => [   // 검색 화면 전용 상태 묶음 
                'category' => $filters->category !== '' ? $filters->category : 'all', // 현재 선택된 카테고리 
                'sort' => $filters->sort->value,    // 현재 정렬 ㅣ준의 실제 값
                'query' => $filters->query, // 사용자가 입력한 검색어 원문
                'tags' => $filters->tagNames,   // 현재 검색 조건에 포함된 태그명 목록
                'sort_options' => [ // 정렬 선택 ui에 표시할 옵션 목록
                    'latest' => '최신순',
                    'popular' => '조회순',
                    'likes' => '좋아요순',
                    'bookmarks' => '북마크순',
                ],
                'total_count' => (int) $tipItems->total(), // 검색 결과 전체 건수
                'total_count_text' => number_format((int) $tipItems->total()),  // 화면 표시용 포맷된 전체 건수 
                'first_item' => $tipItems->firstItem(), // 현재 페이지에서 보여주는 시작 항목 번호
                'last_item' => $tipItems->lastItem(),  // 현재 페이지에서 ㅗ여주는 마지막 항목 번호
            ],
        ]);
    }

    /**
     * 카테고리별 / 태그별 팁 목록 화면 진입점
     */
    public function tipListBySort(Request $request, int $sort_id)
    {
        // 피드형 목록 화면에서 사용할 공통 필터 생성
        $filters = TipListFilters::forFeed($request);
        $viewerId = Auth::id();

        if ($request->routeIs('tips.category')) {
            $category = Category::findOrFail($sort_id);
            $pageData = $this->tipReadService->getCategoryPageData($sort_id, $filters, $viewerId);

            return view('tips.view', array_merge([
                'sort' => 'category',
                'viewMode' => 'tipListBySort',
                'site_title' => $category->name,
                'description' => $category->description,
                'listView' => $this->buildSortListViewData(
                    'category',
                    $filters,
                    $pageData['tipItems'],
                    (int) $pageData['allCount'],
                    (int) $pageData['todayTipCount'],
                    (float) $pageData['avgLikeCount'],
                    (float) $pageData['avgBookmarkCount'],
                ),
            ], $pageData));
        }

        $tag = Tag::query()->visible()->findOrFail($sort_id);
        $pageData = $this->tipReadService->getTagPageData($sort_id, $filters, $viewerId);

        return view('tips.view', array_merge([
            'sort' => 'tag',
            'viewMode' => 'tipListBySort',
            'site_title' => $tag->name,
            'description' => $tag->description,
            'listView' => $this->buildSortListViewData(
                'tag',
                $filters,
                $pageData['tipItems'],
                (int) $pageData['allCount'],
                (int) $pageData['todayTipCount'],
                (float) $pageData['avgLikeCount'],
                (float) $pageData['avgBookmarkCount'],
            ),
        ], $pageData));
    }

    /**
     * 특정 사용자의 팁 피드 화면에 필요한 데이터를 조립해 뷰로 전달
     * 
     * [처리흐름]
     * 1. 요청값에서 피드용 정렬/페이지 크기 필터를 만든다
     * 2. 현재 로그인 사용자와 피드 주인 정보를 조회 
     * 3. 조회자 기준으로 실제 노출 가능한 팁 수를 계산
     * 4. 팔로워/팔로잉 수와 팔로우 여부를 구함
     * 5. 프로필, 통계, 상위 카테고리/태그, 카드 목록을 묶어 뷰에 전달
     * 
     * [노출규칙]
     * - 본인 피드 : 자신의 모든 팁이 대상
     * - 타인 피드 : 공개(public) + 발행(published) 팁만 대상 
     */
    public function tipUserFeed(Request $request, int $user_id)
    {
        // 사용자 피드 화면에서 허용하는 정렬(sort), 페이지당 개수(per_page)를 표준화
        $filters = TipListFilters::forFeed($request);
        $viewerId = Auth::id(); // 현재 로그인 사용자 ID (비로그인 상태면 null)
        $user = User::findOrFail($user_id); // 피드 주인이 없으면 404 발생
        // 조회자 기준으로 실제 보이는 팁 개수를 계산 
        $tipsCount = $this->tipReadService->countUserVisibleTips($user, $viewerId); 
        // 프로필 헤더에 표시할 팔로워/팔로잉 수를 구함
        $followersCount = (int) $user->followerUsers()->count();
        $followingCount = (int) $user->followingUsers()->count();

        return view('tips.view', [
            'viewMode' => 'tipUserFeed',
            'site_title' => "User {$user_id}'s Feed",
            'myFeed' => $user_id === $viewerId, // 내 피드인지 여부
            'currentSort' => $filters->sort->value, // 현재 선택된 정렬 옵션
            // 프로필 영역에 필요한 최소 사용자 정보만 전달 
            'profileUser' => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'profile_image_url' => (string) $user->profile_image_url,
                'joined' => $user->created_at?->format('Y.m.d'),
            ],
            // 원본 숫자와 포맷된 문자열을 함께 넘겨 뷰에서 재가공을 줄임 
            'followersCount' => $followersCount,
            'followersCountText' => number_format($followersCount),
            'followingCount' => $followingCount,
            'followingCountText' => number_format($followingCount),
            
            // 현재 로그인 사용자가 이 피드 주인을 팔로우 중인지 확인
            'isFollowing' => (bool) $this->followService->isFollowing($viewerId, $user_id),

            // 피드에서 실제 노출 가능한 팁 개수 
            'tipsCount' => (int) $tipsCount,
            'tipsCountText' => number_format((int) $tipsCount),

            // 노출 가능한 팁만 기준으로 상위 카테고리/태그를 집계 
            'topCategories' => $this->tipReadService->getUserTipCategories($user, 5, $viewerId),
            'topTags' => $this->tipReadService->getUserTipTags($user, 5, $viewerId),

            // 카트 목록도 같은 공개 규칙을 적용해 가져옴 
            'tipItems' => $this->tipReadService->getUserFeedCards($user_id, $viewerId, $filters),
            'totalCount' => (int) $tipsCount,
            'totalCountText' => number_format((int) $tipsCount),
        ]);
    }

    /**
     * 카테고리별/태그별 목록 화면에서 공통으로 사용하는 뷰 데이터를 조립
     * 
     * [역할]
     * - 현재 목록이 category인지 tag인지 식별할 수 있는 텍스트 제공
     * - 현재 정렬 상태와 전체/오늘 등록 건수 제공
     * - 평균 좋아요/북마크 통계 제공
     * - 페이지네이션 객체인 경우 현재 페이지의 시작/끝 항목 번호 제공
     * 
     * [주의]
     * - $tipItems는 paginator일 수 있고 단순 컬렉션일 수 있으므로 firstItem(), lastItem() 메서드 존재 여부를 확인한 뒤 호출.
     */
    private function buildSortListViewData(
        string $sort,
        TipListFilters $filters,
        mixed $tipItems,
        int $allCount,
        int $todayTipCount,
        float $avgLikeCount,
        float $avgBookmarkCount,
    ): array {
        return [
            'sort_mode_text' => strtoupper($sort), // 현재 목록 모드(category/tag)를 화면 표시용 대문자 텍스트로 변환
            'current_sort' => $filters->sort->value, // 현재 사용자가 선택한 정렬 옵션
            'total_count' => $allCount, // 전체 목록 건수
            'total_count_text' => number_format($allCount), // 화면 표시용 포맷 문자열

            // 오늘 등록된 팁 ㅅ와 화면 표시용 포맷 문자열 
            'today_tip_count' => $todayTipCount,
            'today_tip_count_text' => number_format($todayTipCount),

            // 해당 분류의 평균 좋아요/북마크 수
            'avg_like_count' => $avgLikeCount,
            'avg_bookmark_count' => $avgBookmarkCount,

            // paginator의 경우 현재 페이지 시작/끝 번호를 제공, 단순 컬렉션이면 null 
            'first_item' => method_exists($tipItems, 'firstItem') ? $tipItems->firstItem() : null,
            'last_item' => method_exists($tipItems, 'lastItem') ? $tipItems->lastItem() : null,
        ];
    }
}
