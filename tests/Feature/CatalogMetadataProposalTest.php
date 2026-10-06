<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Actions\ApplyMetadataProposalAction;
use App\Modules\Catalog\Actions\ScanCatalogMetadataQualityAction;
use App\Modules\Catalog\DTOs\MetadataChange;
use App\Modules\Catalog\DTOs\MetadataProposal;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Exceptions\MetadataProposalOutdated;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Quality\MetadataFingerprint;
use App\Modules\Catalog\Services\MetadataProposalService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\DnbRecordXml;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $title
 * @param  array<string, mixed>  $edition
 * @param  list<array{name: string, sort?: string|null, gnd?: string|null, role?: string}>  $people
 */
function proposalEdition(array $title = [], array $edition = [], array $people = [], bool $withSourceId = true): Edition
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

    return Edition::query()->create($edition + [
        'title_id' => $titleModel->getKey(),
        'isbn' => '9783000000003',
        'publisher_name' => 'Testverlag',
        'media_type' => 'book',
        'language_code' => 'de',
        'source_record_id' => $withSourceId ? '1000000001' : null,
        'metadata_source' => $withSourceId ? 'dnb' : null,
    ]);
}

function proposalReview(Edition $edition): CatalogMetadataReview
{
    app(ScanCatalogMetadataQualityAction::class)->execute();

    return CatalogMetadataReview::query()->where('edition_id', $edition->getKey())->firstOrFail();
}

function proposalFor(CatalogMetadataReview $review): MetadataProposal
{
    $proposed = app(MetadataProposalService::class)->propose($review);

    return MetadataProposal::fromArray($proposed->proposal ?? []);
}

function proposalUserId(): int
{
    return (int) User::factory()->create()->getKey();
}

it('fills missing fields from the DNB record found by the stored DNB id', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => 'Momo',
        'subtitle' => 'oder die seltsame Geschichte',
        'year' => '2020',
        'place' => 'Stuttgart',
        'extent' => '300 Seiten',
        'original_language' => 'eng',
    ]))]);

    $edition = proposalEdition(['preferred_title' => 'Momo'], ['publication_year' => null]);
    $review = proposalReview($edition);
    $proposal = proposalFor($review);

    Http::assertSent(static fn (Request $request): bool => $request['query'] === 'idn=1000000001');

    expect($proposal->source)->toBe('dnb-id')
        ->and($proposal->recordId)->toBe('1000000001')
        ->and($proposal->permalink)->toBe('https://d-nb.info/1000000001')
        ->and($proposal->change('edition.publication_year')?->kind)->toBe(MetadataChange::FILL)
        ->and($proposal->change('edition.publication_year')?->proposed)->toBe('2020')
        ->and($proposal->change('edition.publication_year')?->selected)->toBeTrue()
        ->and($proposal->change('title.subtitle')?->proposed)->toBe('oder die seltsame Geschichte')
        ->and($proposal->change('edition.publication_place')?->proposed)->toBe('Stuttgart')
        ->and($proposal->change('edition.original_language_code')?->proposed)->toBe('en')
        ->and($review->fresh()->proposal_state)->toBe('ready');
});

it('corrects lost umlauts only when the source value is their origin', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => "Bonifaz und der Ra\u{0308}uber Knapp",
        'publisher' => 'Beltz',
    ]))]);

    $edition = proposalEdition(['preferred_title' => 'Bonifaz und der Ra?uber Knapp'], ['publisher_name' => 'Beltz']);
    $proposal = proposalFor(proposalReview($edition));

    $change = $proposal->change('title.preferred_title');

    expect($change?->kind)->toBe(MetadataChange::FIX)
        ->and($change?->current)->toBe('Bonifaz und der Ra?uber Knapp')
        ->and($change?->proposed)->toBe('Bonifaz und der Räuber Knapp')
        ->and($change?->selected)->toBeTrue()
        // Der unveränderte Verlag taucht gar nicht erst als Änderung auf.
        ->and($proposal->change('edition.publisher_name'))->toBeNull();
});

