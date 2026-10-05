<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\TitleContributionData;
use App\Modules\Catalog\Exceptions\DuplicateTitleContribution;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use Illuminate\Support\Facades\DB;

final class UpdateTitleContributionAction
{
    public function execute(
        Title $title,
        TitleContribution $contribution,
        TitleContributionData $data,
    ): TitleContribution {
        return DB::transaction(function () use ($title, $contribution, $data): TitleContribution {
            $lockedContribution = TitleContribution::query()
                ->whereKey($contribution->getKey())
                ->where('title_id', $title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->hasDuplicate($title, $lockedContribution, $data)) {
                throw DuplicateTitleContribution::for($data->displayName, $data->roleKey);
            }

            $contributor = Contributor::query()
                ->whereKey($lockedContribution->contributor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $contributor->forceFill([
                'display_name' => $data->displayName,
                'sort_name' => $data->sortName,
            ])->save();

            $lockedContribution->forceFill([
                'role_key' => $data->roleKey,
                'position' => $data->position,
            ])->save();

            return $lockedContribution;
        });
    }

    private function hasDuplicate(
        Title $title,
        TitleContribution $current,
        TitleContributionData $data,
    ): bool {
        return $title->contributions()
            ->where('id', '!=', $current->getKey())
            ->with('contributor')
            ->get()
            ->contains(
                static fn (TitleContribution $contribution): bool => mb_strtolower(trim($contribution->contributor->display_name)) === mb_strtolower($data->displayName)
                    && $contribution->role_key === $data->roleKey,
            );
    }
}
