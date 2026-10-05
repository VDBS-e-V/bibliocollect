<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\ImportLegacyCatalogAction;
use App\Modules\Catalog\Console\AnalyzeLegacyCatalogCommand;
use App\Modules\Catalog\Console\ImportLegacyCatalogCommand;
use App\Modules\Catalog\Legacy\LegacyCatalogNormalizer;
use App\Modules\Catalog\Legacy\PhpMyAdminJsonTableReader;

it('keeps the legacy importer inside Catalog and its writes in an Action', function (): void {
    $action = new ReflectionClass(ImportLegacyCatalogAction::class);
    $analyze = new ReflectionClass(AnalyzeLegacyCatalogCommand::class);
    $import = new ReflectionClass(ImportLegacyCatalogCommand::class);
    $reader = new ReflectionClass(PhpMyAdminJsonTableReader::class);
    $normalizer = new ReflectionClass(LegacyCatalogNormalizer::class);

    expect($action->getNamespaceName())->toBe('App\\Modules\\Catalog\\Actions')
        ->and($analyze->getNamespaceName())->toBe('App\\Modules\\Catalog\\Console')
        ->and($import->getNamespaceName())->toBe('App\\Modules\\Catalog\\Console')
        ->and($reader->getNamespaceName())->toBe('App\\Modules\\Catalog\\Legacy')
        ->and($normalizer->getNamespaceName())->toBe('App\\Modules\\Catalog\\Legacy');

    $source = file_get_contents(app_path('Modules/Catalog/Actions/ImportLegacyCatalogAction.php'));

    expect($source)->toBeString()
        ->and($source)->toContain('DB::transaction')
        ->and($source)->toContain('LegacyCatalogImportAnalyzer');
});
