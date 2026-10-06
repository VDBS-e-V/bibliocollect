<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;

final readonly class CatalogIntakeResult
{
    public function __construct(
        public Title $title,
        public Edition $edition,
        public Copy $copy,
        public bool $createdEdition,
    ) {}
}
