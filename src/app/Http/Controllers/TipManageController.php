<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyTipRequest;
use App\Http\Requests\StoreTipRequest;
use App\Http\Requests\UpdateTipRequest;
use App\Models\Tip;
use App\Services\Tip\TipReadService;
use App\Services\TipWriteService;
use Illuminate\Http\RedirectResponse;
use Throwable;

/**
 * 팁 작성/수정/삭제 화면 진입과 요청을 처리하는 컨트롤러
 * 
 * [역할]
 * - 프론트용 팁 작성/수정 폼 화면 출력
 * - 팁 생성|수정|삭제 요청 처리 
 */
class TipManageController extends Controller
{
    public function __construct(
        private TipWriteService $tipWriteService, // 생성/수정/삭제, 태그, synce, 썸네일 처리, 본문 이미지 정리 
        private TipReadService $tipReadService, // 작성 폼에 필요한 카테고리 목록 등 조회 전용 데이터 제공 
    ) {
    }

    /**
     * 프론트용 팁 작성/수정 폼 화면을 보여줌
     */
    public function formFront(?Tip $tip = null)
    {
        // 작성/수정 폼에서 사용할 카테고리 목록
        $categories = $this->tipReadService->getTipFormCategories();

        // 기본값 : 새 글 작성 모드 
        $formAction = route('tip.store');
        $siteTitle = '글작성';
        $submitLabel = '게시하기';
        $data = null;

        if ($tip !== null) {
            // 기존 글 수정은 현재 사용자가 수정 가능한지 정책 검사 
            $this->authorize('update', $tip);

            // 수정 폼에서 태그가지 바로 쓸 수 있도록 관계를 미리 로드 
            $data = $tip->loadMissing('tags:id,name');

            // 수정 모드용 화면 텍스트/전송 주소로 변경
            $siteTitle = '글수정';
            $submitLabel = '수정하기';
            $formAction = route('tip.update', $tip);
        }

        // 같은 뷰를 재사용하되 viewMode/frontForm 값으로 목록/상세가 아니라 폼 모드라는 것을 구분 
        return view('tips.view', [
            'viewMode' => 'frontForm',
            'site_title' => $siteTitle,
            'categories' => $categories,
            'tip_id' => $tip?->id,
            'formAction' => $formAction,
            'submitLabel' => $submitLabel,
            'data' => $data,
        ]);
    }

    /**
     * 새 팁을 저장
     * 
     * [처리흐름]
     * 1. StoreTipRequest가 검증한 입력값을 목적별로 분리해서 꺼냄
     * 2. TipWriteServce::create()에 전달
     * 3. 예외가 나면 실패 redirect 처리
     * 4. 성공하면 admin/front 출처에 따라 다른 화면으로 이동
     * 5. 차단된 태그가 있었다면 warining flash message를 함께 붙임 
     */
    public function store(StoreTipRequest $request): RedirectResponse
    {
        try {
            // named argument를 써서 어떤 값이 어떤 파라미터인지 명확히 전달 
            $result = $this->tipWriteService->create(
                actor: $request->user(),
                attributes: $request->payload(),
                thumbnailFile: $request->thumbnailFile(),
                tagsPayload: $request->tagsPayload(),
                draftKey: $request->draftKey(),
            );
        } catch (Throwable $e) {
            report($e);

            return $this->tipFailureRedirect(
                $request->submitFrom(),
                '팁 저장 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.'
            );
        }

        // 관리자 화면에서 저장한 경우 (다시 admin tips 탭으로 돌아가게 함)
        if ($request->submitFrom() === 'admin') {
            $redirect = $this->tipAdminRedirect()
                ->with('success', '팁이 성공적으로 저장되었습니다.')
                ->withInput();

            return $this->withBlockedTagWarning($redirect, $result->warningMessage);
        }

        // 일반 프론트 작성인 경우, 저장된 팁 상세 화면으로 이동 
        $redirect = redirect()
            ->route('tip.show', ['tip_id' => $result->tip->id])
            ->with('success', '팁이 성공적으로 저장되었습니다.');

        return $this->withBlockedTagWarning($redirect, $result->warningMessage);
    }

