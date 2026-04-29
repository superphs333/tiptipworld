<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SocialAuthException;
use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * 소셜 로그인 진입점과 콜백 완료 처리를 담당
 * 
 * [역할]
 * - Goolge, Kakao 같은 소셜 로그인 요청을 받음
 * - 실제 소셜 인증/회원 연동 로직은 SocialAuthService에 위임
 * - 성공하면 로그인 세션, 실패하면 로그인 화면으로 돌려보냄
 * 
 */
class SocialLoginController extends Controller
{
    public function __construct(
        private SocialAuthService $socialAuth,
    ) {
    }

    /**
     * 사용자를 해당 provider의 OAuth 인증 화면으로 리다이렉션 
     * 
     * @param string $provider : 현재 로그인 대상 provider 이름 (google, kakao)
     * 
     * @return RedirectResponse : SocialAuthService가 provider별 scope/옵션을 적용한 뒤 redirect 응답.
     */
    public function redirect(string $provider): RedirectResponse
    {
        return $this->socialAuth->redirect($provider);
    }

    /**
     * provider 인증 완료 후 돌아온 callback 요청을 처리
     * 
     * @param string $provider : 현재 callback을 처리할 provider 이름
     * 
     * @return RedirectResponse : 성공 시 로그인 후 원래 가려던 또는 홈으로 이동, 실패 시 로그인 페이지로 이동
     * 
     * [흐름]
     * - 성공
     *  - SocialAuthService가 최종 User을 반환 
     *  - Auth::login($user, true)로 로그인 상태 만들기
     *  - session regenerate()로 세션 고정 공격 방어를 강화
     *  - intended()로 원래 이동하려던 페이지가 있으면 그쪽으로 보내기
     * - 실패
     *  - SocialAuthException은 사용자에게 안내 가능한 소셜 로그인 실패  (ex. provider 응답 실패, 이메일 누락, 다른 소셜 계정과 충돌)
     *  - 이 경우 로그인 화면으로 돌려보내고 메세지를 errors에 담아 전달 
     */
    public function callback(string $provider): RedirectResponse
    {
        try {
            $user = $this->socialAuth->resolveFromCallback($provider);
        } catch (SocialAuthException $e) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => $e->getMessage()]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }
}
