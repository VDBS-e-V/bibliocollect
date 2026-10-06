<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Quality\MetadataFingerprint;
use Illuminate\Support\Facades\DB;

/**
 * Hält fest, dass ein Fall bewusst keinen Handlungsbedarf hat. Der Entscheid gilt für den aktuellen Stand der
 * Ausgabe; ändert sie sich später, öffnet der nächste Scan den Fall wieder.
 */
final readonly class DismissMetadataReviewAction
{
    public function __construct(private MetadataFingerprint $fingerprint) {}

    public function execute(CatalogMetadataReview $review, int $userId): CatalogMetadataReview
    {
        return DB::transaction(function () use ($review, $userId): CatalogMetadataReview {
            /** @var CatalogMetadataReview $locked */
            $locked = CatalogMetadataReview::query()->whereKey($review->getKey())->lockForUpdate()->firstOrFail();
            $edition = Edition::query()->with('title.contributions.contributor')->findOrFail($locked->edition_id);

            $locked->forceFill([
                'status' => MetadataReviewStatus::Dismissed,
                'fingerprint' => $this->fingerprint->for($edition),
                'proposal' => null,
                'proposal_source' => null,
                'proposal_state' => null,
                'proposal_fetched_at' => null,
                'decided_by_user_id' => $userId,
                'decided_at' => now(),
                'history' => [...($locked->history ?? []), [
                    'at' => now()->toIso8601String(),
                    'user_id' => $userId,
                    'action' => 'dismissed',
                    'changes' => [],
                ]],
            ])->save();

            return $locked;
        });
    }
}
