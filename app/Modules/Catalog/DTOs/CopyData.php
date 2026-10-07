<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Modules\Catalog\Enums\CopyStatus;

final readonly class CopyData
{
    public function __construct(
        public string $barcode,
        public ?string $shelfLocation,
        public CopyStatus $status,
    ) {}
}
