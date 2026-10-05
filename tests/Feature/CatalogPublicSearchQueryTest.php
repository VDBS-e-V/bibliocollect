<?php

declare(strict_types=1);

use App\Modules\Catalog\DTOs\CatalogSearchCriteria;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Queries\CatalogSearchFilterOptionsQuery;
use App\Modules\Catalog\Queries\SearchCatalogTitlesQuery;
use App\Modules\Catalog\Services\CatalogHoldingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps legacy term search behavior while allowing paginated catalog browsing', function (): void {
    $alpha = Title::query()->create(['preferred_title' => 'Alpha']);
    $beta = Title::query()->create(['preferred_title' => 'Beta']);

    $query = app(SearchCatalogTitlesQuery::class);

    expect($query->execute('Alpha')->pluck('id')->all())->toBe([$alpha->getKey()])
        ->and($query->execute('%__'))->toHaveCount(0);

    $page = $query->paginate(new CatalogSearchCriteria(perPage: 1, page: 2));

    expect($page->total())->toBe(2)
        ->and($page->currentPage())->toBe(2)
        ->and($page->items())->toHaveCount(1)
        ->and($page->items()[0]->is($beta))->toBeTrue();
});

it('applies edition and active-copy filters without turning copy barcodes into title search fields', function (): void {
    $german = Title::query()->create(['preferred_title' => 'Deutscher Titel']);
    $germanEdition = Edition::query()->create([
        'title_id' => $german->getKey(),
        'isbn' => '9780000100001',
        'media_type' => 'book',
        'language_code' => 'de',
    ]);
    Copy::query()->create([
        'edition_id' => $germanEdition->getKey(),
        'barcode' => 'QUERY-BARCODE-001',
        'status' => CopyStatus::Damaged,
    ]);

    $english = Title::query()->create(['preferred_title' => 'English Title']);
    $englishEdition = Edition::query()->create([
        'title_id' => $english->getKey(),
        'isbn' => '9780000100002',
        'media_type' => 'audiobook',
        'language_code' => 'en',
    ]);
    Copy::query()->create([
        'edition_id' => $englishEdition->getKey(),
        'barcode' => 'QUERY-BARCODE-002',
        'status' => CopyStatus::Active,
    ]);

    $query = app(SearchCatalogTitlesQuery::class);

    $filtered = $query->paginate(new CatalogSearchCriteria(
        mediaType: 'audiobook',
        languageCode: 'en',
        activeCopiesOnly: true,
    ));

    expect($filtered->total())->toBe(1)
        ->and($filtered->items()[0]->is($english))->toBeTrue();

    $mixed = Title::query()->create(['preferred_title' => 'Gemischte Ausgaben']);
    $mixedBook = Edition::query()->create([
        'title_id' => $mixed->getKey(),
        'media_type' => 'book',
        'language_code' => 'de',
    ]);
    Copy::query()->create([
        'edition_id' => $mixedBook->getKey(),
        'barcode' => 'QUERY-MIXED-BOOK',
        'status' => CopyStatus::Damaged,
    ]);
    $mixedAudio = Edition::query()->create([
        'title_id' => $mixed->getKey(),
        'media_type' => 'audiobook',
        'language_code' => 'en',
    ]);
    Copy::query()->create([
        'edition_id' => $mixedAudio->getKey(),
        'barcode' => 'QUERY-MIXED-AUDIO',
        'status' => CopyStatus::Active,
    ]);

    $sameEditionFilter = $query->paginate(new CatalogSearchCriteria(
        mediaType: 'book',
        languageCode: 'de',
        activeCopiesOnly: true,
    ));

    expect($sameEditionFilter->total())->toBe(0);

    $barcodeSearch = $query->paginate(new CatalogSearchCriteria(term: 'QUERY-BARCODE-002'));

    expect($barcodeSearch->total())->toBe(0);
});

it('summarizes copy states and exposes shelf locations only for active holdings', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Bestandstest']);
    $edition = Edition::query()->create([
        'title_id' => $title->getKey(),
        'media_type' => 'book',
        'language_code' => 'de',
    ]);

    foreach ([
        ['BC-SUM-001', CopyStatus::Active, 'J 1 TEST'],
        ['BC-SUM-002', CopyStatus::Active, 'J 1 TEST'],
        ['BC-SUM-003', CopyStatus::Damaged, 'WERKSTATT'],
        ['BC-SUM-004', CopyStatus::Lost, 'VERMISST'],
        ['BC-SUM-005', CopyStatus::Withdrawn, 'MAGAZIN'],
    ] as [$barcode, $status, $location]) {
        Copy::query()->create([
            'edition_id' => $edition->getKey(),
            'barcode' => $barcode,
            'status' => $status,
            'shelf_location' => $location,
        ]);
    }

    $summary = app(CatalogHoldingService::class)->summarizeTitle($title);

    expect($summary->totalCopies)->toBe(5)
        ->and($summary->activeCopies)->toBe(2)
        ->and($summary->damagedCopies)->toBe(1)
        ->and($summary->lostCopies)->toBe(1)
        ->and($summary->withdrawnCopies)->toBe(1)
        ->and($summary->shelfLocations)->toBe(['J 1 TEST'])
        ->and($summary->hasActiveCopies())->toBeTrue();
});

it('derives public filter options from the open edition vocabularies', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Filteroptionen']);

    foreach ([
        ['book', 'de'],
        ['audiobook', 'en'],
        ['book', 'de'],
        [null, null],
    ] as [$mediaType, $languageCode]) {
        Edition::query()->create([
            'title_id' => $title->getKey(),
            'media_type' => $mediaType,
            'language_code' => $languageCode,
        ]);
    }

    $options = app(CatalogSearchFilterOptionsQuery::class)->execute();

    expect($options['mediaTypes'])->toBe(['audiobook', 'book'])
        ->and($options['languageCodes'])->toBe(['de', 'en']);
});
