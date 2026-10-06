<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Actions\DismissMetadataReviewAction;
use App\Modules\Catalog\Actions\ScanCatalogMetadataQualityAction;
use App\Modules\Catalog\Enums\MetadataIssue;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Quality\CatalogMetadataAssessor;
use App\Modules\Catalog\Quality\QualityText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $title
 * @param  array<string, mixed>  $edition
 * @param  list<array{name: string, sort?: string|null, gnd?: string|null, role?: string}>  $people
 */
function qualityEdition(array $title = [], array $edition = [], array $people = [['name' => 'Michael Ende', 'sort' => 'Ende, Michael']]): Edition
{
    $titleModel = Title::query()->create($title + ['preferred_title' => 'Momo']);

    foreach ($people as $position => $person) {
        $contributor = Contributor::query()->create([
            'display_name' => $person['name'],
            'sort_name' => $person['sort'] ?? null,
            'gnd_id' => $person['gnd'] ?? null,
        ]);
        $titleModel->contributions()->create([
            'contributor_id' => $contributor->getKey(),
            'role_key' => $person['role'] ?? 'author',
            'position' => $position + 1,
        ]);
    }

    $editionModel = Edition::query()->create($edition + [
        'title_id' => $titleModel->getKey(),
        'isbn' => '9783522202800',
        'publisher_name' => 'Thienemann',
        'publication_year' => 1973,
        'media_type' => 'book',
        'language_code' => 'de',
    ]);

    return $editionModel->load('title.contributions.contributor');
}

/** @return list<string> */
function qualityIssueValues(Edition $edition): array
{
    return array_map(
        static fn (MetadataIssue $issue): string => $issue->value,
        app(CatalogMetadataAssessor::class)->assessEdition($edition),
    );
}

it('restores the original of damaged text only when the source value explains the damage', function (): void {
    expect(QualityText::lostMarksMask('München'))->toBe('Mu?nchen')
        ->and(QualityText::lostMarksMask('Räuber'))->toBe('Ra?uber')
        ->and(QualityText::sameAfterLoss('Mu?nchen', 'München'))->toBeTrue()
        ->and(QualityText::sameAfterLoss('Ra?uber Knapp', 'Räuber Knapp'))->toBeTrue()
        // Auch die zerlegte Form (NFD), wie sie die DNB liefert, führt zum gleichen Ergebnis.
        ->and(QualityText::sameAfterLoss('Ku?mmel', "Ku\u{0308}mmel"))->toBeTrue()
        // Geraten wird nie: Ein anderes Wort ist keine Korrektur.
        ->and(QualityText::sameAfterLoss('Mu?nchen', 'Mannheim'))->toBeFalse()
        ->and(QualityText::sameAfterLoss('Mu?nchen', null))->toBeFalse();
});

it('cleans control characters and normalizes decomposed characters', function (): void {
    expect(QualityText::clean("\u{0098}Der\u{009C} Titel"))->toBe('Der Titel')
        ->and(QualityText::clean("Ku\u{0308}mmel  "))->toBe('Kümmel')
        ->and(QualityText::same('Der Titel', "\u{0098}Der\u{009C} titel"))->toBeTrue()
        ->and(QualityText::hasLostCharacters('Warum?'))->toBeFalse()
        ->and(QualityText::hasLostCharacters('Ra?uber'))->toBeTrue()
        ->and(QualityText::hasControlCharacters("\u{0098}Der"))->toBeTrue()
        ->and(QualityText::hasMojibake('MÃ¼nchen'))->toBeTrue();
});

it('finds no defect in a complete edition and only reports enrichment gaps', function (): void {
    $issues = qualityIssueValues(qualityEdition());

    expect($issues)->toBe(['missing_summary', 'missing_keywords']);

    $severity = app(CatalogMetadataAssessor::class)->severity(app(CatalogMetadataAssessor::class)->assessEdition(qualityEdition()));

    expect($severity)->toBe(0);
});

