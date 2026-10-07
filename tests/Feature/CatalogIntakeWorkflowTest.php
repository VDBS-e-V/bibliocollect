<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Jobs\RefreshEditionCoverJob;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// Die Suche nach Zusammenfassungen darf in den Tests nie ins Netz gehen.
beforeEach(function (): void {
    Http::fake([
        'www.googleapis.com/*' => Http::response(['items' => []]),
        'openlibrary.org/*' => Http::response('', 404),
    ]);
});

function intakeUser(string $email = 'staff@demo.bibliocollect.test'): User
{
    test()->seed(DatabaseSeeder::class);

    foreach (['J 5 MORO', 'REG 1', 'HEFT'] as $order => $code) {
        CatalogShelf::query()->create(['code' => $code, 'label' => 'Testbrett '.$code, 'sort_order' => $order]);
    }

    return User::query()->where('email', $email)->firstOrFail();
}

function intakeDnbXml(): string
{
    return (string) file_get_contents(__DIR__.'/../Fixtures/dnb/isbn-9783522202800.xml');
}

/** Schritt 1 (Inventarnummer) und Schritt 2 (Abfrage) nacheinander; die Antwort ist die des zweiten Schritts. */
function intakeLookup(array $overrides = []): TestResponse
{
    $barcode = $overrides['barcode'] ?? '0012345';
    unset($overrides['barcode']);

    test()->post(route('pos.catalog.intake.barcode'), ['barcode' => $barcode])->assertRedirect(route('pos.catalog.intake.medium'));

    return test()->post(route('pos.catalog.intake.lookup'), intakeLookupPayload($overrides));
}

/** @return array<string, string> */
function intakeLookupPayload(array $overrides = []): array
{
    return array_merge([
        'isbn' => '978-3-522-20280-0',
        'title' => '',
        'person' => '',
        'action' => 'lookup',
    ], $overrides);
}

/** @return array<string, mixed> */
function intakeDetailsPayload(array $overrides = []): array
{
    return array_merge([
        'preferred_title' => 'Shi Yu',
        'subtitle' => 'die Unbezwingbare',
        'responsibility_statement' => 'Davide Morosinotto ; aus dem Italienischen von Cornelia Panzacchi',
        'contributors' => [
            ['name' => 'Morosinotto, Davide', 'role' => 'author', 'gnd_id' => '1015211690'],
            ['name' => 'Panzacchi, Cornelia', 'role' => 'translator', 'gnd_id' => '112058426'],
            ['name' => '', 'role' => 'author', 'gnd_id' => ''],
        ],
        'isbn' => '978-3-522-20280-0',
        'publisher_name' => 'Thienemann',
        'publication_place' => 'Stuttgart',
        'publication_year' => '2022',
        'edition_statement' => '',
        'physical_extent' => '506 Seiten',
        'media_type' => 'book',
        'language_code' => 'de',
        'original_language_code' => 'it',
        'series_statement' => '',
        'local_classification' => 'J 5',
        'target_audience' => 'ab 13 Jahre',
        'minimum_age' => '',
        'subject_keywords' => 'Piraten, Karate',
        'summary' => '',
    ], $overrides);
}

it('keeps the intake process behind catalog.manage', function (): void {
    $staff = intakeUser();
    $extendedAg = User::query()->where('email', 'ag-extended@demo.bibliocollect.test')->firstOrFail();
    $basicAg = User::query()->where('email', 'ag-basic@demo.bibliocollect.test')->firstOrFail();
    $technicalAdmin = User::query()->where('email', 'technik@demo.bibliocollect.test')->firstOrFail();
    $student = User::query()->where('email', 'student@demo.bibliocollect.test')->firstOrFail();

    $this->get(route('pos.catalog.intake.identify'))->assertRedirect(route('login'));
    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => '0012345'])->assertRedirect(route('login'));

    $this->actingAs($technicalAdmin)->get(route('pos.catalog.intake.identify'))->assertForbidden();
    $this->actingAs($student)->get(route('pos.catalog.intake.identify'))->assertForbidden();
    $this->actingAs($basicAg)->get(route('pos.catalog.intake.identify'))->assertForbidden();
    $this->actingAs($technicalAdmin)->post(route('pos.catalog.intake.commit'), ['next' => 'open'])->assertForbidden();

    $this->actingAs($staff)->get(route('pos.catalog.intake.identify'))->assertOk()->assertSee('Medium erfassen');
    $this->actingAs($extendedAg)->get(route('pos.catalog.intake.identify'))->assertOk();
});

