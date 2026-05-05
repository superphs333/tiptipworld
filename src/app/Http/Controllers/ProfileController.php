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

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->loadMissing('socialAccounts');

        if ($user->hasSocialAccounts()) {
            if (! $this->revoker->revoke($user)) {
                return Redirect::route('profile.edit')
                    ->withErrors(['account' => '소셜 연결 해제에 실패했습니다. 다시 시도해 주세요.'], 'userDeletion');
            }
        } else {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        }

        $this->profileImages->remove($user, false);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
