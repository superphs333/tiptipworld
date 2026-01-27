<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleLoginController extends Controller
{
    /**
     * Redirect the user to Google's OAuth screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google') 
            ->scopes(['openid', 'email', 'profile']) // 요청할 데이터 범위 지정
            ->redirect();
    }

    /**
     * Handle the Google OAuth callback.
     */
    public function callback(): RedirectResponse
    {
        try {
            // 사용자 정보 수신하기 
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => '구글 로그인에 실패했습니다. 다시 시도해 주세요.']);
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => '구글 계정 이메일을 가져올 수 없습니다.']);
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            $user = new User();
            $user->email = $email;
            $user->password = Hash::make(Str::random(32));
        }

        $user->name = $googleUser->getName() ?: $user->name ?: $email;
        $user->google_id = $googleUser->getId();

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        Auth::login($user, true); // 로그인 유지
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
