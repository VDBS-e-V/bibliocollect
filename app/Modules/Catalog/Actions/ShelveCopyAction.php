<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\Copy;

/** Vermerkt, dass ein Exemplar ins Regal einsortiert wurde: Mit dem Standort ist es nicht mehr auf dem Stapel. */
final readonly class ShelveCopyAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(Copy $copy, string $shelfCode): Copy
    {
        $previous = $copy->shelf_location;

        $copy->forceFill([
            'shelf_location' => $shelfCode,
            'shelved_at' => now(),
        ])->save();

        $this->audit->record('catalog.copy.shelved', 'Exemplar einsortiert.', $copy, ['shelf' => $shelfCode, 'previous' => $previous]);

        return $copy;
    }
}
