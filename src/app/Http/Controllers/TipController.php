<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use App\Services\FileStorageService;
use App\Services\TipViewCounterService;
use App\Services\TipService;
use App\Services\FollowService;
use App\Models\Tip;
use App\Models\Tag;
use App\Models\Comment;
use App\Models\User;

class TipController extends Controller
{
    public function __construct(private FollowService $followService, private TipService $tipService, private TipViewCounterService $tipViewCounter)
    {
        
    }
    private $validatedArr = [
            'category_id' => ['nullable', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'thumbnail' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,archived,deleted'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'thumbnail_delete' => ['nullable', 'in:true,false'],
    ];

    // 추가/업데이트 폼
    public function form(?int $tip_id = null)
    {
        $tabs = config('admin.tabs', []);
        $tab = 'tips';
    
        $categories = Category::query()
            ->forTipForm()
            ->get([
                'id',
                'name',
            ]);

        //$formAction = is_null($tip_id) ? 'tip.store' : 'tip.update';
        $formAction = is_null($tip_id) ? route('tip.store') : route('tip.update', $tip_id);

        $data = !is_null($tip_id) ? Tip::find($tip_id) : null;

        return view('admin.dashboard', [
            'tab' => $tab,
            'mode' => is_null($tip_id) ? 'create' : 'update',
            'formAction' => $formAction,
            'tip_id' => $tip_id,
            'headerTitle' => $tabs[$tab] ?? 'Tips',
            'tabView' => 'admin.partials.tips.create',
            'data' => $data,
            'categories' => $categories
        ]);
    }

    public function formFront(?int $tip_id = null){
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

        if (!is_null($tip_id)) {
            $data = Tip::with('tags:id,name')->findOrFail($tip_id);

            if (!$this->canManageTip($data)) {
                abort(403);
            }

            $siteTitle = '글수정';
            $submitLabel = '수정하기';
            $formAction = route('tip.update', ['tip_id' => $tip_id]);
        }
        
        return view('tips.view', [
            'viewMode' => 'frontForm',
            'site_title' => $siteTitle,
            'categories' => $categories,
            'tip_id' => $tip_id,
            'formAction' => $formAction,
            'submitLabel' => $submitLabel,
            'data' => $data,
        ]);
    }

    public function saveTip(Request $request, FileStorageService $storage){
        $validated = $request->validate($this->validatedArr);
        $userId = Auth::id();
        $created_at = Date::now();
        $validated['user_id'] = $userId;
        $validated['created_at'] = $created_at;
        


        if ($request->hasFile('thumbnail')) {
            $tip_thumbnail_url = $storage->storeUploaded($validated['thumbnail'], 'tip-cover');
            $validated['thumbnail'] = $tip_thumbnail_url;
        }

        
        /**
         * Tip 저장
         */
        $tip = Tip::create($validated);
        

        /**
         * 태그 저장 (in tips, tip_tag)
         */
        // JSON 문자열("['tag1', 'tag2']")을 PHP 배열로 변환
        $tagNames = $request->filled('tags') ? json_decode($request->input('tags'), true) : [];

        if(!empty($tagNames)){
            $this->saveTags($tagNames, $tip->id);
        }

        $submitFrom = (string) $request->input('submit_from', '');

        if ($submitFrom === 'admin') {
            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tips'], session('tips.query', []))
            )->with('success', '팁이 성공적으로 저장되었습니다.')
            ->withInput();
        }

        return redirect()->route('tip.show', ['tip_id' => $tip->id])
            ->with('success', '팁이 성공적으로 저장되었습니다.');

    }

