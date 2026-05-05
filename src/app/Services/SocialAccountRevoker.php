<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 소셜 계정 연동 해제 / 토큰 폐기 처리를 담당하는 서비스 클래스 
 * 
 * [역할]
 * - 사용자가 연결한 소셜 계정 목록을 조회
 * - provider별로 Google/Kako revoke API를 호출
 * - revoke 성공 후 DB에 저장된 소셜 토큰 메타데이터를 제거 
 * 
 * [사용예]
 * - 회원 탈퇴 시
 * - 소셜 계정 연결 해제 시
 * - 개인정보 보호를 위해 외부 OAuth 토큰을 폐기해야 할 때 
 */
final class SocialAccountRevoker
{
    /**
     * 요청한 사용자에 대해 provider 정보로 
     * revoke 호출을 시도
     */
    public function revoke(User $user): bool
    {
        // 사용자의 소셜 계정 목록 조회 
        $socialAccounts = $user->socialAccounts()->get();

        // 연결된 소셜 계정이 하나도 없는 경우
        if ($socialAccounts->isEmpty()) {
            Log::warning('연결된 소셜 계정이 존재하지 않아 revoke를 건너뜁니다.', ['user_id' => $user->id]);

            return false;
        }

        // 연결된 모든 소셜 계정에 대해 revoke 시도 
        foreach ($socialAccounts as $socialAccount) {
            if (! $this->revokeAccount($socialAccount)) {
                return false;
            }
        }

        // 모든 소셜 계정의 revoke가 성공한 경우 true 반환 
        return true;
    }

    /**
     * 개별 소셜 계정 하나에 대해 provider별 revoke 처리를 수행 
     */
    private function revokeAccount(SocialAccount $socialAccount): bool
    {
        $provider = $socialAccount->provider;
        $metadata = $socialAccount->meta;

        // 메타데이터가 배열이 아니거나 비어있으면 revoke할 토큰 x
        if (! is_array($metadata) || $metadata === []) {
            Log::info('소셜 메타데이터가 없어 revoke를 건너뜁니다.', [
                'social_account_id' => $socialAccount->id,
                'user_id' => $socialAccount->user_id,
                'provider' => $provider,
            ]);

            return false;
        }

        $result = match ($provider) {
            'google' => $this->revokeGoogle($metadata),
            'kakao' => $this->revokeKakao($metadata),
            default => $this->unsupportedProvider($provider, $socialAccount->user_id),
        };

        // provider별 revoke실패시 -> 
        // DB의 meta값을 제거하지 x 
        // (실제 외부 서비스에서 revoke가 완료되지 않았는데
        // 내부 토큰 정보만 지워버리면 추후 재시도하기 어려워짐)
        if (! $result) {
            return false;
        }

        // revoke 성공했으므로 -> db에 저장된 토큰 메타데이터 제거
        $socialAccount->meta = null;
        // 부수 효과 없이 조용히 db만 삭제
        $socialAccount->saveQuietly();

        return true;
    }

    /**
     * Google OAuth 토큰을 revoke 
     */
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
