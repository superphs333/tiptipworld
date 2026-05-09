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

/**
 * TipReadService
 * - 어떤 화면인지 context로 구분
 * - context에 맞는 기본 쿼리 생성
 * - 필터 적용
 * - 정렬 적용
 * - 페이지네이션 또는 컬렉션 조회
 * - Pretenter를 통해 화면 출력용 데이터로 변환
 * - View에서 쓰기 쉬운 payload 배열 구성 
 * 
 * [역할]
 * - 팁 목록 조회
 * - 팁 상세 조회
 * - 사용자 피드 조회
 * - 내 팁 관리 목록 조회
 * - 관리자 팁 목록 조회
 * - 북마크/좋아요 보관함 조회
 * - 사용자별 카테고리/태그 통계 조회
 * 
 * [구조적 특징]
 * config 기반으로 동작
 * ex) config/tip_lists.php 안에 contexts 설정이 있고, context마다 query_method, presenter, result_mode, meta_profile 같은 값이 정의 되어 있음.
 * => context 설정에 맞춰, 쿼리생성 -> 결과 조회 -> Pretenter 변환 -> 화면용 payload 생성 
 */
final class TipReadService
{
    public function __construct(
        private TipPresenter $presenter, // 화면에 쓰기 좋은 배열/DTO 형태로 바꿈.
    ) {
    }

    /**
     * context 기반으로 팁 목록 화면에 필요한 전체 데이터를 조회.
     * 
     * @param string $context : 조회하려는 목록 화면의 종류
     * @param array  $options : context별 추가 옵션
     * 
     * @return array : View에서 사용할 최종 데이터 배열
     */
    public function getListData(string $context, array $options = []): array
    {
        // config에서 context별 설정 가져옴
        $contextConfig = $this->contextConfig($context);
        // context에 맞는 Eloquent Builder를 생성 (options을 바탕으로 기본 쿼리 만듦)
        $query = $this->buildContextQuery($context, $contextConfig, $options);
        // 쿼리 결과를 paginate | collection 방식으로 가져옴 (화면 출력용 item으로 변환)
        $items = $this->resolveContextItems($query, $contextConfig, $options);

        // 최종적으로 View에 넘길 배열 구성 (tipItmes+화면에 필요한 메타 정보)
        return $this->buildContextPayload(
            $context,
            $contextConfig,
            $items,
            $query,
            $options,
        );
    }

