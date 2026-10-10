<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Actions\ScanCatalogMetadataQualityAction;
use App\Modules\Catalog\Contracts\BibliographicLookupProvider;
use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\DTOs\MetadataProposal;
use App\Modules\Catalog\Lookup\ChainedLookupProvider;
use App\Modules\Catalog\Lookup\Dnb\DnbLookupProvider;
use App\Modules\Catalog\Lookup\GoogleBooks\GoogleBooksLookupProvider;
use App\Modules\Catalog\Lookup\OpenLibrary\OpenLibraryLookupProvider;
use App\Modules\Catalog\Lookup\Support\LanguageCodes;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Catalog\Quality\QualityText;
use App\Modules\Catalog\Queries\SafeMetadataProposalsQuery;
use App\Modules\Catalog\Services\BibliographicLookupService;
use App\Modules\Catalog\Services\MetadataProposalService;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\DnbRecordXml;

uses(RefreshDatabase::class);

const FOREIGN_ISBN = '9780141439518';
const GERMAN_ISBN = '9783522202800';

function foreignUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** @return array<string, mixed> */
function openLibraryBody(string $isbn = FOREIGN_ISBN, array $details = []): array
{
    return ['ISBN:'.$isbn => [
        'bib_key' => 'ISBN:'.$isbn,
        'info_url' => 'https://openlibrary.org/books/OL1M/Pride_and_Prejudice',
        'details' => array_merge([
            'key' => '/books/OL1M', 'title' => 'Pride and Prejudice', 'authors' => [['name' => 'Jane Austen', 'key' => '/authors/OL1A']],
            'publishers' => ['Penguin Classics'], 'publish_date' => 'March 3, 2003', 'languages' => [['key' => '/languages/eng']],
            'number_of_pages' => 480, 'subjects' => ['Fiction', 'Sisters', 'fiction'],
        ], $details),
    ]];
}

/** @return array<string, mixed> */
function googleBody(string $isbn = FOREIGN_ISBN, array $info = []): array
{
    return ['items' => [['id' => 'abc123', 'volumeInfo' => array_merge([
        'title' => 'Pride and Prejudice', 'authors' => ['Jane Austen'], 'publisher' => 'Penguin', 'publishedDate' => '2003-03-03', 'language' => 'en',
        'pageCount' => 480, 'description' => '<p>Ein Klassiker über   Liebe.</p>', 'categories' => ['Fiction / Classics'],
        'industryIdentifiers' => [['type' => 'ISBN_13', 'identifier' => $isbn]], 'canonicalVolumeLink' => 'https://books.google.com/books?id=abc123',
    ], $info)]]];
}

/** Neue nachgestellte Antworten (die zuerst eingerichteten würden sonst gewinnen). */
function foreignFake(array $stubs): void
{
    Http::swap(new HttpFactory);
    Http::fake($stubs);
}

function foreignSourcesOn(): void
{
    config(['catalog.lookup.open_library.enabled' => true, 'catalog.lookup.google_books.enabled' => true]);
    app()->forgetInstance(BibliographicLookupProvider::class);
    app()->forgetInstance(BibliographicLookupService::class);
    app()->forgetInstance(MetadataProposalService::class);
}

it('unifies language codes of the sources', function (): void {
    expect(LanguageCodes::marc('en'))->toBe('eng')
        ->and(LanguageCodes::marc('DEU'))->toBe('ger')
        ->and(LanguageCodes::marc('/languages/tur'))->toBe('tur')
        ->and(LanguageCodes::marc('pt-BR'))->toBe('por')
        ->and(LanguageCodes::marc('und'))->toBeNull()
        ->and(LanguageCodes::marc(''))->toBeNull()
        ->and(LanguageCodes::label('ar'))->toBe('Arabisch')
        ->and(LanguageCodes::label('xyz'))->toBeNull()
        ->and(LanguageCodes::isbnGroup('9783522202800'))->toBe('3')
        ->and(LanguageCodes::isbnGroup('9780141439518'))->toBe('0');
});

