<?php

declare(strict_types=1);

namespace App\Modules\School\Actions;

use App\Modules\School\DTOs\SchoolYearData;
use App\Modules\School\Models\SchoolYear;

final class CreateSchoolYearAction
{
    public function execute(SchoolYearData $data): SchoolYear
    {
        return SchoolYear::query()->create([
            'name' => trim($data->name),
            'starts_on' => $data->startsOn,
            'ends_on' => $data->endsOn,
            'is_active' => false,
        ]);
    }
}
