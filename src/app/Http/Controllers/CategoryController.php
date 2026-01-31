<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function store(Request $request){
        // 검증
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean'
        ]);
        Category::create($validated);
        
        return redirect()->route('admin',['tab'=>'categories'])->with('success','카테고리가 성공적으로 생성되었습니다.');         
    }


    // 데이터 삭제하기  
    public function destroy($category_ids){
        $categories = explode(',', $category_ids);
        Category::whereIn('id', $categories)->delete();
        return redirect()->route('admin',['tab'=>'categories'])->with('success','선택한 카테고리들이 성공적으로 삭제되었습니다.');
    }

    // 데이터 수정하기
    public function update($category_id, Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean'
        ]);
        Category::where('id',$category_id)->update($validated);
        return redirect()->route('admin',['tab'=>'categories'])->with('success','선택한 카테고리가 성공적으로 수정되었습니다.');
    }

    // 데이터 활성화/비활성화 수정
    public function updateIsActive($category_ids,Request $request){
        $isActive = $request->input('is_active_action');
        $categories = explode(',', $category_ids);
        Category::whereIn('id', $categories)->update(['is_active' => $isActive]);
        return redirect()->route('admin',['tab'=>'categories'])->with('success','선택한 카테고리들이 성공적으로 수정되었습니다.');
    }

    // 데이터 순서 정렬하기
    public function updateSort(Request $request){
        // 순서 배열 받기
        $orderedIds = $request->input('ordered_ids');

        if ($orderedIds) {
            $ids = explode(',', $orderedIds);
            foreach ($ids as $index => $id) {
                Category::where('id', $id)->update(['sort_order' => $index + 1]);
            }
        }

        return redirect()->route('admin', ['tab' => 'categories'])->with('success', '순서가 변경되었습니다.');
    }
}
