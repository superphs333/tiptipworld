<?php

namespace App\Services;

use App\Exceptions\SocialAuthException;
use App\Models\User;
use App\Services\Media\ProfileImageService;
use Illuminate\Http\RedirectResponse;
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
 * 4. social_id, provider, social_meta 갱신
 * 5. 필요 시 프로필 이미지 import
 * 6. 로그인 진행이 불가능한 경우 SocialAuthException으로 명확히 실패 전달 
 */
final class SocialAuthService
{
    public function __construct(
        private ProfileImageService $profileImages,
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
        $settings = $this->settingsFor($provider);

        try {
            // 사용자 정보
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            throw SocialAuthException::loginFailed($settings['login_error'], $e);
        }

        return $this->resolve($provider, $socialUser);
    }

    /**
     * Socialte 사용자 정보를 기준으로 실제 서비스의 User를 결정 
     * => 소셜 사용자를 기존 회원으로 볼지, 기존 계정에 연결할지, 새 회원으로 만들지 결정
     * 
     * @param string $provider : 현재 로그인 provider
     * @param SocialteUser $socialUser : provier가 돌려준 사용자 정보 객체 
     * @return User : 최종 로그인 대상으로 확정된 사용자 모델
     * @throws SocialAuthException : 필수 정보 누락, provider 오류, 연동 충돌 등
     * 
     * [정책]
     * 1. 같은 provider + 같은 social_id가 가장 우선
     * 2. 그 다음에만 이메일 기준 연동을 시도
     * 3. 이미 다른 provider와 연결된 계정이면 자동 덮어쓰기를 막는다.
     * 
     * [처리순서]
     * 1. provider 정규화
     * 2. provider 설정 조회
     * 3. provider 고유 ID 추출
     * 4. 이메일 정규화
     * 5. 즉시 실패 조건 : provider ID X, 이메일 X
     * 6. provider+social_id로 기조 ㄴ회원 조회
     * 7. 있으면 기존 소셜 로그인 흐름(refreshExistingUser)
     * 8. 없고 이메일 연도이 허용되면 email로 기존 계정 조회
     * 9. 찾았으면 충돌 여부 검사 후 기존 계정 연동(limkExistingUser)
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

        // 1. 조회 : provider+social_id => 기존 사용자 찾기 
        $user = User::query()
            ->where('provider', $provider)
            ->where('social_id', $providerId)
            ->first();

        if ($user !== null) { // 사용자 정보 최신화 
            return $this->refreshExistingUser($user, $provider, $providerId, $socialUser, $settings);
        }

        // 2. 조회2 : email 기준으로 기존 사용자 찾기 
        if ($settings['lookup_by_email'] && $email !== null) {
            $user = User::query()->where('email', $email)->first();

            if ($user !== null) {
                $this->ensureLinkable($user, $provider, $providerId);

                // 연결 가능한 기존 계정이면 현재 소셜 정보를 연결 
                return $this->linkExistingUser($user, $provider, $providerId, $socialUser, $settings);
            }
        }

        return $this->registerUser($provider, $providerId, $socialUser, $email, $settings);
    }

    /**
     * 이미 같은 provider/social_id로 가입된 기존 사용자의 소셜 메타데이터를 갱신 
     * 
     * @return User : 갱신 후 저장된 사용자 모델 
     * 
     * [호출시기]
     * ex) 예전에 Goolge로 가입했던 사용자가 다시 Google 로그인 하는 경우 
     * 
     * [하는 일]
     * - provider, social_id를 다시 맞춤
     * - 최신 token / refreshToekn 정보를 반영
     * - provider 정책상 필요하면 email_verifed_at을 채움
     * - DB에 저장
     */
    private function refreshExistingUser(
        User $user,
        string $provider,
        string $providerId,
        SocialiteUser $socialUser,
        array $settings,
    ): User {
        // 사용자의 provider/social_id를 현재 로그인 정보와 일치시킴
        $this->applySocialIdentity($user, $provider, $providerId);
        // provider에서 새로 받은 token/refreshToken을 social_meta에 반영 
        $this->syncSocialMetadata($user, $socialUser);
        // provider 정책상 이메일 인증을 신뢰할 수 있다면 email_verified_at을 채움 
        $this->markEmailAsVerified($user, $settings);
        
        // 변경된 정보 저장 
        $user->save();

        return $user;
    }

