<?php

namespace App\Http\Controllers;

use App\Data\Tip\TipListFilters;
use App\Models\User;
use App\Services\Tip\TipReadService;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 마이페이지 대시보드 컨트롤러
 * 
 * [역할]
 * - 마이페이지의 탭별 화면 진입을 한 곳에서 처리
 * - 현재 로그인 사용자 기준으로 필요한 데이터를 조립
 * - 선택된 탭(mytpis, myarchive, notifications 등)에 따라 필요한 서비스 호출 결과를 viewData에 담아 전달 
 */
class MyPageController extends Controller
{
    /**
     * 읽기 전용 팁 데이터 서비스와 알림 보드 서비스를 주입받음.
     */
    public function __construct(
        private TipReadService $tipReadService, // 내 팁 목록, 보관함, 카테고리/태그 조회 전용 로직 
        private UserNotificationService $userNotificationService, // 알림 목록/상태/유형 필터링 등 알림 화면 데이터 조립 담당 
    ) {
    }

    /**
     * 마이페이지 메인 화면 렌더링 
     * 
     * [동작흐름]
     * 1. 현재 로그인 사용자와 사용자 ID를 확인
     * 2. 요청된 탭(tab)이 유효한지 검사
     * 3. 공통 viewData를 먼저 구성
     * 4. 탭 종류에 따라 필요한 데이터를 추가
     * 5. 최종적으로 mypage.dashboard 뷰를 반환 
     * 
     * @param Request $request 현재 HTTP 요청
     * @param string|null $tab 라우트 또는 외부에서 전달된 탭 이름 
     * 
     * @return View 마이페이지 대시보드 뷰 
     */
    public function index(Request $request, ?string $tab = null): View
    {
        $user = Auth()->user(); // 현재 로그인 사용자 객체
        $user_id = Auth()->id(); // 현재 로그인 사용자 ID 

        $tipOwner = $user instanceof User ? $user : User::findOrFail((int) $user_id);
        $tabs = config('mypage.tabs', []);  // 마이페이지 탭 설정 값  (profile, mytips, myarchive, notifications 등)        
        $defaultTab = array_key_first($tabs) ?? 'profile'; // 설정된 탭의 첫 번째 기본 탭으로 사용 
        // 최종 탭 결정 우선순위
        // 1. 메서드 인자로 받은 $tab
        // 2. 현재 라우트의 tab 파라미터
        // 3. 기본 탭 
        $tab = $tab ?? $request->route('tab') ?? $defaultTab;

        // 요청된 탭이 설정 목록에 없으면 잘못된 값으로 보고, 기본 탭으로 강제 보정 
        if (! array_key_exists($tab, $tabs)) {
            $tab = $defaultTab;
        }

        // 모든 탭에서 공통으로 쓰는 기본 view 데이터 
        $viewData = [
            'tab' => $tab, // 현재 활성 탭 이름 
            'headerTitle' => $tabs[$tab] ?? 'My Page', // 헤더제목
            'tabView' => 'mypage.partials.' . $tab, // 실제 탭 내용 partial 경로 
            'user' => $request->user()?->loadMissing('socialAccounts'), // 현재 로그인 한 사용자 
        ];

        switch ($tab) {
            // 내 팁 화면에서 사용할 필터 객체 생성 
            case 'mytips':
                $filters = TipListFilters::forOwner($request);
                $viewData = array_merge(
                    $viewData,
                    $this->tipReadService->getListData('my_tips', [
                        'filters' => $filters,
                        'user' => $tipOwner,
                    ])
                );

                break;
            
            // 보관함 화면 데이터 조립 : bookmark/like 팁셋, 개수, 통계 등을 서비스에서 받아 병합 
            case 'myarchive':
                $viewData = array_merge(
                    $viewData,
                    $this->tipReadService->getMyArchivePageData($tipOwner)
                );

                break;
            
            // 로그인 사용자가 있을 때만 알림 보드 데이터를 붙임 
            case 'notifications':
                if ($user !== null) {
                    $status = (string) $request->query('status', 'all');
                    $type = (string) $request->query('type', 'all');

                    // 알림 보드용 데이터 병합 
                    $viewData = array_merge(
                        $viewData,
                        $this->userNotificationService->getBoardData($user, $status, $type)
                    );
                }

                break;
        }

        // 하나의 대시보드 뷰에서 공통 레이아웃+탭별 partial를 렌더링
        return view('mypage.dashboard', $viewData);
    }
}
