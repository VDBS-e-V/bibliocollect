<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Support;

use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\DTOs\CatalogIntakeDetails;
use App\Modules\Catalog\DTOs\CatalogIntakeProvenance;
use App\Modules\Catalog\Enums\CopyStatus;
use Illuminate\Contracts\Session\Session;

/**
 * Zwischenstand eines Erfassungsvorgangs. Er liegt ausschließlich in der Session der handelnden
 * Person; in den Katalog wird erst beim ausdrücklichen Speichern in Schritt 5 geschrieben.
 */
final class CatalogIntakeDraft
{
    public const MODE_NEW = 'new';

    public const MODE_EXISTING = 'existing';

    private const KEY = 'catalog_intake';

    public function __construct(private readonly Session $session) {}

    /** @param array{isbn: string|null, title: string|null, person: string|null} $query */
    public function start(string $barcode, array $query): void
    {
        $this->session->put(self::KEY, [
            'barcode' => $barcode,
            'query' => $query,
        ]);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    public function exists(): bool
    {
        return $this->barcode() !== null;
    }

    public function barcode(): ?string
    {
        $barcode = $this->all()['barcode'] ?? null;

        return is_string($barcode) && $barcode !== '' ? $barcode : null;
    }

    /** @return array{isbn: string|null, title: string|null, person: string|null} */
    public function query(): array
    {
        $query = $this->all()['query'] ?? [];
        $query = is_array($query) ? $query : [];

        return [
            'isbn' => is_string($query['isbn'] ?? null) ? $query['isbn'] : null,
            'title' => is_string($query['title'] ?? null) ? $query['title'] : null,
            'person' => is_string($query['person'] ?? null) ? $query['person'] : null,
        ];
    }

    /** @param list<BibliographicRecord> $hits */
    public function setLookup(array $hits, bool $available): void
    {
        $this->merge([
            'hits' => array_map(static fn (BibliographicRecord $hit): array => $hit->toArray(), $hits),
            'lookup_available' => $available,
        ]);
    }

    /** @return list<BibliographicRecord> */
    public function hits(): array
    {
        $hits = $this->all()['hits'] ?? [];
        $records = [];

        if (is_array($hits)) {
            foreach ($hits as $hit) {
                if (is_array($hit)) {
                    /** @var array<string, mixed> $hit */
                    $records[] = BibliographicRecord::fromArray($hit);
                }
            }
        }

        return $records;
    }

    public function lookupAvailable(): bool
    {
        return ($this->all()['lookup_available'] ?? true) !== false;
    }

    /** Neues Medium: Vorbefüllung (noch unbestätigt) und deren Herkunft festlegen. */
    public function chooseNew(CatalogIntakeDetails $prefill, ?CatalogIntakeProvenance $provenance): void
    {
        $this->merge([
            'mode' => self::MODE_NEW,
            'existing_edition_id' => null,
            'prefill' => $prefill->toArray(),
            'provenance' => $provenance?->toArray(),
            'details' => null,
            'copy' => null,
        ]);
    }

    public function chooseExistingEdition(string $editionId): void
    {
        $this->merge([
            'mode' => self::MODE_EXISTING,
            'existing_edition_id' => $editionId,
            'prefill' => null,
            'provenance' => null,
            'details' => null,
            'copy' => null,
        ]);
    }

    public function mode(): ?string
    {
        $mode = $this->all()['mode'] ?? null;

        return $mode === self::MODE_NEW || $mode === self::MODE_EXISTING ? $mode : null;
    }

    public function existingEditionId(): ?string
    {
        $id = $this->all()['existing_edition_id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function provenance(): ?CatalogIntakeProvenance
    {
        $provenance = $this->all()['provenance'] ?? null;

        if (! is_array($provenance)) {
            return null;
        }

        /** @var array<string, mixed> $provenance */
        return CatalogIntakeProvenance::fromArray($provenance);
    }

    public function prefill(): ?CatalogIntakeDetails
    {
        return $this->detailsFrom('prefill');
    }

    public function setDetails(CatalogIntakeDetails $details): void
    {
        $this->merge(['details' => $details->toArray()]);
    }

    public function details(): ?CatalogIntakeDetails
    {
        return $this->detailsFrom('details');
    }

    /** Bestätigte Daten, sonst die noch unbestätigte Vorbefüllung. */
    public function detailsOrPrefill(): ?CatalogIntakeDetails
    {
        return $this->details() ?? $this->prefill();
    }

    public function setCopy(?string $shelfLocation, CopyStatus $status): void
    {
        $this->merge(['copy' => ['shelf_location' => $shelfLocation, 'status' => $status->value]]);
    }

    /** @return array{shelf_location: string|null, status: CopyStatus}|null */
    public function copy(): ?array
    {
        $copy = $this->all()['copy'] ?? null;

        if (! is_array($copy)) {
            return null;
        }

        $status = is_string($copy['status'] ?? null) ? CopyStatus::tryFrom($copy['status']) : null;

        if ($status === null) {
            return null;
        }

        return [
            'shelf_location' => is_string($copy['shelf_location'] ?? null) ? $copy['shelf_location'] : null,
            'status' => $status,
        ];
    }

    /** Schritt 3 (Titel & Ausgabe) ist erledigt oder entfällt, weil nur ein Exemplar ergänzt wird. */
    public function detailsReady(): bool
    {
        return match ($this->mode()) {
            self::MODE_EXISTING => $this->existingEditionId() !== null,
            self::MODE_NEW => $this->details() !== null,
            default => false,
        };
    }

    private function detailsFrom(string $key): ?CatalogIntakeDetails
    {
        $details = $this->all()[$key] ?? null;

        if (! is_array($details)) {
            return null;
        }

        /** @var array<string, mixed> $details */
        return CatalogIntakeDetails::fromArray($details);
    }

    /** @param array<string, mixed> $values */
    private function merge(array $values): void
    {
        $this->session->put(self::KEY, [...$this->all(), ...$values]);
    }

    /** @return array<string, mixed> */
    private function all(): array
    {
        $draft = $this->session->get(self::KEY, []);

        /** @var array<string, mixed> */
        return is_array($draft) ? $draft : [];
    }
}
