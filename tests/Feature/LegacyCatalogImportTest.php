<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\ImportLegacyCatalogAction;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Exceptions\LegacyCatalogImportException;
use App\Modules\Catalog\Legacy\LegacyCatalogImportAnalyzer;
use App\Modules\Catalog\Legacy\PhpMyAdminJsonTableReader;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function legacyFixturePath(string $name): string
{
    return database_path('seeders/fixtures/'.$name);
}

it('analyzes phpMyAdmin JSON without catalog writes', function (): void {
    $report = app(LegacyCatalogImportAnalyzer::class)->analyze(
        legacyFixturePath('legacy-media-demo.json'),
        legacyFixturePath('legacy-topics-demo.json'),
        legacyFixturePath('legacy-signatures-demo.json'),
    );

    expect($report->hasConflicts())->toBeFalse()
        ->and($report->summary['media_rows'])->toBe(3)
        ->and($report->summary['planned_titles'])->toBe(2)
        ->and($report->summary['planned_editions'])->toBe(2)
        ->and($report->summary['topic_rows'])->toBe(3)
        ->and($report->summary['signature_rows'])->toBe(2)
        ->and(Title::query()->count())->toBe(0)
        ->and(Edition::query()->count())->toBe(0)
        ->and(Copy::query()->count())->toBe(0);
});

it('imports rich legacy metadata topics signatures and physical copies transactionally', function (): void {
    $report = app(ImportLegacyCatalogAction::class)->execute(
        legacyFixturePath('legacy-media-demo.json'),
        legacyFixturePath('legacy-topics-demo.json'),
        legacyFixturePath('legacy-signatures-demo.json'),
    );

    expect($report->hasConflicts())->toBeFalse()
        ->and(Title::query()->count())->toBe(2)
        ->and(Edition::query()->count())->toBe(2)
        ->and(Copy::query()->count())->toBe(3)
        ->and(Contributor::query()->count())->toBe(2)
        ->and(CatalogTopic::query()->count())->toBe(3)
        ->and(CatalogSignature::query()->count())->toBe(2);

    $edition = Edition::query()->where('source_record_id', 'DEMO-RCN-001')->firstOrFail();

    expect($edition->title->preferred_title)->toBe('Legacy-Demo: Der Datenbaum')
        ->and($edition->responsibility_statement)->toContain('Ada Beispiel')
        ->and($edition->series_statement)->toBe('BiblioCollect Testbibliothek')
        ->and($edition->publication_place)->toBe('Berlin')
        ->and($edition->edition_number)->toBe('1. Auflage')
        ->and($edition->isbn)->toBe('9783000000001')
        ->and($edition->alternate_identifiers)->toContain('978-3-00-000000-1', 'DEMO-EAN-001')
        ->and($edition->original_language_code)->toBe('en')
        ->and($edition->page_count)->toBe(224)
        ->and($edition->summary)->toBe('Ein Demo-Datensatz für ausführliche bibliografische Metadaten.')
        ->and($edition->metadata_source)->toBe('dnb');

    $contributor = Contributor::query()->where('gnd_id', 'DEMO-GND-001')->firstOrFail();
    expect($contributor->display_name)->toBe('Beispiel, Ada');

    $firstCopy = Copy::query()->where('barcode', 'LEGACY-DEMO-001')->firstOrFail();
    $unavailableLegacyCopy = Copy::query()->where('barcode', 'LEGACY-DEMO-002')->firstOrFail();
    $damagedCopy = Copy::query()->where('barcode', 'LEGACY-DEMO-003')->firstOrFail();

    expect($firstCopy->signature?->signature)->toBe('I. A 1 a')
        ->and($firstCopy->signature?->topics()->pluck('name')->all())->toContain('Kinder- und Jugendmedien', 'Metadaten & Bibliothek')
        ->and($unavailableLegacyCopy->legacy_is_available)->toBeFalse()
        ->and($unavailableLegacyCopy->status)->toBe(CopyStatus::Active)
        ->and($damagedCopy->status)->toBe(CopyStatus::Damaged)
        ->and($damagedCopy->legacy_in_transition)->toBeTrue();
});

it('is idempotent for records linked by legacy ids and grouping keys', function (): void {
    $action = app(ImportLegacyCatalogAction::class);
    $arguments = [
        legacyFixturePath('legacy-media-demo.json'),
        legacyFixturePath('legacy-topics-demo.json'),
        legacyFixturePath('legacy-signatures-demo.json'),
    ];

    $action->execute(...$arguments);
    $second = $action->execute(...$arguments);

    expect(Title::query()->count())->toBe(2)
        ->and(Edition::query()->count())->toBe(2)
        ->and(Copy::query()->count())->toBe(3)
        ->and(CatalogTopic::query()->count())->toBe(3)
        ->and(CatalogSignature::query()->count())->toBe(2)
        ->and($second->summary['reused_copies'])->toBe(3)
        ->and($second->summary['created_copies'])->toBe(0);
});

