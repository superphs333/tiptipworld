<?php

namespace App\Services;

use App\Exceptions\SocialAuthException;
use Illuminate\Support\Str;

/**
 * 소셜 공급자 설정/표시 메타데이터의 단일 진입점 
 * 
 * [의도]
 * - 뷰, 서비스, 라우트 쪽에서 provider 정보는 제각각 하드코딩하지 않게 함
 * - 공급자 추가 시 config만 바꾸고 나머지는 최대한 그대로 동작하게 만든다.
 * 
 * [역할]
 * - config/social-auth.php 에 있는 provider 목록은 읽는다
 * - 누락된 표시용 기본값(name, icon 등)을 채운다
 * - 현재 실행 가능한 provider만 걸러낸다
 * - key(google, kakao..)로 특정 provider 설정을 안전하게 꺼낸다
 * 
 */
final class SocialProviderRegistry
{
    /**
     * 모든 provider 설정을 정규화된 ㅕㅇ태로 반환
     * 
     * 반환 예시
     * [
     *   'google' => [
     *     'key' => 'google',
     *     'name' => 'Google',
     *     'description' => '...',
     *     'icon' => 'G',
     *     ...
     *   ],
     *   'kakao' => [
     *     ...
     *   ],
     * ]
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $providers = config('social-auth.providers', []);

        if (! is_array($providers)) {
            return [];
        }

        // 최종적으로 화면/서비스에서 사용할 정규화 결과를 담을 배열
        $resolved = [];

        // 설정에 등록된 각 provider를 순회
        foreach ($providers as $key => $provider) {
            if (! is_array($provider)) {
                continue;
            }

            $resolved[$key] = array_merge([
                'key' => $key, // provider 고유 키
                'name' => Str::headline($key), // 표시용 이름 기본값
                'description' => null, // 설명 문구 
                'icon' => Str::upper(Str::substr($key, 0, 1)), // 아이콘 텍스트 기본값 
                'icon_class' => 'border border-slate-200 bg-slate-100 text-slate-700', // 아이콘 원형 배지의 기본 css 클래스 
                'button_class' => 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-indigo-500', // 버튼 기본 css 클래스 
            ], $provider);
        }

        return $resolved;
    }

    /**
     * 현재 실제로 사용 가능한 provider만 반환
     * 
     * @return array<string, array<string, mixed>>
     */
    public function enabled(): array
    {
        return array_filter(
            $this->all(),
            fn (array $provider, string $key): bool => $this->hasCredentials($key),
            ARRAY_FILTER_USE_BOTH, // callback에 key, value 둘 다 넘김 
        );
    }

    /**
     * 등록된 provider key 목록만 반환
     * ex) ['google', 'kakao']
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    /**
     * 특정 provider 하나의 설정을 안전하게 반환
     * 
     * @return array<string, mixed>
     */
    public function provider(string $provider): array
    {
        $provider = Str::lower(trim($provider)); // 정규화

        // 정규화된 전체 목록에서 해당 provider 설정 조회 
        $settings = $this->all()[$provider] ?? null;

        // 없으면 명확한 도메인 예외 발생
        if ($settings === null) {
            throw SocialAuthException::unsupportedProvider($provider);
        }

        return $settings;
    }

    /**
     * 해당 provider가 실제로 사용 가능한지 판별
     * 
     * 기준) config/services.php의 services.{provider}.client_id 값이 채워져 있는가
     */
    private function hasCredentials(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id"));
    }
}
