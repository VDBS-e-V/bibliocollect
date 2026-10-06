<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Services\MetadataProposalService;
use Illuminate\Console\Command;

final class FetchMetadataProposalsCommand extends Command
{
    protected $signature = 'catalog:quality:propose
        {--limit=100 : Maximale Zahl der Fälle pro Lauf}
        {--delay=400 : Pause zwischen zwei DNB-Abfragen in Millisekunden}';

    protected $description = 'Holt Vorschläge für offene Qualitätsfälle vorab (DNB), damit sie gesammelt geprüft werden können. Schreibt nur in die Prüftabelle.';

    public function handle(MetadataProposalService $proposals): int
    {
        $limit = max(1, min((int) $this->option('limit'), 2000));
        $delay = max(0, (int) $this->option('delay'));

        $reviews = CatalogMetadataReview::query()
            ->where('status', MetadataReviewStatus::Open->value)
            ->where('severity', '>', 0)
            ->whereNull('proposal_state')
            ->orderByDesc('severity')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $counts = [MetadataProposalService::STATE_READY => 0, MetadataProposalService::STATE_NONE => 0, MetadataProposalService::STATE_UNAVAILABLE => 0];
        $unavailableInARow = 0;

        foreach ($reviews as $review) {
            $state = $proposals->propose($review)->proposal_state ?? MetadataProposalService::STATE_NONE;
            $counts[$state] = ($counts[$state] ?? 0) + 1;

            // Ist die DNB nicht erreichbar, bringt Weitermachen nichts: Der Fall bleibt ohne Vorschlag, der Lauf hört auf.
            $unavailableInARow = $state === MetadataProposalService::STATE_UNAVAILABLE ? $unavailableInARow + 1 : 0;

            if ($unavailableInARow >= 3) {
                $this->warn('Die DNB ist nicht erreichbar. Der Lauf wird abgebrochen.');

                break;
            }

            if ($delay > 0) {
                usleep($delay * 1000);
            }
        }

        $this->info($reviews->count().' Fälle bearbeitet: '.$counts[MetadataProposalService::STATE_READY].' mit Vorschlag, '.$counts[MetadataProposalService::STATE_NONE].' ohne Treffer, '.$counts[MetadataProposalService::STATE_UNAVAILABLE].' nicht erreichbar.');

        return self::SUCCESS;
    }
}
