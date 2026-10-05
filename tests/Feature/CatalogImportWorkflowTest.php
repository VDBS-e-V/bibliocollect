<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Actions\CommitCatalogImportAction;
use App\Modules\Catalog\Actions\CreateCatalogImportBatchAction;
use App\Modules\Catalog\Actions\PreviewCatalogImportAction;
use App\Modules\Catalog\DTOs\CatalogImportMapping;
use App\Modules\Catalog\Enums\CatalogImportRowStatus;
use App\Modules\Catalog\Enums\CatalogImportStatus;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Exceptions\CatalogImportCommitException;
use App\Modules\Catalog\Import\CsvCatalogImportSource;
use App\Modules\Catalog\Models\CatalogImportBatch;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function catalogImportUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** @return array<string, string|null> */
function catalogImportRow(array $overrides = []): array
{
    return array_merge([
        'preferred_title' => 'Der Hobbit',
        'subtitle' => null,
        'sort_title' => 'Hobbit',
        'edition_statement' => 'Schulausgabe',
        'isbn' => '9783423214126',
        'publisher_name' => 'dtv',
        'publication_year' => '2024',
        'media_type' => 'book',
        'language_code' => 'de',
        'minimum_age' => '11',
        'age_rating_label' => 'ab 11',
        'contributor_name' => 'J. R. R. Tolkien',
        'contributor_sort_name' => 'Tolkien, J. R. R.',
        'contributor_role' => 'author',
        'barcode' => 'IMP-HOBBIT-001',
        'shelf_location' => 'J 6 TOLK',
        'copy_status' => 'active',
    ], $overrides);
}

/** @param list<array<string, string|null>> $rows */
function catalogImportCsvText(array $rows): string
{
    $headers = array_keys(catalogImportRow());
    $handle = fopen('php://temp', 'w+');

    if ($handle === false) {
        throw new RuntimeException('Temporäre CSV konnte nicht erstellt werden.');
    }

    fputcsv($handle, $headers, ';', '"', '');

    foreach ($rows as $row) {
        $values = [];

        foreach ($headers as $header) {
            $values[] = $row[$header] ?? null;
        }

        fputcsv($handle, $values, ';', '"', '');
    }

    rewind($handle);
    $contents = stream_get_contents($handle);
    fclose($handle);

    if (! is_string($contents)) {
        throw new RuntimeException('Temporäre CSV konnte nicht gelesen werden.');
    }

    return $contents;
}

function catalogImportBatch(User $user, array $rows): CatalogImportBatch
{
    $path = tempnam(sys_get_temp_dir(), 'bibliocollect-import-');

    if ($path === false) {
        throw new RuntimeException('Temporäre Importdatei konnte nicht erstellt werden.');
    }

    file_put_contents($path, catalogImportCsvText($rows));

    try {
        return app(CreateCatalogImportBatchAction::class)->execute(
            new CsvCatalogImportSource($path),
            (int) $user->getKey(),
            'test-import.csv',
        );
    } finally {
        @unlink($path);
    }
}

function previewCatalogImport(CatalogImportBatch $batch): CatalogImportBatch
{
    if (! is_array($batch->mapping)) {
        throw new RuntimeException('Testimport besitzt kein Mapping.');
    }

    return app(PreviewCatalogImportAction::class)->execute(
        $batch,
        CatalogImportMapping::fromArray($batch->mapping),
    );
}