it('maps an Open Library record to neutral catalog fields', function (): void {
    foreignFake(['openlibrary.org/*' => Http::response(openLibraryBody())]);

    $records = app(OpenLibraryLookupProvider::class)->findByIsbn('978-0-14-143951-8');

    expect($records)->toHaveCount(1)
        ->and($records[0]->source)->toBe('openlibrary')
        ->and($records[0]->title)->toBe('Pride and Prejudice')
        ->and($records[0]->contributors)->toBe([['name' => 'Jane Austen', 'role' => 'author', 'gnd_id' => null]])
        ->and($records[0]->publisherName)->toBe('Penguin Classics')
        ->and($records[0]->publicationYear)->toBe(2003)
        ->and($records[0]->languageCode)->toBe('eng')
        ->and($records[0]->physicalExtent)->toBe('480 Seiten')
        ->and($records[0]->subjectKeywords)->toBe('Fiction, Sisters')
        ->and($records[0]->isbn)->toBe(FOREIGN_ISBN)
        ->and($records[0]->sourceLabel())->toBe('Open Library')
        ->and($records[0]->isAuthoritative())->toBeFalse();

    foreignFake(['openlibrary.org/*' => Http::response([])]);
    expect(app(OpenLibraryLookupProvider::class)->findByIsbn(FOREIGN_ISBN))->toBe([])
        ->and(app(OpenLibraryLookupProvider::class)->findByIsbn('kein-isbn'))->toBe([]);
});

it('maps Google Books volumes, ignores other ISBNs and normalizes the language', function (): void {
    foreignFake(['www.googleapis.com/*' => Http::response(googleBody(FOREIGN_ISBN, ['language' => 'tr', 'title' => 'Çocuk Kitabı']))]);

    $records = app(GoogleBooksLookupProvider::class)->findByIsbn(FOREIGN_ISBN);

    expect($records)->toHaveCount(1)
        ->and($records[0]->title)->toBe('Çocuk Kitabı')
        ->and($records[0]->languageCode)->toBe('tur')
        ->and($records[0]->publicationYear)->toBe(2003)
        ->and($records[0]->summary)->toBe('Ein Klassiker über Liebe.')
        ->and($records[0]->subjectKeywords)->toBe('Fiction, Classics');

    Http::assertSent(static fn (Request $request): bool => str_contains($request->url(), 'q=isbn%3A'.FOREIGN_ISBN));

    // Ein Treffer mit anderer ISBN gehört zu einem anderen Medium.
    foreignFake(['www.googleapis.com/*' => Http::response(googleBody('9781234567897'))]);
    expect(app(GoogleBooksLookupProvider::class)->findByIsbn(FOREIGN_ISBN))->toBe([]);
});

it('asks the DNB first for German ISBNs and the other sources first for all others', function (): void {
    foreignSourcesOn();
    foreignFake([
        'services.dnb.de/*' => Http::response(DnbRecordXml::record(['id' => '1244853364', 'isbns' => [GERMAN_ISBN], 'title' => 'Shi Yu', 'contributors' => [['name' => 'Autor, Anna', 'code' => 'aut']]])),
        'openlibrary.org/*' => Http::response(openLibraryBody()),
        'www.googleapis.com/*' => Http::response(googleBody()),
    ]);

    $german = app(BibliographicLookupService::class)->byIsbn(GERMAN_ISBN);
    expect($german->records[0]->source)->toBe('dnb');
    Http::assertNotSent(static fn (Request $request): bool => str_contains($request->url(), 'openlibrary.org') || str_contains($request->url(), 'googleapis.com'));

    foreignFake([
        'services.dnb.de/*' => Http::response(DnbRecordXml::record(['isbns' => [FOREIGN_ISBN], 'title' => 'Aus der DNB'])),
        'openlibrary.org/*' => Http::response(openLibraryBody()),
        'www.googleapis.com/*' => Http::response(googleBody()),
    ]);
    $foreign = app(BibliographicLookupService::class)->byIsbn(FOREIGN_ISBN);
    expect($foreign->records[0]->source)->toBe('openlibrary');
});

