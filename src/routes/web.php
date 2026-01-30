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
    
    Route::post('/admin/categories/save', [CategoryController::class, 'store'])->name('admin.categories.store'); // 저장 
    Route::delete('/admin/categories/delete/{category_ids}',[CategoryController::class,'destroy'])->name('admin.categories.delete'); // 삭제
});
