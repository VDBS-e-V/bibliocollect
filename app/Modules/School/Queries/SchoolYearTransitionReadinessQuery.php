<?php

declare(strict_types=1);

namespace App\Modules\School\Queries;

use App\Modules\School\DTOs\SchoolYearTransitionReadiness;
use App\Modules\School\Models\SchoolYear;

final class SchoolYearTransitionReadinessQuery
{
    public function execute(?string $targetYearId = null): SchoolYearTransitionReadiness
    {
        $activeYear = SchoolYear::query()
            ->with('classes')
            ->where('is_active', true)
            ->first();

        $candidateYears = SchoolYear::query()
            ->with('classes')
            ->where('is_active', false)
            ->orderBy('starts_on')
            ->get();

        $targetYear = $targetYearId !== null
            ? $candidateYears->firstWhere('id', $targetYearId)
            : $candidateYears->first();

        $requiredGrades = [];

        if ($activeYear !== null) {
            foreach ($activeYear->classes as $schoolClass) {
                if ($schoolClass->is_active && $schoolClass->grade_level < 13) {
                    $requiredGrades[] = $schoolClass->grade_level + 1;
                }
            }
        }

        $requiredGrades = array_values(array_unique($requiredGrades));
        sort($requiredGrades);

        $targetGrades = [];
        $targetActiveClassCount = 0;

        if ($targetYear !== null) {
            foreach ($targetYear->classes as $schoolClass) {
                if (! $schoolClass->is_active) {
                    continue;
                }

                $targetActiveClassCount++;
                $targetGrades[] = $schoolClass->grade_level;
            }
        }

        $targetGrades = array_values(array_unique($targetGrades));
        sort($targetGrades);

        /** @var list<int> $missingGrades */
        $missingGrades = array_values(array_diff($requiredGrades, $targetGrades));

        return new SchoolYearTransitionReadiness(
            activeYear: $activeYear,
            candidateYears: $candidateYears,
            targetYear: $targetYear,
            targetActiveClassCount: $targetActiveClassCount,
            missingPromotedGradeLevels: $missingGrades,
        );
    }
}
