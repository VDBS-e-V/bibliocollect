<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Actions\ScanCatalogMetadataQualityAction;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\DnbRecordXml;

uses(RefreshDatabase::class);

function qualityUiUser(string $email = 'staff@demo.bibliocollect.test'): User
{
    test()->seed(DatabaseSeeder::class);

    return User::query()->where('email', $email)->firstOrFail();
}

/**
 * Legt eine Ausgabe an und berücksichtigt in der Prüfliste nur die eigenen Testfälle,
 * nicht die Demodaten des Seeders.
 *
 * @param  array<string, mixed>  $title
 * @param  array<string, mixed>  $edition
 */
function qualityUiEdition(array $title, array $edition = [], bool $withPerson = true): Edition
{
    $titleModel = Title::query()->create($title);

    if ($withPerson) {
        $person = Contributor::query()->create(['display_name' => 'Michael Ende', 'sort_name' => 'Ende, Michael']);
        $titleModel->contributions()->create(['contributor_id' => $person->getKey(), 'role_key' => 'author', 'position' => 1]);
    }

    return Edition::query()->create($edition + [
        'title_id' => $titleModel->getKey(),
        'isbn' => '9783000000003',
        'publisher_name' => 'Testverlag',
        'publication_year' => 2000,
        'media_type' => 'book',
        'language_code' => 'de',
        'source_record_id' => '1000000001',
        'metadata_source' => 'dnb',
    ]);
}

/** @param list<Edition> $mine */
function qualityUiScan(array $mine): void
{
    app(ScanCatalogMetadataQualityAction::class)->execute();

    CatalogMetadataReview::query()
        ->whereNotIn('edition_id', array_map(static fn (Edition $edition): string => (string) $edition->getKey(), $mine))
        ->delete();
}

function qualityUiReview(Edition $edition): CatalogMetadataReview
{
    return CatalogMetadataReview::query()->where('edition_id', $edition->getKey())->firstOrFail();
}

it('keeps the quality pages behind catalog.manage', function (): void {
    $staff = qualityUiUser();
    $extendedAg = User::query()->where('email', 'ag-extended@demo.bibliocollect.test')->firstOrFail();
    $basicAg = User::query()->where('email', 'ag-basic@demo.bibliocollect.test')->firstOrFail();
    $technicalAdmin = User::query()->where('email', 'technik@demo.bibliocollect.test')->firstOrFail();
    $student = User::query()->where('email', 'student@demo.bibliocollect.test')->firstOrFail();

    $edition = qualityUiEdition(['preferred_title' => 'Ra?uber']);
    qualityUiScan([$edition]);
    $review = qualityUiReview($edition);

    $this->get(route('pos.catalog.quality.index'))->assertRedirect(route('login'));
    $this->post(route('pos.catalog.quality.scan'))->assertRedirect(route('login'));

    foreach ([$technicalAdmin, $student, $basicAg] as $denied) {
        $this->actingAs($denied)->get(route('pos.catalog.quality.index'))->assertForbidden();
        $this->actingAs($denied)->get(route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]))->assertForbidden();
        $this->actingAs($denied)->post(route('pos.catalog.quality.apply', ['reviewId' => $review->getKey()]), ['changes' => ['title.preferred_title']])->assertForbidden();
        $this->actingAs($denied)->post(route('pos.catalog.quality.dismiss', ['reviewId' => $review->getKey()]))->assertForbidden();
        $this->actingAs($denied)->post(route('pos.catalog.quality.scan'))->assertForbidden();
    }

    $this->actingAs($staff)->get(route('pos.catalog.quality.index'))->assertOk();
    $this->actingAs($extendedAg)->get(route('pos.catalog.quality.index'))->assertOk();
});

