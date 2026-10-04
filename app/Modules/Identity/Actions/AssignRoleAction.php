<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Foundation\Auth\RoleRegistry;
use App\Models\User;
use App\Modules\Identity\Models\UserRoleAssignment;
use InvalidArgumentException;

final readonly class AssignRoleAction
{
    public function __construct(private RoleRegistry $roles) {}

    public function execute(User $user, string $roleKey, ?User $assignedBy = null): UserRoleAssignment
    {
        if (! array_key_exists($roleKey, $this->roles->all())) {
            throw new InvalidArgumentException("Unknown role [{$roleKey}].");
        }

        /** @var UserRoleAssignment $assignment */
        $assignment = $user->roleAssignments()->firstOrCreate(
            ['role_key' => $roleKey],
            ['assigned_by_user_id' => $assignedBy?->getKey()],
        );

        return $assignment;
    }
}
