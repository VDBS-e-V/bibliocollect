<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CopyData;
use App\Modules\Catalog\Exceptions\DuplicateCopyBarcode;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use Illuminate\Support\Facades\DB;

final class UpdateCopyAction
{
    public function execute(Edition $edition, Copy $copy, CopyData $data): Copy
    {
        return DB::transaction(function () use ($edition, $copy, $data): Copy {
            $lockedCopy = Copy::query()
                ->whereKey($copy->getKey())
                ->where('edition_id', $edition->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $barcodeExists = Copy::query()
                ->where('barcode', $data->barcode)
                ->where('id', '!=', $lockedCopy->getKey())
                ->exists();

            if ($barcodeExists) {
                throw DuplicateCopyBarcode::for($data->barcode);
            }

            $lockedCopy->forceFill([
                'barcode' => $data->barcode,
                'shelf_location' => $data->shelfLocation,
                'status' => $data->status,
                'access_status' => $data->access->value,
            ])->save();

            return $lockedCopy;
        });
    }
}
