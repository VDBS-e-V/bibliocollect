<?php

declare(strict_types=1);

use App\Foundation\Auth\PermissionRegistry;
use App\Foundation\Auth\RoleRegistry;
use App\Foundation\Navigation\NavigationRegistry;
use App\Foundation\Support\BusinessClock;
use App\Foundation\Support\ModuleRegistry;

it('defines stable surface permissions and combinable role bundles', function (): void {
    $permissions = app(PermissionRegistry::class);
    $roles = app(RoleRegistry::class);

    expect($permissions->keys())->toContain(
        'surface.portal.access',
        'surface.pos.access',
        'surface.administration.access',
    );

    expect($roles->permissionsFor(['student_ag_basic']))
        ->toContain('surface.portal.access', 'surface.pos.access')
        ->not->toContain('surface.administration.access');

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

    expect($navigation->allForSurface('public'))->toHaveCount(1);
    expect($navigation->allForSurface('portal'))->toHaveCount(1);
    expect($navigation->allForSurface('pos'))->toHaveCount(1);
    expect($navigation->allForSurface('administration'))->toHaveCount(1);
});

it('uses Europe Berlin as the business timezone by default', function (): void {
    expect(app(BusinessClock::class)->timezone()->getName())->toBe('Europe/Berlin');
});
