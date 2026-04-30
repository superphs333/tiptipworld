<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyTipRequest;
use App\Http\Requests\StoreTipRequest;
use App\Http\Requests\UpdateTipRequest;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\Tip;
use App\Models\User;
use App\Services\FollowService;
use App\Services\SearchKeywordService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use App\Services\TipService;
use App\Services\TipWriteService;
use App\Services\TipViewCounterService;
use App\Services\UserNotificationService;
use Throwable;

/**
 * 팁 게시글과 관련된 주요 화면/기능 담당
 *
 * [담당기능]
 * - 관리자/프론트 글쓰기 폼 출력
 * - 탭 저장/수정/삭제
 * - 썸네일 이미지 저장/삭제
 * - 에디터 이미지 draft -> 실제 게시글 경로 정리
 * - 팁 상세 조회
 * - 팁 목록/검색/분류별 목록/사용자 피드
 * - 좋아요/북마크 토글
 */
class TipController extends Controller
{
    public function __construct(
        private FollowService $followService,
        private TipService $tipService,
        private TipWriteService $tipWriteService,
        private TipViewCounterService $tipViewCounter,
        private SearchKeywordService $searchKeywordService,
        private UserNotificationService $userNotificationService,
    )
    {
        
    }

    // 추가/업데이트 폼
    public function form(?Tip $tip = null)
    {
        $tabs = config('admin.tabs', []);
        $tab = 'tips';

        $categories = Category::query()
            ->forTipForm()
            ->get([
                'id',
                'name',
            ]);

        $data = $tip?->loadMissing('tags:id,name');
        $formAction = $tip === null ? route('tip.store') : route('tip.update', $tip);

        return view('admin.dashboard', [
            'tab' => $tab,
            'mode' => $tip === null ? 'create' : 'update',
            'formAction' => $formAction,
            'tip_id' => $tip?->id,
            'headerTitle' => $tabs[$tab] ?? 'Tips',
            'tabView' => 'admin.partials.tips.create',
            'data' => $data,
            'categories' => $categories,
        ]);
    }

    public function formFront(?Tip $tip = null)
    {
        $categories = Category::query()
            ->forTipForm()
            ->get([
                'id',
                'name',
            ]);
        $formAction = route('tip.store');
        $siteTitle = '글작성';
        $submitLabel = '게시하기';
        $data = null;

        if ($tip !== null) {
            $this->authorize('update', $tip);

            $data = $tip->loadMissing('tags:id,name');
            $siteTitle = '글수정';
            $submitLabel = '수정하기';
            $formAction = route('tip.update', $tip);
        }
        
        return view('tips.view', [
            'viewMode' => 'frontForm',
            'site_title' => $siteTitle,
            'categories' => $categories,
            'tip_id' => $tip?->id,
            'formAction' => $formAction,
            'submitLabel' => $submitLabel,
            'data' => $data,
        ]);
    }

    public function store(StoreTipRequest $request): RedirectResponse
    {
        try {
            $result = $this->tipWriteService->create(
                actor: $request->user(),
                attributes: $request->payload(),
                thumbnailFile: $request->thumbnailFile(),
                tagsPayload: $request->tagsPayload(),
                draftKey: $request->draftKey(),
            );
        } catch (Throwable $e) {
            report($e);

            return $this->tipFailureRedirect(
                $request->submitFrom(),
                '팁 저장 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.'
            );
        }

        if ($request->submitFrom() === 'admin') {
            $redirect = $this->tipAdminRedirect()
                ->with('success', '팁이 성공적으로 저장되었습니다.')
                ->withInput();

            return $this->withBlockedTagWarning($redirect, $result->warningMessage);
        }

        $redirect = redirect()
            ->route('tip.show', ['tip_id' => $result->tip->id])
            ->with('success', '팁이 성공적으로 저장되었습니다.');

        return $this->withBlockedTagWarning($redirect, $result->warningMessage);
    }

    public function update(UpdateTipRequest $request, Tip $tip): RedirectResponse
    {
        try {
            $result = $this->tipWriteService->update(
                actor: $request->user(),
                tip: $tip,
                attributes: $request->payload(),
                thumbnailFile: $request->thumbnailFile(),
                deleteThumbnail: $request->shouldDeleteThumbnail(),
                tagsPayload: $request->tagsPayload(),
                draftKey: $request->draftKey(),
            );
        } catch (Throwable $e) {
            report($e);

            return $this->tipFailureRedirect(
                $request->submitFrom(),
                '팁 수정 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
                $tip
            );
        }

        if ($request->submitFrom() !== 'admin') {
            $redirect = redirect()
                ->route('tip.show', ['tip_id' => $result->tip->id])
                ->with('success', '팁이 성공적으로 수정되었습니다.');

            return $this->withBlockedTagWarning($redirect, $result->warningMessage);
        }

        $redirect = $this->tipAdminRedirect()
            ->with('success', '팁이 성공적으로 수정되었습니다.')
            ->withInput();

        return $this->withBlockedTagWarning($redirect, $result->warningMessage);
    }

