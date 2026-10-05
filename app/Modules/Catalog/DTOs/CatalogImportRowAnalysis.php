<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Modules\Catalog\Models\CatalogImportRow;

final class CatalogImportRowAnalysis
{
    /**
     * @param  array<string, string|int|null>  $data
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     * @param  list<string>  $conflicts
     * @param  array<string, mixed>|null  $plan
     */
    public function __construct(
        public readonly CatalogImportRow $row,
        public readonly array $data,
        public array $warnings,
        public readonly array $errors,
        public array $conflicts = [],
        public ?array $plan = null,
    ) {}
}
