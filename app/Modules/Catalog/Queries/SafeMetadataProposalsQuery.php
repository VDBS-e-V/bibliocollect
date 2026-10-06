<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\DTOs\MetadataChange;
use App\Modules\Catalog\DTOs\MetadataProposal;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Services\MetadataProposalService;

/**
 * Findet offene Fälle, deren Vorschlag so eindeutig ist, dass man ihn gesammelt bestätigen kann.
 *
 * Sicher heißt: Der Vorschlag liegt vor, trägt keine Warnung und alle vorausgewählten Änderungen sind Ergänzungen
 * leerer Felder, belegte Korrekturen beschädigter Werte oder lokale Bereinigungen. Namen von Personen (Ergänzen,
 * Umbenennen) und Abweichungen bleiben immer der Einzelprüfung vorbehalten.
 */
final class SafeMetadataProposalsQuery
{
    private const SAFE_KINDS = [MetadataChange::FILL, MetadataChange::FIX, MetadataChange::LOCAL];

    /** @return list<array{review: CatalogMetadataReview, proposal: MetadataProposal, keys: list<string>, changes: list<MetadataChange>}> */
    public function execute(?int $limit = null): array
    {
        $found = [];

        $reviews = CatalogMetadataReview::query()
            ->where('status', MetadataReviewStatus::Open->value)
            ->where('severity', '>', 0)
            ->where('proposal_state', MetadataProposalService::STATE_READY)
            ->whereNotNull('proposal')
            ->with('edition.title')
            ->orderByDesc('severity')
            ->orderBy('id')
            ->cursor();

        foreach ($reviews as $review) {
            $proposal = MetadataProposal::fromArray($review->proposal ?? []);

            if ($proposal->warnings !== []) {
                continue;
            }

            $selected = array_values(array_filter($proposal->changes, static fn (MetadataChange $change): bool => $change->selected));

            if ($selected === []) {
                continue;
            }

            foreach ($selected as $change) {
                if (! in_array($change->kind, self::SAFE_KINDS, true)) {
                    continue 2;
                }
            }

            $found[] = [
                'review' => $review,
                'proposal' => $proposal,
                'keys' => array_map(static fn (MetadataChange $change): string => $change->key, $selected),
                'changes' => $selected,
            ];

            if ($limit !== null && count($found) >= $limit) {
                break;
            }
        }

        return $found;
    }

    /** @return array{open: int, fetched: int, safe: int} */
    public function counts(): array
    {
        $base = CatalogMetadataReview::query()->where('status', MetadataReviewStatus::Open->value)->where('severity', '>', 0);

        return [
            'open' => (clone $base)->count(),
            'fetched' => (clone $base)->whereNotNull('proposal_state')->count(),
            'safe' => count($this->execute()),
        ];
    }
}
