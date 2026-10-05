<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class CatalogImportNormalizationResult
{
    /**
     * @param  array<string, string|int|null>  $data
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    public function __construct(
        public array $data,
        public array $warnings,
        public array $errors,
    ) {}
}
