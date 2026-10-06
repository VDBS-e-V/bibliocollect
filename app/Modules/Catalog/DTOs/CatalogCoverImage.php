<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class CatalogCoverImage
{
    public function __construct(
        public string $contents,
        public string $mimeType,
        public string $source,
        public ?string $sourceReference = null,
    ) {}
}
