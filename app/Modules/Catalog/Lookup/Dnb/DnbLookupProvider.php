<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Lookup\Dnb;

use App\Modules\Catalog\Contracts\BibliographicLookupProvider;
use App\Modules\Catalog\DTOs\BibliographicRecord;

final readonly class DnbLookupProvider implements BibliographicLookupProvider
{
    private const MAX_TERMS_PER_FIELD = 6;

    public function __construct(
        private DnbSruClient $client,
        private DnbMarcMapper $mapper,
    ) {}

    /** @return list<BibliographicRecord> */
    public function findByIsbn(string $isbn): array
    {
        $compact = strtoupper(preg_replace('/[\s\p{Pd}]+/u', '', $isbn) ?? $isbn);

        if (preg_match('/^(?:\d{13}|\d{9}[\dX])$/', $compact) !== 1) {
            return [];
        }

        return $this->mapper->map($this->client->search('num='.$compact));
    }

    /** @return list<BibliographicRecord> */
    public function search(?string $title, ?string $person): array
    {
        $clauses = [
            ...$this->clauses('tit', $title),
            ...$this->clauses('per', $person),
        ];

        if ($clauses === []) {
            return [];
        }

        return $this->mapper->map($this->client->search(implode(' and ', $clauses)));
    }

    /**
     * Zerlegt Freitext in einzelne Wörter. Sonderzeichen fallen weg, damit Nutzereingaben
     * die CQL-Abfrage nicht verändern können.
     *
     * @return list<string>
     */
    private function clauses(string $index, ?string $text): array
    {
        if ($text === null) {
            return [];
        }

        $terms = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if ($terms === false) {
            return [];
        }

        return array_map(
            static fn (string $term): string => $index.'='.$term,
            array_slice($terms, 0, self::MAX_TERMS_PER_FIELD),
        );
    }
}
