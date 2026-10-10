<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Exceptions\CatalogShelfInUse;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Copy;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCatalogShelfAction
{
    public function __construct(private AuditRecorder $audit) {}

    /**
     * Löscht das Regalbrett. Stehen noch Exemplare darauf, geht das nur ausdrücklich: Sie verlieren dann ihren Standort (das Medium
     * selbst und seine Inventarnummer bleiben) und erscheinen wieder zum Einsortieren.
     *
     * @return int Zahl der Exemplare, die ihren Standort verloren haben
     *
     * @throws CatalogShelfInUse
     */
    public function execute(CatalogShelf $shelf, bool $releaseCopies = false): int
    {
        return DB::transaction(function () use ($shelf, $releaseCopies): int {
            $copies = Copy::query()->where('shelf_location', $shelf->code)->count();

            if ($copies > 0 && ! $releaseCopies) {
                throw CatalogShelfInUse::withCopies($shelf->code, $copies);
            }

            if ($copies > 0) {
                Copy::query()->where('shelf_location', $shelf->code)->update(['shelf_location' => null]);
            }

            $this->audit->record('catalog.shelf.deleted', 'Regalbrett gelöscht.', $shelf, ['code' => $shelf->code, 'copies_released' => $copies]);
            $shelf->delete();

            return $copies;
        });
    }
}