it('grants catalog import only to staff and management', function (): void {
    $extendedAg = catalogImportUser('student_ag_extended');
    $staff = catalogImportUser('staff');
    $management = catalogImportUser('management');
    $technicalAdmin = catalogImportUser('technical_admin');

    expect($extendedAg->allowsPermission('catalog.manage'))->toBeTrue()
        ->and($extendedAg->allowsPermission('catalog.import'))->toBeFalse()
        ->and($staff->allowsPermission('catalog.import'))->toBeTrue()
        ->and($management->allowsPermission('catalog.import'))->toBeTrue()
        ->and($technicalAdmin->allowsPermission('catalog.import'))->toBeFalse();

    $this->actingAs($extendedAg)->get(route('pos.catalog.import.create'))->assertForbidden();
    $this->actingAs($technicalAdmin)->get(route('pos.catalog.import.create'))->assertForbidden();
    $this->actingAs($staff)->get(route('pos.catalog.import.create'))->assertOk();
    $this->actingAs($management)->get(route('pos.catalog.import.create'))->assertOk();

    $managementBatch = previewCatalogImport(catalogImportBatch($management, [
        catalogImportRow(['barcode' => 'MANAGEMENT-IMPORT-001']),
    ]));

    $this->actingAs($management)
        ->post(route('pos.catalog.import.commit', ['batchId' => $managementBatch->getKey()]), ['confirm_import' => '1'])
        ->assertRedirect(route('pos.catalog.import.show', ['batchId' => $managementBatch->getKey()]));

    expect($managementBatch->fresh()->status)->toBe(CatalogImportStatus::Committed);
});

it('uploads csv headers and proposes a mapping without catalog writes', function (): void {
    $staff = catalogImportUser('staff');
    $csv = implode("\n", [
        'Haupttitel;ISBN;Medientyp;Sprache;Barcode;Status',
        'Momo;978-3-522-20280-3;Buch;DEU;UPLOAD-001;Aktiv',
    ])."\n";

    $response = $this->actingAs($staff)->post(route('pos.catalog.import.store'), [
        'catalog_file' => UploadedFile::fake()->createWithContent('katalog.csv', $csv),
    ]);

    $batch = CatalogImportBatch::query()->firstOrFail();

    $response->assertRedirect(route('pos.catalog.import.show', ['batchId' => $batch->getKey()]));

    expect($batch->headers)->toBe(['Haupttitel', 'ISBN', 'Medientyp', 'Sprache', 'Barcode', 'Status'])
        ->and($batch->mapping['preferred_title'] ?? null)->toBe('Haupttitel')
        ->and($batch->mapping['barcode'] ?? null)->toBe('Barcode')
        ->and($batch->rows()->count())->toBe(1)
        ->and($batch->status)->toBe(CatalogImportStatus::Uploaded);

    expect(Title::query()->count())->toBe(0)
        ->and(Edition::query()->count())->toBe(0)
        ->and(Copy::query()->count())->toBe(0);
});

it('validates required mapping fields against actual csv headers', function (): void {
    $staff = catalogImportUser('staff');
    $batch = catalogImportBatch($staff, [catalogImportRow()]);

    $this->actingAs($staff)
        ->post(route('pos.catalog.import.preview', ['batchId' => $batch->getKey()]), [
            'mapping' => [
                'preferred_title' => '',
                'barcode' => 'not-a-header',
            ],
        ])
        ->assertSessionHasErrors(['mapping.preferred_title', 'mapping.barcode']);

    expect($batch->fresh()->status)->toBe(CatalogImportStatus::Uploaded);
});

it('persists a normalized preview without creating or changing catalog records', function (): void {
    $staff = catalogImportUser('staff');
    $batch = catalogImportBatch($staff, [catalogImportRow([
        'isbn' => 'ISBN 978-3-423-21412-6',
        'media_type' => 'Buch',
        'language_code' => 'DEU',
        'copy_status' => 'Beschädigt',
    ])]);

    $preview = previewCatalogImport($batch);
    $row = $preview->rows->firstOrFail();

    expect($preview->status)->toBe(CatalogImportStatus::Ready)
        ->and($row->status)->toBe(CatalogImportRowStatus::Valid)
        ->and($row->normalized_data['isbn'] ?? null)->toBe('9783423214126')
        ->and($row->normalized_data['media_type'] ?? null)->toBe('book')
        ->and($row->normalized_data['language_code'] ?? null)->toBe('de')
        ->and($row->normalized_data['copy_status'] ?? null)->toBe(CopyStatus::Damaged->value);

    expect(Title::query()->count())->toBe(0)
        ->and(Edition::query()->count())->toBe(0)
        ->and(Contributor::query()->count())->toBe(0)
        ->and(TitleContribution::query()->count())->toBe(0)
        ->and(Copy::query()->count())->toBe(0);
});

