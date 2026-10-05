<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class CatalogSearchCriteria
{
    public function __construct(
        public ?string $term = null,
        public ?string $mediaType = null,
        public ?string $languageCode = null,
        public bool $activeCopiesOnly = false,
        public string $sort = 'title',
        public int $perPage = 12,
        public int $page = 1,
    ) {}
}
