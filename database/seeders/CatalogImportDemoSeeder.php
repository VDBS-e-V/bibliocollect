<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Actions\CreateCatalogImportBatchAction;
use App\Modules\Catalog\Actions\PreviewCatalogImportAction;
use App\Modules\Catalog\DTOs\CatalogImportMapping;
use App\Modules\Catalog\Enums\CatalogImportStatus;
use App\Modules\Catalog\Import\CsvCatalogImportSource;
use App\Modules\Catalog\Models\CatalogImportBatch;
use Illuminate\Database\Seeder;
use RuntimeException;

final class CatalogImportDemoSeeder extends Seeder
{
    public const DEMO_FILENAME = 'catalog-import-demo.csv';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('CatalogImportDemoSeeder darf nicht in production ausgeführt werden.');
        }

        $existing = CatalogImportBatch::query()
            ->where('original_filename', self::DEMO_FILENAME)
            ->first();

        if ($existing !== null) {
            return;
        }

        $staff = User::query()
            ->where('email', 'staff@demo.bibliocollect.test')
            ->firstOrFail();
        $fixture = database_path('seeders/fixtures/'.self::DEMO_FILENAME);

        $batch = app(CreateCatalogImportBatchAction::class)->execute(
            new CsvCatalogImportSource($fixture),
            (int) $staff->getKey(),
            self::DEMO_FILENAME,
        );

        if (! is_array($batch->mapping)) {
            throw new RuntimeException('Für den Demo-Import konnte kein Feldmapping erzeugt werden.');
        }

        $batch = app(PreviewCatalogImportAction::class)->execute(
            $batch,
            CatalogImportMapping::fromArray($batch->mapping),
        );

        if ($batch->status !== CatalogImportStatus::Ready) {
            throw new RuntimeException('Der Demo-Import muss konfliktfrei zur Übernahme bereitstehen.');
        }
    }
}