it('offers the intake process from the catalog maintenance page', function (): void {
    $this->actingAs(intakeUser())
        ->get(route('pos.catalog.index'))
        ->assertOk()
        ->assertSee(route('pos.catalog.intake.identify', ['neu' => 1]), false);
});

it('walks through all steps with DNB data and writes only on the final save', function (): void {
    $staff = intakeUser();
    Http::fake(['services.dnb.de/*' => Http::response(intakeDnbXml(), 200)]);
    Queue::fake();
    config()->set('catalog.covers.open_library.enabled', true);

    $titles = Title::query()->count();
    $editions = Edition::query()->count();
    $copies = Copy::query()->count();

    $this->actingAs($staff);

    $this->get(route('pos.catalog.intake.identify', ['neu' => 1]))
        ->assertOk()
        ->assertSee('Inventarnummer')
        ->assertDontSee('ISBN / EAN')
        ->assertDontSee('Bei der DNB abfragen');

    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => '0012345'])->assertRedirect(route('pos.catalog.intake.medium'));
    $this->get(route('pos.catalog.intake.medium'))->assertOk()->assertSee('ISBN / EAN')->assertSee('Bei der DNB abfragen')->assertSee('Inventarnummer 0012345');
    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => '0012345']);

    // Genau ein DNB-Treffer zur ISBN: Schritt 2 entfällt, die Daten sind vorbefüllt.
    intakeLookup()
        ->assertRedirect(route('pos.catalog.intake.details'));

    $this->get(route('pos.catalog.intake.details'))
        ->assertOk()
        ->assertSee('Die Daten wurden von der DNB übernommen')
        ->assertSee('Shi Yu')
        ->assertSee('die Unbezwingbare')
        ->assertSee('Morosinotto, Davide')
        ->assertSee('Thienemann')
        ->assertSee('Stuttgart')
        ->assertSee('ab 13 Jahre');

    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload())
        ->assertRedirect(route('pos.catalog.intake.copy'));

    $this->get(route('pos.catalog.intake.copy'))->assertOk()->assertSee('0012345');

    $this->post(route('pos.catalog.intake.copy.store'), ['shelf_location' => 'J 5 MORO', 'status' => 'active'])
        ->assertRedirect(route('pos.catalog.intake.review'));

    $this->get(route('pos.catalog.intake.review'))
        ->assertOk()
        ->assertSee('Shi Yu')
        ->assertSee('Morosinotto, Davide')
        ->assertSee('J 5 MORO')
        ->assertSee('0012345')
        ->assertSee('Speichern');

    // Bis hierhin wurde nichts in den Katalog geschrieben.
    expect(Title::query()->count())->toBe($titles)
        ->and(Edition::query()->count())->toBe($editions)
        ->and(Copy::query()->count())->toBe($copies);

    $response = $this->post(route('pos.catalog.intake.commit'), ['next' => 'open']);

    $edition = Edition::query()->where('isbn', '9783522202800')->with('title.contributions.contributor')->firstOrFail();

    $response->assertRedirect(route('pos.catalog.titles.show', ['titleId' => $edition->title_id]))
        ->assertSessionHas('catalog_success');

    expect(Title::query()->count())->toBe($titles + 1)
        ->and(Edition::query()->count())->toBe($editions + 1)
        ->and(Copy::query()->count())->toBe($copies + 1)
        ->and($edition->title->preferred_title)->toBe('Shi Yu')
        ->and($edition->title->subtitle)->toBe('die Unbezwingbare')
        ->and($edition->publisher_name)->toBe('Thienemann')
        ->and($edition->publication_place)->toBe('Stuttgart')
        ->and($edition->publication_year)->toBe(2022)
        ->and($edition->language_code)->toBe('de')
        ->and($edition->original_language_code)->toBe('it')
        ->and($edition->local_classification)->toBe('J 5')
        ->and($edition->minimum_age)->toBeNull()
        ->and($edition->metadata_source)->toBe('dnb')
        ->and($edition->source_record_id)->toBe('1244853364')
        ->and($edition->source_permalink)->toBe('https://d-nb.info/1244853364');

    $links = $edition->title->contributions->sortBy('position')->values();

    expect($links)->toHaveCount(2)
        ->and($links[0]->role_key)->toBe('author')
        ->and($links[0]->contributor->display_name)->toBe('Davide Morosinotto')
        ->and($links[0]->contributor->sort_name)->toBe('Morosinotto, Davide')
        ->and($links[0]->contributor->gnd_id)->toBe('1015211690')
        ->and($links[1]->role_key)->toBe('translator');

    $copy = Copy::query()->where('barcode', '0012345')->firstOrFail();

    expect($copy->edition_id)->toBe($edition->getKey())
        ->and($copy->shelf_location)->toBe('J 5 MORO')
        ->and($copy->status->value)->toBe('active')
        ->and($edition->cover_status)->toBe('pending');

    Queue::assertPushed(
        RefreshEditionCoverJob::class,
        static fn (RefreshEditionCoverJob $job): bool => $job->editionId === $edition->getKey(),
    );

    // Der Vorgang ist abgeschlossen; ein erneutes Absenden speichert nichts doppelt.
    $this->get(route('pos.catalog.intake.review'))->assertRedirect(route('pos.catalog.intake.identify'));
    $this->post(route('pos.catalog.intake.commit'), ['next' => 'open'])->assertRedirect(route('pos.catalog.intake.identify'));
    expect(Title::query()->count())->toBe($titles + 1);
});

