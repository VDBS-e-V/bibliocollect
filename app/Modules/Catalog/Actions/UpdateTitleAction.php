<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\TitleData;
use App\Modules\Catalog\Models\Title;
use Illuminate\Support\Facades\DB;

final class UpdateTitleAction
{
    public function execute(Title $title, TitleData $data): Title
    {
        return DB::transaction(function () use ($title, $data): Title {
            $lockedTitle = Title::query()
                ->whereKey($title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedTitle->forceFill([
                'preferred_title' => $data->preferredTitle,
                'subtitle' => $data->subtitle,
                'sort_title' => $data->sortTitle,
            ])->save();

            return $lockedTitle;
        });
    }
}
