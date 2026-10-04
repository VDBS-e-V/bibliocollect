<?php

declare(strict_types=1);

use App\Foundation\Support\ModuleRegistry;
use App\Foundation\Support\SurfaceRegistry;
use App\Foundation\Validation\FoundationValidator;

it('has a valid foundation structure', function (): void {
    $validator = app(FoundationValidator::class);

    expect($validator->validate())->toBe([]);
});

it('discovers the planned modules and four application surfaces', function (): void {
    $modules = app(ModuleRegistry::class)->all();
    $surfaces = app(SurfaceRegistry::class)->all();

    expect(array_keys($modules))->toContain('Identity', 'Patrons', 'School', 'Catalog', 'Circulation');
    expect(array_keys($surfaces))->toBe(['Administration', 'Portal', 'Pos', 'Public']);
});

it('keeps modules independent from concrete surfaces', function (): void {
    $violations = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Modules')));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        if (str_contains($contents, 'App\\Surfaces\\')) {
            $violations[] = $file->getPathname();
        }
    }

    expect($violations)->toBe([]);
});

it('keeps foundation independent from concrete product modules', function (): void {
    $violations = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Foundation')));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        if (str_contains($contents, 'App\\Modules\\')) {
            $violations[] = $file->getPathname();
        }
    }

    expect($violations)->toBe([]);
});
