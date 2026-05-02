<?php

namespace App\Data\Tip;

use App\Enums\TipSort;
use Illuminate\Http\Request;

final class TipListFilters
{
    public function __construct(
        public readonly string $category,
        public readonly string $query,
        public readonly array $tagNames,
        public readonly array $tagIds,
        public readonly ?string $status,
        public readonly ?string $visibility,
        public readonly TipSort $sort,
        public readonly int $perPage,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
    ) {
    }

    /**
     * 공개 팁 검색 화면에서 사용할 필터 객체를 생성
     * 
     * [목적]
     * - URL query string으로 들어온 검색 조건을 한곳에서 정규화한다. 
     * - 컨트롤러/서비스가 request 값을 직접 해석하지 않고, 항상 동일한 구조의 TipListFilters 객체만 사용하도록 만든다. 
     * 
     * [대상화면]
     * /tips/search 같은 공개 검색 페이지
     * 
     * [처리내용]
     * - cateogy : 카테고리 값 정리, 없으면 기본값 'all'
     * - query : 검색어를 문자열로 변환 후 앞뒤 공백 제거
     * - tagNames : 태그 이름 목록 정리
     *  - 문자열이면 쉼표 기준 분리
     *  - 배열이면 그대로 사용
     *  - 공백 제거 / 중복 제거 / 빈 값 제거
     * - sort : 허용된 정렬값만 enum으로 변환, 잘못된 값이면 최신순 기본 적용
     * - perPage : 페이지당 개수를 안전 범위로 제한
     * 
     * [특징]
     * - 공개 검색은 태그를 id가 아니라 "이름(tagNames)" 기준으로 받음 
     * - status, visibility, startDate, endDate는 공개 검색에서 직접 받지 않으므로 비워 둔다
     * - 비공개/미발행 팁 제외 같은 실제 노출 조건은 여기서 처리하지 x 
     */
    public static function forPublicSearch(Request $request): self
    {
        return new self(
            // category query parameter (ex. all, 3, uncategorized)
            category: self::normalizeCategory($request->query('category', 'all')),
            // 검색어 원문 (null 방지를 위해 문자열로 캐스팅하고 앞뒤 공백 제거)
            query: trim((string) $request->query('query', '')),
            // 태그 이름 목록
            tagNames: self::normalizeTagNames($request->query('tags', [])),
            // 공개 검색은 태그 id 기반 필터를 사용하지 않으므로 빈 배열 
            tagIds: [],
            // 공개 검색 화면에서는 상태(draft/published 등)을 직접 받지 않음
            status: null,

            visibility: null,
            sort: TipSort::fromNullable($request->query('sort')), // 정렬 기준
            perPage: self::normalizePerPage($request->query('per_page', 12), 12, 50), // 페이지당 개수
            startDate: null,
            endDate: null,
        );
    }

    /**
     * 카테고리/태그/사용자 피드 같은 "단순 목록형 화면"에서 쓸 필터 객체 생성 
     * 대상) 카테고리 목록, 태그 목록, 사용자 피드 
     * 
     */
    public static function forFeed(Request $request): self
    {
        return new self(
            category: 'all',
            query: '',
            tagNames: [],
            tagIds: [],
            status: null,
            visibility: null,            
            sort: TipSort::fromNullable($request->query('sort')), // 허용된 정렬 값만 enum으로 정규화 (없거나 잘못되면 기본값 적용)
            perPage: self::normalizePerPage($request->query('per_page', 12), 12, 50),   // per_page를 숫자로 정규화하고 최소/최대 범위를 강제 
            startDate: null,
            endDate: null,
        );
    }

