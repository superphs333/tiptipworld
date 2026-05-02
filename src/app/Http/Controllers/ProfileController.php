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
 * - 일반 계정 삭제
 * - 소셜 계정 연결 해제 후 탈퇴 
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
            'user' => $request->user(),
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
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $this->profileImages->remove($user, false);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * 소셜 로그인 계정을 해제한 뒤 회원 탈퇴 처리
     * 
     * [처리흐름]
     * 1. 현재 사용자 객체를 가져옴
     * 2. provider가 email이면 소셜 계정이 아니므로 403으로 차단
     * 3. 소셜 연결 해제를 시도
     * 4. 실패하면 socialDeletion 에러백에 메세지를 담아 edit 화면으로 되돌림
     * 5. 성공하면 프로필 이미지 정리
     * 6. 로그아웃 후 사용자 계정을 삭제 
     * 7. 세션 무효화 및 토킅 재생성 후 홈으로 이동 
     * 
     * 
     */
    public function destroySocial(Request $request): RedirectResponse
    {

        $user = $request->user();

        // 이메일 가입 계정은 소셜 연결 해제 대상이 아니므로 차단 
        if ($user->provider === 'email') {
            abort(403);
        }

        // 소셜 공급자와 연결 해제 실패 시 탈퇴를 중단하고 에러 반환
        if (!$this->revoker->revoke($user)) {
            return Redirect::route('profile.edit')
                ->withErrors(['confirmation' => '소셜 연결 해제에 실패했습니다. 다시 시도해 주세요.'], 'socialDeletion');
        }

        $this->profileImages->remove($user, false);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
