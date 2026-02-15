<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use App\Services\FileStorageService;
use App\Services\TipViewCounterService;
use App\Models\Tip;
use App\Models\Tag;
use App\Models\Comment;

class TipController extends Controller
{
    private $validatedArr = [
            'category_id' => ['nullable', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'thumbnail' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,archived,deleted'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'thumbnail_delete' => ['nullable', 'in:true,false'],
    ];

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

        $data = !is_null($tip_id) ? Tip::find($tip_id) : null;

        return view('admin.dashboard', [
            'tab' => $tab,
            'mode' => is_null($tip_id) ? 'create' : 'update',
            'formAction' => $formAction,
            'tip_id' => $tip_id,
            'headerTitle' => $tabs[$tab] ?? 'Tips',
            'tabView' => 'admin.partials.tips.create',
            'data' => $data,
            'categories' => $categories
        ]);
    }

    public function saveTip(Request $request, FileStorageService $storage){
        $validated = $request->validate($this->validatedArr);
        $userId = Auth::id();
        $created_at = Date::now();
        $validated['user_id'] = $userId;
        $validated['created_at'] = $created_at;
        


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
            $this->saveTags($tagNames, $tip->id);
        }

        return redirect()->route(
            'admin',
            array_merge(['tab' => 'tips'], session('tips.query', []))
        )->with('success', '팁이 성공적으로 저장되었습니다.')
        ->withInput();

    }

    public function updateTipPost(Request $request,int $tip_id , FileStorageService $storage){
        $target_tip = Tip::findOrFail($tip_id);
        $validated = $request->validate($this->validatedArr);
        $validated['update_user_id'] = Auth::id();
        $validated['updated_at'] = Date::now();

        /**
        * 썸네일 저장 (name : thumbnail) 
        */
        $thumbnail_deleted = $request->boolean('thumbnail_delete');
        if ($request->hasFile('thumbnail')) {
            $storage->deleteIfExists($target_tip->thumbnail);
            $tip_thumbnail_url = $storage->storeUploaded($validated['thumbnail'], 'tip-cover');
            $validated['thumbnail'] = $tip_thumbnail_url;
        }
        if($thumbnail_deleted){
            $storage->deleteIfExists($target_tip->thumbnail);
            $validated['thumbnail'] = null;
        }
            

        /**
         * 태그
         */
        $tagNames = $request->filled('tags') ? json_decode($request->input('tags'), true) : [];
        if(!empty($tagNames)){
            $this->saveTags($tagNames, $tip_id);
        }


        /**
         * 수정
         */
        $target_tip->update($validated);


        return redirect()->route(
            'admin',
            array_merge(['tab' => 'tips'], session('tips.query', []))
        )->with('success', '팁이 성공적으로 수정되었습니다.')
        ->withInput();

    }

    public function destroy(int $tip_id, FileStorageService $storage)
    {
        try {
            $target_tip = Tip::findOrFail($tip_id);

            $storage->deleteIfExists($target_tip->thumbnail);
            $target_tip->delete();

            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tips'], session('tips.query', []))
            )->with('success', '팁이 성공적으로 삭제되었습니다.');
        } catch (\Throwable $e) {
            return redirect()->route(
                'admin',
                array_merge(['tab' => 'tips'], session('tips.query', []))
            )->with('error', '삭제 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
        }
    }

    private function saveTags($tagNames, $tip_id){
        $tagIds = [];
        foreach($tagNames as $tagName){
            $tag = Tag::firstOrCreate(['name'=>$tagName]);
            $tagIds[] = $tag->id;
        }
        // 팁모델에 연결
        $tip = Tip::findOrFail($tip_id);
        $tip->tags()->sync($tagIds);
    }

    /**
     * TIP ONE VIEW
     */
    public function showPost(Request $request, int $tip_id, TipViewCounterService $tipViewCounter){
        $tip = Tip::with([
            'category:id,name',
            'user:id,name',
            'tags:id,name',
        ])->findOrFail($tip_id);
        $user = Auth::user();
        $is_admin = $user?->isAdmin() ?? false;
        $isTipOwner = $user?->id == $tip->user_id;
        $tip_status = $tip->status;
        $tip_visibility = $tip->visibility;

        /**
         * Tip url 용
         */
        // content 첫문장
        $plain = trim(strip_tags($tip->content));
        $first = preg_split('/[.!?]/u', $plain, 2)[0] ?? $plain;
        $tip_data_for_share = [
            "url_tip_title" => $tip->title,
            "url_tip_text" => mb_strimwidth($first, 0, 120, '...'),
            'url_tip_url' => route('tip.show', ['tip_id' => $tip->id]),

        ];

        /**
         * 팁 접근 설정
         * 본인 글 | 관리자 -> 모두 접근 가능
         * status -> published 
         * visibility -> public 만 가능 
         */
        if (!$is_admin && !$isTipOwner && ($tip_status !== 'published' || $tip_visibility !== 'public')) {
            return response(
                "<script>alert('접근할 수 없는 게시글입니다.');" .
                "if (window.history.length > 1) { window.history.back(); }" .
                "else { window.location.href = '/'; }</script>"
            );
        }

        /**
         * 조회수
         * 같은 방문자(로그인/비로그인) 기준 24시간 중복 조회 방지
         */
        $tipViewCounter->increaseIfNeeded($request, $tip);

        return view('tips.view', [
            'viewMode' => 'detailView',
            'tip' => $tip,
            'tip_data_for_share' => $tip_data_for_share,
        ]);
    }

    /**
     * 분류별 페이지
     */
    public function tipListBySort(Request $request, int $sort_id){
        $sort = "";
        $site_title = "";
        if($request->routeIs('tips.category')){
            $sort = "category";
            $site_title = Category::findOrFail($sort_id)->name;
        }else if($request->routeIs('tips.tag')){
            $sort = "tag";
            $site_title = Tag::findOrFail($sort_id)->name;
        }

        return view('tips.view', [
            'viewMode' => 'tipListBySort',
            'site_title' => $site_title,
        ]);

    }

    /**
     * 좋아요 기능
     */
    public function like(int $tip_id)
    {
        $tip = Tip::findOrFail($tip_id);
        $userId = Auth::id();

        $changed = $tip->likedUsers()->toggle($userId);
        $liked = !empty($changed['attached']);

        $likeCount = $tip->likedUsers()->count();
        $tip->update(['like_count' => $likeCount]);

        return response()->json([
            'success' => true,
            'tip_id' => $tip->id,
            'liked' => $liked,
            'like_count' => $likeCount,
        ]);
    }


    /**
     * 북마크 기능
     */
    public function bookmark(int $tip_id){
        $tip = Tip::findOrFail($tip_id);
        $userId = Auth::id();

        $changed = $tip->bookmarkedUsers()->toggle($userId);
        $bookmarked = !empty($changed['attached']);

        $bookmarkCount = $tip->bookmarkedUsers()->count();
        $tip->update(['bookmark_count' => $bookmarkCount]);

        return response()->json([
            'success' => true,
            'tip_id' => $tip->id,
            'bookmarked' => $bookmarked,
            'bookmark_count' => $bookmarkCount,
        ]);


    }

}
