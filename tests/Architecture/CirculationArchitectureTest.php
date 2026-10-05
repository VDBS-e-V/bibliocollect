<?php

declare(strict_types=1);

use App\Foundation\Auth\PermissionRegistry;
use App\Foundation\Auth\RoleRegistry;
use App\Foundation\Support\ModuleRegistry;

it('defines circulation as a permission based operational capability', function (): void {
    $permissions = app(PermissionRegistry::class);
    $roles = app(RoleRegistry::class);

    expect($permissions->keys())->toContain('circulation.manage');

    expect($roles->permissionsFor(['student_ag_basic']))
        ->toContain('circulation.manage');

    expect($roles->permissionsFor(['student_ag_extended']))
        ->toContain('circulation.manage', 'catalog.manage')
        ->not->toContain('catalog.import');

    expect($roles->permissionsFor(['staff', 'management']))
        ->toContain('circulation.manage');

    expect($roles->permissionsFor(['student']))
        ->not->toContain('circulation.manage');

    expect($roles->permissionsFor(['teacher']))
        ->not->toContain('circulation.manage');

    expect($roles->permissionsFor(['technical_admin']))
        ->toBe(['surface.administration.access']);
});

it('keeps circulation dependent on patrons school and catalog without reversing module boundaries', function (): void {
    $modules = app(ModuleRegistry::class)->all();

    expect($modules['Circulation']['dependencies'])
        ->toBe(['Patrons', 'School', 'Catalog'])
        ->and($modules['Circulation']['providers'])
        ->toBe(['App\\Modules\\Circulation\\Providers\\CirculationServiceProvider'])
        ->and($modules['Catalog']['dependencies'])
        ->not->toContain('Circulation')
        ->and($modules['Patrons']['dependencies'])
        ->not->toContain('Circulation');
});

it('keeps circulation writes transactional and pessimistically locked', function (): void {
    $checkout = (string) file_get_contents(app_path('Modules/Circulation/Actions/CheckoutCopyAction.php'));
    $return = (string) file_get_contents(app_path('Modules/Circulation/Actions/ReturnLoanAction.php'));

    expect($checkout)
        ->toContain('DB::transaction')
        ->toContain('lockForUpdate()')
        ->and($return)
        ->toContain('DB::transaction')
        ->toContain('lockForUpdate()');
});
