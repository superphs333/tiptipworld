<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TipController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Web entry points: map URLs to controllers or Inertia pages.
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * 팁 관련 라우트
 */
// Public: list + detail.
Route::resource('tips', TipController::class)
    ->only(['index', 'show'])
    ->whereNumber('tip');

// Authenticated: create/edit/delete.
Route::middleware(['auth'])->group(function () {
    Route::resource('tips', TipController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy']);
});


require __DIR__.'/auth.php';
