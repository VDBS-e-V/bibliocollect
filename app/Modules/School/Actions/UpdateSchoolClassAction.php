<?php

declare(strict_types=1);

namespace App\Modules\School\Actions;

use App\Modules\School\DTOs\SchoolClassData;
use App\Modules\School\Exceptions\SchoolClassStateConflict;
use App\Modules\School\Models\SchoolClass;
use Illuminate\Support\Facades\DB;

final class UpdateSchoolClassAction
{
    public function execute(SchoolClass $schoolClass, SchoolClassData $data): SchoolClass
    {
        return DB::transaction(function () use ($schoolClass, $data): SchoolClass {
            $lockedClass = SchoolClass::query()
                ->with('schoolYear')
                ->whereKey($schoolClass->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedClass->is_active
                && ! $data->isActive
                && $lockedClass->schoolYear?->is_active
                && ! SchoolClass::query()
                    ->where('school_year_id', $lockedClass->school_year_id)
                    ->where('id', '!=', $lockedClass->getKey())
                    ->where('is_active', true)
                    ->exists()
            ) {
                throw SchoolClassStateConflict::lastActiveClass();
            }

            $lockedClass->forceFill([
                'name' => trim($data->name),
                'grade_level' => $data->gradeLevel,
                'is_active' => $data->isActive,
            ])->save();

            return $lockedClass->load('schoolYear');
        });
    }
}