it('lets staff continue straight to the next medium after saving', function (): void {
    $staff = intakeUser();
    Http::fake(['services.dnb.de/*' => Http::response(intakeDnbXml(), 200)]);
    Queue::fake();

    $this->actingAs($staff);
    intakeLookup();
    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload());
    $this->post(route('pos.catalog.intake.copy.store'), ['shelf_location' => '', 'status' => 'active']);

    $this->post(route('pos.catalog.intake.commit'), ['next' => 'again'])
        ->assertRedirect(route('pos.catalog.intake.identify'))
        ->assertSessionHas('catalog_success');

    $this->get(route('pos.catalog.intake.identify'))->assertOk()->assertSee('0012345');
    expect(Copy::query()->where('barcode', '0012345')->firstOrFail()->shelf_location)->toBeNull();
});

it('offers adding a copy to an existing edition instead of creating a duplicate', function (): void {
    $staff = intakeUser();
    Http::fake(['services.dnb.de/*' => Http::response(intakeDnbXml(), 200)]);
    Queue::fake();
    config()->set('catalog.covers.open_library.enabled', true);

    $title = Title::query()->create(['preferred_title' => 'Schon vorhanden']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'isbn' => '9783522202800', 'publisher_name' => 'Altverlag']);
    $edition->copies()->create(['barcode' => 'BC-OLD-001', 'status' => 'active']);

    $titles = Title::query()->count();
    $editions = Edition::query()->count();

    $this->actingAs($staff);

    intakeLookup(['barcode' => '0012346'])
        ->assertRedirect(route('pos.catalog.intake.matches'));

    $this->get(route('pos.catalog.intake.matches'))
        ->assertOk()
        ->assertSee('Bereits im Katalog')
        ->assertSee('Schon vorhanden')
        ->assertSee('1 Exemplar')
        ->assertSee('Exemplar ergänzen')
        ->assertSee('Treffer der DNB');

    $this->post(route('pos.catalog.intake.choose'), ['choice' => 'edition:'.$edition->getKey()])
        ->assertRedirect(route('pos.catalog.intake.copy'));

    // Titel- und Ausgabedaten entfallen; Schritt 3 leitet zum Exemplar weiter.
    $this->get(route('pos.catalog.intake.details'))->assertRedirect(route('pos.catalog.intake.copy'));

    $this->get(route('pos.catalog.intake.copy'))->assertOk()->assertSee('Weiteres Exemplar');

    $this->post(route('pos.catalog.intake.copy.store'), ['shelf_location' => 'REG 1', 'status' => 'active'])
        ->assertRedirect(route('pos.catalog.intake.review'));

    $this->get(route('pos.catalog.intake.review'))->assertOk()->assertSee('Vorhandene Ausgabe')->assertSee('Altverlag');

    $this->post(route('pos.catalog.intake.commit'), ['next' => 'again'])
        ->assertRedirect(route('pos.catalog.intake.identify'));

    expect(Title::query()->count())->toBe($titles)
        ->and(Edition::query()->count())->toBe($editions)
        ->and($edition->copies()->count())->toBe(2)
        ->and(Copy::query()->where('barcode', '0012346')->firstOrFail()->edition_id)->toBe($edition->getKey());

    Queue::assertNothingPushed();
});

