<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\InventoryCount;
use App\Modules\Catalog\Services\InventoryReport;

/** Übernimmt die Fundorte der Inventur: Bücher an falschem Platz und Bücher ohne Standort bekommen das Regalbrett, an dem sie standen. */
final readonly class ApplyInventoryCorrectionsAction
{
    public function __construct(private InventoryReport $report, private ShelveCopyAction $shelve) {}

    public function execute(InventoryCount $count): int
    {
        $report = $this->report->build($count);
        $applied = 0;

        foreach ($report['misplaced']->concat($report['unplaced']) as $item) {
            if ($item->copy instanceof Copy) {
                $this->shelve->execute($item->copy, $item->shelf_code);
                $applied++;
            }
        }

        return $applied;
    }
}
