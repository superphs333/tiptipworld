<?php

namespace App\Policies;

use App\Models\Tip;
use App\Models\User;

class TipPolicy
{
    // 게시글 생성 권한 확인
    public function create(User $user): bool
    {
        // db에 존재하는 실제 계정인지 확인
        return $user->exists;
    }

    // 게시글 수정 권한 확인
    public function update(User $user, Tip $tip): bool
    {
        return $user->isAdmin() || (int) $user->id === (int) $tip->user_id;
    }

    // 게시글 삭제 권한 확인
    public function delete(User $user, Tip $tip): bool
    {
        return $this->update($user, $tip);
    }
}
