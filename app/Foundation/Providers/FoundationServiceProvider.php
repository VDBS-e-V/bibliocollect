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
use App\Foundation\Console\LaunchResetCommand;
use App\Foundation\Console\MailTestCommand;
use App\Foundation\Console\RestoreDatabaseCommand;
use App\Foundation\Contracts\AuthorizesPermissions;
use App\Foundation\Navigation\NavigationRegistry;
use App\Foundation\Settings\SettingsRegistry;
use App\Foundation\Settings\SettingsRepository;
use App\Foundation\Support\BusinessClock;
use App\Foundation\Support\ModuleRegistry;
use App\Foundation\Support\SurfaceRegistry;
use App\Foundation\Support\SystemErrorLog;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        $this->app->singleton(SettingsRegistry::class);
        $this->app->singleton(SettingsRepository::class);

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
        // Links, Cover und signierte Adressen im Betrieb immer mit https, auch wenn der Proxy es nicht meldet.
        if (str_starts_with((string) config('app.url'), 'https://') && ! $this->app->environment(['local', 'testing'])) {
            URL::forceScheme('https');
        }

        // Im Web geänderte Regeln über die Konfiguration legen (siehe Seite „Regeln“).
        $this->app->make(SettingsRepository::class)->apply();

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
            LaunchResetCommand::class,
            FoundationCheckCommand::class,
            CronCommand::class,
            DemoQueueCommand::class,
            DoctorCommand::class,
            BackupDatabaseCommand::class,
            RestoreDatabaseCommand::class,
            MailTestCommand::class,
            EnvSyncCommand::class,
            EnvCheckCommand::class,
            EnvDiffCommand::class,
            EnvBackupCommand::class,
            EnvRestoreCommand::class,
        ]);
    }
}
