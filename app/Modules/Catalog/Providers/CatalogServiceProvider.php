<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Providers;

use App\Modules\Catalog\Console\AnalyzeLegacyCatalogCommand;
use App\Modules\Catalog\Console\AuditLegacyCatalogQualityCommand;
use App\Modules\Catalog\Console\ImportLegacyCatalogCommand;
use App\Modules\Catalog\Console\QueueCatalogCoverRefreshCommand;
use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\Covers\NullCatalogCoverProvider;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CatalogCoverProvider::class, NullCatalogCoverProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AnalyzeLegacyCatalogCommand::class,
                AuditLegacyCatalogQualityCommand::class,
                ImportLegacyCatalogCommand::class,
                QueueCatalogCoverRefreshCommand::class,
            ]);
        }
    }
}
