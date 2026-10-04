<?php

declare(strict_types=1);

return [
    'modules_path' => app_path('Modules'),
    'surfaces_path' => app_path('Surfaces'),

    'architecture' => [
        'prevent_module_cycles' => true,
        'prevent_surface_dependencies_from_modules' => true,
    ],

    'production' => [
        'require_debug_disabled' => true,
        'require_app_key' => true,
        'require_database_connection' => true,
    ],
];
