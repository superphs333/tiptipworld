<?php

namespace App\View\Composers;

use App\Services\SocialProviderRegistry;
use Illuminate\View\View;

/**
 * 로그인/회원가입 화면용 소셜 provider 버튼 데이터를 만들기 위한 composer 
 * 
 * [역할]
 * - 현재 활성화된 소셜 공급자 목록 가져옴
 * - 각 공급자에 대해 href / class / label 을 조립
 * - Blade는 전달받은 socialProviderButtons만 렌더링하게 만든다 
 * 
 * [의도]
 * - 로그인 화면, 회원가입 화면에서 같은 provider 목록을 재사용
 * - 공급자 추가 시 Blade 직접 수정하지 않게 함.
 */
final class AuthSocialProvidersComposer
{
    public function __construct(
        private SocialProviderRegistry $providers,
    ) {
    }

    /**
     *  auth.partials.social-providers 에 필요한 버튼 데이터 조립
     * 
     * [처리흐름]
     * 1. 현재 화면이 login인지 register인지 확인
     * 2. 버튼 문구 suffix 결정
     *  - login => ~로 로그인
     *  - register => ~로 계속하기
     * 3. 활성화된 provider 목록 순회
     * 4. 각 provider의 href / class / label 생성
     * 5. 최종 버튼 배열을 socialProviderButtons로 뷰에 전달 . 
     */
    public function compose(View $view): void
    {
        // include시 넘긴 mode 값 읽기 
        $mode = (string) ($view->getData()['mode'] ?? 'login');
        // 화면 종류에 따라 버튼 문구 뒤쪽을 바꾼다 
        $suffix = $mode === 'register' ? '로 계속하기' : '로 로그인';
        $buttons = [];

        // 설정되어있고 실제로 노출 가능한 provider만 반환 
        foreach ($this->providers->enabled() as $providerKey => $provider) {
            $buttons[] = [
                'key' => (string) $providerKey,
                'name' => (string) $provider['name'],
                'description' => (string) ($provider['description'] ?? ''),
                // 해당 provider OAuth 시작 url ex) /auth/google
                'href' => route('social.redirect', ['provider' => $providerKey]),
                'icon' => (string) $provider['icon'],
                'icon_class' => (string) $provider['icon_class'],
                // provider별 버튼 스타일 클래스 ex) 구글/카카오용 색상, boder 등 
                'class' => (string) $provider['button_class'],
                // 사용자에게 보여줄 버튼 문구 ex) Google로 로그인 / Kakao로 계속하기
                'label' => sprintf('%s%s', $provider['name'], $suffix),
            ];
        }

        // Blade는 이 배열만 받아서 반복 렌더링 
        $view->with([
            'socialProviderButtons' => $buttons,
        ]);
    }
}
