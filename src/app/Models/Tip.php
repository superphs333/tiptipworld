<?php

namespace App\Models;

use App\Enums\TipSort;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Category;
use App\Models\Tag;
use Carbon\Carbon;
use App\Services\Media\TipThumbnailService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tip extends Model
{
    protected $fillable = [
        'user_id',
        'update_user_id',
        'category_id',
        'title',
        'thumbnail',
        'content',
        'excerpt',
        'status',
        'visibility',
        'published_at',
        'tags_count',
        'view_count',
        'like_count',
        'bookmark_count',
        'comment_count',
    ];

    protected $appends = [
        'thumbnailUrl',
        'createdDate',
        'updatedDate',
        'userName',
        'categoryName',
        'displayTags',
    ];

    /**
    * 관계정의 
    */
    // Tip - Category (N:1) : tips.category_id -> caregories.id
    public function category(){
        return $this->belongsTo(Category::class);
    }

    // Tip - Tag (M:N) => pivot : tip_tag (tip_id, tag_id)
    public function tags() : BelongsToMany
    {
        return $this->allTags()->where('tags.is_blocked', false);
    }

    // Tip - Tag 전체 관계(저장/동기화용)
    public function allTags() : BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tip_tag','tip_id', 'tag_id')->withTimestamps();
    }

    // Tip - User 
    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    // Tip - Update user
    public function updatedBy() {
        return $this->belongsTo(User::class,'update_user_id');
    }

    // tip_likes 테이블 
    public function likedUsers() : BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'tip_likes',
            'tip_id',
            'user_id',
        ); 
    }

    // tip_bookmark 테이블
    public function bookmarkedUsers() : BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'tip_bookmark',
            'tip_id',
            'user_id',
        );
    }

    // comments
    public function comments() : HasMany
    {
        return $this->hasMany(Comment::class);
    }


    /**
     * 접근자 모음
     */
    // 썸네일 이미지 
    public function getThumbnailUrlAttribute() : string
    {
        if (blank($this->thumbnail)) {
            return asset('images/no-thumbnail.png');
        }

        return app(TipThumbnailService::class)->url($this->thumbnail);
    }

    // 생성일
    public function getCreatedDateAttribute() : string
    {
        return $this->created_at->format('Y-m-d H시i분s초');
    }
    // 수정일
    public function getUpdatedDateAttribute() : string
    {
        return $this->updated_at->format('Y-m-d H시i분s초');
    }
    // 작성자 이름
    public function getUserNameAttribute() : string
    {
        return optional($this->user)->name ?? '작성자 미상';
    }
    // 카테고리 이름
    public function getCategoryNameAttribute() : string
    {
        return optional($this->category)->name ?? '미분류';
    }
    // 태그 리스트
    public function getDisplayTagsAttribute() : Collection
    {
        return $this->relationLoaded('tags') ? $this->tags : collect();
    }
    // 좋아요 갯수
    public function getLikeCountAttribute($value) : int
    {
        if ($value !== null) {
            return (int) $value;
        }

        if (array_key_exists('likes_count', $this->attributes)) {
            return (int) $this->attributes['likes_count'];
        }

        return $this->relationLoaded('likedUsers') ? $this->likedUsers->count() : 0;
    }
   

    
    /**
     * get
     */
    // 좋아요 갯수
    public function isLikedBy(User $user) : bool
    {
        // 관계가 로드되어 있으면 컬렉션에서 즉시 판단(추가 쿼리 없음)
        if($this->relationLoaded('likedUsers')){
            return $this->likedUsers->contains('id',$user->id);
        }
        // 로드되어 있지 않으면 exists 쿼리 1번
        return $this->likedUsers()->where('user_id', $user->id)->exists();

    }

    // 북마크 여부
    public function isBookmarkedBy(User $user) : bool
    {
        if($this->relationLoaded('bookmarkedUsers')){
            return $this->bookmarkedUsers->contains('id', $user->id);
        }

        return $this->bookmarkedUsers()->where('user_id', $user->id)->exists();
    }

    /**
     * status가 published인 팁만 남긴다
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('tips.status', 'published');
    }

    /**
     * visibility가 public인 팁만 남긴다.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('tips.visibility', 'public');
    }

    /**
     * 공개 피드에 노출 가능한 팁만 남기는 스코프
     */
    public function scopePublicFeed(Builder $query): Builder
    {
        return $query->published()->publiclyVisible();
    }

    /**
     * 특정 작성자의 팁만 조회하도록 제한 
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('tips.user_id', $userId);
    }

    /**
     * 목록 카드/리스트에 필요한 관계를 미리 로드
     * : 작성자, 카테고리, 태그 
     */
    public function scopeWithPreviewRelations(Builder $query): Builder
    {
        return $query->with([
            'user:id,name,profile_image_path',
            'category:id,name',
            'tags:id,name',
        ]);
    }

    /**
     * 관리 화면에서 팁 목록을 조회할 때 필요한 연관 관계를 한 번에 미리 로드
     * 
     * [필요이유]
     * - 관리 목록에서는 팁 자체 정보뿐 아니라 카테고리, 작성자, 수정자 정보도 함께 자주 표시됨
     * 
     */
    public function scopeWithManagementRelations(Builder $query): Builder
    {
        return $query->with([
            'category:id,name', // 팁이 속한 카테고리 이름 표시용
            'tags:id,name', // 팁에 연결된 태그 목록 표시용
            'user:id,name', // 작성자 이름 
            'updatedBy:id,name', // 마지막 수정자 이름 표시용 
        ]);
    }

    /**
     * 현재 로그인 사용자가 이 팁을 좋아요/북마크 했는지 계산해서 
     * is_liked, is_bookmared count 컬럼처럼 붙임 
     */
    public function scopeWithViewerState(Builder $query, ?int $viewerId): Builder
    {
        if ($viewerId === null || $viewerId <= 0) {
            return $query;
        }

        return $query->withCount([
            'likedUsers as is_liked' => static function ($countQuery) use ($viewerId) {
                $countQuery->where('users.id', $viewerId);
            },
            'bookmarkedUsers as is_bookmarked' => static function ($countQuery) use ($viewerId) {
                $countQuery->where('users.id', $viewerId);
            },
        ]);
    }

    /**
     * 검색어(keyword)가 있을 때 팁 목록에 통합 검색 조건을 적용
     * 
     * [검색 대상]
     * - tips.title : 팁 제목
     * - user.name : 작성자 이름
     * 
     * [처리 흐름]
     * 1. 전달받은 검색어 앞뒤 공백을 제거
     * 2. 검색어가 있으면 제목 LIKE 검색 또는 작성자 LIKE 검색 조건을 묶어서 추가
     */
    public function scopeApplyKeyword(Builder $query, string $keyword): Builder
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return $query;
        }

        return $query->where(function ($searchQuery) use ($keyword) {
            $searchQuery->where('tips.title', 'like', "%{$keyword}%")
                ->orWhereHas('user', static function ($userQuery) use ($keyword) {
                    $userQuery->where('name', 'like', "%{$keyword}%");
                });
        });
    }

    /**
     * 제목(title) 컬럼에 대한 키워드 검색 조건을 쿼리에 추가
     * - 검색어가 비어 있지 않으면 tips.title LIKE '%키워드%' 조건을 추가
     * - 검색어가 비어 있으면 아무 조건도 추가하지 않고 기존 쿼리를 그대로 유지 
     */
    public function scopeApplyTitleKeyword(Builder $query, string $keyword): Builder
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return $query;
        }

        return $query->where('tips.title', 'like', "%{$keyword}%");
    }

    /**
     * 카테고리 조건을 쿼리에 선택적으로 추가하는 로컬 스코프
     * - 값
     *  - 비어 있음 | all => 카테고리 필터 적용x
     *  - uncategorized => 카테고리가 없는 데이터만 조회 
     *  - 숫자 문자열 => 해당 category_id
     *  - 잘못된 값 => 무시하고 기존 쿼리 그대로 유지 
     */
    public function scopeApplyCategory(Builder $query, ?string $category): Builder
    {
        $category = trim((string) $category);

        if ($category === '' || $category === 'all') {
            return $query;
        }

        if ($category === 'uncategorized') {
            return $query->whereNull('tips.category_id');
        }

        return ctype_digit($category)
            ? $query->where('tips.category_id', (int) $category)
            : $query;
    }

    /**
     * 전달된 태그 ID들을 모두 가지고 있는 팁만 조회하도록 조건을 추가하는 로컬 스코프.
     */
    public function scopeApplyTagIdsAll(Builder $query, array $tagIds): Builder
    {
        // 전달받은 태그 ID 목록을 검색에 안전한 형태로 정리
        $tagIds = collect($tagIds)
            ->map(static fn ($tagId) => (int) $tagId)
            ->filter(static fn ($tagId) => $tagId > 0)
            ->unique()
            ->values()
            ->all();

        // 정리 후 태그 id가 비어 있으면 태그 필터를 적용할 수 없으므로 기존 쿼리 그대로 반환 
        if ($tagIds === []) {
            return $query;
        }

        // tags 관계를 기준으로 조건을 검사 
        return $query->whereHas(
            'tags',
            static fn ($tagQuery) => $tagQuery->whereIn('tags.id', $tagIds),
            '=',
            count($tagIds)
        );
    }

    /**
     * status 값이 주어졌을 때 해당 상태의 팁만 조회하도록 조건을 추가 
     * - status 값이 비어 있지 않으면 tips.status=상태값 조건을 추가한다
     * - status 값이 비어 있지 않으면 아무 조건도 추가하지 않고 기존 쿼리를 그대로 유지 
     */
    public function scopeApplyStatus(Builder $query, ?string $status): Builder
    {
        $status = trim((string) $status);

        return $status !== ''
            ? $query->where('tips.status', $status)
            : $query;
    }

    /**
     * visibility 값이 주어졌을 때 해당 공개 범위의 팁만 조회하도록 조건을 추가하는 로컬 스코프 
     */
    public function scopeApplyVisibility(Builder $query, ?string $visibility): Builder
    {
       
        $visibility = trim((string) $visibility);

        return $visibility !== ''
            ? $query->where('tips.visibility', $visibility)
            : $query;
    }

    /**
     * 생성일을 기준으로 시작일~종료일 범위 조건을 쿼리에 추가 
     * - 값이 있으면 Carbon 객체로 파싱
     * - 날짜 형식이 잘못되면 -> [null,null] 반환
     * - 시작일이 종료일보다 늦으면 두 값을 서로 교환
     * - 날짜만 입력된 시작일 -> startOfDay() 적용
     * - 날짜만 입력된 종료일 -> endOfDay() 적용 
     * 
     * [반환값]
     * - [Carbon|null $startAt, Carbon|null $endAt]
     */
    public function scopeApplyDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        [$startAt, $endAt] = self::resolveDateRangeBounds($startDate, $endDate);

        if ($startAt === null && $endAt === null && (filled($startDate) || filled($endDate))) {
            return $query;
        }

        return $query
            ->when($startAt, static fn ($builder) => $builder->where('tips.created_at', '>=', $startAt))
            ->when($endAt, static fn ($builder) => $builder->where('tips.created_at', '<=', $endAt));
    }

    // 전달된 날자 문자열에 시각 정보(시:분)가 명시되어 있는지 판별 
    private static function hasExplicitTime(string $value): bool
    {
        return preg_match('/[T\s]\d{2}:\d{2}/', trim($value)) === 1;
    }

    /**
     * 시작일/종료일 문자열을 실제 쿼리 비교에 사용할 Carbon 범위 값으로 정리
     * 
     * [처리내용]
     * - 값이 있으면 Carbon 객체로 파싱
     * - 날짜 형식이 잘못되면 [null, null] 반환
     * - 시작일이 종료일보다 늦으면 두 값을 서로 교환
     * - 날짜만 입력된 시작일 -> startOfDay() 적용
     * - 날짜만 입력된 종료일 -> endOfDay() 적용
     */
    private static function resolveDateRangeBounds(?string $startDate, ?string $endDate): array
    {
        // 전달된 시작일/종료일 문자열을 Carbon 객체로 변환 
        try {
            $startAt = filled($startDate) ? Carbon::parse($startDate) : null;
            $endAt = filled($endDate) ? Carbon::parse($endDate) : null;
        } catch (\Throwable $e) {
            return [null, null];
        }

        // 시작일이 종료일보다 늦으면 -> 두 값을 자동으로 교환
        if ($startAt && $endAt && $startAt->gt($endAt)) {
            [$startAt, $endAt] = [$endAt, $startAt];
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // 시작일에 시간이 명시되지 않으면 해당 날짜의 시작 시각으로 보정
        if ($startAt && ! self::hasExplicitTime((string) $startDate)) {
            $startAt = $startAt->startOfDay();
        }

        if ($endAt && ! self::hasExplicitTime((string) $endDate)) {
            $endAt = $endAt->endOfDay();
        }

        return [$startAt, $endAt];
    }

    /**
     * 화면에서 선택한 정렬 옵션에 따라 목록 정렬 조건을 적용 
     * 
     */
    public function scopeSortByOption(Builder $query, TipSort $sort): Builder
    {
        return match ($sort) {
            TipSort::Popular => $query->orderByDesc('tips.view_count')->orderByDesc('tips.id'),
            TipSort::Likes => $query->orderByDesc('tips.like_count')->orderByDesc('tips.id'),
            TipSort::Bookmarks => $query->orderByDesc('tips.bookmark_count')->orderByDesc('tips.id'),
            default => $query->orderByDesc('tips.created_at')->orderByDesc('tips.id'),
        };
    }
    






    /**
     * Tip 목록 가져오기
     * 
     */
    public static function getTips(array $filters = [], int $perPage = 20){
        $q = Tip::query()
            ->with('category')
            ->with('tags:id,name')
            ->with(['user:id,name', 'updatedBy:id,name']);

        // 쿼리(검색어) : title or user
        if(isset($filters['query'])){
            $keyword = trim((string) $filters['query']);
            if($keyword !== ''){
                $q->where(function ($searchQ) use ($keyword) {
                    $searchQ->where('title','like',"%{$keyword}%")
                        ->orWhereHas('user', function ($uq) use ($keyword){
                            $uq->where('name','like',"%{$keyword}%");
                        });
                });
            }
        }

        // 카테고리
        $categoryFilter = $filters['category_id'] ?? null;
        if($categoryFilter !== null){
            $categoryFilter = trim((string) $categoryFilter);

            if($categoryFilter === 'uncategorized'){
                $q->whereNull('category_id');
            }elseif($categoryFilter !== '' && $categoryFilter !== 'all'){
                $q->where('category_id', $categoryFilter);
            }
        }

        // 상태
        if(array_key_exists('status', $filters)){
            $status = trim((string) $filters['status']);
            if($status !== ''){
                $q->where('status', $status);
            }
        }

        // 노출여부
        if(array_key_exists('visibility', $filters)){
            $visibility = trim((string) $filters['visibility']);
            if($visibility !== ''){
                $q->where('visibility', $visibility);
            }
        }


        // 기간
        if(isset($filters['start_date']) || isset($filters['end_date'])){
            [$startAt, $endAt] = self::resolveDateRangeBounds(
                $filters['start_date'] ?? null,
                $filters['end_date'] ?? null
            );

            // 쿼리
            $q->when($startAt, fn ($q) => $q->where('created_at', '>=', $startAt))
                ->when($endAt, fn ($q) => $q->where('created_at', '<=', $endAt));
        }

        

        // 정렬

        
        // 결과
        return $q->orderBy('id')->paginate($perPage)->withQueryString();


    }
}
