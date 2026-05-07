<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\SocialAccountRevoker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Services\Media\ProfileImageService;

/**
 * 프로필 화면에서 사용하는 사용자 정보 수정/이미지 변경/계정 삭제 처리를 담당하는 컨트롤러
 * 
 * [역할]
 * - 프로필 편집 화면 출력
 * - 기본 프로필 정보 수정
 * - 프로필 이미지 업로드/삭제
 * - 회원 탈퇴
 */
class ProfileController extends Controller
{

    public function __construct(
        private SocialAccountRevoker $revoker, // 소셜 계정 해제 서비스
        private ProfileImageService $profileImages, // 프로필 이미지 처리 서비스
    ) {
    }

    /**
     * 프로필 편집 화면 보여주기 
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('socialAccounts'),
        ]);
    }

    /**
     * 사용자의 기본 프로필 정보 수정
     * 
     * [처리흐름]
     * 1. ProfileUpdateRequest에서 검증된 값만 가져옴
     * 2. 현재 사용자 모델에 값을 채움
     * 3. 이메일이 변경되었으면 이메일 인증 상태를 초기화
     * 4. 변경 내용 저장
     * 5. 프로필 수정 완료 상태값과 함께 edit 화면으로 리다이렉트 
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * 프로필 이미지를 새로 업로드해서 교체
     * 
     * [처리흐름]
     * 1. profile_image 입력값을 검증
     * 2. 현재 사용자 기준으로 프로필 이미지를 교체 
     * 3. 완료 상태값과 함께 edit 화면으로 리다이렉트
     */
    public function updateImage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile_image' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $this->profileImages->replace($request->user(), $validated['profile_image']);

        return Redirect::route('profile.edit')->with('status', 'profile-image-updated');
    }

    /**
     * 사용자의 프로필 이미지 삭제 
     * 
     * [동작]
     * - 현재 사용자와 연결된 프로필 이미지 제거
     * - 성공 후 상태값과 함께 edit 화면으로 리다이렉트 
     */
    public function destroyImage(Request $request): RedirectResponse
    {
        $this->profileImages->remove($request->user());

        return Redirect::route('profile.edit')->with('status', 'profile-image-removed');
    }

    /**
     * 사용자가 연결한 특정 소셜 계정을 해제 
     * 
     * - 현재 로그인 사용자의 소셜 계정 목록에서 요청한 provider를 찾음
     * - 마지막 소셜 연동인지 확인
     * - 마지막 소설이라도 로컬 비밀번호 로그인 수단이 있으면 해제 허용
     * - 반대로 마지막 소셜이고 비밀번호 로그인도 불가능하면 해제 차단
     * - 실제 해제 SocialAccountRevoker에 위임
     * - 완료 후 현재 보고 있던 화면으로 다시 이동
     */
    public function destroySocial(Request $request, string $provider): RedirectResponse
    {
        // 사용자가 어느 화면에서 해제 버튼을 눌렀는지에 따라 작업 후 다시 돌아갈 경로를 계산 
        $returnPath = $this->resolveProfileReturnPath((string) $request->input('return_to', 'mypage'));
        // 현재 로그인 사용자와 연결된 socialAccounts 준비 
        $user = $request->user()->loadMissing('socialAccounts');
        // URL의 {provider} 값으로 실제 연결된 소셜 계정 조회 (ex google, kakao)
        $socialAccount = $user->socialAccounts
            ->firstWhere('provider', strtolower(trim($provider)));

        // 연결된 provider가 없으면 잘못된 요청이므로 종료
        if ($socialAccount === null) {
            return Redirect::to($returnPath)
                ->withErrors(['provider' => '연결된 소셜 계정을 찾을 수 없습니다.'], 'socialConnections');
        }

        // 현재 이 provider을 끊으면 마지막 소셜 연동이 되는지 계산 
        $isLastSocialConnection = $user->socialAccounts->count() <= 1;

        // 사요자가 로컬 비밀번호 수단 갖고 있으면, 해제 가능 
        if ($isLastSocialConnection && ! $user->hasUsablePasswordLogin()) {
            return Redirect::to($returnPath)
                ->withErrors(['provider' => '비밀번호 로그인 또는 다른 소셜 연동이 없어 해제할 수 없습니다.'], 'socialConnections');
        }

        // disconnect 
        if (! $this->revoker->disconnect($socialAccount)) {
            return Redirect::to($returnPath)
                ->withErrors(['provider' => '소셜 연결 해제에 실패했습니다. 다시 시도해 주세요.'], 'socialConnections');
        }

        // 성공 시 상태값을 세션에 담아 원래 화면으로 되림
        return Redirect::to($returnPath)
            ->with('status', 'social-disconnected');
    }

    /**
     * 계정 삭제 처리
     * 
     * [역할]
     * - 현재 로그인 사용자의 계정을 영구 삭제 
     * - 비밀번호 로그인 가능한 사용자는 현재 비밀번호 확인을 요구
     * - 소셜 계정이 연결돼 있으면 삭제 전에 unlink/revoke 시도
     * - 사용자 데이터 정리 후 로그아웃 및 세션 무효화 
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->loadMissing('socialAccounts');

        // 사용자가 실제로 로컬 비밀번호 로그인 가능한 상태면, 소셜 연결했는지 여부 상관없이
        // 계정 삭제 전에 비밀번호 확인 요구
        if ($user->hasUsablePasswordLogin()) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        }

        // 소셜 계정이 연결돼 있으면 삭제 전에 revoke/unlink 시도 
        if ($user->hasSocialAccounts() && ! $this->revoker->revoke($user)) {
            return Redirect::route('profile.edit')
                ->withErrors(['account' => '소셜 연결 해제에 실패했습니다. 다시 시도해 주세요.'], 'userDeletion');
        }

        // 사용자 프로필 이미지 정리 
        $this->profileImages->remove($user, false);

        // 현재 인증 세션 로그아웃
        Auth::logout();

        // usrs 레코드 삭제
        $user->delete();

        // 세션 무효화 및 새 CSRF 토큰 발급 
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * 프로필 관련 작업 후 사용자를 어느 화면으로 돌려보낼지 결정
     * 
     * [용도]
     * - /profile 화면에서 소셜 연결 해제를 눌렀는지 
     * - /mypage/profile 화면에서 눌렀는지
     */
    private function resolveProfileReturnPath(string $returnTo): string
    {
        return $returnTo === 'profile.edit'
            ? route('profile.edit')
            : route('mypage', ['tab' => 'profile']);
    }
}
