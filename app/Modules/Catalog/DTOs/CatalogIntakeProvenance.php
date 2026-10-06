<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

/**
 * Herkunft der vorbefüllten Metadaten. Wird nur gesetzt, wenn die Daten aus einer externen
 * Quelle stammen und von Mitarbeitenden bestätigt wurden.
 */
final readonly class CatalogIntakeProvenance
{
    public function __construct(
        public string $source,
        public ?string $recordId,
        public ?string $permalink,
    ) {}

    public static function fromRecord(BibliographicRecord $record): self
    {
        return new self($record->source, $record->sourceRecordId, $record->sourcePermalink);
    }

    /** @return array{source: string, record_id: string|null, permalink: string|null} */
    public function toArray(): array
    {
        return ['source' => $this->source, 'record_id' => $this->recordId, 'permalink' => $this->permalink];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): ?self
    {
        if (! is_string($data['source'] ?? null) || $data['source'] === '') {
            return null;
        }

        return new self(
            $data['source'],
            is_string($data['record_id'] ?? null) ? $data['record_id'] : null,
            is_string($data['permalink'] ?? null) ? $data['permalink'] : null,
        );
    }
}
