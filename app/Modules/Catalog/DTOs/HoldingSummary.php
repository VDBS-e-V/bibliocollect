<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class HoldingSummary
{
    /**
     * @param  list<string>  $shelfLocations
     */
    public function __construct(
        public int $totalCopies,
        public int $activeCopies,
        public int $damagedCopies,
        public int $lostCopies,
        public int $withdrawnCopies,
        public array $shelfLocations,
    ) {}

    public function hasCopies(): bool
    {
        return $this->totalCopies > 0;
    }

    public function hasActiveCopies(): bool
    {
        return $this->activeCopies > 0;
    }
}
