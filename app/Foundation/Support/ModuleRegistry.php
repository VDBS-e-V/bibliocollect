<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use JsonException;
use RuntimeException;

final class ModuleRegistry
{
    /**
     * @return array<string, array{name:string,description:string,dependencies:list<string>,providers:list<string>,enabled:bool,path:string}>
     */
    public function all(): array
    {
        $modulesPath = (string) config('foundation.modules_path', app_path('Modules'));
        $manifests = glob($modulesPath.'/*/module.json') ?: [];
        sort($manifests);

        $modules = [];

        foreach ($manifests as $manifestPath) {
            if (basename(dirname($manifestPath)) === '_Template') {
                continue;
            }

            try {
                $data = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException("Invalid module manifest: {$manifestPath}", 0, $exception);
            }

            if (! is_array($data) || ! isset($data['name']) || ! is_string($data['name'])) {
                throw new RuntimeException("Module manifest must contain a string name: {$manifestPath}");
            }

            $name = $data['name'];
            $modules[$name] = [
                'name' => $name,
                'description' => is_string($data['description'] ?? null) ? $data['description'] : '',
                'dependencies' => $this->stringList($data['dependencies'] ?? []),
                'providers' => $this->stringList($data['providers'] ?? []),
                'enabled' => (bool) ($data['enabled'] ?? true),
                'path' => dirname($manifestPath),
            ];
        }

        return $modules;
    }

    /**
     * @return array<string, array{name:string,description:string,dependencies:list<string>,providers:list<string>,enabled:bool,path:string}>
     */
    public function enabled(): array
    {
        return array_filter($this->all(), static fn (array $module): bool => $module['enabled']);
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_string($item) && $item !== ''));
    }
}
