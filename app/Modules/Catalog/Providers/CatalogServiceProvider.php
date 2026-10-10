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
use App\Modules\Catalog\Lookup\ChainedLookupProvider;
use App\Modules\Catalog\Lookup\Dnb\DnbLookupProvider;
use App\Modules\Catalog\Lookup\GoogleBooks\GoogleBooksLookupProvider;
use App\Modules\Catalog\Lookup\OpenLibrary\OpenLibraryLookupProvider;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Erst nach dem Start auswerten, damit die Schalter in der Konfiguration (auch in Tests) gelten.
        $this->app->bind(BibliographicLookupProvider::class, static function (Application $app): BibliographicLookupProvider {
            $providers = ['dnb' => $app->make(DnbLookupProvider::class)];

            if ((bool) config('catalog.lookup.open_library.enabled', true)) {
                $providers['openlibrary'] = $app->make(OpenLibraryLookupProvider::class);
            }

            if ((bool) config('catalog.lookup.google_books.enabled', true)) {
                $providers['googlebooks'] = $app->make(GoogleBooksLookupProvider::class);
            }

            return new ChainedLookupProvider($providers, $app->make(CatalogIsbnNormalizer::class));
        });

        // Reihenfolge = Priorität: erst Open Library (ohne Key), dann Google Books (nur mit Key).
        $this->app->bind(CatalogCoverProvider::class, static fn (Application $app): CatalogCoverProvider => new ChainedCatalogCoverProvider([
            $app->make(OpenLibraryCoverProvider::class),
            $app->make(GoogleBooksCoverProvider::class),
        ]));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Auch außerhalb der Konsole registrieren: Der Web-Cron ruft app:cron und die Zeitplan-Befehle über die URL auf.
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
