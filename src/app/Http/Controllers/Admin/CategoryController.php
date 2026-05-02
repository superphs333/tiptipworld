<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 관리자 화면에서 카테고리 생성/수정/삭제/차단여부 변경을 담당하는 컨트롤러
 * 
 * 
 */
class CategoryController extends Controller
{
    /**
     * 새 카테고리 생성
     * 
     * [처리흐름]
     * 1. 입력값을 검증
     * 2. db 트랜잭션 안에서 현재 가장 큰 sort_order 값을 조회 
     * 3. 새 카테고리의 sort_order를 마지막 순서+1로 정함
     * 4. 카테고리를 생성
     * 5. 성공 후 categoreis 탭으로 리다이렉트 
     */
    public function store(Request $request)
    {
    
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use ($validated) {
            // 현재 카테고리 중 가장 큰 sort_order 조회 
            $lastSortOrder = Category::query()
                ->lockForUpdate()
                ->max('sort_order');

            // 새 카테고리는 맨 뒤에 붙도록 마지막 순서 + 1 부여 
            $validated['sort_order'] = (int) ($lastSortOrder ?? 0) + 1;

            // 실제 카테고리 생성 
            Category::create($validated);
        });

        return redirect()->route(
            'admin',
            array_merge(['tab' => 'categories'], session('categories.query', []))
        )->with('success', '카테고리가 성공적으로 생성되었습니다.');
    }

    /**
     * 하나 이상의 카테고리를 삭제 
     * 
     * @param mixed $category_ids 쉼표로 연결된 카테고리 ID 문자열 
     * 
     */
    public function destroy($category_ids)
    {
        $categories = explode(',', $category_ids);
        Category::whereIn('id', $categories)->delete();

        return redirect()->route(
            'admin',
            array_merge(['tab' => 'categories'], session('categories.query', []))
        )->with('success', '선택한 카테고리들이 성공적으로 삭제되었습니다.');
    }

    /**
     * 기존 카테고리의 정보 수정
     * 
     * [처리 흐름]
     * 1. 입력값을 검증
     * 2. 대상 category_id에 해당하는 카테고리를 찾아 업데이트한다
     * 3. 수정 후 categories 탭으로 되돌아간다. 
     */
    public function update($category_id, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        Category::where('id', $category_id)->update($validated);

        return redirect()->route(
            'admin',
            array_merge(['tab' => 'categories'], session('categories.query', []))
        )->with('success', '선택한 카테고리가 성공적으로 수정되었습니다.');
    }

    /**
     * 여러 카테고리의 활성화 상태(is_active)를 한 번에 변경
     * 
     * @param mixed $category_ids 쉼표로 연결된 카테고리 ID 문자열 
     * 
     */
    public function updateIsActive($category_ids, Request $request)
    {
        // 일괄 활성/비활성 처리용 값
        $isActive = $request->input('is_active_action');
        // 선택된 카테고리 ID 배열 
        $categories = explode(',', $category_ids);
        // 선택된 카테고리들의 활성 상태를 한 번에 변경 
        Category::whereIn('id', $categories)->update(['is_active' => $isActive]);

        return redirect()->route(
            'admin',
            array_merge(['tab' => 'categories'], session('categories.query', []))
        )->with('success', '선택한 카테고리들이 성공적으로 수정되었습니다.');
    }

    /**
     * 드래그 앤 드롭 등으로 카테고리 순서를 저장
     * 
     */
    public function updateSort(Request $request)
    {
        // 정렬 결과를 담은 카테고리 ID 문자열
        $orderedIds = $request->input('ordered_ids');

        if ($orderedIds) {
            $ids = explode(',', $orderedIds);
            // 배열 앞에서부터 1,2,3...순서대로 sort_order 재부여
            foreach ($ids as $index => $id) {
                Category::where('id', $id)->update(['sort_order' => $index + 1]);
            }
        }

        return redirect()->route(
            'admin',
            array_merge(['tab' => 'categories'], session('categories.query', []))
        )->with('success', '순서가 변경되었습니다.');
    }
}
