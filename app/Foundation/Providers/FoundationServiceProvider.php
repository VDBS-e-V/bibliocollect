<?php

declare(strict_types=1);

namespace App\Foundation\Providers;

use App\Foundation\Auth\PermissionRegistry;
use App\Foundation\Auth\RoleRegistry;
use App\Foundation\Console\BackupDatabaseCommand;
use App\Foundation\Console\CronCommand;
use App\Foundation\Console\DemoQueueCommand;
use App\Foundation\Console\DoctorCommand;
use App\Foundation\Console\EnvBackupCommand;
use App\Foundation\Console\EnvCheckCommand;
use App\Foundation\Console\EnvDiffCommand;
use App\Foundation\Console\EnvRestoreCommand;
use App\Foundation\Console\EnvSyncCommand;
use App\Foundation\Console\FoundationCheckCommand;
use App\Foundation\Console\MailTestCommand;
use App\Foundation\Contracts\AuthorizesPermissions;
use App\Foundation\Navigation\NavigationRegistry;
use App\Foundation\Support\BusinessClock;
use App\Foundation\Support\ModuleRegistry;
use App\Foundation\Support\SurfaceRegistry;
use App\Foundation\Support\SystemErrorLog;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
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

        // Fehlgeschlagene Jobs festhalten und melden.
        Event::listen(JobFailed::class, static function (JobFailed $event): void {
            app(SystemErrorLog::class)->recordFailedJob($event->job->resolveName(), $event->exception);
        });

        // Auch außerhalb der Konsole registrieren: Der Web-Cron ruft app:cron und die Zeitplan-Befehle über die URL auf.
        $this->commands([
            FoundationCheckCommand::class,
            CronCommand::class,
            DemoQueueCommand::class,
            DoctorCommand::class,
            BackupDatabaseCommand::class,
            MailTestCommand::class,
            EnvSyncCommand::class,
            EnvCheckCommand::class,
            EnvDiffCommand::class,
            EnvBackupCommand::class,
            EnvRestoreCommand::class,
        ]);
    }
}
