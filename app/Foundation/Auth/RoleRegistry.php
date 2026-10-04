<?php

declare(strict_types=1);

namespace App\Foundation\Auth;

use RuntimeException;

final class RoleRegistry
{
    public function __construct(private readonly PermissionRegistry $permissions) {}

    /**
     * @return array<string, array{label:string,permissions:list<string>}>
     */
    public function all(): array
    {
        $configured = config('authorization.roles', []);

        if (! is_array($configured)) {
            throw new RuntimeException('authorization.roles must be an array.');
        }

        $roles = [];

        foreach ($configured as $key => $definition) {
            if (! is_string($key) || $key === '') {
                throw new RuntimeException('Every role needs a non-empty string key.');
            }

            if (! is_array($definition)) {
                throw new RuntimeException("Role [{$key}] must be configured as an array.");
            }

            $label = $definition['label'] ?? null;
            $permissionKeys = $definition['permissions'] ?? [];

            if (! is_string($label) || $label === '') {
                throw new RuntimeException("Role [{$key}] needs a non-empty label.");
            }

            if (! is_array($permissionKeys)) {
                throw new RuntimeException("Role [{$key}] permissions must be an array.");
            }

            $normalized = [];

            foreach ($permissionKeys as $permission) {
                if (! is_string($permission) || $permission === '') {
                    throw new RuntimeException("Role [{$key}] contains an invalid permission key.");
                }

                if (! $this->permissions->has($permission)) {
                    throw new RuntimeException("Role [{$key}] references unknown permission [{$permission}].");
                }

                $normalized[] = $permission;
            }

            $roles[$key] = [
                'label' => $label,
                'permissions' => array_values(array_unique($normalized)),
            ];
        }

        ksort($roles);

        return $roles;
    }

    /**
     * @param  list<string>  $roleKeys
     * @return list<string>
     */
    public function permissionsFor(array $roleKeys): array
    {
        $roles = $this->all();
        $permissions = [];

        foreach ($roleKeys as $roleKey) {
            if (! isset($roles[$roleKey])) {
                continue;
            }

            $permissions = [...$permissions, ...$roles[$roleKey]['permissions']];
        }

        $permissions = array_values(array_unique($permissions));
        sort($permissions);

        return $permissions;
    }
}
