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
        $tipPageData = null;
        // 현재 탭에 필요한 필요한 실제 목록 데이터 조회 
        if ($tab === 'tips') {
            $tipPageData = $this->tipReadService->getListData('admin_tips', [
                'filters' => TipListFilters::forAdmin($request),
            ]);
            $datas = $tipPageData['tips'];
        } else {
            $datas = $this->getDatas($tab, $request);
        }

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
            $viewArray['categories'] = data_get($tipPageData, 'categories', collect());
            $viewArray['tipsView'] = data_get($tipPageData, 'tipsView', []);
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
}
