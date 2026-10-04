<?php

declare(strict_types=1);

namespace App\Foundation\Support;

final class SurfaceRegistry
{
    /** @return array<string, array{name:string,path:string,routes:string}> */
    public function all(): array
    {
        $surfacesPath = (string) config('foundation.surfaces_path', app_path('Surfaces'));
        $directories = glob($surfacesPath.'/*', GLOB_ONLYDIR) ?: [];
        sort($directories);

        $surfaces = [];

        foreach ($directories as $directory) {
            $name = basename($directory);

            if ($name === '_Template') {
                continue;
            }

            $surfaces[$name] = [
                'name' => $name,
                'path' => $directory,
                'routes' => $directory.'/routes.php',
            ];
        }

        return $surfaces;
    }
}
