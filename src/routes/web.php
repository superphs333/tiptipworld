<?php

use App\Http\Controllers\AdminDashboard;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
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
    Route::get('/admin', [AdminDashboard::class, 'index'])->name('admin.index');
    Route::get('/admin/dashboard', function () {
        return redirect()->route('admin.index');
    })->name('admin.dashboard');
    Route::get('/admin/users', function () {
        return redirect()->route('admin.index', ['tab' => 'users']);
    })->name('admin.users');
    Route::get('/admin/categories', function () {
        return redirect()->route('admin.index', ['tab' => 'categories']);
    })->name('admin.categories');
    Route::get('/admin/tags', function () {
        return redirect()->route('admin.index', ['tab' => 'tags']);
    })->name('admin.tags');
    Route::get('/admin/tips', function () {
        return redirect()->route('admin.index', ['tab' => 'tips']);
    })->name('admin.tips');
});
