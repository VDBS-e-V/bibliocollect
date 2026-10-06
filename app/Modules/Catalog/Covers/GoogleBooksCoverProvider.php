<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Covers;

use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\DTOs\CatalogCoverImage;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Fallback-Quelle über die Google Books API. Die Nutzung ist kostenlos, setzt in der Praxis aber
 * einen API-Key voraus (CATALOG_COVER_GOOGLE_BOOKS_KEY). Ohne Key bleibt der Provider ausgeschaltet.
 */
final readonly class GoogleBooksCoverProvider implements CatalogCoverProvider
{
    private const IMAGE_SIZES = ['extraLarge', 'large', 'medium', 'small', 'thumbnail'];

    public function __construct(
        private CatalogIsbnNormalizer $isbns,
        private CoverImageFactory $images,
    ) {}

    public function configured(): bool
    {
        $key = config('catalog.covers.google_books.key');

        return is_string($key) && trim($key) !== '';
    }

    public function fetch(Edition $edition): ?CatalogCoverImage
    {
        $isbn = is_string($edition->isbn) ? $this->isbns->normalize($edition->isbn) : null;

        if (! $this->configured() || $isbn === null || ! $this->isbns->isStandardFormat($isbn)) {
            return null;
        }

        $timeout = max(1, (int) config('catalog.covers.google_books.timeout', 10));

        try {
            $volume = Http::timeout($timeout)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => 'isbn:'.$isbn,
                'maxResults' => 1,
                'key' => (string) config('catalog.covers.google_books.key'),
                'fields' => 'items(volumeInfo/imageLinks)',
            ]);

            if (! $volume->successful()) {
                return null;
            }

            $imageUrl = $this->imageUrl($volume->json('items.0.volumeInfo.imageLinks'));

            if ($imageUrl === null) {
                return null;
            }

            $image = Http::timeout($timeout)->get($imageUrl);
        } catch (ConnectionException) {
            return null;
        }

        if (! $image->successful()) {
            return null;
        }

        return $this->images->fromBinary($image->body(), 'google-books', $imageUrl);
    }

    private function imageUrl(mixed $links): ?string
    {
        if (! is_array($links)) {
            return null;
        }

        foreach (self::IMAGE_SIZES as $size) {
            $candidate = $links[$size] ?? null;

            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            // Google liefert teils http://; die Seitenkante ("edge=curl") ist reine Dekoration.
            $url = preg_replace('/^http:/i', 'https:', $candidate) ?? $candidate;
            $url = str_replace('&edge=curl', '', $url);

            return $this->isTrustedHost($url) ? $url : null;
        }

        return null;
    }

    private function isTrustedHost(string $url): bool
    {
        if (! str_starts_with($url, 'https://')) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return false;
        }

        $host = strtolower($host);

        return $host === 'books.google.com'
            || str_ends_with($host, '.google.com')
            || str_ends_with($host, '.googleusercontent.com')
            || str_ends_with($host, '.gstatic.com');
    }
}
