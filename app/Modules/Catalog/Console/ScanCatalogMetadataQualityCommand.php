<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Actions\ScanCatalogMetadataQualityAction;
use App\Modules\Catalog\Enums\MetadataIssue;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use Illuminate\Console\Command;

final class ScanCatalogMetadataQualityCommand extends Command
{
    protected $signature = 'catalog:quality:scan';

    protected $description = 'Bewertet die Metadaten aller Ausgaben und führt die Prüfliste nach. Schreibt nur in die Prüfliste, nie in den Katalog.';

    public function handle(ScanCatalogMetadataQualityAction $scan): int
    {
        $summary = $scan->execute();

        $this->info(sprintf(
            '%d Ausgaben geprüft: %d neu, %d aktualisiert, %d wieder geöffnet, %d erledigt.',
            $summary['scanned'],
            $summary['created'],
            $summary['updated'],
            $summary['reopened'],
            $summary['resolved'],
        ));

        $open = CatalogMetadataReview::query()->where('status', MetadataReviewStatus::Open->value);
        $this->line(sprintf(
            'Offen: %d mit Mangel, %d nur zur Anreicherung. Kein Handlungsbedarf: %d.',
            (clone $open)->where('severity', '>', 0)->count(),
            (clone $open)->where('severity', 0)->count(),
            CatalogMetadataReview::query()->where('status', MetadataReviewStatus::Dismissed->value)->count(),
        ));

        $rows = [];

        foreach (MetadataIssue::cases() as $issue) {
            $rows[] = [
                $issue->label(),
                CatalogMetadataReview::query()
                    ->where('status', MetadataReviewStatus::Open->value)
                    ->whereJsonContains('issues', $issue->value)
                    ->count(),
            ];
        }

        $this->table(['Problem (offene Fälle)', 'Anzahl'], $rows);

        return self::SUCCESS;
    }
}