it('never preselects a different value and never proposes to replace an existing isbn', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => 'Momo',
        'publisher' => 'Thienemann Verlag',
        'isbns' => ['9783000000003', '9783999999990'],
    ]))]);

    $edition = proposalEdition(['preferred_title' => 'Momo'], [
        'publisher_name' => 'Thienemann',
        'publication_place' => 'Berlin',
        'publication_year' => 2020,
    ]);
    $proposal = proposalFor(proposalReview($edition));

    $publisher = $proposal->change('edition.publisher_name');

    expect($publisher?->kind)->toBe(MetadataChange::DIFFERS)
        ->and($publisher?->selected)->toBeFalse()
        ->and($proposal->differences())->toHaveCount(1)
        ->and($proposal->suggestions())->toBe([])
        ->and($proposal->change('edition.isbn'))->toBeNull();
});

it('offers a local cleanup without any external source', function (): void {
    Http::fake();

    $edition = proposalEdition(
        ['preferred_title' => "\u{0098}Der\u{009C} weiße Schwan"],
        ['isbn' => null],
        withSourceId: false,
    );
    $review = proposalReview($edition);
    $proposal = proposalFor($review);

    Http::assertNothingSent();

    $change = $proposal->change('title.preferred_title');

    expect($proposal->source)->toBe('local')
        ->and($change?->kind)->toBe(MetadataChange::LOCAL)
        ->and($change?->proposed)->toBe('Der weiße Schwan')
        ->and($change?->selected)->toBeTrue()
        ->and($review->fresh()->proposal_state)->toBe('ready');
});

it('warns and preselects nothing when the record does not fit the edition', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => 'Shi Yu',
        'isbns' => ['9783522202800'],
        'year' => '2022',
    ]))]);

    $edition = proposalEdition(['preferred_title' => 'Momo'], ['publication_year' => null]);
    $proposal = proposalFor(proposalReview($edition));

    expect($proposal->warnings)->toHaveCount(2)
        ->and($proposal->warnings[0])->toContain('weicht stark')
        ->and($proposal->warnings[1])->toContain('ISBN')
        ->and($proposal->change('edition.publication_year')?->selected)->toBeFalse();

    foreach ($proposal->changes as $change) {
        expect($change->selected)->toBeFalse();
    }
});

it('falls back to the isbn when there is no DNB id', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['title' => 'Momo', 'year' => '2020']))]);

    $edition = proposalEdition(['preferred_title' => 'Momo'], ['publication_year' => null], withSourceId: false);
    $proposal = proposalFor(proposalReview($edition));

    Http::assertSent(static fn (Request $request): bool => $request['query'] === 'num=9783000000003');

    expect($proposal->source)->toBe('dnb-isbn')
        ->and($proposal->change('edition.publication_year')?->proposed)->toBe('2020');
});

it('does not guess when the isbn has several records', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::response([
        DnbRecordXml::element(['id' => '1', 'title' => 'Momo']),
        DnbRecordXml::element(['id' => '2', 'title' => 'Momo']),
    ]))]);

    $edition = proposalEdition(['preferred_title' => 'Momo'], ['publication_year' => null], withSourceId: false);
    $review = proposalReview($edition);
    $proposal = proposalFor($review);

    expect($proposal->source)->toBe('local')
        ->and($proposal->warnings[0])->toContain('mehrere Datensätze')
        ->and($review->fresh()->proposal_state)->toBe('none');
});

