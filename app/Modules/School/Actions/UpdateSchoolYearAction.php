<?php

declare(strict_types=1);

namespace App\Modules\School\Actions;

use App\Modules\School\DTOs\SchoolYearData;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Support\Facades\DB;

final class UpdateSchoolYearAction
{
    public function execute(SchoolYear $schoolYear, SchoolYearData $data): SchoolYear
    {
        return DB::transaction(function () use ($schoolYear, $data): SchoolYear {
            $lockedYear = SchoolYear::query()
                ->whereKey($schoolYear->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedYear->forceFill([
                'name' => trim($data->name),
                'starts_on' => $data->startsOn,
                'ends_on' => $data->endsOn,
            ])->save();

            return $lockedYear->load('classes');
        });
    }
}
