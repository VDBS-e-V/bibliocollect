<?php

declare(strict_types=1);

use App\Foundation\Support\ModuleRegistry;

it('keeps identity free of concrete patron module dependencies', function (): void {
    $violations = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Modules/Identity')));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        if (str_contains($contents, 'App\\Modules\\Patrons\\')) {
            $violations[] = $file->getPathname();
        }
    }

    expect($violations)->toBe([]);
});

it('uses the patron adapter as the one-way integration point', function (): void {
    $modules = app(ModuleRegistry::class)->all();

    expect($modules['Identity']['dependencies'])->toBe([])
        ->and($modules['Patrons']['dependencies'])->toContain('Identity', 'School');
});
