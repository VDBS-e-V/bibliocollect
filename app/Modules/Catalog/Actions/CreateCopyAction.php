<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CopyData;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Events\CopyBecameAvailable;
use App\Modules\Catalog\Exceptions\DuplicateCopyBarcode;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use Illuminate\Support\Facades\DB;

final class CreateCopyAction
{
    public function execute(Edition $edition, CopyData $data): Copy
    {
        $copy = DB::transaction(function () use ($edition, $data): Copy {
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
                'access_status' => $data->access->value,
            ]);

            return $copy;
        });

        if ($copy->status === CopyStatus::Active) {
            event(new CopyBecameAvailable($copy));
        }

        return $copy;
    }
}
