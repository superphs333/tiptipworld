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

    // 데이터 가져오기
    public function getCategories(Request $request){
        $returnData = Category::query()
            ->filter($request->query('is_active'), $request->query('name'))
            ->latest()
            ->get();
        return $returnData;
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
}
