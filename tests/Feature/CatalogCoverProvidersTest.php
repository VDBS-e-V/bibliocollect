<?php

declare(strict_types=1);

use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\Covers\ChainedCatalogCoverProvider;
use App\Modules\Catalog\Covers\GoogleBooksCoverProvider;
use App\Modules\Catalog\Covers\OpenLibraryCoverProvider;
use App\Modules\Catalog\DTOs\CatalogCoverImage;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function coverPng(): string
{
    return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=', true);
}

function coverEdition(?string $isbn = '9783522202800'): Edition
{
    $title = Title::query()->create(['preferred_title' => 'Cover-Quelle']);

    return Edition::query()->create(['title_id' => $title->getKey(), 'isbn' => $isbn]);
}

it('downloads a cover from Open Library by ISBN without any API key', function (): void {
    config()->set('catalog.covers.open_library.enabled', true);
    Http::fake(['covers.openlibrary.org/*' => Http::response(coverPng(), 200, ['Content-Type' => 'image/png'])]);

    $image = app(OpenLibraryCoverProvider::class)->fetch(coverEdition('978-3-522-20280-0'));

    expect($image)->toBeInstanceOf(CatalogCoverImage::class)
        ->and($image?->mimeType)->toBe('image/png')
        ->and($image?->source)->toBe('open-library');

    Http::assertSent(static fn (Request $request): bool => str_starts_with($request->url(), 'https://covers.openlibrary.org/b/isbn/9783522202800-L.jpg')
        && $request['default'] === 'false');
});

it('treats a missing Open Library cover, a broken image and a missing ISBN as not found', function (): void {
    config()->set('catalog.covers.open_library.enabled', true);
    $provider = app(OpenLibraryCoverProvider::class);

    Http::fake(['covers.openlibrary.org/*' => Http::response('', 404)]);
    expect($provider->fetch(coverEdition()))->toBeNull();

    Http::fake(['covers.openlibrary.org/*' => Http::response('<html>kein Bild</html>', 200)]);
    expect($provider->fetch(coverEdition()))->toBeNull();

    Http::fake();
    expect($provider->fetch(coverEdition(null)))->toBeNull()
        ->and($provider->fetch(coverEdition('keine-isbn')))->toBeNull();
});

it('keeps Open Library switchable and Google Books off without a key', function (): void {
    config()->set('catalog.covers.open_library.enabled', false);
    config()->set('catalog.covers.google_books.key', null);

    expect(app(OpenLibraryCoverProvider::class)->configured())->toBeFalse()
        ->and(app(GoogleBooksCoverProvider::class)->configured())->toBeFalse()
        ->and(app(CatalogCoverProvider::class)->configured())->toBeFalse();

    Http::fake();
    expect(app(GoogleBooksCoverProvider::class)->fetch(coverEdition()))->toBeNull();
    Http::assertNothingSent();
});

it('downloads a Google Books cover through https and rejects untrusted image hosts', function (): void {
    config()->set('catalog.covers.google_books.key', 'test-key');

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [['volumeInfo' => ['imageLinks' => [
                'thumbnail' => 'http://books.google.com/books/content?id=abc&printsec=frontcover&img=1&zoom=1&edge=curl',
            ]]]],
        ]),
        'books.google.com/*' => Http::response(coverPng(), 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $image = app(GoogleBooksCoverProvider::class)->fetch(coverEdition());

    expect($image?->source)->toBe('google-books')
        ->and($image?->mimeType)->toBe('image/png')
        ->and($image?->sourceReference)->toStartWith('https://books.google.com/')
        ->and($image?->sourceReference)->not->toContain('edge=curl');

    Http::assertSent(static fn (Request $request): bool => str_contains($request->url(), 'googleapis.com')
        && $request['key'] === 'test-key'
        && $request['q'] === 'isbn:9783522202800');
});

it('rejects Google Books image links that do not point to a Google host', function (): void {
    config()->set('catalog.covers.google_books.key', 'test-key');

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [['volumeInfo' => ['imageLinks' => ['thumbnail' => 'https://evil.example.test/cover.jpg']]]],
        ]),
    ]);

    expect(app(GoogleBooksCoverProvider::class)->fetch(coverEdition()))->toBeNull();

    Http::assertNotSent(static fn (Request $request): bool => str_contains($request->url(), 'evil.example.test'));
});

it('falls back from Open Library to Google Books and returns nothing when both miss', function (): void {
    config()->set('catalog.covers.open_library.enabled', true);
    config()->set('catalog.covers.google_books.key', 'test-key');

    Http::fake([
        'covers.openlibrary.org/*' => Http::response('', 404),
        'www.googleapis.com/*' => Http::response([
            'items' => [['volumeInfo' => ['imageLinks' => ['large' => 'https://books.google.com/books/content?id=abc']]]],
        ]),
        'books.google.com/*' => Http::response(coverPng(), 200),
    ]);

    $chain = app(CatalogCoverProvider::class);

    expect($chain)->toBeInstanceOf(ChainedCatalogCoverProvider::class)
        ->and($chain->configured())->toBeTrue()
        ->and($chain->fetch(coverEdition())?->source)->toBe('google-books');
});

it('returns nothing when neither Open Library nor Google Books has a cover', function (): void {
    config()->set('catalog.covers.open_library.enabled', true);
    config()->set('catalog.covers.google_books.key', 'test-key');

    Http::fake([
        'covers.openlibrary.org/*' => Http::response('', 404),
        'www.googleapis.com/*' => Http::response(['totalItems' => 0]),
    ]);

    expect(app(CatalogCoverProvider::class)->fetch(coverEdition()))->toBeNull();
});

it('prefers Open Library and does not call Google Books when it already has a cover', function (): void {
    config()->set('catalog.covers.open_library.enabled', true);
    config()->set('catalog.covers.google_books.key', 'test-key');

    Http::fake(['covers.openlibrary.org/*' => Http::response(coverPng(), 200)]);

    expect(app(CatalogCoverProvider::class)->fetch(coverEdition())?->source)->toBe('open-library');

    Http::assertNotSent(static fn (Request $request): bool => str_contains($request->url(), 'googleapis.com'));
});
