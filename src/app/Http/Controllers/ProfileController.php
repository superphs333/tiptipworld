<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\SocialAccountRevoker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\FileStorageService;




class ProfileController extends Controller
{

    private SocialAccountRevoker $revoker;

    public function __construct(SocialAccountRevoker $revoker)
    {
        $this->revoker = $revoker;
    }



    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
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
     * Update the user's profile image.
     */
    public function updateImage(Request $request, FileStorageService $storage): RedirectResponse
    {
        $validated = $request->validate([
            'profile_image' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $user = $request->user();

        $storage->deleteIfExists($user->profile_image_path);
        $user->profile_image_path = $storage->storeUploaded($validated['profile_image'], 'profile');

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-image-updated');
    }

    /**
     * Remove the user's profile image.
     */
    public function destroyImage(Request $request, FileStorageService $storage): RedirectResponse
    {
        $user = $request->user();

        $storage->deleteIfExists($user->profile_image_path);
        $user->profile_image_path = null;
        $user->save();

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

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Delete the user's social account.
     */
    public function destroySocial(Request $request): RedirectResponse
    {

        $user = $request->user();

        if ($user->provider === 'email') {
            abort(403);
        }

        if (!$this->revoker->revoke($user)) {
            return Redirect::route('profile.edit')
                ->withErrors(['confirmation' => '소셜 연결 해제에 실패했습니다. 다시 시도해 주세요.'], 'socialDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * 관리자에서 user의 정보 편집
     */
    public function updateUserInAdmin($user_id,Request $request): RedirectResponse
    {
        $allowedStatusItemValues = Status::getStatuses();
        $allowedRoleItemValues = Role::getAllRoles()->pluck('id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required',Rule::in($allowedStatusItemValues)],
            'roles' => ['nullable','array'],
            'roles.*' => [Rule::in($allowedRoleItemValues)]
        ]);

        $user = User::findOrFail($user_id);

        // users 테이블의 컬럼만 업데이트
        $user->update(Arr::except($validated,['roles']));

        // roles는 관계(pivot) => sync로 처리
        $user->roles()->sync($validated['roles'] ?? []);

        $persistedFilters = array_filter(
            session('users.query', []),
            static fn ($value) => !($value === null || $value === '')
        );

        return redirect()
            ->route('admin', array_merge(['tab' => 'users'], $persistedFilters))
            ->with('success', '유저의 정보가 수정되었습니다.');
    }
}
