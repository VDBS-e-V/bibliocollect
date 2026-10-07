<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Providers;

use App\Modules\Circulation\Console\ExpireReservationsCommand;
use App\Modules\Circulation\Services\OpenCirculationDepartureGuard;
use App\Modules\Patrons\Services\PatronDepartureGuards;
use Illuminate\Support\ServiceProvider;

final class CirculationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([OpenCirculationDepartureGuard::class], PatronDepartureGuards::TAG);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Auch außerhalb der Konsole registrieren: Der Web-Cron ruft app:cron und die Zeitplan-Befehle über die URL auf.
        $this->commands([ExpireReservationsCommand::class]);
    }
}
