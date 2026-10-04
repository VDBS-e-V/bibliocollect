<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Foundation\Auth\RoleRegistry;

final readonly class StudentAgRoleRegistry
{
    /** @var list<string> */
    private const MANAGEABLE_ROLE_KEYS = [
        'student_ag_basic',
        'student_ag_extended',
    ];

    public function __construct(private RoleRegistry $roles) {}

    /**
     * @return array<string, array{label:string,permissions:list<string>}>
     */
    public function all(): array
    {
        $roles = $this->roles->all();

        return array_intersect_key($roles, array_flip(self::MANAGEABLE_ROLE_KEYS));
    }

    public function contains(string $roleKey): bool
    {
        return in_array($roleKey, self::MANAGEABLE_ROLE_KEYS, true);
    }
}
