<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Services\MetadataProposalService;
use App\Modules\Catalog\Services\QualityQueueSettings;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

final class FetchMetadataProposalsCommand extends Command
{
    protected $signature = 'catalog:quality:propose
        {--limit= : Maximale Zahl der Fälle pro Lauf (Standard: die Einstellung im Systemzustand)}
        {--delay=400 : Pause zwischen zwei DNB-Abfragen in Millisekunden}
        {--issues= : Problemarten (durch Komma getrennt), die zuerst abgearbeitet werden (Standard: die Auswahl im Systemzustand)}
        {--enrichment : Auch Fälle nur zur Anreicherung (Zusammenfassung, Schlagwörter) bearbeiten}';

    protected $description = 'Holt Vorschläge für offene Qualitätsfälle vorab (DNB), damit sie gesammelt geprüft werden können. Schreibt nur in die Prüftabelle.';

    public function handle(MetadataProposalService $proposals, QualityQueueSettings $settings): int
    {
        $saved = $settings->get();
        $limit = max(1, min((int) ($this->option('limit') ?: $saved['per_night']), 2000));
        $delay = max(0, (int) $this->option('delay'));
        $chosen = $this->option('issues') !== null && $this->option('issues') !== ''
            ? array_values(array_filter(array_map('trim', explode(',', (string) $this->option('issues')))))
            : $saved['issues'];
        $enrichment = (bool) $this->option('enrichment') || $saved['enrichment'];

        $open = static fn () => CatalogMetadataReview::query()
            ->where('status', MetadataReviewStatus::Open->value)
            ->whereNull('proposal_state')
            ->when(! $enrichment, static fn ($query) => $query->where('severity', '>', 0));

        // Erst die gewählten Problemarten (schwerste zuerst), dann der Rest, bis das Limit erreicht ist.
        $reviews = new Collection;

        if ($chosen !== []) {
            $reviews = $open()
                ->where(static function ($query) use ($chosen): void {
                    foreach ($chosen as $issue) {
                        $query->orWhereJsonContains('issues', $issue);
                    }
                })
                ->orderByDesc('severity')->orderBy('id')->limit($limit)->get();
        }

        if ($reviews->count() < $limit) {
            $rest = $open()
                ->when($reviews->isNotEmpty(), static fn ($query) => $query->whereNotIn('id', $reviews->modelKeys()))
                ->orderByDesc('severity')->orderBy('id')->limit($limit - $reviews->count())->get();
            $reviews = $reviews->concat($rest);
        }

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
