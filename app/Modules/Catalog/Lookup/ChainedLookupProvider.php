<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Lookup;

use App\Modules\Catalog\Contracts\BibliographicLookupProvider;
use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\Exceptions\BibliographicLookupUnavailable;
use App\Modules\Catalog\Lookup\Support\LanguageCodes;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;

/**
 * Fragt mehrere Quellen der Reihe nach, bis eine etwas findet. Für Bücher aus dem deutschen Sprachraum (ISBN-Gruppe 3) kommt die DNB
 * zuerst, für alle anderen zuerst Open Library und Google Books. Fehlt dem Treffer noch Verlag, Jahr, Sprache oder Verantwortliche,
 * werden diese Lücken (und nur sie) aus der nächsten Quelle zur selben ISBN gefüllt.
 *
 * Ist keine Quelle erreichbar und gibt es keinen Treffer, gilt die Abfrage als „nicht verfügbar“ (nicht als „nichts gefunden“).
 */
final readonly class ChainedLookupProvider implements BibliographicLookupProvider
{
    /** Wie viele weitere Quellen höchstens zum Füllen von Lücken gefragt werden. */
    private const MAX_GAP_SOURCES = 2;

    /**
     * @param  array<string, BibliographicLookupProvider>  $providers  Quellenname zu Anbieter (nur die eingeschalteten)
     * @param  list<string>  $germanOrder  Reihenfolge für ISBN-Gruppe 3
     * @param  list<string>  $foreignOrder  Reihenfolge für alle anderen
     */
    public function __construct(
        private array $providers,
        private CatalogIsbnNormalizer $isbns,
        private array $germanOrder = ['dnb', 'openlibrary', 'googlebooks'],
        private array $foreignOrder = ['openlibrary', 'googlebooks', 'dnb'],
    ) {}

    /** @return list<BibliographicRecord> */
    public function findByIsbn(string $isbn): array
    {
        $order = $this->orderFor($isbn);
        $unavailable = false;

        foreach ($order as $index => $name) {
            try {
                $records = $this->providers[$name]->findByIsbn($isbn);
            } catch (BibliographicLookupUnavailable) {
                $unavailable = true;

                continue;
            }

            if ($records === []) {
                continue;
            }

            return count($records) === 1 ? [$this->fillGaps($records[0], $isbn, array_slice($order, $index + 1))] : $records;
        }

        if ($unavailable) {
            throw BibliographicLookupUnavailable::because('keine der Quellen war erreichbar');
        }

        return [];
    }

    /** @return list<BibliographicRecord> */
    public function findByRecordId(string $recordId): array
    {
        if (! isset($this->providers['dnb'])) {
            return [];
        }

        return $this->providers['dnb']->findByRecordId($recordId);
    }

    /** @return list<BibliographicRecord> */
    public function search(?string $title, ?string $person): array
    {
        $unavailable = false;

        // Titelsuche: die DNB zuerst (beste Qualität), danach die übrigen in eingerichteter Reihenfolge.
        foreach ($this->searchOrder() as $name) {
            try {
                $records = $this->providers[$name]->search($title, $person);
            } catch (BibliographicLookupUnavailable) {
                $unavailable = true;

                continue;
            }

            if ($records !== []) {
                return $records;
            }
        }

        if ($unavailable) {
            throw BibliographicLookupUnavailable::because('keine der Quellen war erreichbar');
        }

        return [];
    }

    /** @return list<string> */
    private function searchOrder(): array
    {
        return array_values(array_filter($this->germanOrder, fn (string $name): bool => isset($this->providers[$name])));
    }

    /** @return list<string> eingeschaltete Quellen in der Reihenfolge, die zur ISBN passt */
    private function orderFor(string $isbn): array
    {
        $isbn13 = $this->isbns->toIsbn13($this->isbns->normalize($isbn));
        $german = $isbn13 !== null && LanguageCodes::isbnGroup($isbn13) === '3';
        $wanted = $german ? $this->germanOrder : $this->foreignOrder;

        return array_values(array_filter($wanted, fn (string $name): bool => isset($this->providers[$name])));
    }

    /**
     * @param  list<string>  $remaining  Quellen, die noch nicht gefragt wurden
     */
    private function fillGaps(BibliographicRecord $record, string $isbn, array $remaining): BibliographicRecord
    {
        $asked = 0;

        foreach ($remaining as $name) {
            if (! $this->hasGaps($record) || $asked >= self::MAX_GAP_SOURCES) {
                break;
            }

            $asked++;

            try {
                $others = $this->providers[$name]->findByIsbn($isbn);
            } catch (BibliographicLookupUnavailable) {
                continue;
            }

            if (count($others) === 1) {
                $record = $this->merge($record, $others[0]);
            }
        }

        return $record;
    }

    private function hasGaps(BibliographicRecord $record): bool
    {
        return $record->publisherName === null || $record->publicationYear === null || $record->languageCode === null || $record->contributors === [];
    }

    /** Füllt nur leere Felder des Treffers aus dem anderen Datensatz; die Quelle nennt beide. */
    private function merge(BibliographicRecord $primary, BibliographicRecord $other): BibliographicRecord
    {
        $data = $primary->toArray();
        $theirs = $other->toArray();
        $filled = false;

        foreach (['publisher_name', 'publication_place', 'publication_year', 'language_code', 'physical_extent', 'series_statement', 'subject_keywords', 'summary'] as $field) {
            if ($data[$field] === null && $theirs[$field] !== null) {
                $data[$field] = $theirs[$field];
                $filled = true;
            }
        }

        if ($data['contributors'] === [] && $theirs['contributors'] !== []) {
            $data['contributors'] = $theirs['contributors'];
            $filled = true;
        }

        if ($filled && ! str_contains($primary->source, $other->source)) {
            $data['source'] = $primary->source.'+'.$other->source;
        }

        return BibliographicRecord::fromArray($data);
    }
}
