<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;

/** Holt ein ausgesondertes Exemplar zurück in den Bestand (zum Beispiel nach einem Irrtum). */
final readonly class RestoreWithdrawnCopyAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(Copy $copy): bool
    {
        if ($copy->status !== CopyStatus::Withdrawn) {
            return false;
        }

        $copy->forceFill(['status' => CopyStatus::Active, 'depreciation_reason' => null, 'further_use' => null, 'depreciated_at' => null])->save();

        $this->audit->record('catalog.copy.restored', 'Ausgesondertes Exemplar zurückgeholt.', $copy);

        return true;
    }
}
