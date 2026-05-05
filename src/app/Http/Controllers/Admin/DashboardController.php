<?php

namespace App\Http\Controllers\Admin;

use App\Data\Tip\TipListFilters;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Role;
use App\Models\Status;
use App\Models\Tag;
use App\Models\User;
use App\Services\Tip\TipReadService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * 관리자 대시보드의 탭별 목록 화면을 조립하는 컨트롤러 
 * 
 * [역할]
 * - 현재 요청 탭(categories, users, tags, tips)을 판별
 * - 탭별 목록 데이터를 조회
 * - 각 탭에서 필요한 부가 데이터(statuses, roles, tipsView 등)를 추가 구성
 * - 최종적으로 admin.dashboard 뷰에 공통 구조로 전달 
 */
class DashboardController extends Controller
{
    public function __construct(
        private TipReadService $tipReadService, // 팁 관리자 목록 조회/표현 조립 
    ) {
    }

    /**
     * 관리자 대시보드 메인 진입점 
     * 
     * @param Request $request 현재 관리자 요청
     * @param string|null $tab 라우트 파라미터 또는 외부에서 전달된 탭 이름 
     * 
     * [처리흐름]
     * 1. 설정된 admin 탭 목록을 읽는다
     * 2. 현재 요청에서 어떤 탭을 열어야 하는지 결정
     * 3. 해당 탭의 실제 목록 데이터를 조회 
     * 4. 현재 탭의 query string을 세션에 저장 
     * 5. 공통 view 데이터(tab, headerTitle, tabView, datas)를 만든다
     * 6. users/tips 탭이면 추가 부가 데이터를 더 붙임
     * 7. admin.dashboard 뷰를 반환
     */
    public function index(Request $request, ?string $tab = null)
    {
        // 관리자 탭 설정값 (users, categories, tags, tips)
        $tabs = config('admin.tabs', []);
        // 설정된 첫 번째 탭을 기본 탭으로 사용 
        $defaultTab = array_key_first($tabs) ?? 'users';
        // 라우트/쿼리스트링을 기준으로 최종 활성 탭 결정 
        $tab = $this->resolveTab($request, $tab, $tabs, $defaultTab);
        // 현재 탭에 필요한 필요한 실제 목록 데이터 조회 
        $datas = $this->getDatas($tab, $request);

        /**
         * 각 탭별로 현재 query string을 세션에 저장 
         */
        if ($tab === 'categories') {
            session(['categories.query' => $request->query()]);
        }

        if ($tab === 'users') {
            session(['users.query' => $request->query()]);
        }

        if ($tab === 'tags') {
            session(['tags.query' => $request->query()]);
        }

        if ($tab === 'tips') {
            session(['tips.query' => $request->query()]);
        }

        // 모든 탭에 공통으로 사용하는 뷰 데이터
        $viewArray = [
            'tab' => $tab, // 현재 활성 탭 이름
            'headerTitle' => $tabs[$tab] ?? 'Admin', // 헤더 제목 
            'tabView' => 'admin.partials.' . $tab, // 실제 partial 경로 
            'datas' => $datas, // 탭별 실제 목록 데이터 
        ];

        // users 탭은 화면에서 상태/권한 선택 UI가 필요하므로, 별도 옵션 데이터를 같이 전달 
        if ($tab === 'users') {
            $viewArray['statuses'] = Status::getStatuses();
            $viewArray['roles'] = Role::getAllRoles();
        }

        // TIPS탭 : + 필터 UI 복원용 데이터 
        if ($tab === 'tips') {
            // 팁 작성/수정/필터용 카테고리 목록 
            $viewArray['categories'] = $this->tipReadService->getTipFormCategories();
            // tips 목록 렌더링/필터 UI 표시용 추가 데이터 
            $viewArray['tipsView'] = $this->buildTipAdminViewData(
                $datas,
                TipListFilters::forAdmin($request),
            );
        }

        return view('admin.dashboard', $viewArray);
    }

    /**
     * 요청값을 기준으로 최종 탭 이름을 결정
     * 
     * [우선순위]
     * 1. 메서드 인자로 받은 $routeTab
     * 2. query string의 tab 값
     * 3. 기본 탭 
     */
    private function resolveTab(Request $request, ?string $routeTab, array $tabs, string $defaultTab): string
    {
        $tab = $routeTab ?? $request->query('tab', $defaultTab);
        if (! array_key_exists($tab, $tabs)) {
            return $defaultTab;
        }

        return $tab;
    }

