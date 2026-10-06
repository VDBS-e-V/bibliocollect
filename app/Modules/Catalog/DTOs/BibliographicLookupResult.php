<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class BibliographicLookupResult
{
    /** @param list<BibliographicRecord> $records */
    public function __construct(
        public array $records,
        public bool $available = true,
    ) {}

    public static function unavailable(): self
    {
        return new self([], false);
    }
}
