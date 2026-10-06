<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Lookup\Dnb;

use App\Modules\Catalog\Contracts\BibliographicLookupProvider;
use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;

final readonly class DnbLookupProvider implements BibliographicLookupProvider
{
    private const MAX_TERMS_PER_FIELD = 6;

    public function __construct(
        private DnbSruClient $client,
        private DnbMarcMapper $mapper,
        private CatalogIsbnNormalizer $isbns,
    ) {}

    /** @return list<BibliographicRecord> */
    public function findByIsbn(string $isbn): array
    {
        $compact = strtoupper(preg_replace('/[\s\p{Pd}]+/u', '', $isbn) ?? $isbn);

        if (preg_match('/^(?:\d{13}|\d{9}[\dX])$/', $compact) !== 1) {
            return [];
        }

        $records = $this->mapper->map($this->client->search('num='.$compact));

        // Die DNB-Suche nach Nummern ist großzügig (z. B. bei vertippter Prüfziffer). Ein Treffer mit anderer
        // ISBN gehört zu einem anderen Medium und würde sonst unbemerkt vorbefüllt.
        $wanted = $this->isbns->toIsbn13($compact);

        return array_values(array_filter(
            $records,
            fn (BibliographicRecord $record): bool => $record->isbn !== null && $this->isbns->toIsbn13($record->isbn) === $wanted,
        ));
    }

    /** @return list<BibliographicRecord> */
    public function findByRecordId(string $recordId): array
    {
        $compact = strtoupper(trim($recordId));

        // DNB-IDN: 8 bis 10 Ziffern, die Prüfstelle kann ein X sein (z. B. 04029630X).
        if (preg_match('/^\d{7,10}[\dX]?$/', $compact) !== 1) {
            return [];
        }

        return $this->mapper->map($this->client->search('idn='.$compact));
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
