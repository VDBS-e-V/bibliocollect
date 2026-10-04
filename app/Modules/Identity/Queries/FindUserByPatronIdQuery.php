<?php

declare(strict_types=1);

namespace App\Modules\Identity\Queries;

use App\Models\User;

final class FindUserByPatronIdQuery
{
    public function execute(string $patronId): ?User
    {
        return User::query()
            ->with('roleAssignments')
            ->where('patron_id', $patronId)
            ->first();
    }
}
