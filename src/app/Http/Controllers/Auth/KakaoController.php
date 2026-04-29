<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Media\ProfileImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class KakaoController extends Controller
{
    public function __construct(
        private ProfileImageService $profileImages,
    ) {
    }

    /**
     * 카카오 OAuth 인증 화면으로 리디렉트.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('kakao')
            ->scopes(['profile_nickname', 'profile_image'])
            ->redirect();
    }

    /**
     * 카카오 콜백을 처리하고 로그인 또는 회원가입.
     */
    public function callback(): RedirectResponse
    {
        try {
            $kakaoUser = Socialite::driver('kakao')->user();
        } catch (Throwable $e) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => '카카오 로그인에 실패했습니다. 다시 시도해 주세요.']);
        }

        $user = User::where('social_id', $kakaoUser->getId())->first();

        // 회원가입
        if (! $user) {
            $user = new User();
            $user->password = Hash::make(Str::random(32));

            $user->name = $kakaoUser->getName();
            $user->social_id = $kakaoUser->getId();
            $user->provider = 'kakao';                
            $user->social_meta = json_encode(['token' => $kakaoUser->token,'refreshToken'=>$kakaoUser->refreshToken]); // 소셜 관련 데이터
            $user->save();

            $kakaoAvatarUrl = $kakaoUser->getAvatar();

            if (! $user->profile_image_path && $kakaoAvatarUrl) {
                $this->profileImages->importFromUrl($user, $kakaoAvatarUrl, 'kakao-profile');
            }
        } else {
            // 기존 회원 로그인 시 토큰 정보 갱신
            $currentMeta = json_decode($user->social_meta ?? '{}', true);
            $user->social_meta = json_encode([
                'token' => $kakaoUser->token,
                'refreshToken' => $kakaoUser->refreshToken ?? $currentMeta['refreshToken'] ?? null,
            ]);
            $user->save();
        }



        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }
}