    public function destroy(DestroyTipRequest $request, Tip $tip): RedirectResponse
    {
        try {
            $this->tipWriteService->delete($request->user(), $tip);

            if ($request->submitFrom() !== 'admin') {
                return redirect()->route('home')
                    ->with('success', '팁이 성공적으로 삭제되었습니다.');
            }

            return $this->tipAdminRedirect()
                ->with('success', '팁이 성공적으로 삭제되었습니다.');
        } catch (Throwable $e) {
            report($e);

            return $this->tipFailureRedirect(
                $request->submitFrom(),
                '삭제 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.'
            );
        }
    }

    private function withBlockedTagWarning($redirect, ?string $warningMessage)
    {
        if ($warningMessage === null || trim($warningMessage) === '') {
            return $redirect;
        }

        return $redirect->with('warning', $warningMessage);
    }

    private function tipAdminRedirect(): RedirectResponse
    {
        return redirect()->route(
            'admin',
            array_merge(['tab' => 'tips'], session('tips.query', []))
        );
    }

    private function tipFailureRedirect(
        string $submitFrom,
        string $message,
        ?Tip $tip = null,
    ): RedirectResponse
    {
        if ($submitFrom === 'admin') {
            return $this->tipAdminRedirect()
                ->withInput()
                ->with('error', $message);
        }

        if ($tip !== null) {
            return redirect()
                ->route('tip.show', ['tip_id' => $tip->id])
                ->withInput()
                ->with('error', $message);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }

    /**
     * TIP ONE VIEW
     */
    public function showPost(Request $request, int $tip_id, TipViewCounterService $tipViewCounter){
        $tip = Tip::with([
            'category:id,name',
            'user:id,name,profile_image_path',
            'tags:id,name',
        ])->findOrFail($tip_id);
        $user = Auth::user();
        $is_admin = $user?->isAdmin() ?? false;
        $isTipOwner = (int) ($user?->id ?? 0) === (int) $tip->user_id;
        $canManageTip = $is_admin || $isTipOwner;
        $tip_status = $tip->status;
        $tip_visibility = $tip->visibility;

        /**
         * Tip url 용
         */
        // content 첫문장
        $plain = trim(strip_tags($tip->content));
        $first = preg_split('/[.!?]/u', $plain, 2)[0] ?? $plain;
        $tip_data_for_share = [
            "url_tip_title" => $tip->title,
            "url_tip_text" => mb_strimwidth($first, 0, 120, '...'),
            'url_tip_url' => route('tip.show', ['tip_id' => $tip->id]),

        ];

        /**
         * 팁 접근 설정
         * 본인 글 | 관리자 -> 모두 접근 가능
         * status -> published 
         * visibility -> public 만 가능 
         */
        if (!$is_admin && !$isTipOwner && ($tip_status !== 'published' || $tip_visibility !== 'public')) {
            return response(
                "<script>alert('접근할 수 없는 게시글입니다.');" .
                "if (window.history.length > 1) { window.history.back(); }" .
                "else { window.location.href = '/'; }</script>"
            );
        }

        $tip->loadMissing([
            'likedUsers:id,name,profile_image_path',
            'bookmarkedUsers:id,name,profile_image_path',
        ]);

        /**
         * 조회수
         * 같은 방문자(로그인/비로그인) 기준 24시간 중복 조회 방지
         */
        $tipViewCounter->increaseIfNeeded($request, $tip);

        return view('tips.view', [
            'viewMode' => 'detailView',
            'tip' => $tip,
            'tip_data_for_share' => $tip_data_for_share,
            'canManageTip' => $canManageTip,
        ]);
    }

    /**
     * 팁 리스트 페이지
     */
    public function tipList(Request $request){
        return view('tips.view', [
            'viewMode' => 'tipList',
            'title' => '팁 목록',
        ]);
    }

    /**
     * 팁 검색 결과 페이지
     */
    public function tipSearch(Request $request){
        $allowedSorts = ['latest', 'popular', 'likes', 'bookmarks'];
        $sort = (string) $request->query('sort', 'latest');
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'latest';
        }

        $category = (string) $request->query('category', 'all');
        $query = trim((string) $request->query('query', ''));

        if ($query !== '') {
            $this->searchKeywordService->record($request, $query);
        }

        $rawTags = $request->query('tags', []);
        if (is_string($rawTags)) {
            $rawTags = explode(',', $rawTags);
        } elseif (!is_array($rawTags)) {
            $rawTags = [];
        }

        $tags = array_values(array_filter(
            array_unique(array_map(static fn ($tag) => trim((string) $tag), $rawTags), SORT_STRING),
            static fn ($tag) => $tag !== ''
        ));

        $existingTags = empty($tags)
            ? collect()
            : Tag::query()->visible()->whereIn('name', $tags)->get(['id', 'name']);
        $tagIds = $existingTags->pluck('id')->map(static fn ($id) => (int) $id)->all();

        $authUserId = Auth::id();
        $countRelations = [
            'comments',
        ];
        if ($authUserId) {
            $countRelations['likedUsers as is_liked'] = function ($countQuery) use ($authUserId) {
                $countQuery->where('users.id', $authUserId);
            };
            $countRelations['bookmarkedUsers as is_bookmarked'] = function ($countQuery) use ($authUserId) {
                $countQuery->where('users.id', $authUserId);
            };
        }

        $baseQuery = Tip::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->with([
                'user:id,name,profile_image_path',
                'category:id,name',
                'tags:id,name',
            ])
            ->withCount($countRelations);

        if ($category !== '' && $category !== 'all') {
            $baseQuery->where('category_id', (int) $category);
        }

        if ($query !== '') {
            $baseQuery->where(function ($searchQuery) use ($query) {
                $searchQuery->where('title', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($userQuery) use ($query) {
                        $userQuery->where('name', 'like', "%{$query}%");
                    });
            });
        }

        if (!empty($tags)) {
            // "선택한 모든 태그 포함" 정책:
            // 입력 태그 중 실제 존재하지 않는 태그가 하나라도 있으면 결과는 빈 목록
            if (count($tagIds) !== count($tags)) {
                $baseQuery->whereRaw('1 = 0');
            } else {
                foreach ($tagIds as $tagId) {
                    $baseQuery->whereHas('tags', function ($tagQuery) use ($tagId) {
                        $tagQuery->where('tags.id', $tagId);
                    });
                }
            }
        }

        $listQuery = match ($sort) {
            'popular' => (clone $baseQuery)->orderByDesc('view_count')->orderByDesc('id'),
            'likes' => (clone $baseQuery)->orderByDesc('like_count')->orderByDesc('id'),
            'bookmarks' => (clone $baseQuery)->orderByDesc('bookmark_count')->orderByDesc('id'),
            default => (clone $baseQuery)->orderByDesc('created_at')->orderByDesc('id'),
        };

        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);
        $tipItems = $listQuery->paginate($perPage)->withQueryString();

