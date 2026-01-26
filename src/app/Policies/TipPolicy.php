<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tip;
use App\Models\User;

class TipPolicy
{
    // Anyone can read tips; only owners can modify.
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Tip $tip): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tip $tip): bool
    {
        return $user->id === $tip->user_id;
    }

    public function delete(User $user, Tip $tip): bool
    {
        return $user->id === $tip->user_id;
    }
}
