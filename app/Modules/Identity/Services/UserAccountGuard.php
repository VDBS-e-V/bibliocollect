<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Foundation\Auth\RoleRegistry;
use App\Models\User;
use App\Modules\Identity\Exceptions\UserAccountStateConflict;
use App\Modules\Identity\Models\UserRoleAssignment;

/** Schützt davor, dass die Verwaltung sich selbst aussperrt: Es muss immer mindestens ein aktives Konto Benutzerkonten verwalten dürfen. */
final readonly class UserAccountGuard
{
    public const PERMISSION = 'users.manage';

    public function __construct(private RoleRegistry $roles) {}

    /** @param list<string> $roleKeys */
    public function grantsManagement(array $roleKeys): bool
    {
        return in_array(self::PERMISSION, $this->roles->permissionsFor($roleKeys), true);
    }

    /** Ist außer diesem Konto noch ein aktives Konto mit dem Recht „Benutzerkonten verwalten“ vorhanden? */
    public function anotherManagerExists(User $except): bool
    {
        $keys = array_keys(array_filter($this->roles->all(), static fn (array $role): bool => in_array(self::PERMISSION, $role['permissions'], true)));

        if ($keys === []) {
            return false;
        }

        $userIds = UserRoleAssignment::query()->whereIn('role_key', $keys)->where('user_id', '!=', $except->getKey())->pluck('user_id');

        return User::query()->whereIn('id', $userIds)->whereNull('disabled_at')->exists();
    }

    /** @param list<string> $newRoleKeys */
    public function assertCanChangeRoles(User $user, array $newRoleKeys): void
    {
        if ($this->grantsManagement($user->roleKeys()) && ! $this->grantsManagement($newRoleKeys) && ! $this->anotherManagerExists($user)) {
            throw UserAccountStateConflict::lastManager();
        }
    }

    public function assertCanDisable(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw UserAccountStateConflict::ownAccount();
        }

        if ($this->grantsManagement($user->roleKeys()) && ! $this->anotherManagerExists($user)) {
            throw UserAccountStateConflict::lastManager();
        }
    }
}
