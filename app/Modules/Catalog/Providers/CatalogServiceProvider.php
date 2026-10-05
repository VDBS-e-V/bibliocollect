<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Providers;

use App\Modules\Catalog\Console\AnalyzeLegacyCatalogCommand;
use App\Modules\Catalog\Console\ImportLegacyCatalogCommand;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AnalyzeLegacyCatalogCommand::class,
                ImportLegacyCatalogCommand::class,
            ]);
        }
    }
}
