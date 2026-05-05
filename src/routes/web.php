<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\TipController as AdminTipController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\SummernoteController;
use App\Http\Controllers\TipBrowseController;
use App\Http\Controllers\TipManageController;
use App\Http\Controllers\TipReactionController;
use App\Http\Controllers\UserFollowController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return auth()->check()
//         ? redirect()->route('dashboard')
//         : redirect()->route('login');
// });

Route::get('/dashboard', function () {
    return redirect('/profile');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/image', [ProfileController::class, 'updateImage'])->name('profile.image.update');
    Route::delete('/profile/image', [ProfileController::class, 'destroyImage'])->name('profile.image.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // 마이페이지
    Route::get('/mypage/{tab?}', [MyPageController::class, 'index'])
        ->whereIn('tab', array_keys(config('mypage.tabs', [])))
        ->defaults('tab', 'profile')
        ->name('mypage');
});

require __DIR__.'/auth.php';


/**
 * Home 화면 
 */
Route::get('/',[HomeController::class, 'index'])->name('home');


/**
 * 팁관련
 */
// 개별페이지
Route::get('/tip/{tip_id}',[TipBrowseController::class, 'showPost'])
    ->whereNumber('tip_id')->name('tip.show');
// 리스트 페이지
Route::get('/tips/',[TipBrowseController::class, 'tipList'])->name('tips.list');
// 검색 페이지
Route::get('/tips/search',[TipBrowseController::class, 'tipSearch'])->name('tips.search');
// 사용자 피드 페이지
Route::get('/tips/user/{user_id}',[TipBrowseController::class, 'tipUserFeed'])->name('tips.userFeed');
// 분류별 페이지
Route::get('/tips/category/{category_id}',[TipBrowseController::class, 'tipListBySort'])->whereNumber('category_id')->name('tips.category');
Route::get('/tips/tag/{tag_id}',[TipBrowseController::class, 'tipListBySort'])->whereNumber('tag_id')->name('tips.tag');
// 좋아요
Route::post('/tip/like/{tip_id}', [TipReactionController::class, 'like'])->whereNumber('tip_id')->middleware('auth')->name('tip.like');
// 북마크
Route::post('/tip/bookmark/{tip_id}', [TipReactionController::class, 'bookmark'])->whereNumber('tip_id')->middleware('auth')->name('tip.bookmark');
// 댓글
Route::post('/tip/comment/{tip_id}',[CommentController::class, 'commentAdd'])->whereNumber('tip_id')->middleware('auth')->name('tip.add.comment');
// 댓글 리스트
Route::get('/tip/comment_list/{tip_id}',[CommentController::class, 'commentList'])->whereNumber('tip_id')->name('tip.comment.list');
// 댓글 좋아요
Route::post('/tip/comment/like/{comment_id}',[CommentController::class, 'commentLike'])->whereNumber('comment_id')->middleware('auth')->name('tip.comment.like');
// 댓글 삭제 (물리삭제가 아닌 status 변경)
Route::delete('/tip/comment/{comment_id}',[CommentController::class, 'commentDelete'])->whereNumber('comment_id')->middleware('auth')->name('tip.comment.delete');
// 댓글 수정
Route::patch('/tip/comment/{comment_id}',[CommentController::class, 'commentUpdate'])->whereNumber('comment_id')->middleware('auth')->name('tip.comment.update');
// 글 작성 
Route::get('/tips/form/{tip?}',[TipManageController::class, 'formFront'])->whereNumber('tip')->middleware('auth')->name('tip.formFront');
Route::post('/tips',[TipManageController::class, 'store'])->middleware('auth')->name('tip.store'); // 프론트/관리자 공통 추가
Route::patch('/tips/{tip}',[TipManageController::class, 'update'])
    ->whereNumber('tip')
    ->middleware('auth')
    ->name('tip.update'); // 프론트/관리자 공통 수정
Route::delete('/tips/{tip}', [TipManageController::class, 'destroy'])
    ->whereNumber('tip')
    ->middleware('auth')
    ->name('tip.destroy'); // 프론트/관리자 공통 삭제
Route::post('/summernote/uploades/image',[SummernoteController::class, 'uploadImage'])
    ->middleware('auth')
    ->name('summernote.uploadImage');

// 팔로우
Route::post('/user/follow/{user_id}', [UserFollowController::class, 'followUser'])->whereNumber('user_id')->middleware('auth')->name('user.follow');
Route::get('/user/follows/{user_id}', [UserFollowController::class, 'followList'])->whereNumber('user_id')->name('user.follow.list');

// 알림 : 읽음 처리
Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])
    ->name('notifications.read');

Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
    ->name('notifications.readAll');

/**
 * 관리자 전용 라우트 그룹
 */
Route::middleware(['admin', 'auth'])->group(function () {
    Route::get('/admin/{tab?}', [AdminDashboardController::class, 'index'])
        ->whereIn('tab', array_keys(config('admin.tabs', [])))
        ->name('admin');                    
    
    // 카테고리 
    Route::post('/admin/categories/save', [AdminCategoryController::class, 'store'])->name('admin.categories.store'); // 저장
    Route::delete('/admin/categories/delete/{category_ids}',[AdminCategoryController::class,'destroy'])->name('admin.categories.delete'); // 삭제
    Route::patch('/admin/category/update/{category_id}',[AdminCategoryController::class, 'update'])->name('admin.category.update'); // 수정
    Route::patch('/admin/categories/updateSort', [AdminCategoryController::class, 'updateSort'])->name('admin.category.updateSort'); // 정렬 순서 변경
    Route::patch('/admin/categories/updateIsActive/{category_ids}', [AdminCategoryController::class, 'updateIsActive'])->name('admin.categories.updateIsActive'); // 활성화/비활성화

    // User
    Route::patch('/admin/user/update/{user_id}',[AdminUserController::class, 'update'])->name('admin.user.update'); // 수정

    // 태그
    Route::post('/admin/tag/save',[AdminTagController::class, 'store'])->name('admin.tag.store'); // 저장
    Route::patch('/admin/tag/update/{tag_id}',[AdminTagController::class, 'update'])->name('admin.tag.update'); // 수정
    Route::delete('/admin/tags/delete/{tag_ids}',[AdminTagController::class,'destroy'])->name('admin.tags.delete'); // 삭제
    Route::patch('/admin/tags/updateIsBlocked/{tag_ids}',[AdminTagController::class, 'updateIsBlocked'])->name('admin.tags.updateIsBlocked'); // 금지/사용 수정
    
    /**
     * Tips
     */
    // 팁 생성/수정 페이지
    Route::get('/admin/tips/form/{tip?}', [AdminTipController::class, 'form'])
        ->whereNumber('tip')
        ->name('admin.tip.form');

});