        $categories = Category::query()
            ->forTipForm()
            ->get(['id', 'name']);

        /*
        인기글 저장 
        */
        if($query !== '' && max((int) $request->query('page', 1),1)===1){
            $this->searchKeywordService->record($request, $query);
        }

        return view('tips.view', [
            'viewMode' => 'tipSearch',
            'title' => '팁 검색 결과',
            'categories' => $categories,
            'tipItems' => $tipItems,
            'totalCount' => (int) $tipItems->total(),
            'initialCategory' => $category === '' ? 'all' : $category,
            'initialSort' => $sort,
            'initialQuery' => $query,
            'initialTags' => $tags,
        ]);
    }

    /**
     * 분류별 페이지
     */
    public function tipListBySort(Request $request, int $sort_id){
        $sort = "";
        $site_title = "";
        $description = "";
        $tipItems = Tip::query();
        $authUserId = Auth::id();
        if($request->routeIs('tips.category')){
            $sort = "category";
            $category = Category::findOrFail($sort_id);
            $site_title = $category->name;
            $description = $category->description;
            $baseQuery  = Tip::query()->where('category_id', $sort_id);

        }else if($request->routeIs('tips.tag')){
            $sort = "tag";
            $tag = Tag::query()->visible()->findOrFail($sort_id);
            $site_title = $tag->name;
            $description = $tag->description;
            $baseQuery  = Tip::query()->whereHas('tags', function($query) use($sort_id){
                $query->where('tags.id', $sort_id);
            });
        
        }
        $countRelations = [
            'comments',
            'likedUsers as likes_count',
            'bookmarkedUsers as bookmarks_count',
        ];

        if ($authUserId) {
            $countRelations['likedUsers as is_liked'] = function ($query) use ($authUserId) {
                $query->where('users.id', $authUserId);
            };
            $countRelations['bookmarkedUsers as is_bookmarked'] = function ($query) use ($authUserId) {
                $query->where('users.id', $authUserId);
            };
        }

        // status : published, visibility : public
        $baseQuery  = $baseQuery 
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->with(['user:id,name,profile_image_path'])
            ->withCount($countRelations);

        // 오늘 올라온 글
        $todayTipCount = (clone $baseQuery)
            ->whereDate('created_at', Date::today())
            ->count();
        // 평균 좋아요
        $avgLikeCount = round((float)((clone $baseQuery)->avg('like_count') ?? 0),1);
        // 평균 북마크
        $avgBookmarkCount = round((float)((clone $baseQuery)->avg('bookmark_count') ?? 0),1);
     
        // 정렬
        $sortKey = $request->query('sort','latest');
        $perPage = min(max((int)$request->query('per_page', 12), 1), 50);
        $listQuery = match ($sortKey){
            'popular' => (clone $baseQuery)->orderByDesc('view_count')->orderByDesc('id'),
            'likes' => (clone $baseQuery)->orderByDesc('like_count')->orderByDesc('id'),
            'bookmarks' => (clone $baseQuery)->orderByDesc('bookmark_count')->orderByDesc('id'),
            default => (clone $baseQuery)->orderByDesc('created_at')->orderByDesc('id'),
        };
        $tipItems = $listQuery->paginate($perPage)->withQueryString();


        return view('tips.view', [
            'sort' => $sort,
            'modelLabel' => 'model',
            'viewMode' => 'tipListBySort',
            'site_title' => $site_title,
            'description' => $description,
            'tipItems' => $tipItems,
            'todayTipCount' => $todayTipCount,
            'avgLikeCount' => $avgLikeCount,
            'avgBookmarkCount' => $avgBookmarkCount,
            'allCount' => $tipItems->total(),

        ]);


    }

    /**
     * 사용자 피드 페이지
     *
     * @param integer $user_id
     * @return void
     */
    public function tipUserFeed(int $user_id){
        $title = "User {$user_id}'s Feed";
        $currentSort = (string) request()->query('sort', 'latest');
        $return_data = [];
        /*
        User 정보 가져오기
        */
        $user = User::findOrFail($user_id);
        $myFeed = $user_id === Auth::id();
        $profile_image_url = $user->profile_image_url;
        $profile_name = $user->name;
        $follower_count = $user->followerUsers()->count();
        $following_count = $user->followingUsers()->count();
        $isFollowing = $this->followService->isFollowing(Auth::id(), $user_id);                
        $registration_date = $user->created_at;
        /*
        글 관련
        */
        $tips_count = $user->tips()->count();       
        $top5Category = $this->tipService->userTipsCategory($user_id,5);
        $top5Tag = $this->tipService->userTipTags($user_id,5);
        /*
        팁 가져오기
        */
        $tipItems = $this->tipService->getUserFeed($user_id);

        $return_data = [
            'viewMode' => 'tipUserFeed',
            'site_title' => $title,
            'myFeed' => $myFeed,
            'currentSort' => $currentSort,
            'profileUser' => [
                'id' => (int) $user->id,
                'name' => (string) $profile_name,
                'profile_image_url' => (string) $profile_image_url,
                'joined' => $registration_date?->format('Y.m.d'),
            ],
            'followersCount' => (int) $follower_count,
            'followingCount' => (int) $following_count,
            'isFollowing' => (bool) $isFollowing,
            'tipsCount' => (int) $tips_count,
            'topCategories' => $top5Category,
            'topTags' => $top5Tag,
            'tipItems' => $tipItems,
            'totalCount' => (int) $tipItems->count(),
        ];
        
        return view('tips.view', $return_data);
    }

    /**
     * 좋아요 기능
     */
    public function like(int $tip_id)
    {
        $tip = Tip::findOrFail($tip_id);
        $userId = Auth::id();

        $changed = $tip->likedUsers()->toggle($userId);
        $liked = !empty($changed['attached']);

        $likeCount = $tip->likedUsers()->count();
        $tip->update(['like_count' => $likeCount]);

        // 좋아요가 새로 생성된 경우에만 알림 전송
        $actor = Auth::user();
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
     * 북마크 기능
     */
    public function bookmark(int $tip_id){
        $tip = Tip::findOrFail($tip_id);
        $userId = Auth::id();

        $changed = $tip->bookmarkedUsers()->toggle($userId);
        $bookmarked = !empty($changed['attached']);

        $bookmarkCount = $tip->bookmarkedUsers()->count();
        $tip->update(['bookmark_count' => $bookmarkCount]);

        // 북마크가 새로 생성된 경우에만 알림 전송
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
