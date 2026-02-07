<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Tag;
use Illuminate\Support\Facades\Date;

class TagController extends Controller
{

    private $formChk = [
            'name' => 'required|string|max:50',
            'is_blocked' => 'nullable|boolean'
        ];
    /**
     * 태그 저장
     */
    public function store(Request $request){
        $validated = $request->validate($this->formChk);
        $validated['is_blocked'] = (int)($validated['is_blocked'] ?? 0);

        try{
            Tag::create($validated);
            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tags'], session('tags.query', []))
            )->with('success','카테고리가 성공적으로 생성되었습니다.');   
        }catch(\Throwable $e){
            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tags'], session('tags.query', []))
            )->with('error', '저장 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요' )
            ->withInput();
        }


    }

    /**
     * 태그 수정
     *
     * @param int $tag_id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($tag_id, Request $request){
        // 유효성 검사 진행
        $validated = $request->validate($this->formChk);
        
        // 체크박스 값 처리 (boolean 타입으로 변환, 미체크 시 false)
        $validated['is_blocked'] = $request->boolean('is_blocked');

        try {
            // 태그 조회 (존재하지 않으면 404 에러 발생)
            $tag = Tag::findOrFail($tag_id);
            
            // 데이터 업데이트 (updated_at은 Eloquent가 자동 처리)
            $tag->update($validated);

            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tags'], session('tags.query', []))
            )->with('success', '선택한 태그가 성공적으로 수정되었습니다.');
        } catch (\Throwable $e) {
            // 예외 발생 시 에러 메시지와 함께 이전 입력값 유지하며 리다이렉트
            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tags'], session('tags.query', []))
            )->with('error', '수정 중 오류가 발생했습니다.')
             ->withInput();
        }
    }

}
