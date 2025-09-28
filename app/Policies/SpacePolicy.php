<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SpacePolicy
{
    /**
     * ユーザーが特定のスペースを閲覧できるか決定
     */
    public function view(User $user, Space $space): bool
    {
        return $user->id === $space->user_id;
    }

    /**
     * ユーザーが特定のスペースを更新できるか決定
     */
    public function update(User $user, Space $space): bool
    {
        return $user->id === $space->user_id;
    }

    // 他のメソッドはこの段階では不要
}
