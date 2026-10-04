<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\School\Queries\ListSchoolYearsQuery;
use App\Modules\School\Queries\SchoolYearTransitionReadinessQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class SchoolIndexController
{
    public function __invoke(
        Request $request,
        ListSchoolYearsQuery $schoolYears,
        SchoolYearTransitionReadinessQuery $transitionReadiness,
    ): Response {
        $targetYearId = $request->query('target');

        return response()
            ->view('pages.surfaces.administration.school.index', [
                'schoolYears' => $schoolYears->execute(),
                'transition' => $transitionReadiness->execute(is_string($targetYearId) ? $targetYearId : null),
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