it('preserves unknown open vocabulary values while normalizing known ones', function (): void {
    $staff = catalogImportUser('staff');
    $batch = catalogImportBatch($staff, [catalogImportRow([
        'media_type' => 'Kamishibai',
        'language_code' => 'x-school',
    ])]);

    $row = previewCatalogImport($batch)->rows->firstOrFail();

    expect($row->normalized_data['media_type'] ?? null)->toBe('Kamishibai')
        ->and($row->normalized_data['language_code'] ?? null)->toBe('x-school');
});

it('preserves nonstandard isbn text without using it as an automatic edition match key', function (): void {
    $staff = catalogImportUser('staff');
    $batch = previewCatalogImport(catalogImportBatch($staff, [
        catalogImportRow([
            'isbn' => 'Lokale Kennung A-17',
            'barcode' => 'LOCAL-ID-001',
        ]),
        catalogImportRow([
            'isbn' => 'Lokale Kennung A-17',
            'barcode' => 'LOCAL-ID-002',
        ]),
    ]));

    expect($batch->status)->toBe(CatalogImportStatus::Ready)
        ->and($batch->summary['new_titles'] ?? null)->toBe(1)
        ->and($batch->summary['new_editions'] ?? null)->toBe(2);

    app(CommitCatalogImportAction::class)->execute($batch);

    expect(Title::query()->where('preferred_title', 'Der Hobbit')->count())->toBe(1)
        ->and(Edition::query()->where('isbn', 'Lokale Kennung A-17')->count())->toBe(2)
        ->and(Copy::query()->whereIn('barcode', ['LOCAL-ID-001', 'LOCAL-ID-002'])->count())->toBe(2);
});

it('marks missing titles invalid and rejects invalid years and minimum ages', function (): void {
    $staff = catalogImportUser('staff');
    $batch = catalogImportBatch($staff, [catalogImportRow([
        'preferred_title' => null,
        'publication_year' => '999',
        'minimum_age' => '19',
    ])]);

    $preview = previewCatalogImport($batch);
    $row = $preview->rows->firstOrFail();

    expect($preview->status)->toBe(CatalogImportStatus::Blocked)
        ->and($row->status)->toBe(CatalogImportRowStatus::Invalid)
        ->and(implode(' ', $row->conflicts))->toContain('Haupttitel')
        ->and(implode(' ', $row->conflicts))->toContain('Erscheinungsjahr')
        ->and(implode(' ', $row->conflicts))->toContain('Mindestalter');
});

it('detects duplicate barcodes inside one import file', function (): void {
    $staff = catalogImportUser('staff');
    $batch = catalogImportBatch($staff, [
        catalogImportRow(['barcode' => 'DUP-IN-FILE']),
        catalogImportRow(['barcode' => 'DUP-IN-FILE']),
    ]);

    $preview = previewCatalogImport($batch);

    expect($preview->status)->toBe(CatalogImportStatus::Blocked)
        ->and($preview->summary['conflict_rows'] ?? null)->toBe(2);

    foreach ($preview->rows as $row) {
        expect($row->status)->toBe(CatalogImportRowStatus::Conflict)
            ->and(implode(' ', $row->conflicts))->toContain('mehrfach in dieser Importdatei');
    }
});

it('detects barcodes that already exist in the catalog', function (): void {
    $staff = catalogImportUser('staff');
    $title = Title::query()->create(['preferred_title' => 'Vorhanden']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'isbn' => '9783000000001']);
    Copy::query()->create([
        'edition_id' => $edition->getKey(),
        'barcode' => 'EXISTS-001',
        'status' => CopyStatus::Active,
    ]);

    $batch = catalogImportBatch($staff, [catalogImportRow(['barcode' => 'EXISTS-001'])]);
    $row = previewCatalogImport($batch)->rows->firstOrFail();

    expect($row->status)->toBe(CatalogImportRowStatus::Conflict)
        ->and(implode(' ', $row->conflicts))->toContain('bereits im Katalog vorhanden');
});

