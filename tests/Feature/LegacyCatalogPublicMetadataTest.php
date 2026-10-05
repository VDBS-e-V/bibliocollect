<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows rich bibliographic metadata and classification publicly without exposing private legacy copy metadata', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Metadaten-Test']);
    $contributor = Contributor::query()->create([
        'display_name' => 'Ada Beispiel',
        'sort_name' => 'Beispiel, Ada',
        'gnd_id' => '123456789',
    ]);
    TitleContribution::query()->create([
        'title_id' => $title->getKey(),
        'contributor_id' => $contributor->getKey(),
        'role_key' => 'author',
        'position' => 1,
    ]);

    $edition = Edition::query()->create([
        'title_id' => $title->getKey(),
        'edition_statement' => '1. Auflage',
        'isbn' => '9783000000001',
        'publisher_name' => 'Testverlag',
        'publication_year' => 2025,
        'media_type' => 'book',
        'language_code' => 'de',
        'responsibility_statement' => 'Ada Beispiel',
        'series_statement' => 'Testreihe',
        'publication_place' => 'Berlin',
        'edition_number' => '1. Auflage',
        'alternate_identifiers' => ['ALT-001'],
        'original_language_code' => 'en',
        'page_count' => 224,
        'physical_extent' => '224 Seiten',
        'summary' => 'Eine ausführliche Inhaltsangabe.',
        'subject_keywords' => 'Bibliothek, Test',
        'subject_keywords_system' => 'Schulbibliothek',
        'target_audience' => 'Jugendliche',
        'metadata_source' => 'dnb',
        'source_record_id' => 'RCN-123',
    ]);

    $topic = CatalogTopic::query()->create(['name' => 'Sachmedien']);
    $signature = CatalogSignature::query()->create(['signature' => 'I. A 1 a']);
    $signature->topics()->attach($topic->getKey(), ['position' => 1]);

    Copy::query()->create([
        'edition_id' => $edition->getKey(),
        'barcode' => 'PRIVATE-BARCODE-001',
        'status' => CopyStatus::Active,
        'shelf_location' => 'I. A 1 a',
        'signature_id' => $signature->getKey(),
        'purchase_price' => '19.90',
        'internal_notes' => 'NICHT ÖFFENTLICH',
        'legacy_media_id' => '42',
        'legacy_loan_count' => 99,
    ]);

    $this->get(route('public.catalog.show', ['titleId' => $title->getKey()]))
        ->assertOk()
        ->assertSee('Ada Beispiel')
        ->assertSee('GND 123456789')
        ->assertSee('Testreihe')
        ->assertSee('Berlin')
        ->assertSee('ALT-001')
        ->assertSee('224 Seiten')
        ->assertSee('Eine ausführliche Inhaltsangabe.')
        ->assertSee('Sachmedien')
        ->assertSee('RCN-123')
        ->assertDontSee('PRIVATE-BARCODE-001')
        ->assertDontSee('NICHT ÖFFENTLICH')
        ->assertDontSee('19.90');
});

it('finds titles through extended dnb gnd series keyword and identifier metadata', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Versteckter Haupttitel']);
    $contributor = Contributor::query()->create([
        'display_name' => 'Normdaten Person',
        'gnd_id' => 'GND-SEARCH-777',
    ]);
    TitleContribution::query()->create([
        'title_id' => $title->getKey(),
        'contributor_id' => $contributor->getKey(),
        'role_key' => 'author',
        'position' => 1,
    ]);
    Edition::query()->create([
        'title_id' => $title->getKey(),
        'series_statement' => 'Reihe Fernweh',
        'subject_keywords' => 'Wolkenkunde, Fernreise',
        'source_record_id' => 'DNB-RCN-SEARCH-42',
        'doi_handle' => '10.1234/example',
    ]);

    $this->get(route('public.catalog.index', ['q' => 'GND-SEARCH-777']))
        ->assertOk()->assertSee('Versteckter Haupttitel');
    $this->get(route('public.catalog.index', ['q' => 'Fernweh']))
        ->assertOk()->assertSee('Versteckter Haupttitel');
    $this->get(route('public.catalog.index', ['q' => 'Wolkenkunde']))
        ->assertOk()->assertSee('Versteckter Haupttitel');
    $this->get(route('public.catalog.index', ['q' => 'DNB-RCN-SEARCH-42']))
        ->assertOk()->assertSee('Versteckter Haupttitel');
});
