<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function store(Request $request){
        // 검증
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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
}
