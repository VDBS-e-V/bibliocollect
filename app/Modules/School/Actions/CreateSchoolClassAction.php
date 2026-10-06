<?php

declare(strict_types=1);

namespace App\Modules\School\Actions;

use App\Modules\School\DTOs\SchoolClassData;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;

final class CreateSchoolClassAction
{
    public function execute(SchoolYear $schoolYear, SchoolClassData $data): SchoolClass
    {
        return SchoolClass::query()->create([
            'school_year_id' => $schoolYear->getKey(),
            'name' => trim($data->name),
            'grade_level' => $data->gradeLevel,
            'is_active' => $data->isActive,
            'homeroom_teacher' => $data->homeroomTeacher,
        ]);
    }
}
