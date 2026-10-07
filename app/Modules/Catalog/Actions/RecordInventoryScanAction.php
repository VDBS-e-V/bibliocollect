<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\InventoryCount;
use App\Modules\Catalog\Models\InventoryCountItem;

/** Verbucht, dass ein Buch an einem Regalbrett gefunden wurde. Wird es erneut gescannt, zählt der letzte Fundort. */
final class RecordInventoryScanAction
{
    /**
     * @return array{result: string, copy: Copy|null} Ergebnis: ok, misplaced, unplaced, inactive oder unknown
     */
    public function execute(InventoryCount $count, string $shelfCode, string $barcode): array
    {
        $barcode = trim($barcode);
        $copy = Copy::query()->with('edition.title')->where('barcode', $barcode)->first();

        InventoryCountItem::query()->updateOrCreate(
            ['inventory_count_id' => $count->getKey(), 'barcode' => $barcode],
            ['copy_id' => $copy?->getKey(), 'shelf_code' => $shelfCode, 'scanned_at' => now()],
        );

        if (! $copy instanceof Copy) {
            return ['result' => 'unknown', 'copy' => null];
        }

        if (in_array($copy->status, [CopyStatus::Lost, CopyStatus::Withdrawn], true)) {
            return ['result' => 'inactive', 'copy' => $copy];
        }

        $location = (string) $copy->shelf_location;

        return ['result' => $location === '' ? 'unplaced' : ($location === $shelfCode ? 'ok' : 'misplaced'), 'copy' => $copy];
    }
}
