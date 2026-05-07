<?php

namespace App\Services;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Media\ProfileImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * 소셜 로그인 공통 비지니스 로직
 * 
 * [용도]
 * 1. provider별 OAuth 옵션 적용
 * 2. Socilaite 사용자 정보 수신
 * 3. 기존 회원 / 기존 이메일 계정 / 신규 회원 분기 처리
 * 4. social_accounts 레코드 갱신
 * 5. 필요 시 프로필 이미지 import
 * 6. 로그인 진행이 불가능한 경우 SocialAuthException으로 명확히 실패 전달 
 */
final class SocialAuthService
{
    public function __construct(
        private ProfileImageService $profileImages,
        private SocialProviderRegistry $providers,
    ) {
    }

    /**
     * 사용자를 provider 인증 화면으로 보냄
     * 
     * @param string $provider : 현재 로그인 대상 소셜 provider 
     * 
     * @return RedirectResponse : Socialite가 만든 OAtuh 인증 화면 redirect 응답 
     * 
     * [흐름]
     * 1. provider 값을 정규화
     * 2. settingsFor()에서 provider별 scope/옵션을 가져옴
     * 3. Socilate driver 생성
     * 4. scope 적용 (Google : openid, email, profile, Kakao : account_email, profile_nickname, profile_iamge)
     * 4. 추가 with 옵션이 있으면 적용
     * 5. 최종 redirect 응답 반환 
     */
    public function redirect(string $provider): RedirectResponse
    {
        // provider 값 정규화 
        $provider = Str::lower(trim($provider));
        // provider별 OAuth 설정을 가져옴 
        $settings = $this->settingsFor($provider);
        // driver 생성후 scope 적용(어떤 정보 요청할지)
        $driver = Socialite::driver($provider)->scopes($settings['scopes']);

        // OAuth요청에 추가 파라미터 필요한 경우 
        if ($settings['with'] !== []) {
            $driver = $driver->with($settings['with']);
        }

        return $driver->redirect();
    }

    /**
     * 소셜 로그인 callback 진입점
     * 
     * provider callback에서 실제 Socialite 사용자 정보를 받아 User 모델로 해석
     * 
     * @param string $provider : callback을 처리할 provider 이름
     * @return User : 로그인 또는 연동이 완료된 최종 사용자 모델
     * @throws SocialAuthException : provider 통신 실패, 사용자 수진 실패 등 로그인 진행이 불가능한 경우
     * 
     * [흐름]
     * 1. provider 값을 정규화한다
     * 2. provider별 에러 메세지 설정을 가져옴
     * 3. Socialite::driver(...)->user()로 provider 사용자 정보를 읽음
     * 4. 예외가 발생하면 loginFailed() 예외로 감싸서 상위에 전달
     * 5. 성공하면 resolve()에 넘겨 실제 회원 연동/생성 로직을 처리 
     */
    public function resolveFromCallback(string $provider): User
    {
        $provider = Str::lower(trim($provider));
        $socialUser = $this->socialUserFor($provider);

        return $this->resolve($provider, $socialUser);
    }

    /**
     * 기존 로그인 사용자에게 소셜 계정 연결
     * 
     * [역할]
     * - 로그인 중인 User과 provider callback 결과를 연결
     * - 이미 다른 사용자에게 연결된 소셜 계정이면 차단
     * - 이미 현재 사용자에게 연결된 계정이면 메타데이터만 최신화
     * - 처음 연결이면 social_accounts 레코드 생성 
     * 
     * [처리순서]
     * 1. provider 정규화
     * 2. provider 설정 조회
     * 3. Socialite 사용자 정보 읽기
     * 4. provider 고유 id / email 추출
     * 5. provider ID 없으면 실패 
     * 6. email이 필수인데 없으면 실패 
     * 7. 같은 provider/provider_user_id가 이미 다른 사용자에게 연결돼 있으면 차단
     * 8. 현재 사용자에게 같은 provider가 이미 연결되어 있으면
     *  - 같은 외부 계정인지 확인
     *  - 맞으면 메타데이터 최신화 후 반환
     *  - 다르면 "이미 다른 계정으로 연결됨" 예외
     * 9.  처음 연결이면 trasaction 안에서 social_accounts 생성/갱신
     * 10. provider 정책상 신뢰 가능하면 email_verifed_at 반영
     * 11. 사요자 프로필 이미지가 비어 있으면 외부 아바타 import       
     */
    public function linkFromCallback(User $user, string $provider): SocialAccount
    {
        $provider = Str::lower(trim($provider));
        $settings = $this->settingsFor($provider);
        $socialUser = $this->socialUserFor($provider);
        $providerId = trim((string) $socialUser->getId());
        $email = $this->normalizeEmail($socialUser->getEmail());
        $providerName = (string) ($settings['name'] ?? Str::headline($provider));

        if ($providerId === '') {
            throw SocialAuthException::loginFailed($settings['login_error']);
        }

        if ($settings['requires_email'] && $email === null) {
            throw SocialAuthException::missingEmail($settings['missing_email_error']);
        }

        // 이미 다른 사용자 계정에 연결된 외부 계정인지 검사
        $existingAccount = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerId)
            ->first();

