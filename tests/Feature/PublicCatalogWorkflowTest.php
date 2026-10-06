<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{title: Title, edition: Edition, copy: Copy|null}
 */
function createPublicCatalogFixture(
    string $titleName,
    string $contributorName,
    string $isbn,
    string $mediaType = 'book',
    string $languageCode = 'de',
    ?CopyStatus $copyStatus = CopyStatus::Active,
    ?string $barcode = null,
    ?string $shelfLocation = 'J TEST',
): array {
    $title = Title::query()->create([
        'preferred_title' => $titleName,
        'sort_title' => $titleName,
    ]);

    $contributor = Contributor::query()->create([
        'display_name' => $contributorName,
        'sort_name' => $contributorName,
    ]);

    TitleContribution::query()->create([
        'title_id' => $title->getKey(),
        'contributor_id' => $contributor->getKey(),
        'role_key' => 'author',
        'position' => 1,
    ]);

    $edition = Edition::query()->create([
        'title_id' => $title->getKey(),
        'edition_statement' => 'Testausgabe',
        'isbn' => $isbn,
        'publisher_name' => 'Testverlag',
        'publication_year' => 2026,
        'media_type' => $mediaType,
        'language_code' => $languageCode,
        'minimum_age' => 10,
        'age_rating_label' => 'ab 10',
    ]);

    $copy = null;

    if ($copyStatus !== null) {
        $copy = Copy::query()->create([
            'edition_id' => $edition->getKey(),
            'barcode' => $barcode ?? 'BC-'.strtoupper(substr(md5($titleName), 0, 10)),
            'status' => $copyStatus,
            'shelf_location' => $shelfLocation,
        ]);
    }

    return compact('title', 'edition', 'copy');
}

it('serves the public catalog without authentication and allows browsing all titles', function (): void {
    createPublicCatalogFixture(
        titleName: 'Momo',
        contributorName: 'Michael Ende',
        isbn: '9783522202803',
    );

    $this->get(route('public.catalog.index'))
        ->assertOk()
        ->assertSee('Medien finden')
        ->assertSee('Momo')
        ->assertSee('Michael Ende')
        ->assertSee('Verfügbar');
});

it('searches public titles by bibliographic metadata but never by copy barcode', function (): void {
    createPublicCatalogFixture(
        titleName: 'Die unendliche Geschichte',
        contributorName: 'Michael Ende',
        isbn: '9783522202605',
        barcode: 'BC-INTERN-SEARCH-001',
    );
    createPublicCatalogFixture(
        titleName: 'Tschick',
        contributorName: 'Wolfgang Herrndorf',
        isbn: '9783499256356',
        barcode: 'BC-INTERN-SEARCH-002',
    );

    $this->get(route('public.catalog.index', ['q' => 'Michael Ende']))
        ->assertOk()
        ->assertSee('Die unendliche Geschichte')
        ->assertDontSee('Tschick');

    $this->get(route('public.catalog.index', ['q' => '9783499256356']))
        ->assertOk()
        ->assertSee('Tschick')
        ->assertDontSee('Die unendliche Geschichte');

    $this->get(route('public.catalog.index', ['q' => 'BC-INTERN-SEARCH-001']))
        ->assertOk()
        ->assertSee('Keine passenden Titel gefunden.')
        ->assertDontSee('Die unendliche Geschichte');
});

it('filters the public catalog by media type language and active copy state', function (): void {
    createPublicCatalogFixture(
        titleName: 'Deutsches Buch',
        contributorName: 'Autor Deutsch',
        isbn: '9780000000001',
        mediaType: 'book',
        languageCode: 'de',
        copyStatus: CopyStatus::Active,
    );
    createPublicCatalogFixture(
        titleName: 'English Audio',
        contributorName: 'English Author',
        isbn: '9780000000002',
        mediaType: 'audiobook',
        languageCode: 'en',
        copyStatus: CopyStatus::Active,
    );
    createPublicCatalogFixture(
        titleName: 'Beschädigtes Buch',
        contributorName: 'Defect Author',
        isbn: '9780000000003',
        mediaType: 'book',
        languageCode: 'de',
        copyStatus: CopyStatus::Damaged,
    );

    $this->get(route('public.catalog.index', ['media_type' => 'audiobook']))
        ->assertOk()
        ->assertSee('English Audio')
        ->assertDontSee('Deutsches Buch');

    $this->get(route('public.catalog.index', ['language_code' => 'EN']))
        ->assertOk()
        ->assertSee('English Audio')
        ->assertDontSee('Deutsches Buch');

    $this->get(route('public.catalog.index', ['active_only' => '1']))
        ->assertOk()
        ->assertSee('English Audio')
        ->assertSee('Deutsches Buch')
        ->assertDontSee('Beschädigtes Buch');
});

