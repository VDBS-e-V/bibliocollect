<?php

declare(strict_types=1);

use App\Modules\Catalog\Legacy\LegacyCatalogQualityAuditor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

function legacyQualityFixturePath(): string
{
    return database_path('seeders/fixtures/legacy-quality-demo.json');
}

it('classifies legacy encoding and contributor quality without catalog writes', function (): void {
    $report = app(LegacyCatalogQualityAuditor::class)->audit(legacyQualityFixturePath());

    expect($report->summary)
        ->toMatchArray([
            'media_rows' => 3,
            'dnb_reference_rows' => 2,
            'encoding_artifact_rows' => 2,
            'encoding_artifact_field_hits' => 2,
            'encoding_artifact_rows_with_dnb' => 1,
            'encoding_artifact_rows_without_dnb' => 1,
            'responsibility_statement_rows' => 3,
            'responsibility_without_structured_contributors_rows' => 1,
            'main_author_rows' => 2,
            'main_author_with_gnd_rows' => 1,
            'main_author_without_gnd_rows' => 1,
            'additional_contributor_rows' => 1,
            'additional_contributor_items' => 1,
            'invalid_additional_contributors_json_rows' => 1,
            'unrecognized_additional_contributor_items' => 0,
            'dnb_refresh_candidate_rows' => 2,
            'manual_review_rows' => 1,
            'quality_issue_rows' => 3,
            'clean_rows' => 0,
        ])
        ->and($report->issues)->toHaveCount(3)
        ->and(Title::query()->count())->toBe(0)
        ->and(Edition::query()->count())->toBe(0)
        ->and(Copy::query()->count())->toBe(0);

    $byBarcode = collect($report->issues)->keyBy('barcode');

    expect($byBarcode['QUALITY-001']['artifact_fields'])->toBe(['main_title'])
        ->and($byBarcode['QUALITY-001']['dnb_record_id'])->toBe('1014097282')
        ->and($byBarcode['QUALITY-001']['structured_contributor_count'])->toBe(2)
        ->and($byBarcode['QUALITY-002']['issues'])->toContain('responsibility_without_structured_contributors')
        ->and($byBarcode['QUALITY-003']['issues'])->toContain('additional_contributors_invalid_json')
        ->and($byBarcode['QUALITY-003']['dnb_record_id'])->toBe('9999999999');
});

it('writes a machine readable quality report from the console command', function (): void {
    $output = storage_path('framework/testing/legacy-quality-report.json');
    File::delete($output);

    $this->artisan('catalog:legacy:audit-quality', [
        'media' => legacyQualityFixturePath(),
        '--output' => $output,
    ])->assertSuccessful();

    expect(File::exists($output))->toBeTrue();

    /** @var array{summary:array<string,int>,issues:list<array<string,mixed>>} $decoded */
    $decoded = json_decode((string) File::get($output), true, flags: JSON_THROW_ON_ERROR);

    expect($decoded['summary']['quality_issue_rows'])->toBe(3)
        ->and($decoded['summary']['dnb_refresh_candidate_rows'])->toBe(2)
        ->and($decoded['issues'])->toHaveCount(3)
        ->and(Title::query()->count())->toBe(0)
        ->and(Edition::query()->count())->toBe(0)
        ->and(Copy::query()->count())->toBe(0);
});
