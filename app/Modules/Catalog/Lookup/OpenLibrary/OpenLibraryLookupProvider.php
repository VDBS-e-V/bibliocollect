<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Lookup\OpenLibrary;

use App\Modules\Catalog\Contracts\BibliographicLookupProvider;
use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\Exceptions\BibliographicLookupUnavailable;
use App\Modules\Catalog\Lookup\Support\LanguageCodes;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Bibliografische Daten aus Open Library (frei, ohne Zugangsschlüssel). Gedacht für Bücher in allen Sprachen, die die DNB nicht kennt.
 * Die Angaben sind nutzergepflegt und weniger verlässlich als die DNB; Vorschläge daraus werden deshalb nie gesammelt übernommen.
 */
final readonly class OpenLibraryLookupProvider implements BibliographicLookupProvider
{
    public const SOURCE = 'openlibrary';

    private const MAX_KEYWORDS = 10;

    public function __construct(private CatalogIsbnNormalizer $isbns) {}

    /** @return list<BibliographicRecord> */
    public function findByIsbn(string $isbn): array
    {
        $compact = $this->isbns->normalize($isbn);

        if (! $this->isbns->isStandardFormat($compact)) {
            return [];
        }

        $response = $this->get('https://openlibrary.org/api/books', ['bibkeys' => 'ISBN:'.$compact, 'format' => 'json', 'jscmd' => 'data']);

        if ($response->status() === 404) {
            return [];
        }

        $entry = $response->json('ISBN:'.$compact);

        if (! is_array($entry) || $this->text($entry['title'] ?? null) === null) {
            return [];
        }

        $record = $this->fromData($entry, $this->isbns->toIsbn13($compact) ?? $compact, $this->editionLanguage($compact));

        return [$record];
    }

    /** Open Library kennt keine Datensatz-Nummern der DNB. */
    public function findByRecordId(string $recordId): array
    {
        return [];
    }

    /** @return list<BibliographicRecord> */
    public function search(?string $title, ?string $person): array
    {
        $title = $this->words($title);
        $person = $this->words($person);

        if ($title === '' && $person === '') {
            return [];
        }

        $response = $this->get('https://openlibrary.org/search.json', array_filter([
            'title' => $title,
            'author' => $person,
            'limit' => 10,
            'fields' => 'key,title,subtitle,author_name,first_publish_year,publisher,isbn,language,subject',
        ], static fn (mixed $value): bool => $value !== ''));

        $records = [];

        foreach ((array) $response->json('docs', []) as $doc) {
            if (! is_array($doc) || ! is_string($doc['title'] ?? null) || trim($doc['title']) === '') {
                continue;
            }

            $isbn = $this->firstIsbn((array) ($doc['isbn'] ?? []));
            $key = is_string($doc['key'] ?? null) ? $doc['key'] : null;

            $records[] = new BibliographicRecord(
                source: self::SOURCE,
                sourceRecordId: $key !== null ? basename($key) : null,
                sourcePermalink: $key !== null ? 'https://openlibrary.org'.$key : null,
                title: trim($doc['title']),
                subtitle: $this->text($doc['subtitle'] ?? null),
                responsibilityStatement: null,
                contributors: $this->contributors((array) ($doc['author_name'] ?? [])),
                isbn: $isbn,
                publisherName: $this->text(((array) ($doc['publisher'] ?? []))[0] ?? null),
                publicationPlace: null,
                publicationYear: is_numeric($doc['first_publish_year'] ?? null) ? (int) $doc['first_publish_year'] : null,
                editionStatement: null,
                physicalExtent: null,
                languageCode: LanguageCodes::marc($this->text(((array) ($doc['language'] ?? []))[0] ?? null)),
                originalLanguageCode: null,
                mediaType: 'book',
                seriesStatement: null,
                summary: null,
                subjectKeywords: $this->keywords((array) ($doc['subject'] ?? [])),
                targetAudience: null,
            );
        }

        return $records;
    }

    /**
     * @param  array<string, mixed>  $data  Antwort von `api/books?jscmd=data`
     */
    private function fromData(array $data, string $isbn, ?string $language): BibliographicRecord
    {
        $names = static fn (array $entries): array => array_map(static fn (mixed $entry): mixed => is_array($entry) ? ($entry['name'] ?? null) : $entry, $entries);
        $identifiers = is_array($data['identifiers'] ?? null) ? $data['identifiers'] : [];
        $id = $this->text(((array) ($identifiers['openlibrary'] ?? []))[0] ?? null);
        $pages = $data['number_of_pages'] ?? null;

        return new BibliographicRecord(
            source: self::SOURCE,
            sourceRecordId: $id,
            sourcePermalink: $this->https($this->text($data['url'] ?? null)),
            title: (string) $this->text($data['title'] ?? null),
            subtitle: $this->text($data['subtitle'] ?? null),
            responsibilityStatement: $this->text($data['by_statement'] ?? null),
            contributors: $this->contributors($names((array) ($data['authors'] ?? []))),
            isbn: $isbn,
            publisherName: $this->text($names((array) ($data['publishers'] ?? []))[0] ?? null),
            publicationPlace: $this->text($names((array) ($data['publish_places'] ?? []))[0] ?? null),
            publicationYear: $this->year($data['publish_date'] ?? null),
            editionStatement: $this->text($data['edition_name'] ?? null),
            physicalExtent: is_numeric($pages) && (int) $pages > 0 ? (int) $pages.' Seiten' : null,
            languageCode: $language,
            originalLanguageCode: null,
            mediaType: 'book',
            seriesStatement: null,
            summary: null,
            subjectKeywords: $this->keywords((array) ($data['subjects'] ?? [])),
            targetAudience: null,
        );
    }

    /** Die Sprache steht nur im Ausgabe-Datensatz (`/isbn/<ISBN>.json`, folgt einer Weiterleitung). Fehlt er, bleibt die Sprache leer. */
    private function editionLanguage(string $isbn): ?string
    {
        try {
            $response = $this->get('https://openlibrary.org/isbn/'.$isbn.'.json', []);
        } catch (BibliographicLookupUnavailable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        foreach ((array) $response->json('languages', []) as $entry) {
            $code = LanguageCodes::marc(is_array($entry) ? ($entry['key'] ?? null) : (is_string($entry) ? $entry : null));

            if ($code !== null) {
                return $code;
            }
        }

        return null;
    }

    /**
     * @param  array<string, scalar|null>  $query
     *
     * @throws BibliographicLookupUnavailable
     */
    private function get(string $url, array $query): Response
    {
        try {
            $response = Http::timeout(max(1, (int) config('catalog.lookup.open_library.timeout', 10)))
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'BiblioCollect/1.0 (VDBS e.V. Schulbibliothek)'])
                ->get($url, $query);
        } catch (ConnectionException $exception) {
            throw BibliographicLookupUnavailable::because('keine Verbindung zu Open Library', $exception);
        }

        if ($response->status() !== 404 && ! $response->successful()) {
            throw BibliographicLookupUnavailable::because('Open Library antwortete mit HTTP '.$response->status());
        }

        return $response;
    }

    private function https(?string $url): ?string
    {
        return $url === null ? null : (string) preg_replace('#^http://#', 'https://', $url);
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** Suchwörter ohne Sonderzeichen, damit Nutzereingaben die Abfrage nicht verändern. */
    private function words(?string $text): string
    {
        return $text === null ? '' : trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text));
    }

    private function year(mixed $value): ?int
    {
        if (is_numeric($value) && (int) $value >= 1000 && (int) $value <= 2200) {
            return (int) $value;
        }

        return is_string($value) && preg_match('/\b(1[0-9]{3}|20[0-9]{2})\b/', $value, $m) === 1 ? (int) $m[1] : null;
    }

    /**
     * @param  list<mixed>  $names
     * @return list<array{name: string, role: string, gnd_id: string|null}>
     */
    private function contributors(array $names): array
    {
        $result = [];

        foreach ($names as $name) {
            $name = $this->text($name);

            if ($name !== null) {
                $result[] = ['name' => $name, 'role' => 'author', 'gnd_id' => null];
            }
        }

        return $result;
    }

    /** @param  list<mixed>  $subjects */
    private function keywords(array $subjects): ?string
    {
        $keywords = [];

        foreach ($subjects as $subject) {
            $text = $this->text(is_array($subject) ? ($subject['name'] ?? null) : $subject);

            if ($text !== null && mb_strlen($text) <= 60) {
                $keywords[mb_strtolower($text)] ??= $text;
            }
        }

        return $keywords === [] ? null : implode(', ', array_slice(array_values($keywords), 0, self::MAX_KEYWORDS));
    }

    /** @param  list<mixed>  $isbns */
    private function firstIsbn(array $isbns): ?string
    {
        foreach ($isbns as $isbn) {
            if (is_string($isbn) && $this->isbns->isStandardFormat($this->isbns->normalize($isbn)) && strlen($this->isbns->normalize($isbn)) === 13) {
                return $this->isbns->normalize($isbn);
            }
        }

        return null;
    }
}