it('shows a public title detail with edition and active shelf information without exposing copy identities', function (): void {
    $fixture = createPublicCatalogFixture(
        titleName: 'Krabat',
        contributorName: 'Otfried Preußler',
        isbn: '9783522177669',
        mediaType: 'book',
        languageCode: 'de',
        copyStatus: CopyStatus::Active,
        barcode: 'BC-KRAB-PUBLIC-HIDDEN',
        shelfLocation: 'J 7 PREU',
    );

    $copy = $fixture['copy'];
    expect($copy)->toBeInstanceOf(Copy::class);

    $this->get(route('public.catalog.show', ['titleId' => $fixture['title']->getKey()]))
        ->assertOk()
        ->assertSee('Krabat')
        ->assertSee('Otfried Preußler')
        ->assertSee('9783522177669')
        ->assertSee('J 7 PREU')
        ->assertSee('Verfügbar')
        ->assertDontSee('BC-KRAB-PUBLIC-HIDDEN')
        ->assertDontSee((string) $copy->getKey());
});

it('distinguishes titles without copies from titles without active copies', function (): void {
    $withoutCopies = createPublicCatalogFixture(
        titleName: 'Noch ohne Bestand',
        contributorName: 'Autor Ohne Bestand',
        isbn: '9780000000010',
        copyStatus: null,
    );
    $damagedOnly = createPublicCatalogFixture(
        titleName: 'Nur beschädigt',
        contributorName: 'Autor Beschädigt',
        isbn: '9780000000011',
        copyStatus: CopyStatus::Damaged,
    );

    $this->get(route('public.catalog.show', ['titleId' => $withoutCopies['title']->getKey()]))
        ->assertOk()
        ->assertSee('Noch kein Exemplarbestand');

    $this->get(route('public.catalog.show', ['titleId' => $damagedOnly['title']->getKey()]))
        ->assertOk()
        ->assertSee('Derzeit kein aktives Exemplar')
        ->assertSee('1 beschädigt');
});

it('does not turn wildcard-only input into a public catalog directory', function (): void {
    createPublicCatalogFixture(
        titleName: 'Soll nicht erscheinen',
        contributorName: 'Unsichtbare Person',
        isbn: '9780000000020',
    );

    $this->get(route('public.catalog.index', ['q' => '%__']))
        ->assertOk()
        ->assertSee('Keine passenden Titel gefunden.')
        ->assertDontSee('Soll nicht erscheinen');
});

it('paginates public catalog browsing while preserving the filter contract', function (): void {
    foreach (range(1, 25) as $number) {
        createPublicCatalogFixture(
            titleName: sprintf('Katalogtitel %02d', $number),
            contributorName: sprintf('Person %02d', $number),
            isbn: sprintf('9780000001%03d', $number),
            copyStatus: null,
        );
    }

    $this->get(route('public.catalog.index'))
        ->assertOk()
        ->assertSee('Katalogtitel 01')
        ->assertDontSee('Katalogtitel 21')
        ->assertSee('von 2');

    $this->get(route('public.catalog.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Katalogtitel 21')
        ->assertDontSee('Katalogtitel 01')
        ->assertSee('von 2');
});

it('rejects invalid public catalog filter values before they reach the query', function (): void {
    $this->get(route('public.catalog.index', ['sort' => 'DROP TABLE', 'active_only' => 'maybe']))
        ->assertRedirect()
        ->assertSessionHasErrors(['sort', 'active_only']);
});