it('reuses a unique existing isbn without silently overwriting existing metadata', function (): void {
    $staff = catalogImportUser('staff');
    $title = Title::query()->create([
        'preferred_title' => 'Der Hobbit',
        'subtitle' => 'Vorhandener Untertitel',
    ]);
    $edition = Edition::query()->create([
        'title_id' => $title->getKey(),
        'isbn' => '978-3-423-21412-6',
        'publisher_name' => 'Bestehender Verlag',
        'publication_year' => 2020,
        'media_type' => 'book',
        'language_code' => 'de',
    ]);

    $batch = catalogImportBatch($staff, [catalogImportRow([
        'subtitle' => 'Import-Untertitel',
        'publisher_name' => 'Import-Verlag',
        'publication_year' => '2024',
    ])]);
    $preview = previewCatalogImport($batch);
    $row = $preview->rows->firstOrFail();

    expect($row->plan['title']['mode'] ?? null)->toBe('reuse')
        ->and($row->plan['edition']['mode'] ?? null)->toBe('reuse')
        ->and(implode(' ', $row->warnings))->toContain('überschreiben keine Stammdaten');

    $committed = app(CommitCatalogImportAction::class)->execute($preview);

    expect($committed->status)->toBe(CatalogImportStatus::Committed)
        ->and(Title::query()->count())->toBe(1)
        ->and(Edition::query()->count())->toBe(1)
        ->and(Copy::query()->where('barcode', 'IMP-HOBBIT-001')->count())->toBe(1)
        ->and($title->fresh()->subtitle)->toBe('Vorhandener Untertitel')
        ->and($edition->fresh()->publisher_name)->toBe('Bestehender Verlag')
        ->and($edition->fresh()->publication_year)->toBe(2020);
});

it('blocks an isbn match when the imported title contradicts the existing edition', function (): void {
    $staff = catalogImportUser('staff');
    $title = Title::query()->create(['preferred_title' => 'Der Hobbit']);
    Edition::query()->create(['title_id' => $title->getKey(), 'isbn' => '9783423214126']);

    $batch = catalogImportBatch($staff, [catalogImportRow(['preferred_title' => 'Ganz anderer Titel'])]);
    $row = previewCatalogImport($batch)->rows->firstOrFail();

    expect($row->status)->toBe(CatalogImportRowStatus::Conflict)
        ->and(implode(' ', $row->conflicts))->toContain('gehört im Katalog zum Titel');
});

it('groups equal isbn rows into one edition with multiple copies', function (): void {
    $staff = catalogImportUser('staff');
    $batch = catalogImportBatch($staff, [
        catalogImportRow(['barcode' => 'MULTI-001']),
        catalogImportRow(['barcode' => 'MULTI-002']),
    ]);

    $preview = previewCatalogImport($batch);

    expect($preview->status)->toBe(CatalogImportStatus::Ready)
        ->and($preview->summary['new_titles'] ?? null)->toBe(1)
        ->and($preview->summary['new_editions'] ?? null)->toBe(1)
        ->and($preview->summary['new_copies'] ?? null)->toBe(2);

    $committed = app(CommitCatalogImportAction::class)->execute($preview);

    expect(Title::query()->count())->toBe(1)
        ->and(Edition::query()->count())->toBe(1)
        ->and(Copy::query()->count())->toBe(2)
        ->and($committed->summary['new_editions'] ?? null)->toBe(1)
        ->and($committed->summary['new_copies'] ?? null)->toBe(2)
        ->and($committed->summary['committed_rows'] ?? null)->toBe(2);
});