    /**
     * 기존 팁을 수정
     * 
     * [처리흐름]
     * 1. UpdatedTipRequest에서 검증된 입력값을 꺼낸다 
     * 2. TipWriteService::update()에 전달
     * 3. 실패하면 출처(admin/front)에 맞는 실패 redirect 처리
     * 4. 성공하면 출처에 맞는 성공 redirect 처리
     * 5. 차단된 태그 있었으면 warning 메세지를 추가
     */
    public function update(UpdateTipRequest $request, Tip $tip): RedirectResponse
    {
        try {
            $result = $this->tipWriteService->update(
                actor: $request->user(),
                tip: $tip,
                attributes: $request->payload(),
                thumbnailFile: $request->thumbnailFile(),
                deleteThumbnail: $request->shouldDeleteThumbnail(),
                tagsPayload: $request->tagsPayload(),
                draftKey: $request->draftKey(),
            );
        } catch (Throwable $e) {
            report($e);

            return $this->tipFailureRedirect(
                $request->submitFrom(),
                '팁 수정 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
                $tip
            );
        }

        // 관리자 출처가 아니면 수정 후 상세 페이지로 복귀 
        if ($request->submitFrom() !== 'admin') {
            $redirect = redirect()
                ->route('tip.show', ['tip_id' => $result->tip->id])
                ->with('success', '팁이 성공적으로 수정되었습니다.');

            return $this->withBlockedTagWarning($redirect, $result->warningMessage);
        }

        // 관리자 출처면 admin tips 탭으로 귀
        $redirect = $this->tipAdminRedirect()
            ->with('success', '팁이 성공적으로 수정되었습니다.')
            ->withInput();

        return $this->withBlockedTagWarning($redirect, $result->warningMessage);
    }

    /**
     * 팁 삭제
     * 
     * [처리흐름]
     * 1. DestroyTipRequest에서 권한/검증을 통과한 요청만 받음
     * 2. TipWriteService::delete()로 실제 삭제 수행
     * 3. 성공하면 admin/front 출처에 따라 다른 화면으로 이동
     * 4. 실패하면 공통 실패 redirect 처리 
     */
    public function destroy(DestroyTipRequest $request, Tip $tip): RedirectResponse
    {
        try {
            $this->tipWriteService->delete($request->user(), $tip);

            // 일반 프론트 화면에서 삭제한 경우 홈으로 이동 
            if ($request->submitFrom() !== 'admin') {
                return redirect()->route('home')
                    ->with('success', '팁이 성공적으로 삭제되었습니다.');
            }

            // 관리자 화면에서 ㅏㄱ제한 경우 admin tips탭으로 복귀 
            return $this->tipAdminRedirect()
                ->with('success', '팁이 성공적으로 삭제되었습니다.');
        } catch (Throwable $e) {
            report($e);

            return $this->tipFailureRedirect(
                $request->submitFrom(),
                '삭제 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.'
            );
        }
    }

    /**
     * 차단된 태그가 있었을 때 warning flash message를 redirect 응답에 추가
     * 
     */
    private function withBlockedTagWarning($redirect, ?string $warningMessage)
    {
        if ($warningMessage === null || trim($warningMessage) === '') {
            return $redirect;
        }

        return $redirect->with('warning', $warningMessage);
    }

    /**
     * 관리자 팁 목록 화면으로 되돌아가는 공통 redirect 응답을 만든다
     * - 상상 admin화면의 tips 탭으로 이동
     * - session('tips.query')를 붙여서 이전 목록 검색/필터 상태를 유지하려고 함
     * 
     */
    private function tipAdminRedirect(): RedirectResponse
    {
        return redirect()->route(
            'admin',
            array_merge(['tab' => 'tips'], session('tips.query', []))
        );
    }

    /**
     * 팁 저장/수정/실패 시 어디로 돌려보낼지 공통 처리 
     * 
     * [규칙]
     * - submitFrom이 admin이면 admin tips 탭으로 복귀
     * - 프론트 수정 중 실패이고 $tip 이 있으면 해당 팁 상세 화면으로 이동
     * - 그 외에는 이전 페이지(back)로 이동 
     */
    private function tipFailureRedirect(
        string $submitFrom,
        string $message,
        ?Tip $tip = null,
    ): RedirectResponse {
        if ($submitFrom === 'admin') {
            return $this->tipAdminRedirect()
                ->withInput()
                ->with('error', $message);
        }

        if ($tip !== null) {
            return redirect()
                ->route('tip.show', ['tip_id' => $tip->id])
                ->withInput()
                ->with('error', $message);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }
}