it('lists real defects first and hides enrichment gaps unless asked for', function (): void {
    $lost = qualityUiEdition(['preferred_title' => 'Aaa Ra?uber']);
    $noYear = qualityUiEdition(['preferred_title' => 'Bbb ohne Jahr'], ['publication_year' => null]);
    $clean = qualityUiEdition(['preferred_title' => 'Ccc sauber'], ['summary' => null]);
    qualityUiScan([$lost, $noYear, $clean]);

    $this->actingAs(qualityUiUser('staff@demo.bibliocollect.test'));

    $response = $this->get(route('pos.catalog.quality.index'))->assertOk();

    $response->assertSeeText('Aaa Ra?uber')
        ->assertSee('Bbb ohne Jahr')
        ->assertDontSee('Ccc sauber')
        ->assertSeeTextInOrder(['Aaa Ra?uber', 'Bbb ohne Jahr'])
        ->assertSee('Verlorene Umlaute')
        ->assertSee('Ohne Jahr')
        // Fehlende Zusammenfassung oder Schlagwörter sind kein Mangel und erscheinen nicht als Badge in der Liste.
        ->assertDontSee('>Ohne Zusammenfassung<', false)
        ->assertDontSee('>Ohne Schlagwörter<', false);

    // Gezielt nach Anreicherung gefiltert, erscheint auch der saubere Titel.
    $this->get(route('pos.catalog.quality.index', ['problem' => 'missing_summary']))
        ->assertOk()
        ->assertSee('Ccc sauber');
});

it('filters by problem, searches by title and isbn and shows the status tabs', function (): void {
    $lost = qualityUiEdition(['preferred_title' => 'Aaa Ra?uber'], ['isbn' => '9783111111111']);
    $noYear = qualityUiEdition(['preferred_title' => 'Bbb ohne Jahr'], ['publication_year' => null, 'isbn' => '9783222222222']);
    qualityUiScan([$lost, $noYear]);

    $this->actingAs(qualityUiUser());

    $this->get(route('pos.catalog.quality.index', ['problem' => 'missing_year']))
        ->assertSee('Bbb ohne Jahr')
        ->assertDontSeeText('Aaa Ra?uber');

    $this->get(route('pos.catalog.quality.index', ['q' => 'Ra?uber']))
        ->assertSeeText('Aaa Ra?uber')
        ->assertDontSee('Bbb ohne Jahr');

    $this->get(route('pos.catalog.quality.index', ['q' => '9783222222222']))
        ->assertSee('Bbb ohne Jahr')
        ->assertDontSeeText('Aaa Ra?uber');

    $this->get(route('pos.catalog.quality.index', ['status' => 'dismissed']))
        ->assertOk()
        ->assertSee('Keine Fälle');

    $this->get(route('pos.catalog.quality.index', ['status' => 'bogus', 'per_page' => 25]))
        ->assertSessionHasErrors(['status', 'per_page']);
});

it('shows how many cases are open on the catalog maintenance page', function (): void {
    $lost = qualityUiEdition(['preferred_title' => 'Aaa Ra?uber']);
    qualityUiScan([$lost]);

    $this->actingAs(qualityUiUser())
        ->get(route('pos.catalog.index'))
        ->assertOk()
        ->assertSee('Metadaten prüfen')
        ->assertSee('(1 offen)');
});

it('fetches the proposal when a case is opened, shows it as a checklist and does not ask the DNB twice', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => 'Bonifaz und der Räuber Knapp',
        'year' => '1996',
        'place' => 'Weinheim',
    ]))]);

    $edition = qualityUiEdition(['preferred_title' => 'Bonifaz und der Ra?uber Knapp'], ['publication_year' => null]);
    qualityUiScan([$edition]);
    $review = qualityUiReview($edition);

    $this->actingAs(qualityUiUser());

    $url = route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]);

    $this->get($url)
        ->assertOk()
        ->assertSee('Bonifaz und der Räuber Knapp')
        ->assertSee('korrigiert')
        ->assertSee('ergänzt')
        ->assertSee('1996')
        ->assertSee('name="changes[]"', false)
        ->assertSee('value="title.preferred_title"', false)
        ->assertSee('class="bc-quality-mark"', false)
        ->assertSee('https://d-nb.info/1000000001', false)
        ->assertSee('Ausgewählte übernehmen');

    $this->get($url)->assertOk();

    Http::assertSentCount(1);

    // Ansehen ändert nichts am Katalog.
    expect($edition->fresh()->publication_year)->toBeNull()
        ->and($edition->fresh('title')->title->preferred_title)->toBe('Bonifaz und der Ra?uber Knapp');
});