it('creates contributors and contributions while keeping role keys open', function (): void {
    $staff = catalogImportUser('staff');
    $batch = catalogImportBatch($staff, [catalogImportRow([
        'contributor_role' => 'wissenschaftliche Beratung',
    ])]);

    $preview = previewCatalogImport($batch);
    $row = $preview->rows->firstOrFail();

    expect($row->normalized_data['contributor_role'] ?? null)->toBe('wissenschaftliche_beratung')
        ->and($preview->summary['new_contributors'] ?? null)->toBe(1)
        ->and($preview->summary['new_contributions'] ?? null)->toBe(1);

    app(CommitCatalogImportAction::class)->execute($preview);

    $contribution = TitleContribution::query()->with('contributor')->firstOrFail();

    expect($contribution->role_key)->toBe('wissenschaftliche_beratung')
        ->and($contribution->contributor->display_name)->toBe('J. R. R. Tolkien');
});

it('reuses only a unique exact title match when no isbn is available', function (): void {
    $staff = catalogImportUser('staff');
    $existing = Title::query()->create([
        'preferred_title' => 'Momo',
        'subtitle' => 'Vorhanden',
    ]);

    $batch = catalogImportBatch($staff, [catalogImportRow([
        'preferred_title' => 'Momo',
        'subtitle' => 'Importwert',
        'isbn' => null,
        'barcode' => 'MOMO-EXACT-001',
    ])]);
    $row = previewCatalogImport($batch)->rows->firstOrFail();

    expect($row->plan['title']['mode'] ?? null)->toBe('reuse')
        ->and($row->plan['title']['id'] ?? null)->toBe($existing->getKey())
        ->and(implode(' ', $row->warnings))->toContain('exakter Haupttitel-Match');
});

it('does not fuzzy merge similar titles', function (): void {
    $staff = catalogImportUser('staff');
    Title::query()->create(['preferred_title' => 'Momo']);

    $batch = catalogImportBatch($staff, [catalogImportRow([
        'preferred_title' => 'Momo!',
        'isbn' => null,
        'barcode' => 'MOMO-FUZZY-001',
    ])]);
    $row = previewCatalogImport($batch)->rows->firstOrFail();

    expect($row->plan['title']['mode'] ?? null)->toBe('create');
});

it('blocks confirmation while conflicts exist and requires explicit http confirmation', function (): void {
    $staff = catalogImportUser('staff');
    $batch = catalogImportBatch($staff, [
        catalogImportRow(['barcode' => 'CONFIRM-DUP']),
        catalogImportRow(['barcode' => 'CONFIRM-DUP']),
    ]);
    $preview = previewCatalogImport($batch);

    expect(fn () => app(CommitCatalogImportAction::class)->execute($preview))
        ->toThrow(CatalogImportCommitException::class);

    expect(Title::query()->count())->toBe(0)
        ->and(Copy::query()->count())->toBe(0);

    $ready = previewCatalogImport(catalogImportBatch($staff, [catalogImportRow(['barcode' => 'CONFIRM-READY'])]));

    $this->actingAs($staff)
        ->post(route('pos.catalog.import.commit', ['batchId' => $ready->getKey()]), [])
        ->assertSessionHasErrors('confirm_import');

    expect($ready->fresh()->status)->toBe(CatalogImportStatus::Ready)
        ->and(Copy::query()->where('barcode', 'CONFIRM-READY')->exists())->toBeFalse();
});

it('rolls back every catalog write if a later row fails during commit', function (): void {
    $staff = catalogImportUser('staff');
    $batch = previewCatalogImport(catalogImportBatch($staff, [
        catalogImportRow(['barcode' => 'TX-001']),
        catalogImportRow(['barcode' => 'TX-002']),
    ]));

    Copy::creating(static function (Copy $copy): void {
        if ($copy->barcode === 'TX-002') {
            throw new RuntimeException('simulated second-copy failure');
        }
    });

    expect(fn () => app(CommitCatalogImportAction::class)->execute($batch))
        ->toThrow(RuntimeException::class, 'simulated second-copy failure');

    expect(Title::query()->count())->toBe(0)
        ->and(Edition::query()->count())->toBe(0)
        ->and(Contributor::query()->count())->toBe(0)
        ->and(TitleContribution::query()->count())->toBe(0)
        ->and(Copy::query()->count())->toBe(0)
        ->and($batch->fresh()->status)->toBe(CatalogImportStatus::Ready);
});

