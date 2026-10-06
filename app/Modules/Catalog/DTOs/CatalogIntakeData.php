<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use InvalidArgumentException;

/**
 * Vollständiger, bestätigter Erfassungsvorgang: entweder ein neues Medium (Titel, Ausgabe, Exemplar)
 * oder ein weiteres Exemplar zu einer bereits vorhandenen Ausgabe.
 */
final readonly class CatalogIntakeData
{
    private function __construct(
        public ?string $existingEditionId,
        public ?CatalogIntakeDetails $details,
        public ?CatalogIntakeProvenance $provenance,
        public CopyData $copy,
    ) {}

    public static function newMedium(
        CatalogIntakeDetails $details,
        ?CatalogIntakeProvenance $provenance,
        CopyData $copy,
    ): self {
        return new self(null, $details, $provenance, $copy);
    }

    public static function additionalCopy(string $editionId, CopyData $copy): self
    {
        if ($editionId === '') {
            throw new InvalidArgumentException('Für ein weiteres Exemplar wird eine Ausgabe benötigt.');
        }

        return new self($editionId, null, null, $copy);
    }
}