it('stays usable when the DNB cannot be reached', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response('', 503)]);

    $edition = proposalEdition(['preferred_title' => "\u{0098}Der\u{009C} Titel"]);
    $review = proposalReview($edition);
    $proposal = proposalFor($review);

    expect($proposal->source)->toBe('local')
        ->and($proposal->warnings[0])->toContain('nicht erreichbar')
        ->and($proposal->change('title.preferred_title')?->kind)->toBe(MetadataChange::LOCAL)
        ->and($review->fresh()->proposal_state)->toBe('ready');

    $clean = proposalEdition(['preferred_title' => 'Ganz sauber'], ['isbn' => '9783000000010', 'source_record_id' => '1000000002']);
    $cleanReview = proposalReview($clean);

    expect(proposalFor($cleanReview)->changes)->toBe([])
        ->and($cleanReview->fresh()->proposal_state)->toBe('unavailable');
});

it('adds missing contributors preselected only when the edition has none', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => 'Momo',
        'contributors' => [
            ['name' => 'Ende, Michael', 'code' => 'aut', 'gnd' => '118530518'],
            ['name' => 'Thienemann Verlag', 'code' => 'pbl'],
        ],
    ]))]);

    $without = proposalEdition(['preferred_title' => 'Momo'], ['publication_year' => 1973]);
    $proposal = proposalFor(proposalReview($without));
    $add = $proposal->change('contributor.add.0');

    expect($add?->kind)->toBe(MetadataChange::ADD)
        ->and($add?->selected)->toBeTrue()
        ->and($add?->payload)->toBe(['name' => 'Ende, Michael', 'role' => 'author', 'gnd_id' => '118530518'])
        // Verlage sind keine Verantwortlichen.
        ->and($proposal->change('contributor.add.1'))->toBeNull();

    $with = proposalEdition(['preferred_title' => 'Momo'], ['publication_year' => 1973, 'source_record_id' => '1000000001'], [['name' => 'M. Ende', 'sort' => null]]);
    $secondProposal = proposalFor(proposalReview($with));

    expect($secondProposal->change('contributor.add.0')?->selected)->toBeFalse();
});

it('proposes to repair a damaged contributor name and tells how many titles it affects', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => 'Rico, Oskar und die Tieferschatten',
        'contributors' => [['name' => 'Steinhöfel, Andreas', 'code' => 'aut', 'gnd' => '118641093']],
    ]))]);

    $first = proposalEdition(['preferred_title' => 'Rico, Oskar und die Tieferschatten'], ['publication_year' => 2008], [['name' => 'Steinho?fel, Andreas', 'sort' => null]]);
    $contributor = $first->title->contributions()->firstOrFail()->contributor;

    $other = Title::query()->create(['preferred_title' => 'Anderes Buch']);
    $other->contributions()->create(['contributor_id' => $contributor->getKey(), 'role_key' => 'author', 'position' => 1]);

    $proposal = proposalFor(proposalReview($first));
    $rename = $proposal->change('contributor.rename.'.$contributor->getKey());

    expect($rename?->kind)->toBe(MetadataChange::RENAME)
        ->and($rename?->selected)->toBeTrue()
        ->and($rename?->payload['display_name'])->toBe('Andreas Steinhöfel')
        ->and($rename?->payload['sort_name'])->toBe('Steinhöfel, Andreas')
        ->and($rename?->payload['gnd_id'])->toBe('118641093')
        ->and($rename?->payload['shared_titles'])->toBe(1);
});

