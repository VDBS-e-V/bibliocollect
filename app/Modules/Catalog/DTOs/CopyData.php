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
        /** Neu erfasst und noch nicht im Regal: das Exemplar liegt auf dem Stapel „Einsortieren“. */
        public bool $awaitingShelving = false,
    ) {}
}
