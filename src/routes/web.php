<?php

use App\Http\Controllers\AdminDashboard;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
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
    
});