    /**
     * 사용자 객체 또는 사용자 ID를 받아 조회자 기준으로 보이는 팁 개수를 반환 
     * (조회자가 본인인지 타인인지에 따라 달라짐)
     * 
     * [노출기준]
     * - 본인 -> 자신의 draft/private 포함 가능
     * - 타인 -> published + public 글만 count 
     * 
     * @param User|int $user : User 모델 또는 사용자 ID
     * @param int|null $viewerId : 현재 조회자 ID
     * 
     * @return int : 조회자 기준으로 보이는 팁 개수
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

    /**
     * 내 보관함 페이지 데이터를 구성(북마트 한 팁, 좋아요한 팁)
     */
    public function getMyArchivePageData(User|int $user): array
    {
        $user = $this->resolveUser($user);
        // 북마크한 팁 목록 
        $bookmarkItems = $this->archiveItemsFor($user, $user->id, 'bookmarkedTips', 'bookmark');
        // 좋아요한 팁 목록 
        $likeItems = $this->archiveItemsFor($user, $user->id, 'likedTips', 'like');

        // View에서 바로 사용할 수 있도록 탭 구조와 개수 텍스트를 함께 반환
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
     * 팁 작성/수정 폼에서 사용할 카테고리 목록
     * 
     */
    public function getTipFormCategories(): Collection
    {
        return Category::query()
            ->forTipForm()
            ->get(['id', 'name']);
    }

    /**
     * context 이름으로 config 설정 가져옴
     * 
     * @param string $context : 목록 화면 종류 
     * @return array : context별 설정 배열
     */
    private function contextConfig(string $context): array
    {
        $config = config("tip_lists.contexts.{$context}");

        // config가 배열이 아니면 잘못된 context로 판단
        if (! is_array($config)) {
            throw new \InvalidArgumentException("Unknown tip list context [{$context}]");
        }

        return $config;
    }

    /**
     * context에 맞는 Eloquent Bilder를 생성 
     * - config에서 query_method 이름을 가져옴 
     * - context별로 필요한 인자를 검증
     * - 해당 query 메서드를 동적으로 호출 
     * 
     * @param string $context : 현재 목록 context
     * @param array $contextConfig : context 설정
     * @param array $options : 필터, 사용자, scope_id, viewer_id 등의 옵션
     * @return Builder : context에 맞게 만들어진 Eloquent 쿼리
     */
    private function buildContextQuery(string $context, array $contextConfig, array $options): Builder
    {
        // config에서 실제 호출할 query 메서드 이름
        $queryMethod = (string) data_get($contextConfig, 'query_method', '');

        // query_method가 없거나, 현재 서비스 클래스에 해당 메서드가 없으면 예외 처리
        if ($queryMethod === '' || ! method_exists($this, $queryMethod)) {
            throw new \InvalidArgumentException("Invalid query method for tip list context [{$context}]");
        }
        // 현재 조회자 ID를 정규화 (비회원이면 null)
        $viewerId = $this->resolveViewerId($options);

        // context별로 필요한 인자가 다름
        $query = match ($context) {
            'public_search' => $this->{$queryMethod}(
                $this->requireFilters($context, $options),
                $viewerId,
            ),
            'category', 'tag' => $this->{$queryMethod}(
                $this->requireScopeId($context, $options),
                $viewerId,
            ),
            'user_feed' => $this->{$queryMethod}(
                $this->requireUser($context, $options)->id,
                $viewerId,
                $this->requireFilters($context, $options),
            ),
            'my_tips' => $this->{$queryMethod}(
                $this->requireUser($context, $options)->id,
                $this->requireFilters($context, $options),
            ),
            'admin_tips' => $this->{$queryMethod}(
                $this->requireFilters($context, $options),
            ),
            'home_popular' => $this->{$queryMethod}($viewerId)
                ->limit($this->resolveLimit($contextConfig, $options)),
            default => throw new \InvalidArgumentException("Unsupported tip list context [{$context}]"),
        };

        if ((bool) data_get($contextConfig, 'apply_sort', false)) {
            $query->sortByOption($this->requireFilters($context, $options)->sort);
        }

        return $query;
    }


    private function resolveContextItems(Builder $query, array $contextConfig, array $options): mixed
    {
        $presenterMethod = (string) data_get($contextConfig, 'presenter', '');
        $resultMode = (string) data_get($contextConfig, 'result_mode', '');

        if ($presenterMethod === '') {
            throw new \InvalidArgumentException('Missing presenter method for tip list context');
        }

        return match ($resultMode) {
            'paginate' => $this->paginate(
                $query,
                $this->requireFilters('paginate', $options),
                $presenterMethod,
            ),
            'collection' => $this->presentCollection($query, $presenterMethod),
            default => throw new \InvalidArgumentException("Unsupported tip list result mode [{$resultMode}]"),
        };
    }

    /**
     * context 별로 이미 조회된 결과($item)를 각 화면이 바로 사용할 수 있는 최종 payload 배열로 바꿔주는 단계 
     * (어떤 화면 구조로 내려줄지 집중한 메서드)
     * 
     */
    private function buildContextPayload(
        string $context,
        array $contextConfig,
        mixed $items,
        Builder $query,
        array $options,
    ): array {
        // config에서 payload 조립 방식을 결정하는 meta_profile 값을 읽음 
        // ex) serach, public_list, user_feed, owner, admin, home_popular 
        $metaProfile = (string) data_get($contextConfig, 'meta_profile', '');

        return match ($metaProfile) {
            // 검색 화면용 payload
            'search' => $this->buildSearchPayload(
                $items,
                $this->requireFilters($context, $options),
                $contextConfig,
            ),
             // 카테고리/태그 공개 목록 화면용 payload
            'public_list' => $this->buildSortedPublicListData(
                $query,
                $this->requireFilters($context, $options),
                (string) data_get($contextConfig, 'sort_mode', $context),
                $items,
            ),
            // 사용자 피드 화면용 payload
            'user_feed' => $this->buildUserFeedPayload(
                $this->requireUser($context, $options),
                $this->requireFilters($context, $options),
                $items,
                $this->resolveViewerId($options),
            ),
            // 내 팁 관리 화면용 payload
            'owner' => $this->buildOwnerPayload(
                $this->requireUser($context, $options),
                $this->requireFilters($context, $options),
                $items,
            ),
            // 관리자 팁 목록 화면용 payload
            'admin' => $this->buildAdminPayload(
                $this->requireFilters($context, $options),
                $items,
                $contextConfig,
            ),
            // 홈 인기 팁 영역용 payload
            'home_popular' => [
                'tipItems' => $items,
            ],
            default => throw new \InvalidArgumentException("Unsupported tip list meta profile [{$metaProfile}]"),
        };
    }

    /**
     * 검색 화면 전용 payload 조립하는 메서드. 
     * 
     * 반환구조]
     * - tipItems   : 검색 결과 목록 자체
     * - searchView : 검색창/정렬/카테고리/태그/페이지 UI를 그리기 위한 메타 정보
     * - categoires : 검색 화면에서 카테고리 선택 UI가 필요할 때만 추가되는 데터
     */
    private function buildSearchPayload(mixed $tipItems, TipListFilters $filters, array $contextConfig): array
    {
        // 검색 화면의 기본 payload
        $payload = [
            'tipItems' => $tipItems, // 실제 검색 결과 목록
            'searchView' => $this->buildSearchViewData($filters, $tipItems), // 현재 필터 상태 + 페이지 메타를 묶은 화면용 데이터
        ];

        // 검색 화면에서 카테고리 필터 UI가 필요한 경우에만 categories를 붙임
        // => 여부는 tip_lists.php의 with_categories 설정으로 제어
        // => 화면 요구사항이 달라져도 이 메서드 구조는 그대로 재사용 가능        
        if ((bool) data_get($contextConfig, 'with_categories', false)) {
            $payload['categories'] = $this->getTipFormCategories();
        }

        // 최종적으로 검색 화면 Blade가 바로 사용할 수 있는 배열 반환
        return $payload;
    }

    /**
     * 사용자 피드 화면용 payload를 조립하는 메서드 
     * 
     */
    private function buildUserFeedPayload(
        User $user, // 피드주인
        TipListFilters $filters, // 현재 피드 정렬 조건
        Collection $tipItems, // 이미 presneter 변환까지 끝난 카드 컬렉션
        ?int $viewerId = null, // 현재 이 피드를 보고 있는 사용자 ID 
    ): array {
        // 현재 조회자 기준으로 실제 화면에 노출 가능한 팁 총 개수 
        $tipsCount = $this->countVisibleTipsForUser($user, $viewerId);

        return [
            'currentSort' => $filters->sort->value, // 현재 선택된 정렬값을 View에서 바로 쓰기 쉬운 scalar 값으로 전달
            'topCategories' => $this->buildUserTipCategories($user, 5, $viewerId),// 현재 조회자에게 보이는 팁만 기준으로 상위 카테고리 5개 집계
            'topTags' => $this->buildUserTipTags($user, 5, $viewerId),  // 현재 조회자에게 보이는 팁만 기준으로 상위 태그 5개 집계
            'tipItems' => $tipItems,    // 실제 피드 카드 목록
            'tipsCount' => $tipsCount,  // 피드 총 팁 개수(숫자 원본)
            'tipsCountText' => number_format($tipsCount),   // 피드 총 팁 개수(화면 표시용 포맷 문자열)
            'totalCount' => $tipsCount, // 다른 payload들과 count 키를 맞춰 쓰기 쉽도록 동일한 총 개수를  totalCount로도 제공
            'totalCountText' => number_format($tipsCount),
        ];
    }

    /**
     * 마이페이지의 "내 팁 관리" 화면용 payload를 조립하는 메서드
     * 
     */
    private function buildOwnerPayload(
     User $user, // 팁 주인(=마이페이지 주체)
     TipListFilters $filters, // 현재 내 팁 화면에서 사용 중인 필터 상태
     mixed $tips    // 이미 paginate+presentOwnerRow 처리된 목록 결과 
     ): array
    {
        return [
            'tips' => $tips,
            'myTipcategories' => $this->buildUserTipCategories($user, null, $user->id),
            'myTipTags' => $this->buildUserTipTags($user, null, $user->id),
            'myTipsFilters' => $this->buildOwnerFiltersViewData($filters),
        ];
    }

    /**
     * 관리자 팁 목록 화면용 payload를 조립
     * 
     * [반환구조]
     * - tips       : 실제 관리자 목록 데이터
     * - tipsView   : 관리자 화면의 필터 UI / 날짜 표시 / 페이지 메타용 데이터
     * - categories : 카테고리 필터 UI가 필요할 때만 붙는 ㅔ이터
     */
    private function buildAdminPayload(
        TipListFilters $filters // 관리자 화면에서 현재 적용 중인 검색/상태/공개범위/날짜 필터
        , mixed $tips   // 이미 조회 + presenter 변환이 끝난 관리자용 목록 결과
        , array $contextConfig) // 현재 context(admin_tips)의 설정값
        : array
    {
        $payload = [
            'tips' => $tips,    // 실제 목록
            'tipsView' => $this->buildAdminTipsViewData($tips, $filters),   // 화면에 필요한 보조 메타 정보
        ];

        // 관리자 화면에서 카테고리 필터 드롭다운 등이 필요할 때만 categories를 추가 
        if ((bool) data_get($contextConfig, 'with_categories', false)) {
            $payload['categories'] = $this->getTipFormCategories();
        }

        return $payload;
    }

    /**
     * options 배열 안에 filters가 정상적으로 들어왔는지 검증하고, 안전하게 TipListFilters를 객체로 꺼내 쓰기 위한 메서드
     */
    private function requireFilters(string $context, array $options): TipListFilters
    {
        $filters = data_get($options, 'filters');

        if (! $filters instanceof TipListFilters) {
            throw new \InvalidArgumentException("TipListFilters is required for tip list context [{$context}]");
        }

        return $filters;
    }

    /**
     * options 배열 안에서 scope_id를 꺼내고, 유효한 범위 ID인지 검증한 뒤 정수로 반환 
     * => 어떤 카테고리/태그를 기준으로 목록을 조회할지 결정하는 필수 ID를 안전하게 꺼내기 위한 검증용 helper
     */
    private function requireScopeId(string $context, array $options): int
    {
        // $context => 어떤 목록 context에서 scope_id가 빠졌는지 예외 메세지에 남겨 디버깅을 쉽게 하려는 목적

        $scopeId = (int) data_get($options, 'scope_id', 0); // context가 특정 대상에 묶여 있을 때 쓰는 식별자

        if ($scopeId <= 0) { 
            throw new \InvalidArgumentException("scope_id is required for tip list context [{$context}]");
        }

        return $scopeId;
    }

    /**
     * options 배열 안에서 user 값을 꺼내 항상 User 모델 인스턴스 형태로 정규화해서 반환하는 메서드
     * 
     * [의의]
     * 어떤 곳은 이미 조회한 User모델을 넘길 수 있고, 단순히 user id(int)만 넘길 수도 있음 => 입력 형태를 하나로 맞춰줌
     * 
     */
    private function requireUser(string $context, array $options): User
    {
        // options에서 user값을 꺼냄
        $user = data_get($options, 'user');

        if ($user instanceof User) {
            return $user;
        }

        if (is_int($user) || ctype_digit((string) $user)) {
            return $this->resolveUser((int) $user);
        }

        // User 객체도 아니고, 유효한 사용자 ID도 아니면 예외 처리 
        throw new \InvalidArgumentException("user is required for tip list context [{$context}]");
    }

    /**
     * options 배열에서 현재 조회자(viewer)의 ID를 꺼내 유효한 로그인 사용자 id 또는 NULL 형태로 정규화 
     */
    private function resolveViewerId(array $options): ?int
    {
        // options에서 viewer_id 값을 꺼냄
        $viewerId = data_get($options, 'viewer_id');

        // 값이 없거나 빈 문자열이면 비로그인 상태로 간주
        // => 이후 로직에서 null은 "조회자 없음"으로 해석
        if ($viewerId === null || $viewerId === '') {
            return null;
        }

        // 숫자처럼 들어온 값을 일단 정수로 정규화
        $viewerId = (int) $viewerId;

        return $viewerId > 0 ? $viewerId : null;
    }

    /**
     * context 설정값과 호출 옵션을 바탕으로 최종 조회 개수(limit)를 안전하게 결정하는 메서드. 
     * - config의 기본 limit 읽기
     * - options의 사용자 지정 limit가 있으면 우선 사용
     * - 잘못된 limit면 안전한 기본값으로 fallback
     */
    private function resolveLimit(array $contextConfig, array $options): int
    {
 
        $defaultLimit = max((int) data_get($contextConfig, 'default_limit', 10), 1);
        $limit = (int) data_get($options, 'limit', $defaultLimit);

        if ($limit < 1) {
            return $defaultLimit;
        }

        return $limit;
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
    private function buildSortedPublicListData(
        Builder $baseQuery,
        TipListFilters $filters,
        string $sortMode,
        mixed $tipItems = null,
    ): array
    {
        // 공통 파이프라인(getListData)에서 이미 paginate+presenter변환까지 끝난 결과가 있으면 그걸 사용, 
        // 없다면 예전 방식처럼 여기서 직접 정렬 + paginate +presenter 변환을 수행
        if ($tipItems === null) {
            $tipItems = (clone $baseQuery)
                ->sortByOption($filters->sort)
                ->paginate($filters->perPage)
                ->withQueryString()
                ->through(fn (Tip $tip) => $this->presenter->presentListItem($tip));
        }

        // 오늘 작성된 팁 수 계산 => 목록 화면 상단의 "오늘 등록 수"같은 통계 용도 
        $todayTipCount = (clone $baseQuery)->whereDate('tips.created_at', Date::today())->count();
        // 현재 공개 목록 전체를 기준으로 평균 좋아요 수 계산 => 결과가 null일 수 있으므로 0으로 보정 후 소수 첫째 자리까지 반올림
        $avgLikeCount = round((float) ((clone $baseQuery)->avg('tips.like_count') ?? 0), 1);
        // 현재 공개 목록 전체를 기준으로 평균 북마크 수 계산
        $avgBookmarkCount = round((float) ((clone $baseQuery)->avg('tips.bookmark_count') ?? 0), 1);

        // paginate 결과의 전체 건수 => 현재 페이지 개수가 아니라 "전체 검색 결과 수"에 가까운 값
        $allCount = (int) $tipItems->total();

        return [
            'tipItems' => $tipItems, // 실제 공개 목록 아이템들
            'todayTipCount' => $todayTipCount,  // 오늘 등록된 팁 개수
            'avgLikeCount' => $avgLikeCount,    // 평균 좋아요 수
            'avgBookmarkCount' => $avgBookmarkCount,    // 평균 북마크 수
            'allCount' => $allCount,    // 전체 목록 건수 
            'listView' => $this->buildPublicListViewData( // 화면에서 바로 쓰기 좋은 공개 목록용 메타 묶음 
                $sortMode,
                $filters,
                $tipItems,
                $allCount,
                $todayTipCount,
                $avgLikeCount,
                $avgBookmarkCount,
            ),
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
     * 검색 화면에서 사용할 "뷰 전용 메타 데이터"를 만드는 메서드 (현재 검색 상태를 다시 그리기 위한 payload)
     * 
     * 검색 폼과 목록 상단 UI가 다시 렌더링될 때 필요한 상태값을 하나의 배열로 정리해서 내려주는 역할
     * 
     * 반환 데이터 성격]
     * - 현재 어떤 카테고리/정렬/검색어/태그로 검색 중인지
     * - 정렬 드롭다운에 어떤 옵션을 보여줄지
     * - 전체 건수, 현재 페이지의 시작/끝 번호 같은 페이지 메타 
     * 
     */
    private function buildSearchViewData(
        TipListFilters $filters // 현재 요청에서 해석된 검색 조건 객체
    , mixed $tipItems   // 이미 조회된 검색 결과 목록 (보통 paginator이지만, 공통 메타 계산을 위해 mixed로 받음)
    ): array
    {
        return array_merge(
            [
                'category' => $filters->category !== '' ? $filters->category : 'all', // 카테고리 필터 값 
                'sort' => $filters->sort->value,    // 현재 선택된 정렬값 
                'query' => $filters->query, // 현재 검색어
                'tags' => $filters->tagNames,   // 현재 선택된 태그 이름 목록 (검색 조건 표시나 태그 필터 ui 복원에 사용)
                'sort_options' => $this->sortOptions(), // 정렬 드롭다운에 보여줄 전체 옵션 목록 
            ],
            // 페이지네이션 관련 공통 메타 병합
            $this->buildPaginationMeta($tipItems),
        );
    }

    /**
     * 카테고리/태그 같은 공개 목록 화면에서 사용할 "뷰 전용 메타 데이터"를 만드는 메서드
     * - 이 메서드는 실제 목록 아이템을 조회하는 역할이 아니라, 공개 목록 화면 상단/주변 UI에 필요한 상태값과 통계를 View가 바로 쓰기 쉬운 형태로 
     * 정리해 주는 역할을 함 
     */
    private function buildPublicListViewData(
        string $sortMode,   // 현재 목록이 어떤 기준인지 구분하는 값
        TipListFilters $filters,    // 현재 정렬/페이지 크기 등 목록 필터 상태
        mixed $tipItems,    // 이미 조회된 공개 목록 결과
        int $allCount,  // 전체 목록 건수
        int $todayTipCount, // 오늘 등록된 팁 수
        float $avgLikeCount,    // 평균 좋아요 수
        float $avgBookmarkCount,    // 평균 북마크 수
    ): array {
        return array_merge(
            [
                'sort_mode_text' => strtoupper($sortMode),
                'current_sort' => $filters->sort->value,
                'today_tip_count' => $todayTipCount,
                'today_tip_count_text' => number_format($todayTipCount),
                'avg_like_count' => $avgLikeCount,
                'avg_bookmark_count' => $avgBookmarkCount,
            ],
            $this->buildPaginationMeta($tipItems, $allCount),
        );
    }

    /**
     * 마이페이지 "내 팁 관리" 화면에서 사용할 필터 UI 전용 메타 데이터를 만드는 메서드
     * : 내 팁 관리 화면의 검색/필터 form 기본값 묶음 
     */
    private function buildOwnerFiltersViewData(TipListFilters $filters): array
    {
        return [
            'category' => $filters->category,   // 현재 선택된 카테고리 값
            'query' => $filters->query, // 현재 검색어
            'status' => $filters->status,   // 현재 선택된 상태값
            'visibility' => $filters->visibility,   // 현재 선택된 공개범위
            'sort' => $filters->sort->value,    // 현재 선택된 정렬값
            'per_page' => $filters->perPage,    // 현재 페이지당 노출 개수
            'selected_tag_ids' => $filters->tagIds, // 현재 선택된 태그 ID목록 
            'selected_tag_ids_map' => array_flip($filters->tagIds), // 태그 ID 목록을 key 기반 map으로 변환한 값(Blade에서 이 태그가 선택되었는지를 빠르고 단순하게 확인하해)
            'sort_options' => $this->sortOptions(), // 정렬 드롭다운에 보여줄 전체 옵션 목록 
        ];
    }

    /**
     * 관리자 팁 목록 화면에서 사용할 뷰 전용 메타 데이터를 조립
     * : 관리자 팁 목록 화면의 필터 UI + 표시 메타 + 페이지 메타 
     * 
     * @param $tips paginate+presentAdminRow 까지 끝난 관리자 목록 결과
     * @param $filters 현재 관리자 검색/필터 조건 
     * 
     */
    private function buildAdminTipsViewData(mixed $tips, TipListFilters $filters): array
    {
        // $tips가 paginator면 내부 컬렉션만 꺼내고, 아니면 그냥 컬렉션으로 감사서 동일한 방식으로 다루기 쉽게 정규화
        $tipItems = method_exists($tips, 'getCollection')
            ? $tips->getCollection()
            : collect($tips);
        // 각 아이템에서 updated_at_raw를 꺼내 가장 최근 수정일을 찾음 
        $lastUpdatedRaw = $tipItems
            ->map(fn ($tip) => data_get($tip, 'updated_at_raw'))
            ->filter()
            ->max();

        return array_merge(
            [
                'tip_items' => $tipItems, // 실제 관리자 목록 아이템 컬렉션 
                'show_pagination' => method_exists($tips, 'links'), // 현재 결과가 paginator인지 여부
                'last_updated_text' => $lastUpdatedRaw // 마지막 수정일 표시용 
                    ? Date::parse($lastUpdatedRaw)->format('Y-m-d')
                    : '-',
                'category' => $filters->category, // 현재 선택된 카테고리 필터값
                'visibility' => $filters->visibility ?? '', // 현재 선택된 공개범위 필터값 
                'status' => $filters->status ?? '', // 현재 선택된 상태 필터값 
                'start_date_input' => $this->normalizeDateInput($filters->startDate),
                'end_date_input' => $this->normalizeDateInput($filters->endDate),
                'query' => $filters->query,
                'per_page' => $filters->perPage,
                // 화면에서 form 기본값/표시값으로 한 번에 쓰기 위한 묶음
                // => 관리자 탭 UI가 그대로 참조하기 쉽도록 별도 배열로 정리 
                'display_values' => [
                    'tab' => 'tips',
                    'query' => $filters->query,
                    'category_id' => $filters->category,
                    'status' => $filters->status ?? '',
                    'visibility' => $filters->visibility ?? '',
                    'start_date' => $filters->startDate ?? '',
                    'end_date' => $filters->endDate ?? '',
                ],
                // 공개범위 select box에 보여줄 옵션 목록
                'visibility_options' => config('app.tip_visibility', []),
                // 상태 select box에 보여줄 옵션 목록
                'status_options' => config('app.tip_status', []),
            ],
            // 공통 페이지 메타를 병합
            $this->buildPaginationMeta($tips),
        );
    }

    /**
     * paginator 또는 collection 형태의 목록 결과에서 화면에서 공통으로 쓰는 페이지 메타 정보를
     * 추출하는 메서드. 
     */
    private function buildPaginationMeta(mixed $items, ?int $totalCount = null): array
    {
        $resolvedTotalCount = $totalCount;

        if ($resolvedTotalCount === null) {
            $resolvedTotalCount = method_exists($items, 'total')
                ? (int) $items->total() // paginator 계열이면 전체 결과 건수 사용
                : collect($items)->count(); // 일반 collection/array 성격이면 현재 요소 개수 사용
        }

        return [
            'total_count' => $resolvedTotalCount, // 전체 건수 원본 숫자
            'total_count_text' => number_format($resolvedTotalCount), // 전체 건수 화면 표시용 문자열
            'first_item' => method_exists($items, 'firstItem') ? $items->firstItem() : null, 
            'last_item' => method_exists($items, 'lastItem') ? $items->lastItem() : null,
        ];
    }

    /**
     * 팁 목록 화면들에서 공통으로 사용할 정렬 옵션 목록을 config에서 읽어 반환하는 메서드 
     */
    private function sortOptions(): array
    {
        $options = config('tip_lists.sort_options', []);

        return is_array($options) ? $options : [];
    }

    /**
     * 날짜 필터 입력값을 HTML date input 등에 다시 넣기 좋은 Y-m-d 형식 문자열로 정규화 
     */
    private function normalizeDateInput(?string $value): string
    {
        if (! filled($value)) {
            return '';
        }

        try {
            return Date::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
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