it('applies exactly the selected changes, logs them and resolves the case', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => 'Bonifaz und der Räuber Knapp',
        'publisher' => 'Beltz',
        'year' => '1996',
        'place' => 'Weinheim',
    ]))]);

    $edition = proposalEdition(
        ['preferred_title' => 'Bonifaz und der Ra?uber Knapp'],
        ['publisher_name' => 'Beltz', 'publication_year' => null, 'summary' => 'Text', 'subject_keywords' => 'Roman'],
        [['name' => 'Josef Holub', 'sort' => 'Holub, Josef']],
    );
    $review = proposalReview($edition);
    proposalFor($review);
    $userId = proposalUserId();

    // Nur Titel und Jahr werden ausgewählt; der Verlagsort bleibt bewusst leer.
    app(ApplyMetadataProposalAction::class)->execute($review->fresh(), ['title.preferred_title', 'edition.publication_year'], $userId);

    $edition->refresh()->load('title');
    $review->refresh();

    expect($edition->title->preferred_title)->toBe('Bonifaz und der Räuber Knapp')
        ->and($edition->publication_year)->toBe(1996)
        ->and($edition->publication_place)->toBeNull()
        ->and($review->status)->toBe(MetadataReviewStatus::Resolved)
        ->and($review->proposal)->toBeNull()
        ->and($review->decided_by_user_id)->toBe($userId)
        ->and($review->history)->toHaveCount(1)
        ->and($review->history[0]['action'])->toBe('applied')
        ->and($review->history[0]['changes'])->toBe([
            ['key' => 'title.preferred_title', 'label' => 'Haupttitel', 'from' => 'Bonifaz und der Ra?uber Knapp', 'to' => 'Bonifaz und der Räuber Knapp'],
            ['key' => 'edition.publication_year', 'label' => 'Erscheinungsjahr', 'from' => null, 'to' => '1996'],
        ]);
});

it('only takes values from the stored proposal and rejects anything else', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['year' => '2020']))]);

    $edition = proposalEdition([], ['publication_year' => null, 'local_classification' => 'J 5', 'minimum_age' => 12]);
    $review = proposalReview($edition);
    proposalFor($review);

    foreach (['edition.local_classification', 'edition.minimum_age', 'title.sort_title', 'edition.unbekannt'] as $key) {
        expect(fn () => app(ApplyMetadataProposalAction::class)->execute($review->fresh(), [$key], proposalUserId()))
            ->toThrow(InvalidArgumentException::class);
    }

    expect(fn () => app(ApplyMetadataProposalAction::class)->execute($review->fresh(), [], proposalUserId()))
        ->toThrow(InvalidArgumentException::class);

    $edition->refresh();

    expect($edition->local_classification)->toBe('J 5')
        ->and($edition->minimum_age)->toBe(12)
        ->and($edition->publication_year)->toBeNull();
});

it('refuses an outdated proposal and writes nothing', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['year' => '2020']))]);

    $edition = proposalEdition([], ['publication_year' => null]);
    $review = proposalReview($edition);
    proposalFor($review);

    // Zwischenzeitlich ändert jemand die Ausgabe von Hand.
    $edition->forceFill(['publisher_name' => 'Nachträglich geändert'])->save();

    expect(fn () => app(ApplyMetadataProposalAction::class)->execute($review->fresh(), ['edition.publication_year'], proposalUserId()))
        ->toThrow(MetadataProposalOutdated::class);

    expect($edition->fresh()->publication_year)->toBeNull();
});

it('rolls back every change when one of them fails', function (): void {
    $edition = proposalEdition([], ['publication_year' => null]);
    $review = proposalReview($edition);

    $fingerprint = app(MetadataFingerprint::class)->for($edition->load('title.contributions.contributor'));
    $proposal = new MetadataProposal('dnb-id', '1000000001', null, $fingerprint, [], [
        new MetadataChange('edition.publication_year', MetadataChange::FILL, 'Erscheinungsjahr', null, '2020', true),
        new MetadataChange('contributor.rename.nicht-vorhanden', MetadataChange::RENAME, 'Name', 'Alt', 'Neu', true, [
            'contributor_id' => '01m0000000000000000000000x',
            'display_name' => 'Neu',
            'sort_name' => null,
            'gnd_id' => null,
        ]),
    ]);
    $review->forceFill(['proposal' => $proposal->toArray()])->save();

    expect(fn () => app(ApplyMetadataProposalAction::class)->execute($review->fresh(), ['edition.publication_year', 'contributor.rename.nicht-vorhanden'], proposalUserId()))
        ->toThrow(ModelNotFoundException::class);

    expect($edition->fresh()->publication_year)->toBeNull()
        ->and($review->fresh()->history)->toBeNull();
});

