<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Foundation\Auth\RoleRegistry;
use App\Models\User;
use App\Modules\Identity\Services\UserAccountGuard;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Setzt die Rollen eines Kontos auf genau die gewählten. Die letzte Verwaltung kann sich nicht selbst die Rolle nehmen. */
final readonly class UpdateUserRolesAction
{
    public function __construct(
        private RoleRegistry $roles,
        private AssignRoleAction $assign,
        private RevokeRoleAction $revoke,
        private UserAccountGuard $guard,
    ) {}

    /** @param list<string> $roleKeys */
    public function execute(User $user, array $roleKeys, User $actor): void
    {
        $roleKeys = array_values(array_unique($roleKeys));

        foreach ($roleKeys as $key) {
            if (! array_key_exists($key, $this->roles->all())) {
                throw new InvalidArgumentException("Unbekannte Rolle [{$key}].");
            }
        }

        $this->guard->assertCanChangeRoles($user, $roleKeys);

        DB::transaction(function () use ($user, $roleKeys, $actor): void {
            $current = $user->roleKeys();

            foreach (array_diff($current, $roleKeys) as $removed) {
                $this->revoke->execute($user, $removed);
            }

            foreach (array_diff($roleKeys, $current) as $added) {
                $this->assign->execute($user, $added, $actor);
            }
        });
    }
}
