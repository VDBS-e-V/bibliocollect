<?php

declare(strict_types=1);

return [
    'covers' => [
        'disk' => env('CATALOG_COVER_DISK', 'public'),
        'directory' => env('CATALOG_COVER_DIRECTORY', 'catalog/covers'),
        'max_bytes' => (int) env('CATALOG_COVER_MAX_BYTES', 8 * 1024 * 1024),
    ],
];
