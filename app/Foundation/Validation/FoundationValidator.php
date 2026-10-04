<?php

declare(strict_types=1);

namespace App\Foundation\Validation;

use App\Foundation\Support\ModuleRegistry;
use App\Foundation\Support\SurfaceRegistry;

final readonly class FoundationValidator
{
    public function __construct(
        private ModuleRegistry $modules,
        private SurfaceRegistry $surfaces,
    ) {}

    /** @return list<string> */
    public function validate(): array
    {
        $errors = [];
        $modules = $this->modules->all();

        foreach ($modules as $name => $module) {
            $directoryName = basename($module['path']);

            if ($directoryName !== $name) {
                $errors[] = "Module {$name}: directory must match manifest name ({$directoryName}).";
            }

            foreach ($module['dependencies'] as $dependency) {
                if ($dependency === $name) {
                    $errors[] = "Module {$name}: self dependency is not allowed.";

                    continue;
                }

                if (! isset($modules[$dependency])) {
                    $errors[] = "Module {$name}: unknown dependency {$dependency}.";
                }
            }

            foreach ($module['providers'] as $provider) {
                if (! class_exists($provider)) {
                    $errors[] = "Module {$name}: provider class {$provider} does not exist.";
                }
            }
        }

        if ((bool) config('foundation.architecture.prevent_module_cycles', true)) {
            $errors = [...$errors, ...$this->cycleErrors($modules)];
        }

        foreach ($this->surfaces->all() as $surface) {
            if (! is_file($surface['routes'])) {
                $errors[] = "Surface {$surface['name']}: routes.php is missing.";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  array<string, array{name:string,description:string,dependencies:list<string>,providers:list<string>,enabled:bool,path:string}>  $modules
     * @return list<string>
     */
    private function cycleErrors(array $modules): array
    {
        /** @var list<string> $errors */
        $errors = [];

        /** @var array<string, true> $visiting */
        $visiting = [];

        /** @var array<string, true> $visited */
        $visited = [];

        /** @var list<string> $stack */
        $stack = [];

        $visit = function (string $name) use (&$visit, &$errors, &$visiting, &$visited, &$stack, $modules): void {
            if (isset($visited[$name])) {
                return;
            }

            if (isset($visiting[$name])) {
                $cycleStart = array_search($name, $stack, true);
                $cycle = $cycleStart === false ? [$name] : array_slice($stack, $cycleStart);
                $cycle[] = $name;
                $errors[] = 'Module dependency cycle: '.implode(' -> ', $cycle);

                return;
            }

            $visiting[$name] = true;
            $stack[] = $name;

            foreach ($modules[$name]['dependencies'] ?? [] as $dependency) {
                if (isset($modules[$dependency])) {
                    $visit($dependency);
                }
            }

            array_pop($stack);
            unset($visiting[$name]);
            $visited[$name] = true;
        };

        foreach (array_keys($modules) as $name) {
            $visit($name);
        }

        return $errors;
    }
}
