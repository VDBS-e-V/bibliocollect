<?php

declare(strict_types=1);

use App\Foundation\Auth\PermissionRegistry;
use App\Foundation\Auth\RoleRegistry;
use App\Foundation\Navigation\NavigationRegistry;
use App\Foundation\Support\BusinessClock;
use App\Foundation\Support\ModuleRegistry;
use App\Modules\Catalog\Contracts\CatalogImportSource;
use App\Modules\Catalog\Import\CsvCatalogImportSource;

it('defines stable surface permissions and combinable role bundles', function (): void {
    $permissions = app(PermissionRegistry::class);
    $roles = app(RoleRegistry::class);

    expect($permissions->keys())->toContain(
        'surface.portal.access',
        'surface.pos.access',
        'surface.administration.access',
        'catalog.manage',
        'catalog.import',
    );

    expect($roles->permissionsFor(['student_ag_basic']))
        ->toContain('surface.portal.access', 'surface.pos.access')
        ->not->toContain('surface.administration.access', 'catalog.manage');

    expect($roles->permissionsFor(['student_ag_extended']))
        ->toContain('surface.portal.access', 'surface.pos.access', 'catalog.manage')
        ->not->toContain('surface.administration.access', 'catalog.import');

    expect($roles->permissionsFor(['staff', 'management']))
        ->toContain('catalog.manage', 'catalog.import');

    expect($roles->permissionsFor(['technical_admin']))
        ->toBe(['surface.administration.access']);
});

it('keeps identity independent from patrons at module level', function (): void {
    $modules = app(ModuleRegistry::class)->all();

    expect($modules['Identity']['dependencies'])->toBe([]);
    expect($modules['Patrons']['dependencies'])->toContain('School');
});

it('builds navigation for all four surfaces', function (): void {
    $navigation = app(NavigationRegistry::class);

    expect($navigation->allForSurface('public'))->toHaveCount(3);
    expect($navigation->allForSurface('portal'))->toHaveCount(1);
    expect($navigation->allForSurface('pos'))->toHaveCount(9);
    expect($navigation->allForSurface('administration'))->toHaveCount(5);
});

it('uses Europe Berlin as the business timezone by default', function (): void {
    expect(app(BusinessClock::class)->timezone()->getName())->toBe('Europe/Berlin');
});

it('keeps catalog import sources behind a format independent contract', function (): void {
    expect(is_subclass_of(CsvCatalogImportSource::class, CatalogImportSource::class))->toBeTrue();
});