it('reuses a contributor with the same GND id instead of creating a duplicate', function (): void {
    $staff = intakeUser();
    Http::fake(['services.dnb.de/*' => Http::response(intakeDnbXml(), 200)]);
    Queue::fake();

    $existing = Contributor::query()->create([
        'display_name' => 'D. Morosinotto',
        'sort_name' => 'Morosinotto, D.',
        'gnd_id' => '1015211690',
    ]);

    $this->actingAs($staff);
    intakeLookup();
    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload());
    $this->post(route('pos.catalog.intake.copy.store'), ['shelf_location' => '', 'status' => 'active']);
    $this->post(route('pos.catalog.intake.commit'), ['next' => 'open']);

    expect(Contributor::query()->where('gnd_id', '1015211690')->count())->toBe(1)
        ->and(Contributor::query()->where('display_name', 'Davide Morosinotto')->count())->toBe(0)
        ->and(Edition::query()->where('isbn', '9783522202800')->firstOrFail()->title->contributions()->where('contributor_id', $existing->getKey())->exists())->toBeTrue();
});

it('lists several DNB hits for a title search and prefills the chosen one', function (): void {
    $staff = intakeUser();
    Http::fake(['services.dnb.de/*' => Http::response(intakeDnbXml(), 200)]);

    $this->actingAs($staff);

    intakeLookup(['isbn' => '', 'title' => 'Shi Yu', 'person' => 'Morosinotto'])
        ->assertRedirect(route('pos.catalog.intake.matches'));

    Http::assertSent(static fn (Request $request): bool => $request['query'] === 'tit=Shi and tit=Yu and per=Morosinotto');

    $this->get(route('pos.catalog.intake.matches'))
        ->assertOk()
        ->assertSee('Shi Yu')
        ->assertSee('Morosinotto, Davide')
        ->assertSee('Daten übernehmen');

    $this->post(route('pos.catalog.intake.choose'), ['choice' => 'hit:0'])
        ->assertRedirect(route('pos.catalog.intake.details'));

    $this->get(route('pos.catalog.intake.details'))
        ->assertOk()
        ->assertSee('Thienemann')
        ->assertSee('Morosinotto, Davide');

    // Ein Treffer, den es nicht gibt, wird abgewiesen statt stillschweigend ignoriert.
    $this->post(route('pos.catalog.intake.choose'), ['choice' => 'hit:7'])
        ->assertRedirect(route('pos.catalog.intake.matches'))
        ->assertSessionHasErrors('choice');
});

it('continues manually and keeps the ISBN when the DNB is unreachable', function (): void {
    $staff = intakeUser();
    $this->actingAs($staff);

    Http::fake(['services.dnb.de/*' => Http::response('', 503)]);

    intakeLookup()
        ->assertRedirect(route('pos.catalog.intake.details'));

    $this->get(route('pos.catalog.intake.details'))
        ->assertOk()
        ->assertSee('derzeit nicht erreichbar')
        ->assertSee('9783522202800');
});

