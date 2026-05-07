<?php

namespace App\View\Composers;

use App\Models\User;
use App\Services\SocialProviderRegistry;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

/**
 * 소셜 연동 섹션용 데이터 조립기
 * 
 * [역할]
 * - Blade에 복잡한 조건 분기를 하지 않도록 소셜 카드 목록 / 버튼 타입 / 상태 메세지 등을 여기서 미리 만듦
 * - 공급자 목록은 SocialProviderResistry에서 가져오고, 현재 사용자의 socialAccounts 상태와 합쳐서 최종 카드 데이터로 만든다. 
 */
final class ProfileSocialConnectionsComposer
{
    public function __construct(
        private SocialProviderRegistry $providers,
    ) {
    }

    /**
     * 소셜 연동 파셜에 필요한 뷰 데이터 구성
     * 
     * 1. 기본값 셋팅
     * 2. 현재 user 가져오기
     * 3. user의 socialAccounts 로드
     * 4. 공급자 목록과 현재 연결 상태를 합쳐 카드 배열 생성
     * 5. 에러/상태 메세지까지 함께 뷰에 전달 
     */
    public function compose(View $view): void
    {
        // user가 없더라도 파셜이 깨지지 않도록 기본값 먼저 셋팅 
        $view->with([
            'socialConnectionCards' => [],
            'socialConnectionEmptyStateMessage' => '현재 사용할 수 있는 소셜 공급자가 없습니다. 공급자 키가 설정되면 이 영역에 자동으로 나타납니다.',
            'socialConnectionMessages' => $this->messages(),
            'socialConnectionStatus' => $this->status(),
        ]);

        // 현재 뷰에 전달된 user 꺼내기 
        $user = $view->getData()['user'] ?? null;

        // user가 없으면 기본값만 유지하고 종료 
        if (! $user instanceof User) {
            return;
        }

        // 카드 조립에 socialAccounts가 필요하므로 필요할 때만 미리 로드 
        $user->loadMissing('socialAccounts');

        // 연결된 소셜 계정을 provider 이름 기준으로 빠르게 찾을 수 있게 keyBy 처리 
        $connectedAccounts = $user->socialAccounts->keyBy(
            fn ($account) => Str::lower((string) $account->provider),
        );
        // 현재 몇 개으 소셜 계정이 연결되어 있는지 
        $connectedCount = $user->socialAccounts->count();
        // 사용자가 실제로 로컬 비밀번호 로그인 가능한 상태인지 
        $hasUsablePasswordLogin = $user->hasUsablePasswordLogin();
        // 현재 어느 화면에서 이 파셜을 보고 있는지 기억 (소셜 연결/해제 후 원래 화면으로 되돌리기)
        $returnTo = request()->routeIs('profile.edit') ? 'profile.edit' : 'mypage';
        $cards = [];

        // 설정된 모든 활성 소셜 공급자에 대해 카드 하나씩 생성 
        foreach ($this->providers->enabled() as $providerKey => $provider) {
            $socialAccount = $connectedAccounts->get($providerKey);
            $isConnected = $socialAccount !== null;

            $cards[] = [
                // 카드에 표시할 기본 메타 정보 
                'name' => $provider['name'],
                'description' => $provider['description'],
                'icon' => $provider['icon'],
                'icon_class' => $provider['icon_class'],

                // 연결 상태에 따라 카드 배경/테두리 색 변경 
                'card_class' => $isConnected
                    ? 'border-emerald-200 bg-emerald-50/40'
                    : 'border-slate-200 bg-slate-50/70',

                // 연결됨 / 미연결 상태 배지 
                'status' => [
                    'label' => $isConnected ? '연결됨' : '미연결',
                    'class' => $isConnected
                        ? 'border border-emerald-200 bg-emerald-50 text-emerald-700'
                        : 'border border-slate-200 bg-white text-slate-600',
                ],
                // 연결된 계정이면 마지막 동기화 시각 표시                
                'synced_at' => $socialAccount?->updated_at?->format('Y.m.d H:i'),

                // 연결하기 / 연결 해제 / 안내문구 중 어떠 ㄴ액션을 보여줄지 계산 
                'action' => $this->makeAction(
                    $isConnected,
                    $connectedCount,
                    $hasUsablePasswordLogin,
                    $providerKey,
                    $provider,
                    $returnTo,
                ),
            ];
        }

        // 최종 카드 목록 전달 
        $view->with([
            'socialConnectionCards' => $cards,
        ]);
    }

    /**
     * 각 카드의 버튼/안내문구 액션 계산
     * 
     * 
     * @return array<string, string>
     * 
     * [반환타입]
     * - link     : 아직 연결 안 된 공급자 => "연결하기"
     * - unlink   : 연결된 공급자 => "연결 해제"
     * - note     : 해제 불가능한 겨웅 안내문구 
     */
    private function makeAction(
        bool $isConnected,
        int $connectedCount,
        bool $hasUsablePasswordLogin,
        string $providerKey,
        array $provider,
        string $returnTo,
    ): array {
        // 아직 연결되지 않은 공급자면 OAuth 연결 시작 링크 제공 
        if (! $isConnected) {
            return [
                'type' => 'link',
                'href' => route('profile.social.redirect', [
                    'provider' => $providerKey,
                    'return_to' => $returnTo,
                ]),
                'class' => sprintf(
                    '%s inline-flex items-center rounded-md border px-4 py-2 text-xs font-semibold tracking-widest shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2',
                    $provider['button_class'],
                ),
                'label' => '연결하기',
            ];
        }

        /**
         * 이미 연결된 공급자라면 해제 가능 여부 판단
         * : 다른 소셜이 하나 더 있거나, 로컬 비밀번호 로그인 수단이 있으면 -> 마지막 소셜이어도 해제 가능 
         */
        if ($connectedCount > 1 || $hasUsablePasswordLogin) {
            return [
                'type' => 'unlink',
                'action' => route('profile.social.destroy', ['provider' => $providerKey]),
                'return_to' => $returnTo,
                'class' => 'inline-flex items-center rounded-md border border-red-200 bg-white px-4 py-2 text-xs font-semibold tracking-widest text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
                'label' => '연결 해제',
            ];
        }

        // 마지막 소셜 연동이고 비밀번호 로그인도 불가능하면 해제 버튼 대신 안내 문구만 출력 
        return [
            'type' => 'note',
            'class' => 'text-xs text-gray-500',
            'label' => '비밀번호 설정 또는 다른 소셜 연동 후 해제할 수 있습니다.',
        ];
    }

    /**
     * socialConnections 에러백에서 메세지 추출 
     * 
     * [용도]
     * - provider 관련 실패
     * - 소셜 연결/해제 실패
     * 를 소셜 연동 섹션 하단에 출력하기 위한 메세지 배열 생성 
     * 
     * @return array<int, string>
     */
    private function messages(): array
    {
        $errors = session('errors');

        if (! $errors instanceof ViewErrorBag) {
            return [];
        }

        $bag = $errors->getBag('socialConnections');

        return array_merge(
            $bag->get('provider'),
            $bag->get('social'),
        );
    }

    /**
     * 세션 status 값을 소셜 연동 전용 상태 메세지로 변환
     * 
     * 
     * @return array{class: string, message: string}|null
     */
    private function status(): ?array
    {
        return match (session('status')) {
            'social-connected' => [
                'class' => 'text-sm text-emerald-600',
                'message' => '소셜 계정 연결이 완료되었습니다.',
            ],
            'social-disconnected' => [
                'class' => 'text-sm text-gray-600',
                'message' => '소셜 계정 연결을 해제했습니다.',
            ],
            default => null,
        };
    }
}