it('flags each kind of metadata defect', function (): void {
    expect(qualityIssueValues(qualityEdition(['preferred_title' => 'Bonifaz und der Ra?uber Knapp'])))->toContain('lost_characters')
        ->and(qualityIssueValues(qualityEdition(['preferred_title' => "\u{0098}Der\u{009C} Titel"])))->toContain('control_characters')
        ->and(qualityIssueValues(qualityEdition(['preferred_title' => 'MÃ¼nchen'])))->toContain('mojibake')
        ->and(qualityIssueValues(qualityEdition([], [], [])))->toContain('missing_contributors')
        ->and(qualityIssueValues(qualityEdition([], ['publication_year' => null])))->toContain('missing_year')
        ->and(qualityIssueValues(qualityEdition([], ['publisher_name' => null])))->toContain('missing_publisher')
        ->and(qualityIssueValues(qualityEdition([], ['media_type' => null])))->toContain('missing_media_type')
        ->and(qualityIssueValues(qualityEdition([], ['isbn' => null])))->toContain('missing_isbn');
});

it('flags an isbn with a wrong check digit but accepts valid isbn-10 and isbn-13', function (): void {
    expect(qualityIssueValues(qualityEdition([], ['isbn' => '9783522202803'])))->toContain('invalid_isbn')
        ->and(qualityIssueValues(qualityEdition([], ['isbn' => 'ISBN abc'])))->toContain('invalid_isbn')
        ->and(qualityIssueValues(qualityEdition([], ['isbn' => '9783522202800'])))->not->toContain('invalid_isbn')
        ->and(qualityIssueValues(qualityEdition([], ['isbn' => '3522202805'])))->not->toContain('invalid_isbn')
        ->and(qualityIssueValues(qualityEdition([], ['isbn' => '080442957X'])))->not->toContain('invalid_isbn')
        ->and(qualityIssueValues(qualityEdition([], ['isbn' => null])))->not->toContain('invalid_isbn');
});

it('also checks the names of contributors and ignores a legitimate question mark', function (): void {
    $damaged = qualityEdition([], [], [['name' => 'Andreas Steinho?fel', 'sort' => 'Steinho?fel, Andreas']]);

    expect(qualityIssueValues($damaged))->toContain('lost_characters');

    $question = qualityEdition(['preferred_title' => 'Warum? Darum!', 'subtitle' => 'Wer weiß das schon?']);

    expect(qualityIssueValues($question))->not->toContain('lost_characters');
});

it('does not treat question marks in free text as lost characters', function (): void {
    $edition = qualityEdition([], ['summary' => 'Was passiert, wenn?der Zug fährt? Niemand weiß es.']);

    expect(qualityIssueValues($edition))->not->toContain('lost_characters');
});

it('creates an open case per flawed edition and leaves the catalog untouched', function (): void {
    $damaged = qualityEdition(['preferred_title' => 'Ra?uber'], ['publication_year' => null]);
    $clean = qualityEdition(['preferred_title' => 'Sauber']);
    $titleBefore = $damaged->title->preferred_title;

    $summary = app(ScanCatalogMetadataQualityAction::class)->execute();

    expect($summary['scanned'])->toBe(2)
        ->and($summary['created'])->toBe(2);

    $review = CatalogMetadataReview::query()->where('edition_id', $damaged->getKey())->firstOrFail();

    expect($review->status)->toBe(MetadataReviewStatus::Open)
        ->and($review->issues)->toContain('lost_characters', 'missing_year')
        ->and($review->severity)->toBe(55);

    // Die saubere Ausgabe hat nur Anreicherungslücken: Fall vorhanden, aber ohne Mangel (Schwere 0).
    expect(CatalogMetadataReview::query()->where('edition_id', $clean->getKey())->firstOrFail()->severity)->toBe(0)
        ->and($damaged->fresh('title')->title->preferred_title)->toBe($titleBefore);
});

