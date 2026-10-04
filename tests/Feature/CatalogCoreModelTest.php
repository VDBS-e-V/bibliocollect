<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('separates titles editions and physical copies with ULID identities', function (): void {
    $title = Title::query()->create([
        'preferred_title' => 'Beispieltitel',
        'subtitle' => 'Ein Test',
        'sort_title' => 'Beispieltitel',
    ]);

    $edition = $title->editions()->create([
        'edition_statement' => '2. Auflage',
        'isbn' => '9781234567890',
        'publisher_name' => 'Beispielverlag',
        'publication_year' => 2026,
        'minimum_age' => 12,
        'age_rating_label' => 'ab 12',
    ]);

    $copy = $edition->copies()->create([
        'barcode' => 'BC-000001',
        'status' => CopyStatus::Active,
        'shelf_location' => 'A-01',
    ]);

    expect(Str::isUlid((string) $title->getKey()))->toBeTrue()
        ->and(Str::isUlid((string) $edition->getKey()))->toBeTrue()
        ->and(Str::isUlid((string) $copy->getKey()))->toBeTrue()
        ->and($edition->title->is($title))->toBeTrue()
        ->and($copy->edition->is($edition))->toBeTrue()
        ->and($edition->minimum_age)->toBe(12)
        ->and($copy->status)->toBe(CopyStatus::Active);
});

it('keeps a visible copy barcode unique without using it as the primary key', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Barcode-Test']);
    $edition = Edition::query()->create([
        'title_id' => $title->getKey(),
        'edition_statement' => 'Ausgabe 1',
    ]);

    $copy = Copy::query()->create([
        'edition_id' => $edition->getKey(),
        'barcode' => 'BC-UNIQUE-1',
    ]);

    expect($copy->getKeyName())->toBe('id')
        ->and($copy->getKey())->not->toBe($copy->barcode);

    expect(fn (): Copy => Copy::query()->create([
        'edition_id' => $edition->getKey(),
        'barcode' => 'BC-UNIQUE-1',
    ]))->toThrow(QueryException::class);
});

it('keeps acquisition and budget concerns out of the catalog core schema', function (): void {
    expect(Schema::hasColumn('catalog_editions', 'supplier_id'))->toBeFalse()
        ->and(Schema::hasColumn('catalog_editions', 'price'))->toBeFalse()
        ->and(Schema::hasColumn('catalog_copies', 'supplier_id'))->toBeFalse()
        ->and(Schema::hasColumn('catalog_copies', 'price'))->toBeFalse();
});
