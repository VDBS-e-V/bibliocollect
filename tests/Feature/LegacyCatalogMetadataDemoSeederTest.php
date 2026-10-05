<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('enriches the normal demo catalog with detailed metadata and classification idempotently', function (): void {
    $this->seed(DatabaseSeeder::class);

    $edition = Edition::query()->where('isbn', '9780544336261')->firstOrFail();
    $copy = Copy::query()->where('barcode', 'BC-GIVER-001')->firstOrFail();
    $contributor = Contributor::query()->where('display_name', 'Lois Lowry')->firstOrFail();

    expect($edition->page_count)->toBe(240)
        ->and($edition->summary)->toContain('erweiterten bibliografischen Metadaten')
        ->and($edition->metadata_source)->toBe('demo')
        ->and($contributor->gnd_id)->toBe('DEMO-GND-LOWRY')
        ->and($copy->signature?->signature)->toBe('EN 7 LOWR')
        ->and($copy->legacy_is_available)->toBeFalse()
        ->and(CatalogTopic::query()->count())->toBe(2)
        ->and(CatalogSignature::query()->count())->toBe(1);

    $this->seed(DatabaseSeeder::class);

    expect(CatalogTopic::query()->count())->toBe(2)
        ->and(CatalogSignature::query()->count())->toBe(1);
});