it('is idempotent and keeps the proposal while the edition is unchanged', function (): void {
    qualityEdition(['preferred_title' => 'Ra?uber']);
    app(ScanCatalogMetadataQualityAction::class)->execute();

    $review = CatalogMetadataReview::query()->firstOrFail();
    $review->forceFill(['proposal' => ['changes' => []], 'proposal_state' => 'ready'])->save();

    $second = app(ScanCatalogMetadataQualityAction::class)->execute();

    expect($second['created'])->toBe(0)
        ->and($second['updated'])->toBe(0)
        ->and(CatalogMetadataReview::query()->count())->toBe(1)
        ->and($review->fresh()->proposal_state)->toBe('ready');
});

it('closes a case once the defect is gone and reopens it when it comes back', function (): void {
    $edition = qualityEdition(['preferred_title' => 'Ra?uber'], ['summary' => 'Text', 'subject_keywords' => 'Roman']);
    app(ScanCatalogMetadataQualityAction::class)->execute();

    $edition->title->forceFill(['preferred_title' => 'Räuber'])->save();
    $summary = app(ScanCatalogMetadataQualityAction::class)->execute();

    expect($summary['resolved'])->toBe(1)
        ->and(CatalogMetadataReview::query()->firstOrFail()->status)->toBe(MetadataReviewStatus::Resolved);

    $edition->title->forceFill(['preferred_title' => 'Ra?uber'])->save();
    $summary = app(ScanCatalogMetadataQualityAction::class)->execute();

    expect($summary['reopened'])->toBe(1)
        ->and(CatalogMetadataReview::query()->firstOrFail()->status)->toBe(MetadataReviewStatus::Open);
});

it('keeps a dismissed case dismissed until the edition changes', function (): void {
    $edition = qualityEdition([], ['publication_year' => null]);
    app(ScanCatalogMetadataQualityAction::class)->execute();

    $review = CatalogMetadataReview::query()->firstOrFail();

    app(DismissMetadataReviewAction::class)->execute($review, (int) User::factory()->create()->getKey());

    app(ScanCatalogMetadataQualityAction::class)->execute();
    expect($review->fresh()->status)->toBe(MetadataReviewStatus::Dismissed);

    // Der Entscheid gilt nur für diesen Stand: Nach einer Änderung (anderer Mangel) ist der Fall wieder offen.
    $edition->forceFill(['publisher_name' => null])->save();
    app(ScanCatalogMetadataQualityAction::class)->execute();

    expect($review->fresh()->status)->toBe(MetadataReviewStatus::Open)
        ->and($review->fresh()->issues)->toContain('missing_publisher');
});

it('drops an outdated proposal when the edition changes', function (): void {
    $edition = qualityEdition([], ['publication_year' => null]);
    app(ScanCatalogMetadataQualityAction::class)->execute();

    $review = CatalogMetadataReview::query()->firstOrFail();
    $review->forceFill(['proposal' => ['changes' => []], 'proposal_state' => 'ready', 'proposal_source' => 'local'])->save();

    $edition->forceFill(['publisher_name' => 'Anderer Verlag'])->save();
    app(ScanCatalogMetadataQualityAction::class)->execute();

    expect($review->fresh()->proposal)->toBeNull()
        ->and($review->fresh()->proposal_state)->toBeNull();
});

it('runs the scan from the command line and reports the counts', function (): void {
    qualityEdition(['preferred_title' => 'Ra?uber']);

    $this->artisan('catalog:quality:scan')
        ->expectsOutputToContain('1 Ausgaben geprüft')
        ->assertSuccessful();
});

it('keeps the metadata review migration rollback capable', function (): void {
    $migration = require app_path('Modules/Catalog/database/migrations/2026_10_06_003000_create_catalog_metadata_reviews.php');

    expect(Schema::hasTable('catalog_metadata_reviews'))->toBeTrue();

    $migration->down();

    expect(Schema::hasTable('catalog_metadata_reviews'))->toBeFalse();

    $migration->up();

    expect(Schema::hasTable('catalog_metadata_reviews'))->toBeTrue();
});
