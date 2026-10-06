<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Providers;

use App\Modules\Catalog\Console\AnalyzeLegacyCatalogCommand;
use App\Modules\Catalog\Console\AuditLegacyCatalogQualityCommand;
use App\Modules\Catalog\Console\FetchMetadataProposalsCommand;
use App\Modules\Catalog\Console\ImportLegacyCatalogCommand;
use App\Modules\Catalog\Console\QueueCatalogCoverRefreshCommand;
use App\Modules\Catalog\Console\ScanCatalogMetadataQualityCommand;
use App\Modules\Catalog\Contracts\BibliographicLookupProvider;
use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\Covers\ChainedCatalogCoverProvider;
use App\Modules\Catalog\Covers\GoogleBooksCoverProvider;
use App\Modules\Catalog\Covers\OpenLibraryCoverProvider;
use App\Modules\Catalog\Lookup\Dnb\DnbLookupProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BibliographicLookupProvider::class, DnbLookupProvider::class);

        // Reihenfolge = Priorität: erst Open Library (ohne Key), dann Google Books (nur mit Key).
        $this->app->bind(CatalogCoverProvider::class, static fn (Application $app): CatalogCoverProvider => new ChainedCatalogCoverProvider([
            $app->make(OpenLibraryCoverProvider::class),
            $app->make(GoogleBooksCoverProvider::class),
        ]));
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
                ScanCatalogMetadataQualityCommand::class,
                FetchMetadataProposalsCommand::class,
            ]);
        }
    }
}
