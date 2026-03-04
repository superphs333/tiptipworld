<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Tip;
use App\Models\Tag;
use App\Models\User;

class TipService
{
    public static function getUserFeed(int $target_id, ?string $sortKey = null){
        $sortKey = $sortKey ?? (string) request()->query('sort', 'latest');
        $viewerId = (int) Auth::id();
        $isOwner = $viewerId >0 && $viewerId === (int) $target_id;
        $countRelations = [];

        if ($viewerId > 0) {
            $countRelations['likedUsers as is_liked'] = static function ($query) use ($viewerId) {
                $query->where('users.id', $viewerId);
            };
            $countRelations['bookmarkedUsers as is_bookmarked'] = static function ($query) use ($viewerId) {
                $query->where('users.id', $viewerId);
            };
        }

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
            ])
            ->withCount($countRelations);
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
                'is_liked' => (bool) data_get($item, 'is_liked', false),
                'is_bookmarked' => (bool) data_get($item, 'is_bookmarked', false),
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
        $query = trim((string) $request->query('query', ''));
        $category = $request->query('category_id') ?? null;
        $status = $request->query('status');
        $visibility = $request->query('visibility');
        $tagIds = collect((array) $request->query('tags', []))
            ->map(static fn ($tagId) => (int) $tagId)
            ->filter(static fn ($tagId) => $tagId > 0)
            ->unique()
            ->values()
            ->all();

        // 정렬
        $sortKey = $request->query('sort', 'latest');
        $perPage = max(1, min((int) $request->query('per_page', 20), 100));

        $tips = Tip::query()
            ->where('user_id', Auth::id())
            ->with('category:id,name')
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
        // 태그 (선택한 태그 모두 포함)
        if ($tagIds !== []) {
            $tips->whereHas(
                'tags',
                static fn ($tagQ) => $tagQ->whereIn('tags.id', $tagIds),
                '=',
                count($tagIds)
            );
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

        return $resultTips
            ->paginate($perPage)
            ->withQueryString()
            ->through(static function ($item) {
                $visibilityRaw = data_get($item, 'visibility', 'public');
                $statusRaw = (string) data_get($item, 'status', 'draft');
                $dateRaw = data_get($item, 'created_at', data_get($item, 'updated_at'));

                return [
                    'id' => (int) data_get($item, 'id', 0),
                    'title' => (string) data_get($item, 'title', ''),
                    'thumbnail_url' => (string) data_get($item, 'thumbnail_url', data_get($item, 'thumbnailUrl', asset('images/no-thumbnail.png'))),
                    'category_id' => (int) data_get($item, 'category.id'),
                    'category_name' => (string) data_get($item, 'category.name', '미분류'),
                    'category' => (string) data_get($item, 'category.name', '미분류'),
                    'tags' => collect(data_get($item, 'tags', []))
                        ->map(static fn ($tag) => (string) data_get($tag, 'name', ''))
                        ->filter()
                        ->values()
                        ->all(),
                    'visibility' => match ($visibilityRaw) {
                        'private', 0, false => '비공개',
                        'unlisted' => '일부공개',
                        default => '공개',
                    },
                    'status' => match ($statusRaw) {
                        'draft' => '임시저장',
                        'published' => '게시',
                        'archived' => '보관',
                        'deleted' => '삭제',
                        default => $statusRaw !== '' ? $statusRaw : '-',
                    },
                    'date' => $dateRaw ? Carbon::parse($dateRaw)->format('y-m-d A h:i') : '-',
                    'view_count' => (int) data_get($item, 'view_count', 0),
                    'like_count' => (int) data_get($item, 'like_count', 0),
                    'comment_count' => (int) data_get($item, 'comment_count', 0),
                    'bookmark_count' => (int) data_get($item, 'bookmark_count', 0),
                ];
            });
        
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

    // 아카이브 가져오기 
    public function getMyArchive()
    {
        $user = Auth()->user();
        $userId = (int) Auth()->id();

        if (! $user || $userId === 0) {
            return collect();
        }

        $likedTips = $this->buildArchiveItems($user->likedTips(), $userId, 'like');
        $bookmarkedTips = $this->buildArchiveItems($user->bookmarkedTips(), $userId, 'bookmark');

        $likedIdMap = $likedTips
            ->pluck('id')
            ->mapWithKeys(static fn ($tipId) => [(int) $tipId => true]);

        $bookmarkedIdMap = $bookmarkedTips
            ->pluck('id')
            ->mapWithKeys(static fn ($tipId) => [(int) $tipId => true]);

        return $likedTips
            ->concat($bookmarkedTips)
            ->map(static function ($item) use ($likedIdMap, $bookmarkedIdMap) {
                $tipId = (int) data_get($item, 'id', 0);

                $item['is_liked'] = $likedIdMap->has($tipId);
                $item['is_bookmarked'] = $bookmarkedIdMap->has($tipId);

                return $item;
            })
            ->sortByDesc('id')
            ->values();
    }

    /**
     * 아카이브 화면에서 바로 사용할 수 있도록
     * 탭별 아이템과 집계 데이터를 한 번에 구성한다.
     */
    public function getMyArchiveViewData(): array
    {
        $archiveItems = $this->getMyArchive();

        $bookmarkItems = $archiveItems
            ->where('saved_type', 'bookmark')
            ->values();

        $likeItems = $archiveItems
            ->where('saved_type', 'like')
            ->values();

        return [
            'tabSets' => [
                'bookmarks' => [
                    'label' => '북마크',
                    'items' => $bookmarkItems->all(),
                    'meta' => $this->buildArchiveMeta($bookmarkItems),
                ],
                'likes' => [
                    'label' => '좋아요',
                    'items' => $likeItems->all(),
                    'meta' => $this->buildArchiveMeta($likeItems),
                ],
            ],
            'bookmarkCount' => $bookmarkItems->count(),
            'likeCount' => $likeItems->count(),
            'totalCount' => $archiveItems->count(),
        ];
    }

    /**
     * 좋아요/북마크 관계 쿼리를 받아, 
     * 아카이브 화면에서바로 쓸 수 있는 아이테 컬렉션으로 변환 
     */
    private function buildArchiveItems($relationQuery, int $userId, string $savedType)
    {
        return $relationQuery
            ->where(function ($query) use ($userId) {
                $query->where('tips.user_id', $userId)
                    ->orWhere(function ($visibleQuery) {
                        $visibleQuery->where('tips.visibility', 'public')
                            ->where('tips.status', 'published');
                    });
            })
            ->with([
                'category:id,name',
                'tags:id,name',
                'user:id,name,profile_image_path',
            ])
            ->orderByDesc('tips.id')
            ->get()
            ->map(fn ($item) => $this->formatArchiveItem($item, $savedType))
            ->values();
    }

    /**
     * 탭에 표시할 카테고리/태그 집계 데이터를 만든다.
     */
    private function buildArchiveMeta($items): array
    {
        $collection = collect($items)->values();

        return [
            'count' => $collection->count(),
            'categories' => $collection
                ->groupBy(static fn ($item) => (string) data_get($item, 'category_id', 'uncategorized'))
                ->map(static function ($group, $categoryId) {
                    $firstItem = $group->first();

                    return [
                        'id' => $categoryId !== '' ? $categoryId : 'uncategorized',
                        'name' => (string) data_get($firstItem, 'category_name', '미분류'),
                        'count' => $group->count(),
                    ];
                })
                ->sortByDesc('count')
                ->values()
                ->all(),
            'tags' => $collection
                ->flatMap(static fn ($item) => collect(data_get($item, 'tags', [])))
                ->filter(static fn ($tag) => (int) data_get($tag, 'id', 0) > 0)
                ->groupBy(static fn ($tag) => (int) data_get($tag, 'id', 0))
                ->map(static function ($group, $tagId) {
                    $firstTag = $group->first();

                    return [
                        'id' => $tagId,
                        'name' => (string) data_get($firstTag, 'name', '태그'),
                        'count' => $group->count(),
                    ];
                })
                ->sortByDesc('count')
                ->values()
                ->take(6)
                ->all(),
        ];
    }

    /**
     * Tip 모델 1개를 myarchive.blade.php에서 쓰기 쉬운 평탄한 배열 구조로 바꾸기 
     */
    private function formatArchiveItem($item, string $savedType): array
    {
        $categoryId = data_get($item, 'category_id');

        // [{id: 1, name: '태그명'}, ...] 형태로 정규화
        $tags = collect(data_get($item, 'tags', []))
            ->map(static function ($tag) {
                return [
                    'id' => (int) data_get($tag, 'id', 0),
                    'name' => (string) data_get($tag, 'name', ''),
                ];
            })
            ->filter(static fn ($tag) => $tag['id'] > 0)
            ->values();

        return [
            'id' => (int) data_get($item, 'id', 0),
            'category_id' => $categoryId ?? 'uncategorized',
            'category_name' => (string) data_get($item, 'category.name', '미분류'),
            'title' => (string) data_get($item, 'title', ''),
            'author' => (string) data_get($item, 'user.name', '작성자 미상'),
            'saved_type' => $savedType,
            'views' => (int) data_get($item, 'view_count', 0),
            'likes' => (int) data_get($item, 'like_count', 0),
            'comments' => (int) data_get($item, 'comment_count', 0),
            'bookmarks' => (int) data_get($item, 'bookmark_count', 0),
            'tags' => $tags->all(),
            'tag_ids' => $tags->pluck('id')->all(),
        ];
    }  

    
}