it('continues manually when the DNB has no record for the ISBN', function (): void {
    $staff = intakeUser();
    $this->actingAs($staff);

    Http::fake(['services.dnb.de/*' => Http::response(
        '<?xml version="1.0"?><searchRetrieveResponse xmlns="http://www.loc.gov/zing/srw/"><numberOfRecords>0</numberOfRecords></searchRetrieveResponse>',
        200,
    )]);

    intakeLookup()
        ->assertRedirect(route('pos.catalog.intake.details'));

    $this->get(route('pos.catalog.intake.details'))
        ->assertOk()
        ->assertSee('nichts gefunden')
        ->assertDontSee('derzeit nicht erreichbar');
});

it('allows manual intake without asking the DNB at all', function (): void {
    $staff = intakeUser();
    Http::fake();
    Queue::fake();

    $this->actingAs($staff);

    intakeLookup(['isbn' => '', 'action' => 'manual'])
        ->assertRedirect(route('pos.catalog.intake.details'));

    Http::assertNothingSent();

    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload([
        'preferred_title' => 'Handerfasstes Heft',
        'subtitle' => '',
        'contributors' => [],
        'isbn' => '',
        'publisher_name' => '',
        'publication_year' => '',
        'minimum_age' => '14',
    ]))->assertRedirect(route('pos.catalog.intake.copy'));

    $this->post(route('pos.catalog.intake.copy.store'), ['shelf_location' => 'HEFT', 'status' => 'damaged']);
    $this->post(route('pos.catalog.intake.commit'), ['next' => 'open']);

    $title = Title::query()->where('preferred_title', 'Handerfasstes Heft')->firstOrFail();
    $edition = $title->editions()->firstOrFail();

    expect($edition->isbn)->toBeNull()
        ->and($edition->metadata_source)->toBeNull()
        ->and($edition->minimum_age)->toBe(14)
        ->and($title->contributions()->count())->toBe(0)
        ->and(Copy::query()->where('barcode', '0012345')->firstOrFail()->status->value)->toBe('damaged');

    // Ohne ISBN gibt es nichts, wonach ein Cover gesucht werden könnte.
    Queue::assertNothingPushed();
});

it('validates the identification step before any lookup happens', function (): void {
    $staff = intakeUser();
    Http::fake();

    $this->actingAs($staff);

    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => ''])->assertSessionHasErrors('barcode');
    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => '12345'])->assertSessionHasErrors('barcode');
    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => '123456789'])->assertSessionHasErrors('barcode');
    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => 'BC-0012'])->assertSessionHasErrors('barcode');
    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => '00123 5'])->assertSessionHasErrors('barcode');
    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => '0012345'])->assertSessionHasNoErrors();

    // Ohne Inventarnummer geht es nicht zur Abfrage.
    $this->flushSession();
    $this->post(route('pos.catalog.intake.lookup'), intakeLookupPayload())->assertRedirect(route('pos.catalog.intake.identify'));
    $this->get(route('pos.catalog.intake.medium'))->assertRedirect(route('pos.catalog.intake.identify'));
    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => '0012345']);
    intakeLookup(['isbn' => 'abc'])->assertSessionHasErrors('isbn');
    intakeLookup(['isbn' => ''])->assertSessionHasErrors('isbn');
    intakeLookup(['action' => 'sofort'])->assertSessionHasErrors('action');

    $edition = Edition::query()->create([
        'title_id' => Title::query()->create(['preferred_title' => 'Belegt'])->getKey(),
    ]);
    $edition->copies()->create(['barcode' => '0012347', 'status' => 'active']);

    $this->post(route('pos.catalog.intake.barcode'), ['barcode' => '0012347'])
        ->assertSessionHasErrors('barcode');

    Http::assertNothingSent();
});

