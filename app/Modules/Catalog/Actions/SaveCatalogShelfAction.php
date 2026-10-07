<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Copy;
use Illuminate\Support\Facades\DB;

/** Legt ein Regalbrett an oder ändert es. Wird der Code geändert, ziehen die Exemplare mit. */
final readonly class SaveCatalogShelfAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(?CatalogShelf $shelf, string $code, ?string $label, int $sortOrder, bool $active, ?string $signatureId = null): CatalogShelf
    {
        return DB::transaction(function () use ($shelf, $code, $label, $sortOrder, $active, $signatureId): CatalogShelf {
            $code = trim($code);
            $label = $label !== null && trim($label) !== '' ? trim($label) : null;

            if ($shelf === null) {
                $shelf = CatalogShelf::query()->create(['code' => $code, 'label' => $label, 'signature_id' => $signatureId, 'sort_order' => $sortOrder, 'is_active' => $active]);
                $this->audit->record('catalog.shelf.created', 'Regalbrett angelegt.', $shelf);

                return $shelf;
            }

            $oldCode = $shelf->code;

            $shelf->forceFill(['code' => $code, 'label' => $label, 'signature_id' => $signatureId, 'sort_order' => $sortOrder, 'is_active' => $active])->save();

            if ($oldCode !== $code) {
                Copy::query()->where('shelf_location', $oldCode)->update(['shelf_location' => $code]);
            }

            $this->audit->record('catalog.shelf.updated', 'Regalbrett geändert.', $shelf, ['renamed' => $oldCode !== $code]);

            return $shelf;
        });
    }
}