it('asks for a new proposal when the edition changed since the last one', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['year' => '1996']))]);

    $edition = qualityUiEdition(['preferred_title' => 'Momo'], ['publication_year' => null]);
    qualityUiScan([$edition]);
    $url = route('pos.catalog.quality.show', ['reviewId' => qualityUiReview($edition)->getKey()]);

    $this->actingAs(qualityUiUser());
    $this->get($url)->assertOk();

    $edition->forceFill(['publisher_name' => 'Neuer Verlag'])->save();

    $this->get($url)->assertOk();

    Http::assertSentCount(2);
});

it('applies the selected changes and continues with the next open case', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'title' => 'Aaa Räuber',
        'year' => '1996',
    ]))]);

    $first = qualityUiEdition(['preferred_title' => 'Aaa Ra?uber'], ['publication_year' => null]);
    $second = qualityUiEdition(['preferred_title' => 'Bbb ohne Jahr'], ['publication_year' => null, 'source_record_id' => '1000000002']);
    qualityUiScan([$first, $second]);
    $review = qualityUiReview($first);

    $user = qualityUiUser();
    $this->actingAs($user);
    $this->get(route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]))->assertOk();

    $this->post(route('pos.catalog.quality.apply', ['reviewId' => $review->getKey()]), [
        'changes' => ['title.preferred_title', 'edition.publication_year'],
    ])
        ->assertRedirect(route('pos.catalog.quality.show', ['reviewId' => qualityUiReview($second)->getKey()]))
        ->assertSessionHas('catalog_success');

    expect($first->fresh('title')->title->preferred_title)->toBe('Aaa Räuber')
        ->and($first->fresh()->publication_year)->toBe(1996)
        ->and(qualityUiReview($first)->status)->toBe(MetadataReviewStatus::Open)
        ->and(qualityUiReview($first)->decided_by_user_id)->toBe($user->getKey())
        ->and(qualityUiReview($first)->history)->toHaveCount(1);
});

it('refuses to apply without a selection or with a change that is not in the proposal', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['year' => '1996']))]);

    $edition = qualityUiEdition(['preferred_title' => 'Momo'], ['publication_year' => null, 'local_classification' => 'J 5']);
    qualityUiScan([$edition]);
    $review = qualityUiReview($edition);
    $show = route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]);

    $this->actingAs(qualityUiUser());
    $this->get($show)->assertOk();

    $this->from($show)->post(route('pos.catalog.quality.apply', ['reviewId' => $review->getKey()]), [])
        ->assertRedirect($show)
        ->assertSessionHasErrors('changes');

    $this->post(route('pos.catalog.quality.apply', ['reviewId' => $review->getKey()]), ['changes' => ['edition.local_classification']])
        ->assertRedirect($show)
        ->assertSessionHasErrors('changes');

    $this->post(route('pos.catalog.quality.apply', ['reviewId' => $review->getKey()]), ['changes' => ['kein schluessel!']])
        ->assertSessionHasErrors('changes.0');

    expect($edition->fresh()->publication_year)->toBeNull()
        ->and($edition->fresh()->local_classification)->toBe('J 5');
});

it('does not apply a proposal that is outdated and explains why', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['year' => '1996']))]);

    $edition = qualityUiEdition(['preferred_title' => 'Momo'], ['publication_year' => null]);
    qualityUiScan([$edition]);
    $review = qualityUiReview($edition);

    $this->actingAs(qualityUiUser());
    $this->get(route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]))->assertOk();

    $edition->forceFill(['publisher_name' => 'Nachträglich geändert'])->save();

    $this->post(route('pos.catalog.quality.apply', ['reviewId' => $review->getKey()]), ['changes' => ['edition.publication_year']])
        ->assertRedirect(route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]))
        ->assertSessionHasErrors('changes');

    expect($edition->fresh()->publication_year)->toBeNull();
});

