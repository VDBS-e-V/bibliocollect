<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\CatalogImportBatch;
use Illuminate\Database\Eloquent\Collection;

final class ListCatalogImportBatchesQuery
{
    /** @return Collection<int, CatalogImportBatch> */
    public function execute(int $limit = 20): Collection
    {
        return CatalogImportBatch::query()
            ->withCount('rows')
            ->latest('created_at')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }
}
