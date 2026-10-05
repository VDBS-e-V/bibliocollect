<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CopyData;
use App\Modules\Catalog\Exceptions\DuplicateCopyBarcode;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use Illuminate\Support\Facades\DB;

final class CreateCopyAction
{
    public function execute(Edition $edition, CopyData $data): Copy
    {
        return DB::transaction(function () use ($edition, $data): Copy {
            $lockedEdition = Edition::query()
                ->whereKey($edition->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (Copy::query()->where('barcode', $data->barcode)->exists()) {
                throw DuplicateCopyBarcode::for($data->barcode);
            }

            /** @var Copy $copy */
            $copy = $lockedEdition->copies()->create([
                'barcode' => $data->barcode,
                'shelf_location' => $data->shelfLocation,
                'status' => $data->status,
            ]);

            return $copy;
        });
    }
}
