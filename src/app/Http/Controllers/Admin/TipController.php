<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tip;
use App\Services\Tip\TipReadService;

/**
 * 관리자 화면에서 팁 작성/수정 폼 진입을 담당 
 * (관리자 대시보드 안에서 팁 폼을 어떤 모드로 띄울지 필요한 view data를 조립해 admin.dashboard 뷰로 넘김 )
 * 
 */
class TipController extends Controller
{
    public function __construct(
        private TipReadService $tipReadService, // 폼에서 사용할 카테고리 목록 조회
    ) {
    }

    /**
     * 관리자용 팁 작성/수정 폼 화면을 렌더링
     * 
     * @param Tip|null $tip 수정 대상 팁 모델. null이면 새 글 작성 모드 
     * 
     * [처리 흐름]
     * 1. 관리자 탭 설정값을 읽는다
     * 2. 폼에서 사용할 카테고리 목록을 조회 
     * 3. 수정 대상 팁이 있으면 tags 관계를 미리 로드함
     * 4. create/update 여부에 따라 formAction 결정
     * 5. admin.dashboard 뷰에 tips 생성 parial을 띄우기 위한 데이터를 전달 
     */
    public function form(?Tip $tip = null)
    {
        // 관리자 대시보드 탭 설정값
        $tabs = config('admin.tabs', []);
        // 작성/수정 폼에서 사용할 카테고리 목록 
        $categories = $this->tipReadService->getTipFormCategories();
        // 수정 대상 팁이 있으면 태그 관계를 함께 로드해서, 폼에서 기존 태그를 바로 표시할 수 있게 준비 
        $data = $tip?->loadMissing('tags:id,name');
        // 생성 모드면 tip.store, 수정 모드면 tip.update 라우트 사용 
        $formAction = $tip === null ? route('tip.store') : route('tip.update', $tip);

        // admin.dashboard 안에서 tips 전용 create parial을 렌더링 
        return view('admin.dashboard', [
            'tab' => 'tips',
            'mode' => $tip === null ? 'create' : 'update',
            'formAction' => $formAction,
            'tip_id' => $tip?->id,
            'headerTitle' => $tabs['tips'] ?? 'Tips',
            'tabView' => 'admin.partials.tips.create',
            'data' => $data,
            'categories' => $categories,
        ]);
    }
}
