<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class CatalogImportSourceRecord
{
    /**
     * @param  array<string, string|null>  $values
     * @param  list<string>  $errors
     */
    public function __construct(
        public int $rowNumber,
        public array $values,
        public array $errors = [],
    ) {}
}