it('validates title and edition data and ignores empty contributor rows', function (): void {
    $staff = intakeUser();
    Http::fake(['services.dnb.de/*' => Http::response(intakeDnbXml(), 200)]);

    $this->actingAs($staff);
    intakeLookup();

    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload(['preferred_title' => '']))
        ->assertSessionHasErrors('preferred_title');

    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload(['publication_year' => '12']))
        ->assertSessionHasErrors('publication_year');

    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload(['minimum_age' => '40']))
        ->assertSessionHasErrors('minimum_age');

    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload([
        'contributors' => [['name' => 'Person', 'role' => 'Ungültige Rolle!', 'gnd_id' => '']],
    ]))->assertSessionHasErrors('contributors.0.role');

    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload())->assertRedirect(route('pos.catalog.intake.copy'));
    $this->post(route('pos.catalog.intake.copy.store'), ['shelf_location' => '', 'status' => 'kaputt'])->assertSessionHasErrors('status');
});

it('does not let anyone skip ahead of the steps that are still open', function (): void {
    $staff = intakeUser();
    Http::fake(['services.dnb.de/*' => Http::response(intakeDnbXml(), 200)]);

    $this->actingAs($staff);

    foreach (['matches', 'details', 'copy', 'review'] as $step) {
        $this->get(route('pos.catalog.intake.'.$step))->assertRedirect(route('pos.catalog.intake.identify'));
    }

    $this->post(route('pos.catalog.intake.commit'), ['next' => 'open'])->assertRedirect(route('pos.catalog.intake.identify'));

    intakeLookup();

    $this->get(route('pos.catalog.intake.copy'))->assertRedirect(route('pos.catalog.intake.details'));
    $this->get(route('pos.catalog.intake.review'))->assertRedirect(route('pos.catalog.intake.details'));

    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload());

    $this->get(route('pos.catalog.intake.review'))->assertRedirect(route('pos.catalog.intake.copy'));
    $this->post(route('pos.catalog.intake.commit'), ['next' => 'open'])->assertRedirect(route('pos.catalog.intake.copy'));

    expect(Copy::query()->where('barcode', '0012345')->exists())->toBeFalse();
});

it('rolls everything back when the barcode was taken in the meantime', function (): void {
    $staff = intakeUser();
    Http::fake(['services.dnb.de/*' => Http::response(intakeDnbXml(), 200)]);
    Queue::fake();

    $this->actingAs($staff);
    intakeLookup();
    $this->post(route('pos.catalog.intake.details.store'), intakeDetailsPayload());
    $this->post(route('pos.catalog.intake.copy.store'), ['shelf_location' => '', 'status' => 'active']);

    // Zwischen Prüfung und Speichern vergibt jemand anderes denselben Barcode.
    $other = Edition::query()->create(['title_id' => Title::query()->create(['preferred_title' => 'Konkurrenz'])->getKey()]);
    $other->copies()->create(['barcode' => '0012345', 'status' => 'active']);

    $titles = Title::query()->count();
    $contributors = Contributor::query()->count();

    $this->post(route('pos.catalog.intake.commit'), ['next' => 'open'])
        ->assertRedirect(route('pos.catalog.intake.identify'))
        ->assertSessionHasErrors('barcode');

    expect(Title::query()->count())->toBe($titles)
        ->and(Contributor::query()->count())->toBe($contributors)
        ->and(Edition::query()->where('isbn', '9783522202800')->exists())->toBeFalse();

    Queue::assertNothingPushed();
});

it('discards the draft when the process is cancelled', function (): void {
    $staff = intakeUser();
    Http::fake(['services.dnb.de/*' => Http::response(intakeDnbXml(), 200)]);

    $this->actingAs($staff);
    intakeLookup();
    $this->get(route('pos.catalog.intake.details'))->assertOk();

    $this->delete(route('pos.catalog.intake.cancel'))->assertRedirect(route('pos.catalog.index'));

    $this->get(route('pos.catalog.intake.details'))->assertRedirect(route('pos.catalog.intake.identify'));
    expect(Title::query()->where('preferred_title', 'Shi Yu')->exists())->toBeFalse();
});
