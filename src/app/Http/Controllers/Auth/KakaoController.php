<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class KakaoController extends Controller
{
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

        $email = $kakaoUser->getEmail();

        $user = User::where('social_id', $kakaoUser->getId())->first();

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            $user = new User();
            $user->password = Hash::make(Str::random(32));
        }

        $user->name = $kakaoUser->getName() ?: $user->name ?: $email;
        $user->social_id = $kakaoUser->getId();
        if ($email) {
            $user->email = $email;
        }
        $user->provider = 'kakao';
        $kakaoAvatarUrl = $kakaoUser->getAvatar();

        if (! $user->profile_image_path && $kakaoAvatarUrl) {
            $downloadedPath = $this->downloadProfileImage($kakaoAvatarUrl);

            if ($downloadedPath) {
                $user->profile_image_path = $downloadedPath;
            }
        }

        if ($email && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function downloadProfileImage(string $avatarUrl): ?string
    {
        try {
            $response = Http::timeout(10)->get($avatarUrl);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $contentType = $response->header('Content-Type', '');

        if (! str_starts_with($contentType, 'image/')) {
            return null;
        }

        $extension = $this->resolveImageExtension($contentType);

        if (! $extension) {
            return null;
        }

        $path = 'profile-images/kakao/' . Str::uuid() . '.' . $extension;
        $stored = Storage::disk('r2')->put($path, $response->body());

        return $stored ? $path : null;
    }

    private function resolveImageExtension(string $contentType): ?string
    {
        $mime = strtolower(trim(explode(';', $contentType, 2)[0] ?? $contentType));

        return match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            default => null,
        };
    }
}