    /**
     * 작상자 본인 화면(내 팁 관리 등)에서 사용할 필터 객체 생성
     */
    public static function forOwner(Request $request): self
    {
        return new self(
            category: self::normalizeCategory($request->query('category_id', '')),
            query: trim((string) $request->query('query', '')),
            tagNames: [],
            tagIds: self::normalizeTagIds($request->query('tags', [])),
            status: self::nullableString($request->query('status')),
            visibility: self::nullableString($request->query('visibility')),
            sort: TipSort::fromNullable($request->query('sort')),
            perPage: self::normalizePerPage($request->query('per_page', 20), 20, 100),
            startDate: null,
            endDate: null,
        );
    }

    /**
     * 관리자 화면에서 사용할 필터 객체 생성
     */
    public static function forAdmin(Request $request): self
    {
        return new self(
            category: self::normalizeCategory($request->query('category_id', 'all')),
            query: trim((string) $request->query('query', '')),
            tagNames: [],
            tagIds: [],
            status: self::nullableString($request->query('status')),
            visibility: self::nullableString($request->query('visibility')),
            sort: TipSort::Latest,
            perPage: self::normalizePerPage($request->query('per_page', 20), 20, 100),
            startDate: self::nullableString($request->query('start_date')),
            endDate: self::nullableString($request->query('end_date')),
        );
    }

    // 현재 category 값이 숫자 문자열이면 정수 category_id로 변환 
    public function categoryId(): ?int
    {
        return ctype_digit($this->category) ? (int) $this->category : null;
    }

    // 현재 category 값이 uncategorized인지 판별 
        // true -> category_id가 없는 데이터 조회 의도
        // false -> 일반 category 조건 또는 전체 조회 
    public function isUncategorizedCategory(): bool
    {
        return $this->category === 'uncategorized';
    }

    /**
     * category 값을 문자열로 정리
     * ex) 문자열 캐스팅, 앞뒤 공백 제거, 빈 문자열이면 그대로 '' 반환
     */
    private static function normalizeCategory(mixed $value): string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : '';
    }

    /**
     * 태그 이름 목록 정규화 
     * 
     * [지원입력]
     * - 문자열 : 'php, laravel, mysql'
     * - 배열 : ['php', 'laravel', '', 'php']
     * 
     * [처리 내용]
     * - 문자열이면 쉼표 기준 분리
     * - 배열이 아니면 빈 배열 반환
     * - 각 항목 문자열 캐스팅 후 공백 제거
     * - 중복 제거
     * - 빈 문자열 제거
     * - 인덱스를 0부터 다시 정렬 
     * 
     * 반환예) 'php, laravel, php' -> ['php', 'laravel']
     */
    private static function normalizeTagNames(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_unique(array_map(
                static fn ($tag) => trim((string) $tag),
                $value
            ), SORT_STRING),
            static fn ($tag) => $tag !== ''
        ));
    }

    /**
     * 태그 ID 목록을 정규화 
     * 
     * [처리내용]
     * - 배열이 아니면 배열로 감싼다
     * - 각 값을 정수로 변환
     * - 0 이하 제거
     * - 중복 제거
     * - 인덱스를 0부터 다시 정렬
     */
    private static function normalizeTagIds(mixed $value): array
    {
        if (! is_array($value)) {
            $value = [$value];
        }

        return collect($value)
            ->map(static fn ($tagId) => (int) $tagId)
            ->filter(static fn ($tagId) => $tagId > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 문자열 입력을 nullable string 형태로 정리
     * 
     * [처리내용]
     * - 문자열 캐스팅
     * - 앞 뒤 공백 제거 
     * - 빈 문자열이면 null 반환
     * - 값이 있으면 정리된 문자열 반환 
     */
    private static function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * per_page 값을 안전한 범위의 정수로 정규화 
     * 
     * [처리규칙]
     * - 숫자로 캐스팅
     * - 1보다 작으면 기본값 사용
     * - 최대값을 넘으면 최대값으로 제한 
     */
    private static function normalizePerPage(mixed $value, int $default, int $max): int
    {
        $perPage = (int) $value;

        if ($perPage < 1) {
            return $default;
        }

        return min($perPage, $max);
    }
}
