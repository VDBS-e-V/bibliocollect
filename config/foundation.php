<?php

declare(strict_types=1);

return [
    'modules_path' => app_path('Modules'),
    'surfaces_path' => app_path('Surfaces'),
    'business_timezone' => env('BUSINESS_TIMEZONE', 'Europe/Berlin'),

    'architecture' => [
        'prevent_module_cycles' => true,
        'prevent_surface_dependencies_from_modules' => true,
    ],

    // Environment-Dateien: .env.example ist die Vorlage für Struktur und Kommentare, Werte bleiben im Ziel.
    'environment' => [
        'root' => base_path(),
        'template' => '.env.example',
        'targets' => ['.env'],
        'backup_path' => '.foundation/env-backups',
    ],

    // Briefpapier für alle Ausdrucke auf A4: 'farbe' (Standard), 'sw' (Schwarz-Weiß) oder 'aus'.
    'letterhead' => env('LETTERHEAD', 'farbe'),

    // Vor dem Schuljahreswechsel automatisch eine Datenbanksicherung erstellen (der Wechsel ist nicht umkehrbar).
    'backup_before_transition' => env('BACKUP_BEFORE_TRANSITION', true),

    'production' => [
        'require_debug_disabled' => true,
        'require_app_key' => true,
        'require_database_connection' => true,
    ],
];
