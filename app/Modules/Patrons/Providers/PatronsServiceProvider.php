<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Providers;

use App\Modules\Identity\Contracts\PatronLinkGateway;
use App\Modules\Patrons\Console\IssuePatronLinkCodeCommand;
use App\Modules\Patrons\Console\RenumberPatronsCommand;
use App\Modules\Patrons\Services\EloquentPatronLinkGateway;
use App\Modules\Patrons\Support\PatronLinkCodeGenerator;
use App\Modules\Patrons\Support\PatronLinkCodeHasher;
use Illuminate\Support\ServiceProvider;

final class PatronsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PatronLinkCodeGenerator::class);
        $this->app->singleton(PatronLinkCodeHasher::class);
        $this->app->bind(PatronLinkGateway::class, EloquentPatronLinkGateway::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Auch außerhalb der Konsole registrieren: Der Web-Cron ruft app:cron und die Zeitplan-Befehle über die URL auf.
        $this->commands([IssuePatronLinkCodeCommand::class, RenumberPatronsCommand::class]);
    }
}
