<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * 일반 이메일/비밀번호 로그인 처리
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * 로그인 화면 출력
     */
    public function create(): View
    {
        return view('auth.login', [
            // 비밀번호 재설정 라우트 존재시 true (링크 노출 여부 결정함)
            'canResetPassword' => Route::has('password.request'),
        ]);
    }

    /**
     * 로그인 요청 처리
     * 
     * [역할]
     * - LoginRequest가 전달한 자격 증명으로 로그인 시도
     * - 로그인 성공 후, password_set_at이 비어 있는 기존 사용자를 보정
     * - 세션 재생성
     * - 원래 가려던 페이지 또는 기본 홈으로 리다이렉트 
     */
    public function store(LoginRequest $request): RedirectResponse
    {
    
        $request->authenticate();
        $user = $request->user(); // 방금 로그인된 사용자 가져오기

        /**
         * 기존 데이터 보정 로직 
         */
        if ($user !== null && ! $user->hasUsablePasswordLogin()) {
            $user->forceFill([
                'password_set_at' => now(),
            ])->save();
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
