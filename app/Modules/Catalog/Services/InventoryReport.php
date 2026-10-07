<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\InventoryCount;
use App\Modules\Catalog\Models\InventoryCountItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Gleicht eine Inventur mit dem Bestand ab. Nur Regalbretter, an denen gescannt wurde, gelten als geprüft; dort fehlt, was
 * laut System dort steht (und nicht ausgeliehen ist), aber nicht gescannt wurde.
 */
final class InventoryReport
{
    /**
     * @return array{
     *     shelves: list<string>, scanned: int, ok: Collection<int, InventoryCountItem>, misplaced: Collection<int, InventoryCountItem>,
     *     unplaced: Collection<int, InventoryCountItem>, inactive: Collection<int, InventoryCountItem>, unknown: Collection<int, InventoryCountItem>,
     *     missing: Collection<int, Copy>, onLoan: int
     * }
     */
    public function build(InventoryCount $count): array
    {
        $items = $count->items()->with('copy.edition.title')->orderBy('scanned_at')->get();
        $shelves = $items->pluck('shelf_code')->unique()->sort()->values()->all();

        $ok = collect();
        $misplaced = collect();
        $unplaced = collect();
        $inactive = collect();
        $unknown = collect();

        foreach ($items as $item) {
            $copy = $item->copy;

            if (! $copy instanceof Copy) {
                $unknown->push($item);

                continue;
            }

            if (in_array($copy->status, [CopyStatus::Lost, CopyStatus::Withdrawn], true)) {
                $inactive->push($item);

                continue;
            }

            $location = (string) $copy->shelf_location;

            if ($location === '') {
                $unplaced->push($item);
            } elseif ($location === $item->shelf_code) {
                $ok->push($item);
            } else {
                $misplaced->push($item);
            }
        }

        $scannedBarcodes = $items->pluck('barcode')->all();

        $expected = Copy::query()
            ->with('edition.title')
            ->whereIn('shelf_location', $shelves)
            ->whereIn('status', [CopyStatus::Active->value, CopyStatus::Damaged->value])
            ->orderBy('shelf_location')
            ->orderBy('barcode')
            ->get();

        $loaned = $expected->isEmpty() ? [] : DB::table('circulation_loans')->whereIn('copy_id', $expected->modelKeys())->whereNull('returned_at')->pluck('copy_id')->all();

        $missing = $expected->reject(static fn (Copy $copy): bool => in_array($copy->barcode, $scannedBarcodes, true) || in_array((string) $copy->getKey(), array_map('strval', $loaned), true))->values();

        return [
            'shelves' => $shelves,
            'scanned' => $items->count(),
            'ok' => $ok,
            'misplaced' => $misplaced,
            'unplaced' => $unplaced,
            'inactive' => $inactive,
            'unknown' => $unknown,
            'missing' => $missing,
            'onLoan' => count($loaned),
        ];
    }
}
