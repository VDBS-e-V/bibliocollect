<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\CatalogImportBatch;

final class FindCatalogImportBatchQuery
{
    public function execute(string $batchId): CatalogImportBatch
    {
        return CatalogImportBatch::query()
            ->with('rows')
            ->findOrFail($batchId);
    }
}
