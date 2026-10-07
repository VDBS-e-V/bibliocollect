<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sucht zu einer ISBN eine Zusammenfassung (Klappentext) bei Google Books und, falls dort nichts steht, bei Open Library.
 * Das Ergebnis ist ein Vorschlag, der in der Erfassung geprüft wird; ein Ausfall der Quellen blockiert nie.
 */
final readonly class CatalogSummaryService
{
    public const MAX_LENGTH = 2000;

    public function __construct(private CatalogIsbnNormalizer $isbns) {}

    /** @return array{text: string, source: string}|null */
    public function findByIsbn(string $isbn): ?array
    {
        $isbn = $this->isbns->normalize($isbn);

        if (! $this->isbns->isStandardFormat($isbn)) {
            return null;
        }

        $timeout = max(1, (int) config('catalog.summaries.timeout', 8));

        try {
            $text = $this->fromGoogleBooks($isbn, $timeout);

            if ($text !== null) {
                return ['text' => $text, 'source' => 'Google Books'];
            }

            $text = $this->fromOpenLibrary($isbn, $timeout);

            if ($text !== null) {
                return ['text' => $text, 'source' => 'Open Library'];
            }
        } catch (ConnectionException $exception) {
            Log::info('Abfrage einer Zusammenfassung fehlgeschlagen.', ['reason' => $exception->getMessage()]);
        }

        return null;
    }

    private function fromGoogleBooks(string $isbn, int $timeout): ?string
    {
        $key = config('catalog.covers.google_books.key');

        $query = [
            'q' => 'isbn:'.$isbn,
            'maxResults' => 1,
            'fields' => 'items(volumeInfo/description)',
        ];

        if (is_string($key) && trim($key) !== '') {
            $query['key'] = trim($key);
        }

        $response = Http::timeout($timeout)->get('https://www.googleapis.com/books/v1/volumes', $query);

        return $response->successful() ? $this->clean($response->json('items.0.volumeInfo.description')) : null;
    }

    private function fromOpenLibrary(string $isbn, int $timeout): ?string
    {
        if (! (bool) config('catalog.summaries.open_library', true)) {
            return null;
        }

        $edition = Http::timeout($timeout)->get('https://openlibrary.org/isbn/'.$isbn.'.json');

        if (! $edition->successful()) {
            return null;
        }

        $text = $this->description($edition->json('description'));

        if ($text === null) {
            $work = $edition->json('works.0.key');

            if (is_string($work) && str_starts_with($work, '/works/')) {
                $response = Http::timeout($timeout)->get('https://openlibrary.org'.$work.'.json');
                $text = $response->successful() ? $this->description($response->json('description')) : null;
            }
        }

        return $this->clean($text);
    }

    /** Open Library liefert Text entweder als Zeichenkette oder als {"value": "..."}. */
    private function description(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value['value'] ?? null;
        }

        return is_string($value) ? $value : null;
    }

    private function clean(mixed $text): ?string
    {
        if (! is_string($text)) {
            return null;
        }

        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace("/[ \t]+/", ' ', $text));

        if (mb_strlen($text) < 40) {
            return null;
        }

        return mb_strlen($text) > self::MAX_LENGTH ? rtrim(mb_substr($text, 0, self::MAX_LENGTH - 1)).'…' : $text;
    }
}
