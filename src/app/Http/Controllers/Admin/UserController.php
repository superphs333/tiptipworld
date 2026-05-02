<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * 관리자 화면에서 사용자 정보 수정을 처리
 * 
 * [역할]
 * - 관리자가 특정 사용자의 기본 정보(name, status)를 수정
 * - 사용자의 역할(role) 연결 정보 동기화 
 * - 수정 후 기존 사용자 목록 필터 상태를 유지한 채 목록 화면으로 복귀 
 */
class UserController extends Controller
{
    /**
     * 관리자 화면에서 특정 사용자의 정보를 수정
     * 
     * [처리흐름]
     * 1. 상태(status), 역할(role)에 대해 허용 가능한 값 목록을 준비 
     * 2. 요청값(name, status, roles)을 검증한다. 
     * 3. 대상 User 모델을 조회 
     * 4. 사용자 기본 정보는 update()로 저장
     * 5. 역할 정보는 roles() 관계를 sync() 해서 갱신
     * 6. 이전 사용자 목록 필터를 복원해서 admin 사용자 탭으로 리다이렉트 
     */
    public function update($user_id, Request $request): RedirectResponse
    {
        $allowedStatusItemValues = Status::getStatuses(); // status 필드에 들어올 수 있는 허용값 목록 
        $allowedRoleItemValues = Role::getAllRoles()->pluck('id'); // roles.* 검증에 사용할 허용 role ID 목록들 

        // 관리자 수정 폼 입력값 검증 
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in($allowedStatusItemValues)],
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::in($allowedRoleItemValues)],
        ]);

        // 수정 대상 사용자 조회
        $user = User::findOrFail($user_id);

        // 사용자 기본 속성 업데이트 
        $user->update(Arr::except($validated, ['roles']));
        // 사용자 역할 관계를 최종 입력값 기준으로 동기화 
        $user->roles()->sync($validated['roles'] ?? []);

        // 관리자 사용자 목록 화면에서 사용하던 검색/필터 조건을 세션에서 복원 
        $persistedFilters = array_filter(
            session('users.query', []),
            static fn ($value) => ! ($value === null || $value === '')
        );

        // 사용자 수정 후 관리자 페이지의 users 탭으로 되돌아감 
        return redirect()
            ->route('admin', array_merge(['tab' => 'users'], $persistedFilters))
            ->with('success', '유저의 정보가 수정되었습니다.');
    }
}
