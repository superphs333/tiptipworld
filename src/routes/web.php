<?php

use App\Http\Controllers\AdminDashboard;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SummernoteController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TipController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return auth()->check()
//         ? redirect()->route('dashboard')
//         : redirect()->route('login');
// });

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/image', [ProfileController::class, 'updateImage'])->name('profile.image.update');
    Route::delete('/profile/image', [ProfileController::class, 'destroyImage'])->name('profile.image.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/social', [ProfileController::class, 'destroySocial'])->name('profile.destroySocial');
});

require __DIR__.'/auth.php';


/**
 * Home 화면 
 */
Route::get('/',[HomeController::class, 'index'])->name('home');


/**
 * 관리자 전용 라우트 그룹
 */
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/{tab?}', [AdminDashboard::class, 'index'])
        ->whereIn('tab', array_keys(config('admin.tabs', [])))
        ->name('admin');                    
    
    // 카테고리 
    Route::post('/admin/categories/save', [CategoryController::class, 'store'])->name('admin.categories.store'); // 저장 
    Route::delete('/admin/categories/delete/{category_ids}',[CategoryController::class,'destroy'])->name('admin.categories.delete'); // 삭제
    Route::patch('/admin/category/update/{category_id}',[CategoryController::class, 'update'])->name('admin.category.update'); // 수정
    Route::patch('/admin/categories/updateSort', [CategoryController::class, 'updateSort'])->name('admin.category.updateSort'); // 정렬 순서 변경
    Route::patch('/admin/categories/updateIsActive/{category_ids}', [CategoryController::class, 'updateIsActive'])->name('admin.categories.updateIsActive'); // 활성화/비활성화

    // User
    Route::patch('/admin/user/update/{user_id}',[ProfileController::class, 'updateUserInAdmin'])->name('admin.user.update'); // 수정 

    // 태그
    Route::post('/admin/tag/save',[TagController::class, 'store'])->name('admin.tag.store'); // 저장 
    Route::patch('/admin/tag/update/{tag_id}',[TagController::class, 'update'])->name('admin.tag.update'); // 수정
    Route::delete('/admin/tags/delete/{tag_ids}',[TagController::class,'destroy'])->name('admin.tags.delete'); // 삭제
    Route::patch('/admin/tags/updateIsBlocked/{tag_ids}',[TagController::class, 'updateIsBlocked'])->name('admin.tags.updateIsBlocked'); // 금지/사용 수정
    
    /**
     * Tips
     */
    // 팁 생성/수정 페이지
    Route::get('/admin/tips/form/{tip_id?}', [TipController::class, 'form'])
        ->whereNumber('tip_id')
        ->name('admin.tip.form');
    Route::post('/tip/store',[TipController::class, 'saveTip'])->name('tip.store'); // 추가
    Route::post('/tip/update/{tip_id}',[TipController::class, 'updateTipPost'])->name('tip.update'); // 수정
    Route::delete('/tips/delete/{tip_id}', [TipController::class, 'destroy'])
        ->whereNumber('tip_id')
        ->name('tip.destroy');
    // 개별페이지
    Route::get('/tip/{tip_id}',[TipController::class, 'showPost'])
        ->whereNumber('tip_id');
    

    
    /**
     * Summernote
     */
    // 이미지 올렸을 때
    Route::post('/summernote/uploades/image',[SummernoteController::class, 'uploadImage'])->name('summernote.uploadImage');


});
