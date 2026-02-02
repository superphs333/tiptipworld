<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SocialAccountRevoker
{
    /**
     * 요청한 사용자에 대해 provider 정보로 revoke 호출을 시도합니다.
     */
    public function revoke(User $user): bool
    {
        $provider = $user->provider;

        if (!$provider) {
            Log::warning('소셜 공급자(provider)가 존재하지 않아 revoke를 건너뜁니다.', ['user_id' => $user->id]);

            return false;
        }

        $metadata = json_decode($user->social_meta ?? '', true);

        if (!is_array($metadata)) {
            $metadata = [];
        }

        if (empty($metadata)) {
            Log::info('소셜 메타데이터가 없어 revoke를 건너뜁니다.', ['user_id' => $user->id, 'provider' => $provider]);

            return false;
        }

        $result = match ($provider) {
            'google' => $this->revokeGoogle($metadata),
            'kakao' => $this->revokeKakao($metadata),
            default => $this->unsupportedProvider($provider, $user->id),
        };

        if (!$result) {
            return false;
        }

        $user->social_meta = null;
        $user->saveQuietly();

        return true;
    }

    private function revokeGoogle(array $payload): bool
    {
        // Access Token은 1시간이면 만료되므로, Refresh Token이 있다면 우선 사용
        $token = $payload['refreshToken'] ?? $payload['token'] ?? $payload['access_token'] ?? null;

        if (!$token) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://oauth2.googleapis.com/revoke', ['token' => $token]);

            if (!$response->successful()) {
                Log::warning('Google revoke가 실패했습니다.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }
        } catch (\Throwable $exception) {
            Log::error('Google revoke 요청 실패', [
                'exception' => $exception,
            ]);

            return false;
        }

        return true;
    }

    private function revokeKakao(array $payload): bool
    {
        $token = $payload['token'] ?? $payload['access_token'] ?? null;

        if (!$token) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
            ])
                ->timeout(10)
                ->post('https://kapi.kakao.com/v1/user/unlink');

            if (!$response->successful()) {
                Log::warning('Kakao revoke가 실패했습니다.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }
        } catch (\Throwable $exception) {
            Log::error('Kakao revoke 요청 실패', [
                'exception' => $exception,
            ]);

            return false;
        }

        return true;
    }

    private function unsupportedProvider(string $provider, int $userId): bool
    {
        Log::warning('지원하지 않는 소셜 공급자', ['provider' => $provider, 'user_id' => $userId]);

        return false;
    }
}
