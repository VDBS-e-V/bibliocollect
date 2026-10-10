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

    // Updates ohne Konsole (Verwaltung → Update). Ordner der Pakete und Ziel sind nur für Tests anders als hier. „protected“ wird nie überschrieben.
    'update' => [
        'directory' => null,
        'target' => null,
        'backup' => true,
        'protected' => ['.env', 'storage', 'public/covers', 'public/card-designs', 'public/storage', 'bootstrap/cache'],
        // Update direkt von GitHub holen (ohne Upload): nur dieses Repository, nur stabile Releases mit Prüfsumme.
        'release' => [
            'repository' => 'VDBS-e-V/bibliocollect',
            'max_bytes' => 100 * 1024 * 1024,
        ],
    ],

    'production' => [
        'require_debug_disabled' => true,
        'require_app_key' => true,
        'require_database_connection' => true,
    ],
];
