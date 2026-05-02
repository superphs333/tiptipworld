<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

/**
 * 관리자 화면에서 태그 생성/수정/삭제/차단여부 변경을 담당하는 컨트롤러.
 *
 * 역할:
 * - 새 태그 생성
 * - 기존 태그 수정
 * - 여러 태그 일괄 삭제
 * - 여러 태그의 차단 여부(is_blocked) 일괄 변경
 *
 * 특징:
 * - 작업 후 항상 admin 화면의 tags 탭으로 돌아간다.
 * - 세션에 저장된 tags.query 값을 붙여서, 이전 목록 필터 상태를 유지하려고 한다.
 */
class TagController extends Controller
{
    /**
     * 태그 생성/수정 폼에서 공통으로 사용하는 검증 규칙.
     *
     * - name       : 필수, 문자열, 최대 50자
     * - is_blocked : 선택값, boolean 형태
     *
     * 목적:
     * - store/update에서 같은 검증 규칙을 반복 작성하지 않기 위함
     */
    private array $formChk = [
        'name' => 'required|string|max:50',
        'is_blocked' => 'nullable|boolean',
    ];

    /**
     * 새 태그를 생성한다.
     *
     * 처리 흐름:
     * 1. 요청값을 검증한다.
     * 2. is_blocked 값을 0 또는 1 정수 형태로 정리한다.
     * 3. Tag 레코드를 생성한다.
     * 4. 성공 시 tags 탭으로 이동하며 성공 메시지를 전달한다.
     * 5. 실패 시 tags 탭으로 돌아가며 에러 메시지와 입력값을 유지한다.
     *
     * 주의:
     * - 체크박스 계열 입력은 값이 없으면 아예 전달되지 않을 수 있어서
     *   기본값 0 처리가 필요하다.
     */
    public function store(Request $request)
    {
        // 공통 규칙으로 입력값 검증
        $validated = $request->validate($this->formChk);

        // 체크되지 않은 경우 null일 수 있으므로
        // DB 저장용으로 0/1 정수값으로 보정
        $validated['is_blocked'] = (int) ($validated['is_blocked'] ?? 0);

        try {
            // 새 태그 생성
            Tag::create($validated);

            // 성공 시 태그 탭으로 복귀
            // session('tags.query', [])를 붙여서 기존 검색/필터 조건을 최대한 유지
            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tags'], session('tags.query', []))
            )->with('success', '카테고리가 성공적으로 생성되었습니다.');
        } catch (\Throwable $e) {
            // 예외 발생 시 에러 메시지를 띄우고 입력값을 다시 채울 수 있도록 withInput() 사용
            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tags'], session('tags.query', []))
            )->with('error', '저장 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요')
                ->withInput();
        }
    }

    /**
     * 기존 태그를 수정한다.
     *
     * 처리 흐름:
     * 1. 요청값을 검증한다.
     * 2. is_blocked 값을 boolean으로 정리한다.
     * 3. 수정 대상 태그를 조회한다.
     * 4. 태그 정보를 업데이트한다.
     * 5. 성공 시 tags 탭으로 복귀한다.
     * 6. 실패 시 에러 메시지와 함께 원래 화면으로 되돌린다.
     *
     */
    public function update($tag_id, Request $request)
    {
        // 공통 규칙 검증
        $validated = $request->validate($this->formChk);

        // boolean() 메서드를 사용해서
        // on / 1 / true 계열 값을 실제 bool 값으로 정규화
        $validated['is_blocked'] = $request->boolean('is_blocked');

        try {
            // 수정 대상 태그 조회. 없으면 404
            $tag = Tag::findOrFail($tag_id);

            // 검증된 값으로 업데이트
            $tag->update($validated);

            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tags'], session('tags.query', []))
            )->with('success', '선택한 태그가 성공적으로 수정되었습니다.');
        } catch (\Throwable $e) {
            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tags'], session('tags.query', []))
            )->with('error', '수정 중 오류가 발생했습니다.')
                ->withInput();
        }
    }

    /**
     * 하나 이상의 태그를 삭제한다.
     *
     * @param mixed $tag_ids 쉼표로 연결된 태그 ID 문자열
     *
     */
    public function destroy($tag_ids)
    {
        $tags = explode(',', $tag_ids);
        Tag::whereIn('id', $tags)->delete();
        return redirect()->route(
            'admin',
            array_merge(['tab' => 'tags'], session('tags.query', []))
        )->with('success', '선택한 태그들을 성공적으로 삭제되었습니다.');
    }

    /**
     * 여러 태그의 차단 여부(is_blocked)를 한 번에 변경
     *
     * @param mixed $tag_ids 쉼표로 연결된 태그 ID 문자열
     *
     * 처리 흐름:
     * 1. 전달받은 태그 ID 문자열을 배열로 분리한다.
     * 2. 요청값 is_blocked_action 을 boolean으로 정리한다.
     * 3. 선택된 태그들의 is_blocked 값을 일괄 업데이트한다.
     * 4. updated_at 도 현재 시각으로 함께 갱신한다.
     * 5. tags 탭으로 돌아가며 성공 메시지를 전달한다.
     *
     * boolean('is_blocked_action') 의미:
     * - 요청값이 '1', 'true', 'on' 같은 값이면 true
     * - 아니면 false
     */
    public function updateIsBlocked($tag_ids, Request $request)
    {
        // 선택된 태그 ID들을 배열로 분리
        $tags = explode(',', $tag_ids);

        // 차단/해제 액션을 실제 boolean 값으로 정리
        $is_blocked = $request->boolean('is_blocked_action');

        // 선택된 태그들을 한 번에 업데이트
        // updated_at을 수동으로 넣는 이유는 query builder update는
        // 모델 인스턴스 save()처럼 자동 타임스탬프 갱신이 보장되지 않기 때문
        Tag::whereIn('id', $tags)->update([
            'is_blocked' => $is_blocked,
            'updated_at' => Date::now(),
        ]);

        return redirect()->route(
            'admin',
            array_merge(['tab' => 'tags'], session('tags.query', []))
        )->with('success', '선택한 태그들을 성공적으로 수정되었습니다.');
    }
}
