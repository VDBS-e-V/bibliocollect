<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogCoverService;
use App\Modules\Catalog\Services\CatalogGeneratedCoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    config()->set('catalog.covers.disk', 'public');
    config()->set('catalog.covers.directory', 'catalog/covers');
});

it('erzeugt fehlende SVG-Cover nur einmal und verwendet danach dieselbe Datei', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Der kleine Prinz']);
    $service = app(CatalogCoverService::class);

    $first = $service->displayUrlForTitle($title);

    expect($first)->not->toBeNull()
        ->and($first)->toContain('catalog/covers/generated/v1/');

    $paths = Storage::disk('public')->allFiles('catalog/covers/generated/v1');

    expect($paths)->toHaveCount(1);
    $content = Storage::disk('public')->get($paths[0]);
    expect($content)->toContain('<svg')
        ->toContain('Der kleine Prinz')
        ->toContain('BIBLIOCOLLECT');

    expect($service->displayUrlForTitle($title))->toBe($first)
        ->and(Storage::disk('public')->allFiles('catalog/covers/generated/v1'))->toHaveCount(1)
        ->and(Storage::disk('public')->get($paths[0]))->toBe($content);
});

it('erstellt nach Titeländerungen eine neue Cache-Datei', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Alte Geschichte']);
    $service = app(CatalogCoverService::class);
    $first = $service->displayUrlForTitle($title);

    $title->update(['preferred_title' => 'Neue Geschichte']);
    $second = $service->displayUrlForTitle($title);

    expect($second)->not->toBe($first)
        ->and(Storage::disk('public')->allFiles('catalog/covers/generated/v1'))->toHaveCount(2);
});

it('bevorzugt jederzeit ein nachträglich hinterlegtes Originalcover', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Original später vorhanden']);
    $edition = Edition::query()->create(['title_id' => $title->getKey()]);
    $service = app(CatalogCoverService::class);

    $generated = $service->displayUrlForTitle($title);
    $originalPath = 'catalog/covers/original.jpg';
    Storage::disk('public')->put($originalPath, 'image');
    $edition->forceFill(['cover_path' => $originalPath])->save();
    $title->unsetRelation('editions');

    $original = $service->displayUrlForTitle($title);

    expect($original)->toContain($originalPath)
        ->and($original)->not->toBe($generated)
        ->and(Storage::disk('public')->allFiles('catalog/covers/generated/v1'))->toHaveCount(1);
});

it('verwendet verschiedene feste Farbvarianten für die beiden Beispielbücher', function (): void {
    $covers = app(CatalogGeneratedCoverService::class);

    expect($covers->paletteIndex('Der kleine Prinz'))
        ->not->toBe($covers->paletteIndex('Momo'));
});

it('schützt SVG-Dateien vor XML- und Markup-Injection aus Katalogmetadaten', function (): void {
    $title = Title::query()->create(['preferred_title' => 'A & B <script>alert(1)</script>']);
    app(CatalogCoverService::class)->displayUrlForTitle($title);
    $file = Storage::disk('public')->allFiles('catalog/covers/generated/v1')[0];
    $svg = Storage::disk('public')->get($file);

    expect($svg)->toContain('A &amp; B')
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>');
});
