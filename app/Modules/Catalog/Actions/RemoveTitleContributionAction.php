<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use Illuminate\Support\Facades\DB;

final class RemoveTitleContributionAction
{
    public function execute(Title $title, TitleContribution $contribution): void
    {
        DB::transaction(function () use ($title, $contribution): void {
            $lockedContribution = TitleContribution::query()
                ->whereKey($contribution->getKey())
                ->where('title_id', $title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $contributor = Contributor::query()
                ->whereKey($lockedContribution->contributor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedContribution->delete();

            if (! $contributor->contributions()->exists()) {
                $contributor->delete();
            }
        });
    }
}