        if ($existingAccount !== null && $existingAccount->user_id !== $user->id) {
            throw SocialAuthException::providerAlreadyLinkedToAnotherAccount($providerName);
        }

        // 현재 사용자에게 같은 provider가 이미 연결되어 있는지 확인 
        $currentAccount = $user->socialAccounts()
            ->where('provider', $provider)
            ->first();

        // 같은 provider가 이미 연결돼 있는데 외부 계정 ID가 다르면, 구글 연결은 되어 있지만 다른 구글 계정으로 바꾸려는 시도이므로 차단
        if (
            $currentAccount !== null
            && $currentAccount->provider_user_id !== $providerId
        ) {
            throw SocialAuthException::providerAlreadyConnected($providerName);
        }

        // 이미 현재 사용자에게 동일한 외부 계정이 연결 => 새 레코드 만들지 않고 TOKEN/META만 최신화
        if ($currentAccount !== null) {
            $this->refreshExistingUser($currentAccount, $socialUser, $settings);

            return $currentAccount->fresh() ?? $currentAccount;
        }

        // 처음 연결하는 경우에만 => social_couunts 레코드를 생성 
        $socialAccount = DB::transaction(function () use ($user, $provider, $providerId, $socialUser, $settings): SocialAccount {
            $socialAccount = $this->upsertSocialAccount($user, $provider, $providerId, $socialUser);
            
            // provider 정책상 email을 신뢰할 수 있으면 사용자 email_verifed_at 반영
            $this->markEmailAsVerified($user, $settings);

            if ($user->isDirty('email_verified_at')) {
                $user->save();
            }

            return $socialAccount;
        });

        // 계정에 프로필 이미지가 비어 있을 때만 외부 아바타 import
        $this->importAvatarIfMissing($user, $socialUser->getAvatar(), $settings['avatar_filename']);