it('falls back to the next source when one finds nothing or is not reachable', function (): void {
    foreignSourcesOn();
    foreignFake([
        'openlibrary.org/*' => Http::response([], 500),
        'www.googleapis.com/*' => Http::response(googleBody()),
        'services.dnb.de/*' => Http::response('', 500),
    ]);

    $result = app(BibliographicLookupService::class)->byIsbn(FOREIGN_ISBN);

    expect($result->available)->toBeTrue()->and($result->records[0]->source)->toBe('googlebooks');
});

it('reports a lookup as unavailable only when no source answered and none found anything', function (): void {
    foreignSourcesOn();
    foreignFake(['*' => Http::response('', 500)]);
    expect(app(BibliographicLookupService::class)->byIsbn(FOREIGN_ISBN)->available)->toBeFalse();

    foreignFake(['openlibrary.org/*' => Http::response([]), 'www.googleapis.com/*' => Http::response(['items' => []]), 'services.dnb.de/*' => Http::response(DnbRecordXml::response([]))]);
    $none = app(BibliographicLookupService::class)->byIsbn(FOREIGN_ISBN);
    expect($none->available)->toBeTrue()->and($none->records)->toBe([]);
});

it('fills gaps of a record from the next source and names both', function (): void {
    foreignSourcesOn();
    foreignFake([
        'openlibrary.org/*' => Http::response(openLibraryBody(FOREIGN_ISBN, ['publishers' => [], 'publish_date' => null, 'title' => 'Von Open Library'])),
        'www.googleapis.com/*' => Http::response(googleBody(FOREIGN_ISBN, ['publisher' => 'Penguin', 'publishedDate' => '1999'])),
    ]);

    $record = app(BibliographicLookupService::class)->byIsbn(FOREIGN_ISBN)->records[0];

    expect($record->title)->toBe('Von Open Library')
        ->and($record->publisherName)->toBe('Penguin')
        ->and($record->publicationYear)->toBe(1999)
        ->and($record->languageCode)->toBe('eng')
        ->and($record->source)->toBe('openlibrary+googlebooks')
        ->and($record->sourceLabel())->toBe('Open Library + Google Books');
});

it('searches by title and person in the German sources first', function (): void {
    foreignSourcesOn();
    foreignFake([
        'services.dnb.de/*' => Http::response(DnbRecordXml::response([])),
        'openlibrary.org/search.json*' => Http::response(['docs' => [['key' => '/works/OL1W', 'title' => 'Kitab', 'author_name' => ['Ali Veli'], 'first_publish_year' => 1999, 'publisher' => ['Verlag A'], 'isbn' => ['9780141439518'], 'language' => ['ara'], 'subject' => ['Märchen']]]]),
    ]);

    $records = app(BibliographicLookupService::class)->search('Kitab', 'Veli')->records;

    expect($records)->toHaveCount(1)->and($records[0]->languageCode)->toBe('ara')->and($records[0]->isbn)->toBe(FOREIGN_ISBN);
});

it('proposes data from another source in the quality check, marks it as less reliable and keeps it out of the bulk takeover', function (): void {
    foreignSourcesOn();
    foreignFake([
        'services.dnb.de/*' => Http::response(DnbRecordXml::response([])),
        'openlibrary.org/*' => Http::response(openLibraryBody()),
        'www.googleapis.com/*' => Http::response(googleBody()),
    ]);

    $title = Title::query()->create(['preferred_title' => 'Pride and Prejudice', 'sort_title' => 'Pride and Prejudice']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'isbn' => FOREIGN_ISBN, 'media_type' => 'book']);
    $contributor = Contributor::query()->create(['display_name' => 'Jane Austen']);
    TitleContribution::query()->create(['title_id' => $title->getKey(), 'contributor_id' => $contributor->getKey(), 'role_key' => 'author']);
    app(ScanCatalogMetadataQualityAction::class)->execute();

    $review = CatalogMetadataReview::query()->where('edition_id', $edition->getKey())->firstOrFail();
    $review = app(MetadataProposalService::class)->propose($review);
    $proposal = MetadataProposal::fromArray($review->proposal ?? []);
    $keys = array_map(static fn ($change): string => $change->key, $proposal->changes);

    expect($review->proposal_state)->toBe('ready')
        ->and($proposal->source)->toBe(MetadataProposal::SOURCE_OPENLIBRARY_ISBN)
        ->and($keys)->toContain('edition.publisher_name')
        ->and(implode(' ', $proposal->warnings))->toContain('weniger verlässlich')
        ->and(app(SafeMetadataProposalsQuery::class)->execute())->toBe([]);

    $page = $this->actingAs(foreignUser('staff'))->get(route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]))->assertOk();
    $page->assertSee('Open Library (über die ISBN, weniger verlässlich als die DNB)', false);
});

