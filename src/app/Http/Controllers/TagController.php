<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;

class TagController extends Controller
{
    /**
     * 태그 저장
     */
    public function store(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'is_blocked' => 'nullable|boolean'
        ]);
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

        return $q->orderBy('id')->paginate($perPage)->withQueryString();
    }
}