    public function updateTipPost(Request $request,int $tip_id , FileStorageService $storage){
        $target_tip = Tip::findOrFail($tip_id);

        if (!$this->canManageTip($target_tip)) {
            abort(403);
        }

        $validated = $request->validate($this->validatedArr);
        $validated['update_user_id'] = Auth::id();
        $validated['updated_at'] = Date::now();

        /**
        * 썸네일 저장 (name : thumbnail) 
        */
        $thumbnail_deleted = $request->boolean('thumbnail_delete');
        if ($request->hasFile('thumbnail')) {
            $storage->deleteIfExists($target_tip->thumbnail);
            $tip_thumbnail_url = $storage->storeUploaded($validated['thumbnail'], 'tip-cover');
            $validated['thumbnail'] = $tip_thumbnail_url;
        }
        if($thumbnail_deleted){
            $storage->deleteIfExists($target_tip->thumbnail);
            $validated['thumbnail'] = null;
        }
            

        /**
         * 태그
         */
        $tagNames = $request->filled('tags') ? json_decode($request->input('tags'), true) : [];
        if(!empty($tagNames)){
            $this->saveTags($tagNames, $tip_id);
        }


        /**
         * 수정
         */
        $target_tip->update($validated);

        $submitFrom = (string) $request->input('submit_from', '');

        if ($submitFrom !== 'admin') {
            return redirect()->route('tip.show', ['tip_id' => $target_tip->id])
                ->with('success', '팁이 성공적으로 수정되었습니다.');
        }

        return redirect()->route(
            'admin',
            array_merge(['tab' => 'tips'], session('tips.query', []))
        )->with('success', '팁이 성공적으로 수정되었습니다.')
        ->withInput();

    }

    public function destroy(Request $request, int $tip_id, FileStorageService $storage)
    {
        $submitFrom = (string) $request->input('submit_from', 'admin');
        $target_tip = Tip::findOrFail($tip_id);

        if (!$this->canManageTip($target_tip)) {
            abort(403);
        }

        try {
            $storage->deleteIfExists($target_tip->thumbnail);
            $target_tip->delete();

            if ($submitFrom !== 'admin') {
                return redirect()->route('home')
                    ->with('success', '팁이 성공적으로 삭제되었습니다.');
            }

            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tips'], session('tips.query', []))
            )->with('success', '팁이 성공적으로 삭제되었습니다.');
        } catch (\Throwable $e) {
            if ($submitFrom !== 'admin') {
                return redirect()->route('home')
                    ->with('error', '삭제 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
            }

            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tips'], session('tips.query', []))
            )->with('error', '삭제 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
        }
    }

    private function saveTags($tagNames, $tip_id){
        $tagIds = [];
        foreach($tagNames as $tagName){
            $tag = Tag::firstOrCreate(['name'=>$tagName]);
            $tagIds[] = $tag->id;
        }
        // 팁모델에 연결
        $tip = Tip::findOrFail($tip_id);
        $tip->tags()->sync($tagIds);
    }

    private function canManageTip(Tip $tip): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $isAdmin = $user->isAdmin();
        $isOwner = (int) $user->id === (int) $tip->user_id;

        return $isAdmin || $isOwner;
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
            $tag = Tag::findOrFail($sort_id);
            $site_title = $tag->name;
            $description = $tag->description;
            $baseQuery  = Tip::query()->whereHas('tags', function($query) use($sort_id){
                $query->where('id', $sort_id);
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
        $top5Category = $user->tips()  // 내 글들 중에서 가장 많이 나오는 카테고리 
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as tips_count')
            ->groupBy('category_id')
            ->orderByDesc('tips_count')
            ->with('category:id,name')
            ->limit(5)
            ->get()
            ->map(static function ($item) {
                return [
                    'id' => (int) $item->category_id,
                    'name' => (string) data_get($item, 'category.name', '미분류'),
                    'tips_count' => (int) data_get($item, 'tips_count', 0),
                ];
            });
        $top5Tag = Tag::query()
            ->withCount([
                'tips as tips_count' =>  fn ($q) => $q->where('tips.user_id', $user_id),
            ])
            ->having('tips_count','>',0)
            ->orderByDesc('tips_count')
            ->limit(5)
            ->get(['id', 'name'])
            ->map(static function ($item) {
                return [
                    'id' => (int) $item->id,
                    'name' => (string) $item->name,
                    'tips_count' => (int) data_get($item, 'tips_count', 0),
                ];
            });
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

        return response()->json([
            'success' => true,
            'tip_id' => $tip->id,
            'bookmarked' => $bookmarked,
            'bookmark_count' => $bookmarkCount,
        ]);


    }

}