    /**
     * 이메일 기준으로 찾은 기존 게정에 소셜 계정을 연결
     * 
     * @param User $user 이메일 기준으로 찾은 기존 사용자
     * @param string $provider provider 이름
     * @param string $providerId provider 고유 사용자 ID
     * @param SocialteUser $socialUser provider에서 받은 사용자 정보 
     * @param array $settings provider별 정책 배열
     * 
     * @return User : 연동 후 저장된 사용자 모델
     * 
     * [호출시기]
     * - 같은 이메일로 이미 가입한 계정은 있지만, 아직 social_id가 없거나, 현재 로그인하려는 같은 provider/social_id와 연동 가능한 경우 
     * 
     * [하는 일]
     * - provider, social_id 연결
     * - token 정보 저장
     * - 필요 시 이메일 인증 상태 반영
     * - 저장 후 프로필 이미지가 비어 있으면 외부 아바타 import 
     */
    private function linkExistingUser(
        User $user,
        string $provider,
        string $providerId,
        SocialiteUser $socialUser,
        array $settings,
    ): User {
        // 기존 계정에 provider/social_id를 연결
        $this->applySocialIdentity($user, $provider, $providerId);
        // 소셜 로그인 토큰 정보를 저장 
        $this->syncSocialMetadata($user, $socialUser);
        // provider 정책상 이메일 인증을 인정할 수 있으면 인증 처리 
        $this->markEmailAsVerified($user, $settings);
        $user->save();

        // 기존 사용자의 프로필 이미지가 비어 있는 경우에만, provider에서 받은 아바타 이미지 내부 저장소로 가져옴 
        $this->importAvatarIfMissing($user, $socialUser->getAvatar(), $settings['avatar_filename']);

        return $user;
    }

    /**
     * 소셜 로그인 정보만으로 신규 회원 생성
     * 
     * @return User : 새로 저장된 사용자 모델
     * 
     * [호출시기]
     * - provider/social_id로도 못 찾고
     * - 이메일 기준 기존 계정도 없는 경우 
     * 
     * [역할]
     * - 새 User 인스턴스 생성
     * - 이메일, 이름, 랜덤 비밀번호 설정
     * - provider/social_id 설정
     * - social_meta 저장
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
        $user = new User();
        $user->email = (string) $email;
        $user->password = Hash::make(Str::random(32));
        $user->name = $socialUser->getName() ?: $socialUser->getNickname() ?: (string) $email;
        $this->applySocialIdentity($user, $provider, $providerId); // provider/social_id 저장
        $this->syncSocialMetadata($user, $socialUser);  // toekn/refreshToekn 저장
        $this->markEmailAsVerified($user, $settings);// provider 정책상 이메일 인증 신뢰할 수 있으면 채움 
        $user->save();

        $this->importAvatarIfMissing($user, $socialUser->getAvatar(), $settings['avatar_filename']);

        return $user;
    }

    /**
     * 사용자 모델에 소셜 식별자(provider, social_id)를 반영
     * 
     * [역할]
     * - 여러 분기에서 반복되는 이 계정은 어떤 provider의 누구인가 설정 코드를 한 메서드로 묶어 중복을 줄임 
     */
    private function applySocialIdentity(User $user, string $provider, string $providerId): void
    {
        $user->provider = $provider;
        $user->social_id = $providerId;
    }

    /**
     * 소셜 로그인 메타데이터를 JSON 형태로 사용자 모델에 반영 
     * 
     * [현재 저장하는 값]
     * - access toeken
     * - refresh token
     * 
     * [설계 포인트]
     * - 기존 social_meta를 먼저 읽기
     * - 이번 응답에 refreshToken이 없으면 기존 값을 유지
     * - provider마다 refresh toekn이 항상 오는 것은 아니므로 덮어쓰기보다 보존이 중요 
     * 
     * [결과]
     * user->social_meta에는 JSON 문자열이 저장됨 
     */
    private function syncSocialMetadata(User $user, SocialiteUser $socialUser): void
    {
        // 기존 social_meta를 배열로 복원 
        $currentMeta = json_decode($user->social_meta ?? '{}', true);

        if (! is_array($currentMeta)) {
            $currentMeta = [];
        }

        // token/refreshtoken 정보를 json 문자열로 저장 
        $user->social_meta = json_encode([
            'token' => $socialUser->token ?? $currentMeta['token'] ?? null,
            'refreshToken' => $socialUser->refreshToken ?? $currentMeta['refreshToken'] ?? null,
        ]);
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
     * 이메일 기준, 기존 게정을 현재 소셜 로그인 시도에 연결해도 되는지 검사 
     * 
     * @throws  SocialAuthException : 이미 다른 provider/social_id와 묶인 계정이면 충돌 예외 던짐
     */
    private function ensureLinkable(User $user, string $provider, string $providerId): void
    {
        if (! $user->social_id) {
            return;
        }

        if ($user->provider !== $provider || (string) $user->social_id !== $providerId) {
            throw SocialAuthException::conflictingAccount();
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
        return match ($provider) {
            'google' => [
                'avatar_filename' => 'google-profile',
                'login_error' => '구글 로그인에 실패했습니다. 다시 시도해 주세요.',
                'lookup_by_email' => true,
                'missing_email_error' => '구글 계정 이메일을 가져올 수 없습니다.',
                'requires_email' => true,
                'scopes' => ['openid', 'email', 'profile'],
                'verify_email' => true,
                'with' => ['access_type' => 'offline'],
            ],
            'kakao' => [
                'avatar_filename' => 'kakao-profile',
                'login_error' => '카카오 로그인에 실패했습니다. 다시 시도해 주세요.',
                'lookup_by_email' => true,
                'missing_email_error' => '카카오 계정 이메일을 가져올 수 없습니다.',
                'requires_email' => true,
                'scopes' => ['account_email', 'profile_nickname', 'profile_image'],
                'verify_email' => false,
                'with' => [],
            ],
            default => throw SocialAuthException::unsupportedProvider($provider),
        };
    }
}
