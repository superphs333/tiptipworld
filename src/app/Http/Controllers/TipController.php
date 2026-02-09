<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use App\Services\FileStorageService;
use App\Models\Tip;
use App\Models\Tag;

class TipController extends Controller
{
    // 추가/업데이트 폼
    public function form(?int $tip_id = null)
    {
        $tabs = config('admin.tabs', []);
        $tab = 'tips';
    
        $categories = Category::query()
            ->forTipForm()
            ->get([
                'id',
                'name',
            ]);

        //$formAction = is_null($tip_id) ? 'tip.store' : 'tip.update';
        $formAction = is_null($tip_id) ? route('tip.store') : route('tip.update', $tip_id);


        return view('admin.dashboard', [
            'tab' => $tab,
            'mode' => is_null($tip_id) ? 'create' : 'update',
            'formAction' => $formAction,
            'tip_id' => $tip_id,
            'headerTitle' => $tabs[$tab] ?? 'Tips',
            'tabView' => 'admin.partials.tips.create',
            'datas' => null,
            'categories' => $categories
        ]);
    }

    public function saveTip(Request $request, FileStorageService $storage){
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'thumbnail' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,archived,deleted'],
            'visibility' => ['required', 'in:public,unlisted,private'],
        ]);
        $userId = Auth::id();
        $created_at = Date::now();
        $updated_at = Date::now();
        $validated['user_id'] = $userId;
        $validated['created_at'] = $created_at;
        $validated['updated_at'] = $updated_at;

        /**
        * 썸네일 저장 (name : thumbnail)
        */
        if ($request->hasFile('thumbnail')) {
            $tip_thumbnail_url = $storage->storeUploaded($validated['thumbnail'], 'tip-cover');
            $validated['thumbnail'] = $tip_thumbnail_url;
        }

        
        /**
         * Tip 저장
         */
        $tip = Tip::create($validated);
        

        /**
         * 태그 저장 (in tips, tip_tag)
         */
        // JSON 문자열("['tag1', 'tag2']")을 PHP 배열로 변환
        $tagNames = $request->filled('tags') ? json_decode($request->input('tags'), true) : [];

        if(!empty($tagNames)){
            $tagIds = [];
            foreach($tagNames as $tagName){
                $tag = Tag::firstOrCreate(['name'=>$tagName]);
                $tagIds[] = $tag->id;
            }
            // 팁모델에 연결
            $tip->tags()->sync($tagIds);
        }


        return redirect()->route(
            'admin',
            array_merge(['tab' => 'tips'], session('tips.query', []))
        )->with('success', '팁이 성공적으로 저장되었습니다.')
        ->withInput();




    }
    

}