it('rechecks catalog conflicts immediately before commit', function (): void {
    $staff = catalogImportUser('staff');
    $batch = previewCatalogImport(catalogImportBatch($staff, [catalogImportRow(['barcode' => 'RACE-001'])]));

    $otherTitle = Title::query()->create(['preferred_title' => 'Zwischenzeitlich']);
    $otherEdition = Edition::query()->create(['title_id' => $otherTitle->getKey(), 'isbn' => '9783000000099']);
    Copy::query()->create([
        'edition_id' => $otherEdition->getKey(),
        'barcode' => 'RACE-001',
        'status' => CopyStatus::Active,
    ]);

    expect(fn () => app(CommitCatalogImportAction::class)->execute($batch))
        ->toThrow(CatalogImportCommitException::class);

    expect(Title::query()->where('preferred_title', 'Der Hobbit')->exists())->toBeFalse()
        ->and(Copy::query()->where('barcode', 'RACE-001')->count())->toBe(1);
});

it('keeps import batches and their preview available after reload but hides them from unauthorized roles', function (): void {
    $staff = catalogImportUser('staff');
    $extendedAg = catalogImportUser('student_ag_extended');
    $batch = previewCatalogImport(catalogImportBatch($staff, [catalogImportRow(['barcode' => 'RELOAD-001'])]));

    $this->actingAs($staff)
        ->get(route('pos.catalog.import.show', ['batchId' => $batch->getKey()]))
        ->assertOk()
        ->assertSee('Bereit zur Übernahme')
        ->assertSee('RELOAD-001')
        ->assertSee('Der Hobbit');

    $this->actingAs($extendedAg)
        ->get(route('pos.catalog.import.show', ['batchId' => $batch->getKey()]))
        ->assertForbidden();

    $this->actingAs($extendedAg)
        ->post(route('pos.catalog.import.commit', ['batchId' => $batch->getKey()]), ['confirm_import' => '1'])
        ->assertForbidden();

    expect($batch->fresh()->status)->toBe(CatalogImportStatus::Ready);
});

it('produces a committed import report with actual counts', function (): void {
    $staff = catalogImportUser('staff');
    $batch = previewCatalogImport(catalogImportBatch($staff, [
        catalogImportRow(['barcode' => 'REPORT-001']),
        catalogImportRow(['barcode' => 'REPORT-002']),
    ]));

    $this->actingAs($staff)
        ->post(route('pos.catalog.import.commit', ['batchId' => $batch->getKey()]), ['confirm_import' => '1'])
        ->assertRedirect(route('pos.catalog.import.show', ['batchId' => $batch->getKey()]));

    $committed = $batch->fresh();

    expect($committed?->status)->toBe(CatalogImportStatus::Committed)
        ->and($committed?->summary['new_titles'] ?? null)->toBe(1)
        ->and($committed?->summary['new_editions'] ?? null)->toBe(1)
        ->and($committed?->summary['new_contributors'] ?? null)->toBe(1)
        ->and($committed?->summary['new_contributions'] ?? null)->toBe(1)
        ->and($committed?->summary['new_copies'] ?? null)->toBe(2)
        ->and($committed?->summary['committed_rows'] ?? null)->toBe(2)
        ->and($committed?->committed_at)->not->toBeNull();
});

it('keeps the catalog import migration rollback capable', function (): void {
    $migration = require app_path('Modules/Catalog/database/migrations/2026_10_05_003000_create_catalog_import_tables.php');

    expect(Schema::hasTable('catalog_import_batches'))->toBeTrue()
        ->and(Schema::hasTable('catalog_import_rows'))->toBeTrue();

    $migration->down();

    expect(Schema::hasTable('catalog_import_rows'))->toBeFalse()
        ->and(Schema::hasTable('catalog_import_batches'))->toBeFalse();

    $migration->up();

    expect(Schema::hasTable('catalog_import_batches'))->toBeTrue()
        ->and(Schema::hasTable('catalog_import_rows'))->toBeTrue();
});
