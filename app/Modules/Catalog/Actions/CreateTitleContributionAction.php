<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\TitleContributionData;
use App\Modules\Catalog\Exceptions\DuplicateTitleContribution;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use Illuminate\Support\Facades\DB;

final class CreateTitleContributionAction
{
    public function execute(Title $title, TitleContributionData $data): TitleContribution
    {
        return DB::transaction(function () use ($title, $data): TitleContribution {
            $lockedTitle = Title::query()
                ->whereKey($title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->hasDuplicate($lockedTitle, $data)) {
                throw DuplicateTitleContribution::for($data->displayName, $data->roleKey);
            }

            $contributor = Contributor::query()->create([
                'display_name' => $data->displayName,
                'sort_name' => $data->sortName,
            ]);

            /** @var TitleContribution $contribution */
            $contribution = $lockedTitle->contributions()->create([
                'contributor_id' => $contributor->getKey(),
                'role_key' => $data->roleKey,
                'position' => $data->position,
            ]);

            return $contribution;
        });
    }

    private function hasDuplicate(Title $title, TitleContributionData $data): bool
    {
        return $title->contributions()
            ->with('contributor')
            ->get()
            ->contains(
                static fn (TitleContribution $contribution): bool => mb_strtolower(trim($contribution->contributor->display_name)) === mb_strtolower($data->displayName)
                    && $contribution->role_key === $data->roleKey,
            );
    }
}
