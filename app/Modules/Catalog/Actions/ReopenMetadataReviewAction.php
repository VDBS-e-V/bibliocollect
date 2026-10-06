<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;

final readonly class ReopenMetadataReviewAction
{
    public function execute(CatalogMetadataReview $review, int $userId): CatalogMetadataReview
    {
        if ($review->status !== MetadataReviewStatus::Dismissed) {
            return $review;
        }

        $review->forceFill([
            'status' => MetadataReviewStatus::Open,
            'history' => [...($review->history ?? []), [
                'at' => now()->toIso8601String(),
                'user_id' => $userId,
                'action' => 'reopened',
                'changes' => [],
            ]],
        ])->save();

        return $review;
    }
}
