<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleLoginController extends Controller
{
    private FileStorageService $storage;

    public function __construct(FileStorageService $storage)
    {
        $this->storage = $storage;
    }
    /**
     * Redirect the user to Google's OAuth screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google') 
            ->scopes(['openid', 'email', 'profile']) // 요청할 데이터 범위 지정
            ->with(['access_type' => 'offline']) // Refresh Token을 받기 위해 필수
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

        $user = User::where('social_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();
        }

        $isRegistration = false;

        if (! $user) {
            $isRegistration = true;
            $user = new User();
            $user->email = $email;
            $user->password = Hash::make(Str::random(32));
        }

        if ($isRegistration) {
            $user->name = $googleUser->getName() ?: $user->name ?: $email;
            $user->social_id = $googleUser->getId();

            /**
             * Social 관련 데이터
             */
            $user->provider = 'google';
            $user->social_meta = json_encode(['token' => $googleUser->token]);

            /**
             * 프로필 이미지 등록
             */
            $googleAvatarUrl = $googleUser->getAvatar();

            if (! $user->profile_image_path && $googleAvatarUrl) {
                $downloadedPath = $this->downloadGoogleProfileImage($googleAvatarUrl);

                if ($downloadedPath) {
                    $user->profile_image_path = $downloadedPath;
                }
            }

            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }

            $user->save();
        } else {
            // 기존 회원 로그인 시 토큰 정보 갱신
            $currentMeta = json_decode($user->social_meta ?? '{}', true);
            $user->social_meta = json_encode([
                'token' => $googleUser->token,
                'refreshToken' => $googleUser->refreshToken ?? $currentMeta['refreshToken'] ?? null,
            ]);

            // 이메일로 기존 가입된 사용자가 구글 로그인 시도 시 연동 정보 업데이트
            if (! $user->social_id) {
                $user->social_id = $googleUser->getId();
            }
            $user->provider = $user->provider ?: 'google';
            $user->save();
        }

        Auth::login($user, true); // 로그인 유지
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function downloadGoogleProfileImage(string $avatarUrl): ?string
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

        return $this->storage->storeRemote($response->body(), 'profile_google', $extension);
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
