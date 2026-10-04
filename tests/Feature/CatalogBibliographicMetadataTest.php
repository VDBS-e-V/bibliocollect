<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Catalog\Queries\SearchCatalogTitlesQuery;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('stores ordered title responsibilities and open edition metadata', function (): void {
    $title = Title::query()->create([
        'preferred_title' => 'Momo',
    ]);

    $contributor = Contributor::query()->create([
        'display_name' => 'Michael Ende',
        'sort_name' => 'Ende, Michael',
    ]);

    $contribution = TitleContribution::query()->create([
        'title_id' => $title->getKey(),
        'contributor_id' => $contributor->getKey(),
        'role_key' => 'author',
        'position' => 1,
    ]);

    $edition = Edition::query()->create([
        'title_id' => $title->getKey(),
        'isbn' => '9783522202803',
        'publisher_name' => 'Thienemann',
        'publication_year' => 2023,
        'media_type' => 'book',
        'language_code' => 'de',
        'minimum_age' => 10,
    ]);

    $loadedTitle = $title->fresh()->load('contributions.contributor');

    expect(Str::isUlid((string) $contributor->getKey()))->toBeTrue()
        ->and(Str::isUlid((string) $contribution->getKey()))->toBeTrue()
        ->and($loadedTitle->contributions)->toHaveCount(1)
        ->and($loadedTitle->contributions->first()->contributor->is($contributor))->toBeTrue()
        ->and($loadedTitle->contributions->first()->role_key)->toBe('author')
        ->and($edition->media_type)->toBe('book')
        ->and($edition->language_code)->toBe('de');
});

it('does not duplicate the same contributor role on one title', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Beispieltitel']);
    $contributor = Contributor::query()->create(['display_name' => 'Beispiel Autor']);

    TitleContribution::query()->create([
        'title_id' => $title->getKey(),
        'contributor_id' => $contributor->getKey(),
        'role_key' => 'author',
        'position' => 1,
    ]);

    expect(fn (): TitleContribution => TitleContribution::query()->create([
        'title_id' => $title->getKey(),
        'contributor_id' => $contributor->getKey(),
        'role_key' => 'author',
        'position' => 2,
    ]))->toThrow(QueryException::class);
});

it('searches catalog titles through title contributor and edition metadata but not copy barcodes', function (): void {
    $momo = Title::query()->create([
        'preferred_title' => 'Momo',
        'subtitle' => 'Ein Märchen-Roman',
    ]);

    $contributor = Contributor::query()->create([
        'display_name' => 'Michael Ende',
        'sort_name' => 'Ende, Michael',
    ]);

    TitleContribution::query()->create([
        'title_id' => $momo->getKey(),
        'contributor_id' => $contributor->getKey(),
        'role_key' => 'author',
        'position' => 1,
    ]);

    $edition = Edition::query()->create([
        'title_id' => $momo->getKey(),
        'isbn' => '9783522202803',
        'publisher_name' => 'Thienemann',
        'media_type' => 'book',
        'language_code' => 'de',
    ]);

    Copy::query()->create([
        'edition_id' => $edition->getKey(),
        'barcode' => 'BC-NOT-TITLE-SEARCH',
    ]);

    Title::query()->create(['preferred_title' => 'Ein anderer Titel']);

    $query = app(SearchCatalogTitlesQuery::class);

    expect($query->execute('Momo')->pluck('id')->all())->toBe([$momo->getKey()])
        ->and($query->execute('Michael Ende')->pluck('id')->all())->toBe([$momo->getKey()])
        ->and($query->execute('9783522202803')->pluck('id')->all())->toBe([$momo->getKey()])
        ->and($query->execute('Thienemann')->pluck('id')->all())->toBe([$momo->getKey()])
        ->and($query->execute('BC-NOT-TITLE-SEARCH'))->toHaveCount(0)
        ->and($query->execute('%__'))->toHaveCount(0);
});