it('does not use legacy availability as the current circulation or copy status', function (): void {
    app(ImportLegacyCatalogAction::class)->execute(legacyFixturePath('legacy-media-demo.json'));

    $copy = Copy::query()->where('barcode', 'LEGACY-DEMO-002')->firstOrFail();

    expect($copy->legacy_is_available)->toBeFalse()
        ->and($copy->legacy_loan_count)->toBe(7)
        ->and($copy->status)->toBe(CopyStatus::Active);
});

it('blocks an existing barcode conflict before any legacy writes occur', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Vorhanden']);
    $edition = $title->editions()->create([]);
    $edition->copies()->create(['barcode' => 'LEGACY-DEMO-001', 'status' => CopyStatus::Active]);

    expect(fn () => app(ImportLegacyCatalogAction::class)->execute(legacyFixturePath('legacy-media-demo.json')))
        ->toThrow(LegacyCatalogImportException::class);

    expect(Title::query()->where('preferred_title', 'Legacy-Demo: Der Datenbaum')->exists())->toBeFalse()
        ->and(Copy::query()->count())->toBe(1);
});

it('keeps duplicate isbn values on different titles as separate editions', function (): void {
    $path = storage_path('framework/testing/legacy-duplicate-isbn.json');
    @mkdir(dirname($path), 0777, true);

    file_put_contents($path, json_encode([
        ['media_id' => '1', 'inventory_number' => 'L-DUP-1', 'main_title' => 'Titel Eins', 'isbn_eans' => '9783000000094', 'media_type' => 'Buch'],
        ['media_id' => '2', 'inventory_number' => 'L-DUP-2', 'main_title' => 'Titel Zwei', 'isbn_eans' => '9783000000094', 'media_type' => 'Buch'],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

    app(ImportLegacyCatalogAction::class)->execute($path);

    expect(Title::query()->count())->toBe(2)
        ->and(Edition::query()->where('isbn', '9783000000094')->count())->toBe(2);
});

it('does not silently merge conflicting editions that share title and isbn', function (): void {
    $path = storage_path('framework/testing/legacy-conflicting-editions.json');
    @mkdir(dirname($path), 0777, true);

    file_put_contents($path, json_encode([
        ['media_id' => '1', 'inventory_number' => 'L-ED-1', 'main_title' => 'Gleicher Titel', 'isbn_eans' => '9783000000087', 'edition_statement' => '1. Auflage', 'publisher' => 'Verlag A'],
        ['media_id' => '2', 'inventory_number' => 'L-ED-2', 'main_title' => 'Gleicher Titel', 'isbn_eans' => '9783000000087', 'edition_statement' => '2. Auflage', 'publisher' => 'Verlag B'],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

    app(ImportLegacyCatalogAction::class)->execute($path);

    expect(Title::query()->count())->toBe(1)
        ->and(Edition::query()->where('isbn', '9783000000087')->count())->toBe(2)
        ->and(Copy::query()->count())->toBe(2);
});

it('keeps legacy edition groups separate when one distinguishing field is null', function (): void {
    $path = storage_path('framework/testing/legacy-null-edition-difference.json');
    @mkdir(dirname($path), 0777, true);

    file_put_contents($path, json_encode([
        [
            'media_id' => '1',
            'inventory_number' => 'L-FLAG-1',
            'main_title' => 'Flaggen der Welt',
            'subtitle' => '[mit Stickern & riesigem Poster]',
            'isbn_eans' => '9783905851953',
            'edition_statement' => 'Dt. Lizenzausg.',
            'edition_number' => 'Dt. Lizenzausg.',
            'publisher' => 'Otus',
            'publication_year' => '2011',
            'dnb_rcn_id' => '1014097282',
        ],
        [
            'media_id' => '2',
            'inventory_number' => 'L-FLAG-2',
            'main_title' => 'Flaggen der Welt',
            'subtitle' => '[mit Stickern & riesigem Poster]',
            'isbn_eans' => '9783905851953',
            'edition_statement' => null,
            'edition_number' => 'Dt. Lizenzausg.',
            'publisher' => 'Otus',
            'publication_year' => '2011',
            'dnb_rcn_id' => '1014097282',
        ],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

    $report = app(ImportLegacyCatalogAction::class)->execute($path);

    expect($report->summary['planned_editions'])->toBe(2)
        ->and($report->summary['created_editions'])->toBe(2)
        ->and($report->summary['reused_editions'])->toBe(0)
        ->and($report->summary['resolved_editions'])->toBe(2)
        ->and(Copy::query()->whereIn('barcode', ['L-FLAG-1', 'L-FLAG-2'])->pluck('edition_id')->unique())->toHaveCount(2);
});

it('repairs a previously merged legacy copy without changing its identity', function (): void {
    $path = storage_path('framework/testing/legacy-merged-edition-repair.json');
    @mkdir(dirname($path), 0777, true);

    $rows = [
        [
            'media_id' => '1',
            'inventory_number' => 'L-REPAIR-1',
            'main_title' => 'Flaggen der Welt',
            'subtitle' => '[mit Stickern & riesigem Poster]',
            'isbn_eans' => '9783905851953',
            'edition_statement' => 'Dt. Lizenzausg.',
            'edition_number' => 'Dt. Lizenzausg.',
            'publisher' => 'Otus',
            'publication_year' => '2011',
            'dnb_rcn_id' => '1014097282',
        ],
        [
            'media_id' => '2',
            'inventory_number' => 'L-REPAIR-2',
            'main_title' => 'Flaggen der Welt',
            'subtitle' => '[mit Stickern & riesigem Poster]',
            'isbn_eans' => '9783905851953',
            'edition_statement' => null,
            'edition_number' => 'Dt. Lizenzausg.',
            'publisher' => 'Otus',
            'publication_year' => '2011',
            'dnb_rcn_id' => '1014097282',
        ],
    ];

    file_put_contents($path, json_encode([$rows[0]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    app(ImportLegacyCatalogAction::class)->execute($path);

    $mergedEdition = Edition::query()->sole();
    $incorrectCopy = $mergedEdition->copies()->create([
        'barcode' => 'L-REPAIR-2',
        'status' => CopyStatus::Active,
        'legacy_source' => 'vdbs-legacy',
        'legacy_media_id' => '2',
        'legacy_metadata' => $rows[1],
    ]);
    $copyId = (string) $incorrectCopy->getKey();

    file_put_contents($path, json_encode($rows, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    $report = app(ImportLegacyCatalogAction::class)->execute($path);

    $repairedCopy = Copy::query()->where('barcode', 'L-REPAIR-2')->firstOrFail();

    expect($report->summary['planned_editions'])->toBe(2)
        ->and($report->summary['created_editions'])->toBe(1)
        ->and($report->summary['resolved_editions'])->toBe(2)
        ->and($report->summary['relinked_copies'])->toBe(1)
        ->and(Edition::query()->count())->toBe(2)
        ->and((string) $repairedCopy->getKey())->toBe($copyId)
        ->and($repairedCopy->edition_id)->not->toBe($mergedEdition->getKey());
});

it('supports a direct row-array JSON export in addition to the phpMyAdmin envelope', function (): void {
    $path = storage_path('framework/testing/legacy-direct-array.json');
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, json_encode([
        ['media_id' => 1, 'inventory_number' => 'DIRECT-001', 'main_title' => 'Direkter Export'],
    ], JSON_THROW_ON_ERROR));

    $rows = app(PhpMyAdminJsonTableReader::class)->read($path, 'mediaList');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['inventory_number'])->toBe('DIRECT-001');
});

it('keeps the legacy metadata migration rollback capable', function (): void {
    $migration = require app_path('Modules/Catalog/database/migrations/2026_10_06_001000_extend_catalog_legacy_metadata.php');

    expect(Schema::hasTable('catalog_topics'))->toBeTrue()
        ->and(Schema::hasColumn('catalog_editions', 'source_record_id'))->toBeTrue()
        ->and(Schema::hasColumn('catalog_copies', 'legacy_media_id'))->toBeTrue();

    $migration->down();

    expect(Schema::hasTable('catalog_topics'))->toBeFalse()
        ->and(Schema::hasTable('catalog_signatures'))->toBeFalse()
        ->and(Schema::hasColumn('catalog_editions', 'source_record_id'))->toBeFalse()
        ->and(Schema::hasColumn('catalog_copies', 'legacy_media_id'))->toBeFalse();

    $migration->up();

    expect(Schema::hasTable('catalog_topics'))->toBeTrue()
        ->and(Schema::hasColumn('catalog_editions', 'source_record_id'))->toBeTrue()
        ->and(Schema::hasColumn('catalog_copies', 'legacy_media_id'))->toBeTrue();
});

it('registers analyze and import console commands', function (): void {
    $this->artisan('catalog:legacy:analyze', [
        'media' => legacyFixturePath('legacy-media-demo.json'),
    ])->assertSuccessful();
});
