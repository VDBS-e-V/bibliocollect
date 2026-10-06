<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\MetadataIssue;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Quality\CatalogMetadataAssessor;
use App\Modules\Catalog\Quality\MetadataFingerprint;
use Illuminate\Database\Eloquent\Collection;

/**
 * Bewertet alle Ausgaben und führt die Prüfliste nach. Schreibt ausschließlich in `catalog_metadata_reviews`,
 * nie in die Katalogdaten selbst, und ist beliebig oft wiederholbar:
 *
 *  - neue Probleme legen einen offenen Fall an,
 *  - behobene Probleme schließen den Fall (resolved),
 *  - "kein Handlungsbedarf" bleibt bestehen, solange sich die Ausgabe nicht geändert hat.
 */
final readonly class ScanCatalogMetadataQualityAction
{
    private const CHUNK_SIZE = 200;

    public function __construct(
        private CatalogMetadataAssessor $assessor,
        private MetadataFingerprint $fingerprint,
    ) {}

    /** @return array{scanned: int, created: int, updated: int, reopened: int, resolved: int} */
    public function execute(): array
    {
        $summary = ['scanned' => 0, 'created' => 0, 'updated' => 0, 'reopened' => 0, 'resolved' => 0];

        Edition::query()
            ->with('title.contributions.contributor')
            ->chunkById(self::CHUNK_SIZE, function (Collection $editions) use (&$summary): void {
                $reviews = CatalogMetadataReview::query()
                    ->whereIn('edition_id', $editions->modelKeys())
                    ->get()
                    ->keyBy('edition_id');

                foreach ($editions as $edition) {
                    $summary['scanned']++;
                    $this->scanEdition($edition, $reviews->get($edition->getKey()), $summary);
                }
            });

        return $summary;
    }

    /** @param array{scanned: int, created: int, updated: int, reopened: int, resolved: int} $summary */
    private function scanEdition(Edition $edition, ?CatalogMetadataReview $review, array &$summary): void
    {
        $issues = $this->assessor->assessEdition($edition);
        $fingerprint = $this->fingerprint->for($edition);

        if ($issues === []) {
            if ($review !== null && $review->status !== MetadataReviewStatus::Resolved) {
                $review->forceFill([
                    'status' => MetadataReviewStatus::Resolved,
                    'issues' => [],
                    'severity' => 0,
                    'fingerprint' => $fingerprint,
                    'proposal' => null,
                    'proposal_source' => null,
                    'proposal_state' => null,
                    'proposal_fetched_at' => null,
                ])->save();
                $summary['resolved']++;
            }

            return;
        }

        $values = array_map(static fn (MetadataIssue $issue): string => $issue->value, $issues);
        $severity = $this->assessor->severity($issues);

        if ($review === null) {
            CatalogMetadataReview::query()->create([
                'edition_id' => $edition->getKey(),
                'status' => MetadataReviewStatus::Open,
                'issues' => $values,
                'severity' => $severity,
                'fingerprint' => $fingerprint,
            ]);
            $summary['created']++;

            return;
        }

        $changed = $review->fingerprint !== $fingerprint;
        $keepDismissed = $review->status === MetadataReviewStatus::Dismissed && ! $changed;
        $status = $keepDismissed ? MetadataReviewStatus::Dismissed : MetadataReviewStatus::Open;

        if ($review->status !== MetadataReviewStatus::Open && $status === MetadataReviewStatus::Open) {
            $summary['reopened']++;
        }

        $unchanged = ! $changed
            && $status === $review->status
            && $review->issues === $values
            && $review->severity === $severity;

        if ($unchanged) {
            return;
        }

        $attributes = [
            'status' => $status,
            'issues' => $values,
            'severity' => $severity,
            'fingerprint' => $fingerprint,
        ];

        // Ein Vorschlag gilt nur für den Stand, auf dem er berechnet wurde.
        if ($changed) {
            $attributes += [
                'proposal' => null,
                'proposal_source' => null,
                'proposal_state' => null,
                'proposal_fetched_at' => null,
            ];
        }

        $review->forceFill($attributes)->save();
        $summary['updated']++;
    }
}
