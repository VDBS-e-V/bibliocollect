<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Exceptions\CatalogShelfInUse;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Copy;

final readonly class DeleteCatalogShelfAction
{
    public function __construct(private AuditRecorder $audit) {}

    /** @throws CatalogShelfInUse */
    public function execute(CatalogShelf $shelf): void
    {
        $copies = Copy::query()->where('shelf_location', $shelf->code)->count();

        if ($copies > 0) {
            throw CatalogShelfInUse::withCopies($shelf->code, $copies);
        }

        $this->audit->record('catalog.shelf.deleted', 'Regalbrett gelöscht.', $shelf);
        $shelf->delete();
    }
}