        return $socialAccount;
    }

    /**
     * Socialte 사용자 조회 공통화 
     * 
     * [역할]
     * - settingFor()에서 provider별 실패 메세지 조회 
     * - Socialte::driver(...)->user() 실행
     * - 실패 시 SocialAuthException::loginFailed(...)로 감싸서 던짐 
     */
    private function socialUserFor(string $provider): SocialiteUser
    {
        $settings = $this->settingsFor($provider);

        try {
            return Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            throw SocialAuthException::loginFailed($settings['login_error'], $e);
        }
    }

    /**
     * Socialte 사용자 정보를 기준으로 실제 서비스의 User를 결정 
     * => 소셜 사용자를 기존 회원으로 볼지, 신규 회원으로 만들지 결정
     * 
     * @param string $provider : 현재 로그인 provider
     * @param SocialteUser $socialUser : provier가 돌려준 사용자 정보 객체 
     * @return User : 최종 로그인 대상으로 확정된 사용자 모델
     * @throws SocialAuthException : 필수 정보 누락, provider 오류, 기존 계정 안내 등
     * 
     * [정책]
     * 1. 같은 provider + 같은 provider_user_id가 가장 우선
     * 2. 같은 이메일의 기존 계정이 있더라도 자동 연동하지 않는다.
     * 3. 소셜 계정이 이미 연결된 사용자만 기존 소셜 로그인으로 처리한다.
     * 
     * [처리순서]
     * 1. provider 정규화
     * 2. provider 설정 조회
     * 3. provider 고유 ID 추출
     * 4. 이메일 정규화
     * 5. 즉시 실패 조건 : provider ID X, 이메일 X
     * 6. social_accounts(provider, provider_user_id)로 기존 회원 조회
     * 7. 있으면 기존 소셜 로그인 흐름(refreshExistingUser)
     * 8. 없고 이메일 조회가 허용되면 email로 기존 계정 조회
     * 9. 찾았으면 자동 연결하지 않고 안내 예외를 던짐
     * 10. 둘 다 아니면 신규 회원 생성(registerUser)
     */
    public function resolve(string $provider, SocialiteUser $socialUser): User
    {
        $provider = Str::lower(trim($provider));
        $settings = $this->settingsFor($provider);
        $providerId = trim((string) $socialUser->getId());
        $email = $this->normalizeEmail($socialUser->getEmail());

        if ($providerId === '') {
            throw SocialAuthException::loginFailed($settings['login_error']);
        }


        if ($settings['requires_email'] && $email === null) {
            throw SocialAuthException::missingEmail($settings['missing_email_error']);
        }

        // 1. 조회 : social_accounts(provider, provider_user_id) => 기존 사용자 찾기 
        $socialAccount = SocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerId)
            ->first();

        if ($socialAccount !== null) { // 사용자 정보 최신화 
            return $this->refreshExistingUser($socialAccount, $socialUser, $settings);
        }

        // 2. 조회2 : email 기준으로 기존 사용자 찾기
        // 기존 로컬 계정이 있더라도 사용자 동의 없는 자동 연결은 하지 않음
        if ($settings['lookup_by_email'] && $email !== null) {
            $user = User::query()->where('email', $email)->first();

            if ($user !== null) {
                throw SocialAuthException::existingAccountRequiresLogin();
            }
        }

        // 소셜 계정도 업속, 같은 이메일의 기존 계정도 없을 때만 신규 가입
        return $this->registerUser($provider, $providerId, $socialUser, $email, $settings);
    }

    /**
     * 이미 같은 provider/provider_user_id로 가입된 기존 사용자의 소셜 메타데이터를 갱신 
     * 
     * @return User : 갱신 후 저장된 사용자 모델 
     * 
     * [호출시기]
     * ex) 예전에 Goolge로 가입했던 사용자가 다시 Google 로그인 하는 경우 
     * 
     * [하는 일]
     * - 최신 token / refreshToekn 정보를 반영
     * - provider 정책상 필요하면 email_verifed_at을 채움
     * - DB에 저장
     */
    private function refreshExistingUser(
        SocialAccount $socialAccount,
        SocialiteUser $socialUser,
        array $settings,
    ): User {
        $user = $socialAccount->user;

        // provider에서 새로 받은 token/refreshToken을 social meta에 반영 
        $this->syncSocialMetadata($socialAccount, $socialUser);
        // provider 정책상 이메일 인증을 신뢰할 수 있다면 email_verified_at을 채움 
        $this->markEmailAsVerified($user, $settings);

        $socialAccount->save();

        if ($user->isDirty('email_verified_at')) {
            $user->save();
        }

        return $user;
    }

    /**
     * 소셜 로그인 정보만으로 신규 회원 생성
     * 
     * @return User : 새로 저장된 사용자 모델
     * 
     * [호출시기]
     * - social_accounts(provider, provider_user_id)로도 못 찾고
     * - 이메일 기준 기존 계정도 없는 경우 
     * 
     * [역할]
     * - 새 User 인스턴스 생성
     * - 이메일, 이름, 랜덤 비밀번호 설정
     * - social_accounts 레코드 저장
     * - 필요 시 이메일 인증 상태 처리
     * - 저장 후 프로필 이미지 import 
     */
    private function registerUser(
        string $provider,
        string $providerId,
        SocialiteUser $socialUser,
        ?string $email,
        array $settings,
    ): User {
        $user = DB::transaction(function () use ($provider, $providerId, $socialUser, $email, $settings): User {
            $user = new User();
            $user->email = (string) $email;
            $user->password = Hash::make(Str::random(32));
            $user->password_set_at = null;
            $user->name = $socialUser->getName() ?: $socialUser->getNickname() ?: (string) $email;
            $this->markEmailAsVerified($user, $settings);
            $user->save();
            $this->upsertSocialAccount($user, $provider, $providerId, $socialUser); // 연결된 social_accounts 레코드 생성/갱신

            return $user;
        });

        // db 저장이 끝난 뒤 프로피 ㄹ이미지 import 
        $this->importAvatarIfMissing($user, $socialUser->getAvatar(), $settings['avatar_filename']);

        return $user;
    }

    /**
     * 사용자 모델과 연결된 소셜 계정 레코드를 upsert
     * 
     * [역할]
     * - 여러 분기에서 반복되는 provider/provider_user_id 저장 코드를 한 메서드로 묶어 중복을 줄임 
     */
    private function upsertSocialAccount(
        User $user,
        string $provider,
        string $providerId,
        SocialiteUser $socialUser,
    ): SocialAccount
    {
        /** @var SocialAccount $socialAccount */
        $socialAccount = $user->socialAccounts()->firstOrNew([
            // 같은 사용자에게 같은 provider 레코드가 있으면 재사용, 없으면 새 SocialAccount 인스턴스를 만든다. 
            'provider' => $provider,
        ]);
        // provider가 내려준 외부 사용자 고유 ID 저장
        $socialAccount->provider_user_id = $providerId;
        // token/refreshToken 같은 메타 정보 저장 
        $this->syncSocialMetadata($socialAccount, $socialUser);
        $socialAccount->save();

        return $socialAccount;
    }

    /**
     * 소셜 로그인 메타데이터를 JSON 형태로 사용자 모델에 반영 
     * 
     * [현재 저장하는 값]
     * - access toeken
     * - refresh token
     * 
     * [설계 포인트]
     * - 기존 meta를 먼저 읽기
     * - 이번 응답에 refreshToken이 없으면 기존 값을 유지
     * - provider마다 refresh toekn이 항상 오는 것은 아니므로 덮어쓰기보다 보존이 중요 
     * 
     * [결과]
     * social_accounts.meta에는 배열(JSON cast)이 저장됨 
     */
    private function syncSocialMetadata(SocialAccount $socialAccount, SocialiteUser $socialUser): void
    {
        // 기존 meta를 배열로 복원 
        $currentMeta = $socialAccount->meta;

        if (! is_array($currentMeta)) {
            $currentMeta = [];
        }

        // token/refreshtoken 정보를 저장 
        $socialAccount->meta = [
            'token' => $socialUser->token ?? $currentMeta['token'] ?? null,
            'refreshToken' => $socialUser->refreshToken ?? $currentMeta['refreshToken'] ?? null,
        ];
    }

    /**
     * provider 정책상 이메일 인증을 자동으로 인정할 수 있으면 email_verified_at을 채움 (이미 인증된 사용자는 다시 덮어쓰기 하지 않음 )
     * 
     * [호출시기]
     * - 기존 소셜 사용자 갱신
     * - 기존 계정에 소셜 계정 연결
     * - 신규 소셜 회원 가입 
     * 
     * [정책]
     * - settings['verify_email']이 true인 provider만 자동 인증 처리한다.
     * - 이미 email_verified_at이 있는 사용자는 다시 덮어쓰지 않는다.  
     */
    private function markEmailAsVerified(User $user, array $settings): void
    {
        if ($settings['verify_email'] && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        }
    }

    /**
     * 사용자의 프로필 이미지가 비어 있을 때만 외부 아바타를 내부 저장소로 import함 
     * 
     * @param User $user : 프로필 이미지를 보완할 대상 사용자
     * @param string|null $avatarUrl : provider가 제공한 외부 아바타 URL
     * @param string $filename : 저장 파일명 prefix (ex.google-profile, kakao-profile)
     */
    private function importAvatarIfMissing(User $user, ?string $avatarUrl, string $filename): void
    {
        if (! $user->profile_image_path && $avatarUrl) {
            $this->profileImages->importFromUrl($user, $avatarUrl, $filename);
        }
    }

    /**
     * 이메일 문자열을 비교 가능한 형태로 정리
     * 
     * @param string|null $email : provider가 반환한 원본 이메일
     * @return string|null : 공백제거 + 소문자화된 이메일 (비어있으면 null )
     */
    private function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email === '' ? null : Str::lower($email);
    }

    /**
     * provider별 로그인 정책과 OAuth 설정을 반환한다
     * 
     * @param string $provider : 설정을 조회할 provider 이름 
     * @return array : provider 처리에 필요한 정책 묶음
     * 
     * @return array{
     *     avatar_filename: string,
     *     login_error: string,
     *     lookup_by_email: bool,
     *     missing_email_error: string,
     *     requires_email: bool,
     *     scopes: array<int, string>,
     *     verify_email: bool,
     *     with: array<string, string>
     * }
     */
    private function settingsFor(string $provider): array
    {
        return $this->providers->provider($provider);
    }
}
