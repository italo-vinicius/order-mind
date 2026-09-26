<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ToolLog;
use App\Models\User;

class ToolLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, ToolLog $toolLog): bool
    {
        return $user->role === UserRole::Admin;
    }
}
