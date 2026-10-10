<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Lookup\GoogleBooks;

use App\Modules\Catalog\Contracts\BibliographicLookupProvider;
use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\Exceptions\BibliographicLookupUnavailable;
use App\Modules\Catalog\Lookup\Support\LanguageCodes;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Bibliografische Daten aus Google Books (Volumes-API). Ohne Schlüssel gilt eine kleine gemeinsame Abfragegrenze; mit dem Schlüssel
 * aus `CATALOG_COVER_GOOGLE_BOOKS_KEY` mehr. Wie Open Library weniger verlässlich als die DNB.
 */
final readonly class GoogleBooksLookupProvider implements BibliographicLookupProvider
{
    public const SOURCE = 'googlebooks';

    public function __construct(private CatalogIsbnNormalizer $isbns) {}

    /** @return list<BibliographicRecord> */
    public function findByIsbn(string $isbn): array
    {
        $compact = $this->isbns->normalize($isbn);

        if (! $this->isbns->isStandardFormat($compact)) {
            return [];
        }

        $wanted = $this->isbns->toIsbn13($compact);
        $records = [];

        foreach ($this->volumes('isbn:'.$compact, 5) as $volume) {
            $record = $this->fromVolume($volume, $wanted);

            // Treffer mit anderer ISBN gehören zu einem anderen Medium.
            if ($record !== null && $record->isbn !== null && $record->isbn === $wanted) {
                $records[] = $record;
            }
        }

        return $records;
    }

    public function findByRecordId(string $recordId): array
    {
        return [];
    }

    /** @return list<BibliographicRecord> */
    public function search(?string $title, ?string $person): array
    {
        $parts = [];

        foreach ($this->terms($title) as $term) {
            $parts[] = 'intitle:'.$term;
        }

        foreach ($this->terms($person) as $term) {
            $parts[] = 'inauthor:'.$term;
        }

        if ($parts === []) {
            return [];
        }

        $records = [];

        foreach ($this->volumes(implode('+', $parts), 10) as $volume) {
            $record = $this->fromVolume($volume, null);

            if ($record !== null) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws BibliographicLookupUnavailable
     */
    private function volumes(string $query, int $max): array
    {
        $params = ['q' => $query, 'maxResults' => $max, 'printType' => 'books'];
        $key = config('catalog.covers.google_books.key');

        if (is_string($key) && trim($key) !== '') {
            $params['key'] = trim($key);
        }

        try {
            $response = Http::timeout(max(1, (int) config('catalog.lookup.google_books.timeout', 10)))
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'BiblioCollect/1.0 (VDBS e.V. Schulbibliothek)'])
                ->get('https://www.googleapis.com/books/v1/volumes', $params);
        } catch (ConnectionException $exception) {
            throw BibliographicLookupUnavailable::because('keine Verbindung zu Google Books', $exception);
        }

        $this->guard($response);

        return array_values(array_filter((array) $response->json('items', []), 'is_array'));
    }

    /** @throws BibliographicLookupUnavailable */
    private function guard(Response $response): void
    {
        if (! $response->successful()) {
            throw BibliographicLookupUnavailable::because('Google Books antwortete mit HTTP '.$response->status());
        }
    }

    /**
     * @param  array<string, mixed>  $volume
     */
    private function fromVolume(array $volume, ?string $fallbackIsbn): ?BibliographicRecord
    {
        $info = is_array($volume['volumeInfo'] ?? null) ? $volume['volumeInfo'] : [];
        $title = $this->text($info['title'] ?? null);

        if ($title === null) {
            return null;
        }

        $isbn = $fallbackIsbn;

        foreach ((array) ($info['industryIdentifiers'] ?? []) as $identifier) {
            if (is_array($identifier) && ($identifier['type'] ?? null) === 'ISBN_13' && is_string($identifier['identifier'] ?? null)) {
                $isbn = $this->isbns->normalize($identifier['identifier']);

                break;
            }
        }

        $contributors = [];

        foreach ((array) ($info['authors'] ?? []) as $name) {
            $name = $this->text($name);

            if ($name !== null) {
                $contributors[] = ['name' => $name, 'role' => 'author', 'gnd_id' => null];
            }
        }

        $pages = $info['pageCount'] ?? null;
        $id = $this->text($volume['id'] ?? null);

        return new BibliographicRecord(
            source: self::SOURCE,
            sourceRecordId: $id,
            sourcePermalink: $this->text($info['canonicalVolumeLink'] ?? null) ?? $this->text($info['infoLink'] ?? null),
            title: $title,
            subtitle: $this->text($info['subtitle'] ?? null),
            responsibilityStatement: null,
            contributors: $contributors,
            isbn: $isbn,
            publisherName: $this->text($info['publisher'] ?? null),
            publicationPlace: null,
            publicationYear: is_string($info['publishedDate'] ?? null) && preg_match('/^(\d{4})/', $info['publishedDate'], $m) === 1 ? (int) $m[1] : null,
            editionStatement: null,
            physicalExtent: is_numeric($pages) && (int) $pages > 0 ? (int) $pages.' Seiten' : null,
            languageCode: LanguageCodes::marc($this->text($info['language'] ?? null)),
            originalLanguageCode: null,
            mediaType: 'book',
            seriesStatement: null,
            summary: $this->summary($info['description'] ?? null),
            subjectKeywords: $this->categories((array) ($info['categories'] ?? [])),
            targetAudience: null,
        );
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function summary(mixed $value): ?string
    {
        $text = $this->text($value);

        return $text === null ? null : mb_substr(trim((string) preg_replace('/\s+/u', ' ', strip_tags($text))), 0, 2000);
    }

    /** @param  list<mixed>  $categories */
    private function categories(array $categories): ?string
    {
        $parts = [];

        foreach ($categories as $category) {
            foreach (explode('/', (string) $category) as $part) {
                $part = trim($part);

                if ($part !== '') {
                    $parts[mb_strtolower($part)] = $part;
                }
            }
        }

        return $parts === [] ? null : implode(', ', array_slice(array_values($parts), 0, 8));
    }

    /** @return list<string> */
    private function terms(?string $text): array
    {
        if ($text === null) {
            return [];
        }

        return array_slice(preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 6);
    }
}
