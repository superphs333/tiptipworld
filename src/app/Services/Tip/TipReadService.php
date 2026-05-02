<?php

namespace App\Services\Tip;

use App\Data\Tip\TipListFilters;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Tip;
use App\Models\User;
use App\Support\Tip\TipPresenter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final class TipReadService
{
    public function __construct(
        private TipPresenter $presenter,
    ) {
    }

    public function searchPublicList(TipListFilters $filters, ?int $viewerId = null)
    {
        return $this->paginate(
            $this->publicSearchQuery($filters, $viewerId),
            $filters,
            'presentListItem',
        );
    }

    /**
     * 카테고리 페이지용 데이터 조회
     * : category id로 기본 쿼리를 만들고, 공통 목록/통계 조립 메서드로 넘긴다
     */
    public function getCategoryPageData(int $categoryId, TipListFilters $filters, ?int $viewerId = null): array
    {
        return $this->buildSortedPublicListData(
            $this->categoryBaseQuery($categoryId, $viewerId),
            $filters,
        );
    }

    /**
     * 태그 페이지용 데이터 조회
     * : tag id로 기본 쿼리를 만들고, 공통 목록/통계 조립 메서드로 넘긴다
     */
    public function getTagPageData(int $tagId, TipListFilters $filters, ?int $viewerId = null): array
    {
        return $this->buildSortedPublicListData(
            $this->tagBaseQuery($tagId, $viewerId),
            $filters,
        );
    }

    /**
     * 사용자 피드용 팁 목록을 카드 UI용 배열 컬렉션으로 변환해 반환
     * 
     * [특징]
     * - 본인 피드가 아니면 공개(public) + 발행(published) 글만 대상
     * - 목록 렌더링에 필요한 관계와 조회자 반응 상태를 미리 붙임
     * - 현재는 paginate가 아니라 get()기반 Collectio을 반환 
     */
    public function getUserFeedCards(int $targetUserId, ?int $viewerId, TipListFilters $filters): Collection
    {
        return $this->presentCollection(
            $this->userFeedBaseQuery($targetUserId, $viewerId, $filters),
            'presentCard',
        );
    }

    public function getMyTipRows(int $userId, TipListFilters $filters)
    {
        return $this->paginate(
            $this->myTipsBaseQuery($userId, $filters),
            $filters,
            'presentOwnerRow',
        );
    }

    public function getAdminTipRows(TipListFilters $filters)
    {
        return $this->paginate(
            $this->adminTipsBaseQuery($filters),
            $filters,
            'presentAdminRow',
        );
    }

    public function getTipFormCategories(): Collection
    {
        return Category::query()
            ->forTipForm()
            ->get(['id', 'name']);
    }

    /**
     * 사용자 객체 또는 사용자 ID를 받아 조회자 기준으로 보이는 팁 개수를 반환 
     */
    public function countUserVisibleTips(User|int $user, ?int $viewerId = null): int
    {
        return $this->countVisibleTipsForUser(
            $this->resolveUser($user),
            $viewerId,
        );
    }

    /**
     * 사용자 피드에서 조회자에게 보이는 팁만 기준으로 카테고리별 개수를 집계 
     */
    public function getUserTipCategories(User|int $user, ?int $limit = null, ?int $viewerId = null): Collection
    {
        return $this->buildUserTipCategories(
            $this->resolveUser($user),
            $limit,
            $viewerId,
        );
    }

    /**
     * 사용자 피드에서 조회자에게 보이는 팁만 기준으로 태그별 개수를 집계
     */
    public function getUserTipTags(User|int $user, ?int $limit = null, ?int $viewerId = null): Collection
    {
        return $this->buildUserTipTags(
            $this->resolveUser($user),
            $limit,
            $viewerId,
        );
    }

    public function getHomePopularCards(?int $viewerId = null, int $limit = 10): Collection
    {
        return $this->homePopularQuery($viewerId)
            ->limit($limit)
            ->get()
            ->map(fn ($tip) => $this->presenter->presentCard($tip))
            ->values();
    }

    /**
     * 팁 상세 페이지에 필요한 데이터를 조회 + 현재 조회자가 해당 팁을 볼 수 있는지 판단 + 화면 출령용 데이터 구성
     * 
     * @param int $tpiId 조회할 팁 ID
     * @param Authenticalable|null $viewer 현재 로그인한 사용자 객체, 비회원이면 null
     * 
     * @return array{
     *  model : mixed,
     *  is_accessible : bool,
     *  detail? : mixed
     * }
     */
    public function getDetailPageData(int $tipId, ?Authenticatable $viewer = null): array
    {
        // 현재 조회자의 사용자 ID 구하기 
        $viewerId = (int) ($viewer?->getAuthIdentifier() ?? 0);
        // 상세 페이지 조회용 기본 쿼리 실행 
        $tip = $this->detailBaseQuery($tipId, $viewerId > 0 ? $viewerId : null)->firstOrFail();
        // 조회자가 관리자인지 확인 
        $isAdmin = is_object($viewer) && method_exists($viewer, 'isAdmin')
            ? (bool) $viewer->isAdmin()
            : false;
        // 현재 조회자가 이 팁의 작성자인지 확인
        $isOwner = $viewerId > 0 && $viewerId === (int) $tip->user_id;
        // 현재 조회자가 이 팁을 관리할 수 있는 사람인지 판단 
        $canManage = $isAdmin || $isOwner;
        // 현재 조회자가 이 팁 상세페이지에 접근 가능한지 판단 
        $isAccessible = $canManage || ($tip->status === 'published' && $tip->visibility === 'public');

        if (! $isAccessible) {
            return [
                'model' => $tip,
                'is_accessible' => false,
            ];
        }

        // 접근 가능한 경우에만 추가 관계 데이터 로드 
        $tip->loadMissing([
            'likedUsers:id,name,profile_image_path',
            'bookmarkedUsers:id,name,profile_image_path',
        ]);

        return [
            'model' => $tip,
            'is_accessible' => true,
            'detail' => $this->presenter->presentDetail($tip, $canManage),
        ];
    }

    public function getMyArchivePageData(User|int $user): array
    {
        $user = $this->resolveUser($user);
        $bookmarkItems = $this->archiveItemsFor($user, $user->id, 'bookmarkedTips', 'bookmark');
        $likeItems = $this->archiveItemsFor($user, $user->id, 'likedTips', 'like');

        return [
            'tabSets' => [
                'bookmarks' => $this->archiveTab('북마크', $bookmarkItems),
                'likes' => $this->archiveTab('좋아요', $likeItems),
            ],
            'bookmarkCount' => $bookmarkItems->count(),
            'bookmarkCountText' => number_format($bookmarkItems->count()),
            'likeCount' => $likeItems->count(),
            'likeCountText' => number_format($likeItems->count()),
            'totalCount' => $bookmarkItems->count() + $likeItems->count(),
            'totalCountText' => number_format($bookmarkItems->count() + $likeItems->count()),
        ];
    }

    /**
     * 공개 목록 화면에서 공통으로 쓰는 결과 조립
     * 
     * [하는 일]
     * - 정렬 적용
     * - paginate 적용
     * - 각 Tip 모델을 뷰용 list item 형태로 변환
     * - 전체 건수 / 오늘 등록 건수 / 평균 좋아요 / 평균 북마크 계산 
     *      
     */
    private function buildSortedPublicListData(Builder $baseQuery, TipListFilters $filters): array
    {
        $tipItems = (clone $baseQuery)
            ->sortByOption($filters->sort)
            ->paginate($filters->perPage)
            ->withQueryString()
            ->through(fn (Tip $tip) => $this->presenter->presentListItem($tip));

        return [
            'tipItems' => $tipItems,
            'todayTipCount' => (clone $baseQuery)->whereDate('tips.created_at', Date::today())->count(),
            'avgLikeCount' => round((float) ((clone $baseQuery)->avg('tips.like_count') ?? 0), 1),
            'avgBookmarkCount' => round((float) ((clone $baseQuery)->avg('tips.bookmark_count') ?? 0), 1),
            'allCount' => (int) $tipItems->total(),
        ];
    }

    private function paginate(Builder $query, TipListFilters $filters, string $presenterMethod)
    {
        return $query
            ->paginate($filters->perPage)
            ->withQueryString()
            ->through(fn (Tip $tip) => $this->presenter->{$presenterMethod}($tip));
    }

    /**
     * 쿼리 결과 전체를 가져와 지정한 Presenter 메서드로 뷰용 배열 컬렉션으로 변환 
     */
    private function presentCollection(Builder $query, string $presenterMethod): Collection
    {
        return $query
            ->get()
            ->map(fn (Tip $tip) => $this->presenter->{$presenterMethod}($tip))
            ->values();
    }

    private function archiveItemsFor(User $user, int $viewerId, string $relationName, string $savedType): Collection
    {
        return $user->{$relationName}()
            ->where(function ($query) use ($viewerId) {
                $query->where('tips.user_id', $viewerId)
                    ->orWhere(function ($visibleQuery) {
                        $visibleQuery->where('tips.visibility', 'public')
                            ->where('tips.status', 'published');
                    });
            })
            ->withPreviewRelations()
            ->withViewerState($viewerId)
            ->orderByDesc('tips.id')
            ->get()
            ->map(fn (Tip $tip) => $this->presenter->presentArchiveItem($tip, $savedType))
            ->values();
    }

    private function archiveTab(string $label, Collection $items): array
    {
        return [
            'label' => $label,
            'items' => $items->all(),
            'meta' => $this->archiveMeta($items),
        ];
    }

    private function archiveMeta(Collection $items): array
    {
        return [
            'count' => $items->count(),
            'count_text' => number_format($items->count()),
            'categories' => $items
                ->groupBy(static fn ($item) => (string) data_get($item, 'filter.category_value', 'uncategorized'))
                ->map(static function (Collection $group, string $categoryValue) {
                    $firstItem = $group->first();
                    $count = $group->count();

                    return [
                        'id' => $categoryValue !== '' ? $categoryValue : 'uncategorized',
                        'name' => (string) data_get($firstItem, 'category.name', '미분류'),
                        'count' => $count,
                        'count_text' => number_format($count),
                    ];
                })
                ->sortByDesc('count')
                ->values()
                ->all(),
            'tags' => $items
                ->flatMap(static fn ($item) => collect(data_get($item, 'tags', [])))
                ->filter(static fn ($tag) => (int) data_get($tag, 'id', 0) > 0)
                ->groupBy(static fn ($tag) => (int) data_get($tag, 'id', 0))
                ->map(static function (Collection $group, int $tagId) {
                    $firstTag = $group->first();
                    $count = $group->count();

                    return [
                        'id' => $tagId,
                        'name' => (string) data_get($firstTag, 'name', '태그'),
                        'label' => (string) data_get($firstTag, 'label', '#태그'),
                        'count' => $count,
                        'count_text' => number_format($count),
                    ];
                })
                ->sortByDesc('count')
                ->values()
                ->take(6)
                ->all(),
        ];
    }

    /**
     * 사용자 피드 공통 스코프를 재사용해 노출 가능한 팁 수만 계산
     */
    private function countVisibleTipsForUser(User $user, ?int $viewerId = null): int
    {
        return $this->userTipScope($user->id, $viewerId)->count();
    }

    /**
     * 사용자 피드에서 보이는 팁만 대상으로 카테고리별 개수를 집계 
     */
    private function buildUserTipCategories(User $user, ?int $limit = null, ?int $viewerId = null): Collection
    {
        return $this->userTipScope($user->id, $viewerId)
            ->selectRaw('category_id, COUNT(*) as tips_count')
            ->groupBy('category_id')
            ->orderByDesc('tips_count')
            ->with('category:id,name') // 카테고리 이름 표시용 
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get()
            ->map(function ($item) {
                $isUncategorized = $item->category_id === null;

                return $this->presenter->presentTipCountItem([
                    'id' => $isUncategorized ? 'uncategorized' : (int) $item->category_id,
                    'name' => $isUncategorized
                        ? '미분류'
                        : (string) data_get($item, 'category.name', '미분류'),
                    'tips_count' => (int) data_get($item, 'tips_count', 0),
                ]);
            })
            ->values();
    }

    /**
     * 사용자 피드에서 보이는 팁만 대상으로 태그별 개수를 집계
     */
    private function buildUserTipTags(User $user, ?int $limit = null, ?int $viewerId = null): Collection
    {
        $isOwner = $viewerId !== null && $viewerId === $user->id;

        return Tag::query()
            ->visible()
            ->whereHas('tips', function ($query) use ($user, $isOwner) {
                $query->where('tips.user_id', $user->id);

                if (! $isOwner) { // 타인 프로필에서는 공개 피드에 노출 가능한 팁만 집계
                    $query->publicFeed();
                }
            })
            ->withCount([
                'tips as tips_count' => function ($query) use ($user, $isOwner) {
                    $query->where('tips.user_id', $user->id);

                    if (! $isOwner) {
                        $query->publicFeed();
                    }
                },
            ])
            ->having('tips_count', '>', 0)
            ->orderByDesc('tips_count')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get(['id', 'name'])
            ->map(fn ($tag) => $this->presenter->presentTipCountItem([
                'id' => (int) $tag->id,
                'name' => (string) $tag->name,
                'tips_count' => (int) data_get($tag, 'tips_count', 0),
            ], true))
            ->values();
    }

    private function publicSearchQuery(TipListFilters $filters, ?int $viewerId = null): Builder
    {
        $query = $this->publicPreviewQuery($viewerId)
            ->applyCategory($filters->category)
            ->applyKeyword($filters->query);

        $this->applyPublicTagNames($query, $filters->tagNames);

        return $query->sortByOption($filters->sort);
    }

    /**
     * 특정 카테고리에 속한 공개 팁 목록의 베이스 쿼리
     */
    private function categoryBaseQuery(int $categoryId, ?int $viewerId = null): Builder
    {
        return $this->publicPreviewQuery($viewerId)
            ->where('tips.category_id', $categoryId);
    }

    /**
     * 특정 태그가 달린 공개 팁 목록의 베이스 쿼리 
     */
    private function tagBaseQuery(int $tagId, ?int $viewerId = null): Builder
    {
        return $this->publicPreviewQuery($viewerId)
            ->whereHas('tags', fn ($query) => $query->where('tags.id', $tagId));
    }

    /**
     * 사용자 피드 목록용 기본 쿼리 
     * - 항상 대상 사용자의 글만 조회
     * - 본인 피드가 아니면 publicFeed()를 적용해 공개 글만 남김
     * - 카드 렌더링용 관계와 조회자 반응 상태를 미리 붙임
     */
    private function userFeedBaseQuery(int $targetUserId, ?int $viewerId, TipListFilters $filters): Builder
    {
        $isOwner = $viewerId !== null && $viewerId === $targetUserId;

        $query = Tip::query()
            ->ownedBy($targetUserId)
            ->when(! $isOwner, static fn ($query) => $query->publicFeed());

        return $this->applyPreviewState($query, $viewerId)
            ->sortByOption($filters->sort);
    }

    private function myTipsBaseQuery(int $userId, TipListFilters $filters): Builder
    {
        return Tip::query()
            ->ownedBy($userId)
            ->withPreviewRelations()
            ->applyCategory($filters->category)
            ->applyTitleKeyword($filters->query)
            ->applyTagIdsAll($filters->tagIds)
            ->applyStatus($filters->status)
            ->applyVisibility($filters->visibility)
            ->sortByOption($filters->sort);
    }

    private function adminTipsBaseQuery(TipListFilters $filters): Builder
    {
        return Tip::query()
            ->withManagementRelations()
            ->applyCategory($filters->category)
            ->applyKeyword($filters->query)
            ->applyStatus($filters->status)
            ->applyVisibility($filters->visibility)
            ->applyDateRange($filters->startDate, $filters->endDate)
            ->orderBy('tips.id');
    }

    private function applyPublicTagNames(Builder $query, array $tagNames): void
    {
        if ($tagNames === []) {
            return;
        }

        $tagIds = Tag::query()
            ->visible()
            ->whereIn('name', $tagNames)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if (count($tagIds) !== count($tagNames)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->applyTagIdsAll($tagIds);
    }

    /**
     * 사용자 팁 통계 계산에 공통으로 쓰는 스코프 
     * - 항상 특정 사용자의 팁만 대상
     * - 조회자가 본인이 아니면 공개(publicFeed) 조건을 강제 적용 
     * 
     */
    private function userTipScope(int $userId, ?int $viewerId = null): Builder
    {
        $query = Tip::query()->ownedBy($userId);

        if ($viewerId === null || $viewerId !== $userId) {
            $query->publicFeed();
        }

        return $query;
    }

    private function homePopularQuery(?int $viewerId = null): Builder
    {
        return $this->publicPreviewQuery($viewerId)
            ->select('tips.*')
            ->selectRaw('
                (tips.view_count * 1)
                + (tips.like_count * 3)
                + (tips.comment_count * 5)
                + (tips.bookmark_count * 8)
                as engagement
            ')
            ->orderByDesc('engagement')
            ->orderByDesc('tips.id');
    }

    private function detailBaseQuery(int $tipId, ?int $viewerId = null): Builder
    {
        return Tip::query()
            ->whereKey($tipId)
            ->with([
                'category:id,name',
                'user:id,name,profile_image_path',
                'tags:id,name',
            ])
            ->withViewerState($viewerId);
    }

    /**
     * 공개 목록 화면용 공통 베이스 쿼리
     * - 공개 상태의 팁만 조회
     * - 카드/리스트 표시용 관계 데이터(user/category/tags) 미리 로드
     * - 로그인 사용자가 있으면 is_lied, is_bookmarked 상태도 함께 계산 
     */
    private function publicPreviewQuery(?int $viewerId = null): Builder
    {
        return $this->applyPreviewState(
            Tip::query()->publicFeed(),
            $viewerId,
        );
    }

    /**
     * 목록 화면에 필요한 관계/조회자 상태를 쿼리에 추가 
     */
    private function applyPreviewState(Builder $query, ?int $viewerId = null): Builder
    {
        return $query
            ->withPreviewRelations()
            ->withViewerState($viewerId);
    }

    /**
     * User 객체 또는 ID를 받아 항상 User 모델 인스턴스로 정규화 
     */
    private function resolveUser(User|int $user): User
    {
        if ($user instanceof User) {
            return $user;
        }

        return User::findOrFail($user);
    }
}
