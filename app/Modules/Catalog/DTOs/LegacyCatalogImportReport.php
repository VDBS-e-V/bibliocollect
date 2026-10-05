<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class LegacyCatalogImportReport
{
    /**
     * @param  array<string, int>  $summary
     * @param  list<string>  $warnings
     * @param  list<string>  $conflicts
     */
    public function __construct(
        public array $summary,
        public array $warnings = [],
        public array $conflicts = [],
    ) {}

    public function hasConflicts(): bool
    {
        return $this->conflicts !== [];
    }
}
