<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTOs\HoldingSummary;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;

final class CatalogHoldingService
{
    public function summarizeEdition(Edition $edition): HoldingSummary
    {
        $edition->loadMissing('copies');

        return $this->summarizeCopies($edition->copies);
    }

    public function summarizeTitle(Title $title): HoldingSummary
    {
        $title->loadMissing('editions.copies');

        $copies = [];

        foreach ($title->editions as $edition) {
            foreach ($edition->copies as $copy) {
                $copies[] = $copy;
            }
        }

        return $this->summarizeCopies($copies);
    }

    /**
     * @param  iterable<Copy>  $copies
     */
    private function summarizeCopies(iterable $copies): HoldingSummary
    {
        $total = 0;
        $active = 0;
        $damaged = 0;
        $lost = 0;
        $withdrawn = 0;
        $shelfLocations = [];

        foreach ($copies as $copy) {
            $total++;

            match ($copy->status) {
                CopyStatus::Active => $active++,
                CopyStatus::Damaged => $damaged++,
                CopyStatus::Lost => $lost++,
                CopyStatus::Withdrawn => $withdrawn++,
            };

            if (
                $copy->status === CopyStatus::Active
                && is_string($copy->shelf_location)
                && trim($copy->shelf_location) !== ''
            ) {
                $shelfLocations[] = trim($copy->shelf_location);
            }
        }

        $shelfLocations = array_values(array_unique($shelfLocations));
        sort($shelfLocations, SORT_NATURAL | SORT_FLAG_CASE);

        return new HoldingSummary(
            totalCopies: $total,
            activeCopies: $active,
            damagedCopies: $damaged,
            lostCopies: $lost,
            withdrawnCopies: $withdrawn,
            shelfLocations: $shelfLocations,
        );
    }
}