it('retries cases without a match so that the other sources are asked, but only for editions with an ISBN', function (): void {
    $withIsbn = Edition::query()->create(['title_id' => Title::query()->create(['preferred_title' => 'Mit ISBN'])->getKey(), 'isbn' => FOREIGN_ISBN, 'media_type' => 'book', 'language_code' => 'eng']);
    $withoutIsbn = Edition::query()->create(['title_id' => Title::query()->create(['preferred_title' => 'Ohne ISBN'])->getKey(), 'media_type' => 'book', 'language_code' => 'eng']);
    app(ScanCatalogMetadataQualityAction::class)->execute();
    CatalogMetadataReview::query()->update(['proposal_state' => 'none', 'proposal_source' => 'local']);

    $admin = foreignUser('management');
    $this->actingAs($admin)->get(route('administration.system.index'))->assertOk()->assertSee('Sprachen im Bestand')->assertSee('Englisch')->assertSee('Fälle ohne Treffer erneut versuchen');
    $this->actingAs($admin)->post(route('administration.system.quality.retry'))->assertRedirect(route('administration.system.index'))->assertSessionHas('system_success');

    expect(CatalogMetadataReview::query()->where('edition_id', $withIsbn->getKey())->value('proposal_state'))->toBeNull()
        ->and(CatalogMetadataReview::query()->where('edition_id', $withoutIsbn->getKey())->value('proposal_state'))->toBe('none');

    $this->actingAs(foreignUser('staff'))->post(route('administration.system.quality.retry'))->assertForbidden();
});

it('handles non-Latin and Turkish text without mistaking it for damaged data and finds it in the catalog', function (): void {
    foreach (['Война и мир', 'كتاب الأطفال', 'Çocuk Kitabı', 'İstanbul’da bir gün', '東京物語'] as $text) {
        expect(QualityText::hasLostCharacters($text))->toBeFalse()->and(QualityText::hasMojibake($text))->toBeFalse()->and(QualityText::hasControlCharacters($text))->toBeFalse();
    }

    $title = Title::query()->create(['preferred_title' => 'Война и мир', 'sort_title' => 'Война и мир']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book', 'language_code' => 'rus']);
    Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => '0090001', 'status' => 'active']);

    $this->get(route('public.catalog.index', ['q' => 'Война']))->assertOk()->assertSee('Война и мир');
    $this->get(route('public.catalog.index', ['language_code' => 'rus']))->assertOk()->assertSee('Война и мир')->assertSee('Russisch');
});

it('keeps the DNB provider usable on its own and builds the chain from the switches', function (): void {
    config(['catalog.lookup.open_library.enabled' => false, 'catalog.lookup.google_books.enabled' => false]);
    app()->forgetInstance(BibliographicLookupProvider::class);

    expect(app(BibliographicLookupProvider::class))->toBeInstanceOf(ChainedLookupProvider::class);
    foreignFake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['isbns' => [FOREIGN_ISBN]]))]);
    expect(app(BibliographicLookupService::class)->byIsbn(FOREIGN_ISBN)->records[0])->toBeInstanceOf(BibliographicRecord::class);
    Http::assertNotSent(static fn (Request $request): bool => str_contains($request->url(), 'openlibrary.org'));
    expect(app(DnbLookupProvider::class))->toBeInstanceOf(DnbLookupProvider::class);
});
