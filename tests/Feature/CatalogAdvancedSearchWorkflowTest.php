<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('offers a simplified public advanced search without authentication', function (): void {
    $this->get(route('public.catalog.advanced'))
        ->assertOk()
        ->assertSee('Erweiterte Suche')
        ->assertSee('Autor:in / Verantwortliche')
        ->assertSee('Schlagwort')
        ->assertSee('ISBN / Kennung')
        ->assertSee('Thema / Klassifikation')
        ->assertDontSee('DNB-/Quell-ID');
});

it('combines public advanced filters on the same bibliographic edition', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Sterne für Neugierige']);
    $contributor = Contributor::query()->create(['display_name' => 'Ada Beispiel']);
    TitleContribution::query()->create([
        'title_id' => $title->getKey(),
        'contributor_id' => $contributor->getKey(),
        'role_key' => 'author',
        'position' => 1,
    ]);

    $edition = Edition::query()->create([
        'title_id' => $title->getKey(),
        'isbn' => '9783000000131',
        'publisher_name' => 'Schulbuch Verlag',
        'publication_year' => 2024,
        'media_type' => 'book',
        'language_code' => 'de',
        'subject_keywords' => 'Weltraum, Sterne, Astronomie',
        'local_classification' => 'NAT ASTRO',
    ]);

    $topic = CatalogTopic::query()->create(['name' => 'Astronomie']);
    $shelf = CatalogShelf::query()->create(['code' => 'NAT ASTRO']);
    $shelf->topics()->attach($topic->getKey(), ['position' => 1]);
    Copy::query()->create([
        'edition_id' => $edition->getKey(),
        'shelf_location' => 'NAT ASTRO',
        'barcode' => 'ADV-PUBLIC-001',
        'status' => CopyStatus::Active,
    ]);

    $other = Title::query()->create(['preferred_title' => 'Fußball total']);
    Edition::query()->create([
        'title_id' => $other->getKey(),
        'isbn' => '9783000000148',
        'publisher_name' => 'Sport Verlag',
        'publication_year' => 2024,
        'media_type' => 'book',
        'language_code' => 'de',
        'subject_keywords' => 'Sport',
    ]);

    $this->get(route('public.catalog.index', [
        'contributor' => 'Ada',
        'subject' => 'Weltraum',
        'publisher' => 'Schulbuch',
        'topic' => 'Astronomie',
        'year_from' => 2020,
        'year_to' => 2026,
        'active_only' => 1,
    ]))
        ->assertOk()
        ->assertSee('Sterne für Neugierige')
        ->assertSee('Astronomie')
        ->assertDontSee('Fußball total');
});

it('renders locally cached covers and falls back to the bundled placeholder', function (): void {
    Storage::fake('public');
    config()->set('catalog.covers.disk', 'public');

    $withCover = Title::query()->create(['preferred_title' => 'Mit Cover']);
    $withCoverEdition = catalogTestWithCopy(Edition::query()->create([
        'title_id' => $withCover->getKey(),
        'isbn' => '9783000000155',
    ]));
    $path = 'catalog/covers/local-test.png';
    Storage::disk('public')->put($path, 'local-cover');
    $withCoverEdition->forceFill(['cover_path' => $path, 'cover_status' => 'ready'])->save();

    $withoutCover = Title::query()->create(['preferred_title' => 'Ohne Cover']);
    catalogTestWithCopy(Edition::query()->create([
        'title_id' => $withoutCover->getKey(),
        'isbn' => '9783000000162',
    ]));

    $this->get(route('public.catalog.index'))
        ->assertOk()
        ->assertSee('catalog/covers/local-test.png', false)
        ->assertSee('brand/vdbs/catalog-cover-placeholder.svg', false);
});

it('provides a more detailed internal catalog search for catalog managers', function (): void {
    $this->seed(DatabaseSeeder::class);
    $staff = User::query()->where('email', 'staff@demo.bibliocollect.test')->firstOrFail();

    $this->actingAs($staff)
        ->get(route('pos.catalog.index', [
            'source_record_id' => 'DEMO-RCN-GIVER',
            'publication_place' => 'Boston',
            'target_audience' => 'Jugendliche',
        ]))
        ->assertOk()
        ->assertSee('Interne erweiterte Recherche')
        ->assertSee('The Giver')
        ->assertDontSee('Momo');
});

it('paginates detailed internal catalog results with selectable page sizes', function (): void {
    $this->seed(DatabaseSeeder::class);
    $staff = User::query()->where('email', 'staff@demo.bibliocollect.test')->firstOrFail();

    foreach (range(1, 25) as $number) {
        $title = Title::query()->create([
            'preferred_title' => sprintf('Paging Intern %02d', $number),
            'sort_title' => sprintf('Paging Intern %02d', $number),
        ]);
        Edition::query()->create([
            'title_id' => $title->getKey(),
            'publisher_name' => 'Paging Verlag',
        ]);
    }

    $this->actingAs($staff)
        ->get(route('pos.catalog.index', [
            'publisher' => 'Paging Verlag',
            'per_page' => 10,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertSee('Paging Intern 11')
        ->assertDontSee('Paging Intern 01')
        ->assertDontSee('Paging Intern 21')
        ->assertSee('Ergebnisse:')
        ->assertSee('11–20 von 25')
        ->assertSee('von 3');
});
