<?php

namespace App\View\Composers;

use App\Models\User;
use Illuminate\View\View;

/**
 * 계정 삭제 파셸에 필요한 표시용 데이터 조립
 * 
 * [역할]
 * - delete-user-form.blade.php 가 직접 분기 계산하지 않도록, 필요한 값을 여기서 미리 만들어 전달
 *  1) 비밀번호 입력칸이 필요한지
 *  2) 어떤 안내 문구를 보여줄지를 사용자 상태에 맞게 결정함 
 * 
 */
final class DeleteUserFormComposer
{
    public function compose(View $view): void
    {
        // 기본값 셋팅 : user 데이터가 없는 화면에서도 파셜이 깨지지 않도록 우선 안전한 기본 문구와 기본 분기값
        $view->with([
            'requiresPasswordConfirmation' => true,
            'deleteAccountDescription' => __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'),
            'deleteAccountConfirmationDescription' => __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.'),
        ]);

        // 현재 view에 전달된 user 객체 
        $user = $view->getData()['user'] ?? null;

        if (! $user instanceof User) {
            return;
        }

        $user->loadMissing('socialAccounts');

        // 사용자가 하나 이상의 소셜 계정을 연결하고 있는지 
        $hasSocialAccounts = $user->hasSocialAccounts();
        $requiresPasswordConfirmation = $user->hasUsablePasswordLogin();


        $view->with([
            // 모달 안에 비밀번호 입력칸을 보여줄지 결정
            'requiresPasswordConfirmation' => $requiresPasswordConfirmation,
            // 소셜 계정이 연결이 연결돼 있는가를 기준으로 탈퇴 시 소셜 unlink도 같이 일어난다느 ㄴ점을 안내할지 결정 
            'deleteAccountDescription' => $hasSocialAccounts
                ? __('Deleting your account will permanently remove your data and unlink your social account. Please back up any important information first.')
                : __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'),
            // 조합별 의미:
                // - 비밀번호 가능 + 소셜 있음
                //   => 비밀번호 확인 후 삭제, 소셜도 같이 해제
                // - 비밀번호 가능 + 소셜 없음
                //   => 일반적인 비밀번호 확인 삭제
                // - 비밀번호 불가 + 소셜 있음
                //   => 소셜 전용 사용자 삭제 안내
                // - 비밀번호 불가 + 소셜 없음
                //   => 이론상 드문 케이스지만 되돌릴 수 없다는 일반 안내
            'deleteAccountConfirmationDescription' => $requiresPasswordConfirmation
            ? ($hasSocialAccounts
                ? __('This action cannot be undone. Please enter your password to confirm account deletion. Connected social accounts will be unlinked together.')
                : __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.'))
            : ($hasSocialAccounts
                ? __('This action cannot be undone. Social unlink will be processed together with account deletion.')
                : __('This action cannot be undone.')),
        ]);
    }
}
