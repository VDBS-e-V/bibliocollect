<?php

declare(strict_types=1);

namespace App\Foundation\Auth;

use RuntimeException;

final class PermissionRegistry
{
    /**
     * @return array<string, array{label:string,description:string}>
     */
    public function all(): array
    {
        $configured = config('authorization.permissions', []);

        if (! is_array($configured)) {
            throw new RuntimeException('authorization.permissions must be an array.');
        }

        $permissions = [];

        foreach ($configured as $key => $definition) {
            if (! is_string($key) || $key === '') {
                throw new RuntimeException('Every permission needs a non-empty string key.');
            }

            if (! is_array($definition)) {
                throw new RuntimeException("Permission [{$key}] must be configured as an array.");
            }

            $label = $definition['label'] ?? null;
            $description = $definition['description'] ?? '';

            if (! is_string($label) || $label === '') {
                throw new RuntimeException("Permission [{$key}] needs a non-empty label.");
            }

            if (! is_string($description)) {
                throw new RuntimeException("Permission [{$key}] description must be a string.");
            }

            $permissions[$key] = [
                'label' => $label,
                'description' => $description,
            ];
        }

        ksort($permissions);

        return $permissions;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function has(string $permission): bool
    {
        return array_key_exists($permission, $this->all());
    }
}
