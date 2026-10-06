<?php

declare(strict_types=1);

namespace App\Foundation\Providers;

use App\Foundation\Auth\PermissionRegistry;
use App\Foundation\Auth\RoleRegistry;
use App\Foundation\Console\BackupDatabaseCommand;
use App\Foundation\Console\CronCommand;
use App\Foundation\Console\DoctorCommand;
use App\Foundation\Console\FoundationCheckCommand;
use App\Foundation\Console\MailTestCommand;
use App\Foundation\Contracts\AuthorizesPermissions;
use App\Foundation\Navigation\NavigationRegistry;
use App\Foundation\Support\BusinessClock;
use App\Foundation\Support\ModuleRegistry;
use App\Foundation\Support\SurfaceRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class FoundationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(SurfaceRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(RoleRegistry::class);
        $this->app->singleton(NavigationRegistry::class);
        $this->app->singleton(BusinessClock::class);

        $registry = $this->app->make(ModuleRegistry::class);

        foreach ($registry->enabled() as $module) {
            foreach ($module['providers'] as $provider) {
                if (class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }

    public function boot(PermissionRegistry $permissions): void
    {
        foreach ($permissions->keys() as $permission) {
            Gate::define(
                $permission,
                static fn (mixed $user): bool => $user instanceof AuthorizesPermissions
                    && $user->allowsPermission($permission),
            );
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                FoundationCheckCommand::class,
                CronCommand::class,
                DoctorCommand::class,
                BackupDatabaseCommand::class,
                MailTestCommand::class,
            ]);
        }
    }
}
