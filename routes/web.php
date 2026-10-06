<?php

declare(strict_types=1);

$surfaceRouteFiles = glob(app_path('Surfaces/*/routes.php')) ?: [];
sort($surfaceRouteFiles);

foreach ($surfaceRouteFiles as $surfaceRouteFile) {
    if (basename(dirname($surfaceRouteFile)) === '_Template') {
        continue;
    }

    require $surfaceRouteFile;
}

require __DIR__.'/maintenance.php';
