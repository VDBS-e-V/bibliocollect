<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\TitleData;
use App\Modules\Catalog\Models\Title;

final class CreateTitleAction
{
    public function execute(TitleData $data): Title
    {
        return Title::query()->create([
            'preferred_title' => $data->preferredTitle,
            'subtitle' => $data->subtitle,
            'sort_title' => $data->sortTitle,
        ]);
    }
}