it('dismisses a case, moves on, and lets a person reopen it later', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['year' => '1996']))]);

    $first = qualityUiEdition(['preferred_title' => 'Aaa Ra?uber']);
    $second = qualityUiEdition(['preferred_title' => 'Bbb ohne Jahr'], ['publication_year' => null]);
    qualityUiScan([$first, $second]);
    $review = qualityUiReview($first);

    $this->actingAs(qualityUiUser());

    $this->post(route('pos.catalog.quality.dismiss', ['reviewId' => $review->getKey()]))
        ->assertRedirect(route('pos.catalog.quality.show', ['reviewId' => qualityUiReview($second)->getKey()]));

    expect(qualityUiReview($first)->status)->toBe(MetadataReviewStatus::Dismissed);

    $this->get(route('pos.catalog.quality.index'))->assertDontSeeText('Aaa Ra?uber');
    $this->get(route('pos.catalog.quality.index', ['status' => 'dismissed']))->assertSeeText('Aaa Ra?uber');

    $this->get(route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]))
        ->assertOk()
        ->assertSee('Kein Handlungsbedarf vermerkt')
        ->assertSee('Wieder öffnen');

    $this->post(route('pos.catalog.quality.reopen', ['reviewId' => $review->getKey()]))
        ->assertRedirect(route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]));

    expect(qualityUiReview($first)->status)->toBe(MetadataReviewStatus::Open);
});

it('skips to the next case and ends at the list after the last one', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::empty())]);

    $first = qualityUiEdition(['preferred_title' => 'Aaa Ra?uber']);
    $second = qualityUiEdition(['preferred_title' => 'Bbb ohne Jahr'], ['publication_year' => null]);
    qualityUiScan([$first, $second]);

    $this->actingAs(qualityUiUser());

    $this->get(route('pos.catalog.quality.skip', ['reviewId' => qualityUiReview($first)->getKey()]))
        ->assertRedirect(route('pos.catalog.quality.show', ['reviewId' => qualityUiReview($second)->getKey()]));

    $this->get(route('pos.catalog.quality.skip', ['reviewId' => qualityUiReview($second)->getKey()]))
        ->assertRedirect(route('pos.catalog.quality.index'));
});

it('stays usable with local cleanups when the DNB is down', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response('', 503)]);

    $edition = qualityUiEdition(['preferred_title' => "\u{0098}Der\u{009C} Titel"]);
    qualityUiScan([$edition]);

    $this->actingAs(qualityUiUser())
        ->get(route('pos.catalog.quality.show', ['reviewId' => qualityUiReview($edition)->getKey()]))
        ->assertOk()
        ->assertSee('nicht erreichbar')
        ->assertSee('Lokale Bereinigung')
        ->assertSee('bereinigt')
        ->assertSee('Der Titel');
});

it('re-runs the scan from the page and reports the result', function (): void {
    $edition = qualityUiEdition(['preferred_title' => 'Aaa Ra?uber']);

    $this->actingAs(qualityUiUser())
        ->post(route('pos.catalog.quality.scan'))
        ->assertRedirect(route('pos.catalog.quality.index'))
        ->assertSessionHas('catalog_success');

    expect(CatalogMetadataReview::query()->where('edition_id', $edition->getKey())->exists())->toBeTrue();
});

it('lets a person refresh the proposal on demand', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['year' => '1996']))]);

    $edition = qualityUiEdition(['preferred_title' => 'Momo'], ['publication_year' => null]);
    qualityUiScan([$edition]);
    $review = qualityUiReview($edition);

    $this->actingAs(qualityUiUser());
    $this->get(route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]))->assertOk();

    $this->post(route('pos.catalog.quality.refresh', ['reviewId' => $review->getKey()]))
        ->assertRedirect(route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]));

    Http::assertSentCount(2);
});
