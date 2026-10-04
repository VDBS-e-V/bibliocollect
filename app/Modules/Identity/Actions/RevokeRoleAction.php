<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;

final class RevokeRoleAction
{
    public function execute(User $user, string $roleKey): void
    {
        $user->roleAssignments()->where('role_key', $roleKey)->delete();
    }
}