    /**
     * 현재 탭에 맞는 실제 목록 데이터를 조회 
     */
    private function getDatas(string $tab, Request $request): mixed
    {
        return match ($tab) {
            // 카테고리 탭
            'categories' => Category::query()
                ->filter($request->query('is_active'), $request->query('name'))
                ->orderBy('sort_order', 'asc')
                ->orderBy('id')
                ->get(),
            // 사용자 탭 
            'users' => User::getUsers(
                $request->only(['query', 'status', 'role']),
                $this->resolvePerPage($request),
            ),
            // 태그 탭 
            'tags' => Tag::getTags(
                $request->only(['is_blocked', 'query']),
                $this->resolvePerPage($request),
            ),
            // 팁 탭 
            'tips' => $this->tipReadService->getAdminTipRows(
                TipListFilters::forAdmin($request)
            ),
            default => null,
        };
    }

    /**
     * per_page query 값을 안전한 범위의 정수로 정규화 (비정상적인 페이지 크기 요청을 방지)
     * 
     * [규칙]
     * - 값이 1보다 작으면 기본값 20
     * - 값이 너무 크면 최대 100으로 한
     */
    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);

        if ($perPage < 1) {
            return 20;
        }

        return min($perPage, 100);
    }

    /**
     * 관리자 팁 탭 화면에서 사용할 추가 표시용 데이터를 조립 
     * 
     * - paginator 또는 collection에서 실제 아이템 목록 추출
     * - 전체 건수/페이지 범위 계산
     * - 마지막 수정일 텍스트 계산
     * - 현재 필터값 복원용 데이터 구성
     * - 상태/노출 옵션 목록 준비 
     */
    private function buildTipAdminViewData(mixed $tips, TipListFilters $filters): array
    {
        // paginator면 내부 collection을 꺼내고, 아니면 일반 collection 처럼 감싼다.
        $tipItems = method_exists($tips, 'getCollection')
            ? $tips->getCollection()
            : collect($tips);
        // paginator면 total()을 사용하고, 아니면 현재 아이템 개수를 사용 
        $totalCount = method_exists($tips, 'total') ? (int) $tips->total() : $tipItems->count();
        // 각 아이템에 담긴 updated_at_raw 중 가장 최근 값을 찾아, 관리 화면 상단의 최근 수정일 표시용으로 사용 
        $lastUpdatedRaw = $tipItems
            ->map(fn ($tip) => data_get($tip, 'updated_at_raw'))
            ->filter()
            ->max();

        return [
            'tip_items' => $tipItems, // 실제 아이템 목록
            'total_count' => $totalCount, // 전체 개수 숫자
            'total_count_text' => number_format($totalCount), // 표시용 문자열 
            
            // paginator 여부에 따라 페이지네이션 UI 표시 여부 결정
            'show_pagination' => method_exists($tips, 'links'),

            // paginator에서 현재 페이지 첫 번째/마지막 항목 순번 
            'first_item' => method_exists($tips, 'firstItem') ? $tips->firstItem() : null,
            'last_item' => method_exists($tips, 'lastItem') ? $tips->lastItem() : null,

            // 가장 최근 수정일을 Y-m-d 형식으로 표시 
            'last_updated_text' => $lastUpdatedRaw
                ? Carbon::parse($lastUpdatedRaw)->format('Y-m-d')
                : '-',
            
            // 현재 선택된 필터 값들 
            'category' => $filters->category,
            'visibility' => $filters->visibility ?? '',
            'status' => $filters->status ?? '',

            // data input에 다시 채울 값  
            'start_date_input' => $this->normalizeDateInput($filters->startDate),
            'end_date_input' => $this->normalizeDateInput($filters->endDate),

            'query' => $filters->query,
            'per_page' => $filters->perPage,

            // from/query string 복원용 원본 값 묶음 
            'display_values' => [
                'tab' => 'tips',
                'query' => $filters->query,
                'category_id' => $filters->category,
                'status' => $filters->status ?? '',
                'visibility' => $filters->visibility ?? '',
                'start_date' => $filters->startDate ?? '',
                'end_date' => $filters->endDate ?? '',
            ],

            // 드롭다운 옵션 목록
            'visibility_options' => config('app.tip_visibility', []),
            'status_options' => config('app.tip_status', []),
        ];
    }

    /**
     * 날짜 입력값을 HTML date input에 맞는 문자열(Y-m-d)로 정규화 
     * 
     * 규칙
     * - 값이 비어 있으면 빈 문자열 반환
     * - 파싱 가능하면 Y-m-d 형식으로 변환
     * - 파싱 실패 시도에도 빈 문자열 반환
     */
    private function normalizeDateInput(?string $value): string
    {
        if (! filled($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }
}
