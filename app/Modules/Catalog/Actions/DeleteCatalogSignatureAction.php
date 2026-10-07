<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Exceptions\CatalogTaxonomyInUse;
use App\Modules\Catalog\Models\CatalogSignature;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCatalogSignatureAction
{
    public function __construct(private AuditRecorder $audit) {}

    /** @throws CatalogTaxonomyInUse */
    public function execute(CatalogSignature $signature): void
    {
        $copies = $signature->copies()->count();

        if ($copies > 0) {
            throw CatalogTaxonomyInUse::signature($signature->signature, $copies);
        }

        DB::transaction(function () use ($signature): void {
            $this->audit->record('catalog.signature.deleted', 'Signatur gelöscht.', $signature);
            $signature->topics()->detach();
            $signature->delete();
        });
    }
}
