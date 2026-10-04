<?php

declare(strict_types=1);

namespace App\Modules\Identity\Queries;

use App\Models\User;

final class HasUserByPatronIdQuery
{
    public function execute(string $patronId): bool
    {
        return User::query()
            ->where('patron_id', $patronId)
            ->exists();
    }
}
