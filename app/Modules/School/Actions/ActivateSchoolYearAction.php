<?php

declare(strict_types=1);

namespace App\Modules\School\Actions;

use App\Modules\School\Exceptions\SchoolYearActivationConflict;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Support\Facades\DB;

final class ActivateSchoolYearAction
{
    public function execute(SchoolYear $schoolYear): SchoolYear
    {
        return DB::transaction(function () use ($schoolYear): SchoolYear {
            $targetYear = SchoolYear::query()
                ->whereKey($schoolYear->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $targetGrades = $this->activeGradeLevels((string) $targetYear->getKey());

            if ($targetGrades === []) {
                throw SchoolYearActivationConflict::withoutActiveClasses();
            }

            $currentYear = SchoolYear::query()
                ->where('is_active', true)
                ->where('id', '!=', $targetYear->getKey())
                ->lockForUpdate()
                ->first();

            if ($currentYear !== null) {
                $requiredGrades = [];

                foreach ($this->activeGradeLevels((string) $currentYear->getKey()) as $gradeLevel) {
                    if ($gradeLevel < 13) {
                        $requiredGrades[] = $gradeLevel + 1;
                    }
                }

                $requiredGrades = array_values(array_unique($requiredGrades));
                sort($requiredGrades);

                $missingGrades = array_values(array_diff($requiredGrades, $targetGrades));

                if ($missingGrades !== []) {
                    throw SchoolYearActivationConflict::missingPromotedGrades($missingGrades);
                }
            }

            SchoolYear::query()
                ->where('id', '!=', $targetYear->getKey())
                ->update(['is_active' => false]);

            $targetYear->forceFill(['is_active' => true])->save();

            return $targetYear->load('classes');
        });
    }

    /** @return list<int> */
    private function activeGradeLevels(string $schoolYearId): array
    {
        $grades = SchoolClass::query()
            ->where('school_year_id', $schoolYearId)
            ->where('is_active', true)
            ->pluck('grade_level')
            ->map(static fn (mixed $grade): int => (int) $grade)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $grades;
    }
}