it('adds contributors through the shared resolver without duplicating a known GND person', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => 'Momo',
        'contributors' => [['name' => 'Ende, Michael', 'code' => 'aut', 'gnd' => '118530518']],
    ]))]);

    $known = Contributor::query()->create(['display_name' => 'Michael Ende', 'sort_name' => 'Ende, Michael', 'gnd_id' => '118530518']);
    $edition = proposalEdition(['preferred_title' => 'Momo'], ['publication_year' => 1973]);
    $review = proposalReview($edition);
    proposalFor($review);

    app(ApplyMetadataProposalAction::class)->execute($review->fresh(), ['contributor.add.0'], proposalUserId());

    $links = $edition->title->contributions()->get();

    expect(Contributor::query()->where('gnd_id', '118530518')->count())->toBe(1)
        ->and($links)->toHaveCount(1)
        ->and($links[0]->contributor_id)->toBe($known->getKey())
        ->and($links[0]->role_key)->toBe('author');
});

it('repairs a shared contributor name for every title that uses it', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => 'Rico, Oskar und die Tieferschatten',
        'contributors' => [['name' => 'Steinhöfel, Andreas', 'code' => 'aut', 'gnd' => '118641093']],
    ]))]);

    $edition = proposalEdition(['preferred_title' => 'Rico, Oskar und die Tieferschatten'], ['publication_year' => 2008], [['name' => 'Steinho?fel, Andreas', 'sort' => 'Steinho?fel, Andreas']]);
    $contributor = $edition->title->contributions()->firstOrFail()->contributor;
    $review = proposalReview($edition);
    proposalFor($review);

    app(ApplyMetadataProposalAction::class)->execute($review->fresh(), ['contributor.rename.'.$contributor->getKey()], proposalUserId());

    $contributor->refresh();

    expect($contributor->display_name)->toBe('Andreas Steinhöfel')
        ->and($contributor->sort_name)->toBe('Steinhöfel, Andreas')
        ->and($contributor->gnd_id)->toBe('118641093');
});

it('adopts the DNB source on the edition only when it had none', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['id' => '1000000001', 'year' => '2020']))]);

    $edition = proposalEdition([], ['publication_year' => null, 'source_record_id' => null, 'metadata_source' => null], withSourceId: false);
    $review = proposalReview($edition);
    proposalFor($review);

    app(ApplyMetadataProposalAction::class)->execute($review->fresh(), ['edition.publication_year'], proposalUserId());

    $edition->refresh();

    expect($edition->metadata_source)->toBe('dnb')
        ->and($edition->source_record_id)->toBe('1000000001')
        ->and($edition->source_permalink)->toBe('https://d-nb.info/1000000001');
});

it('keeps the legacy source of an edition when applying a proposal', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['year' => '2020']))]);

    $edition = proposalEdition([], ['publication_year' => null, 'metadata_source' => 'vdbs-legacy']);
    $review = proposalReview($edition);
    proposalFor($review);

    app(ApplyMetadataProposalAction::class)->execute($review->fresh(), ['edition.publication_year'], proposalUserId());

    expect($edition->fresh()->metadata_source)->toBe('vdbs-legacy');
});

it('explains why there is no proposal when the DNB does not know the isbn', function (): void {
    // Die DNB liefert zur (falschen) ISBN den Datensatz eines anderen Buchs; er wird verworfen.
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['isbns' => ['9783522202800'], 'title' => 'Shi Yu']))]);

    $edition = proposalEdition(['preferred_title' => 'Momo'], ['isbn' => '9783522202803', 'publication_year' => null], withSourceId: false);
    $review = proposalReview($edition);
    $proposal = proposalFor($review);

    expect($proposal->source)->toBe('local')
        ->and($proposal->warnings[0])->toContain('keinen passenden Datensatz')
        ->and($proposal->change('edition.publication_year'))->toBeNull()
        ->and($review->fresh()->proposal_state)->toBe('none');
});
