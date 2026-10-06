<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Foundation\Support\BusinessClock;
use App\Modules\Circulation\Queries\ClassLoanReportQuery;
use App\Modules\School\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ClassLoanReportController
{
    public function __invoke(Request $request, ClassLoanReportQuery $report, BusinessClock $clock): Response
    {
        $mode = $request->query('modus') === 'alle' ? 'alle' : 'ueberfaellig';
        $classId = is_string($request->query('klasse')) ? $request->query('klasse') : '';

        return response()
            ->view('pages.surfaces.pos.reports.class-loans', [
                'groups' => $report->groups($mode === 'ueberfaellig', $classId),
                'mode' => $mode,
                'classId' => $classId,
                'classes' => SchoolClass::query()
                    ->where('is_active', true)
                    ->whereRelation('schoolYear', 'is_active', true)
                    ->orderBy('grade_level')
                    ->orderBy('name')
                    ->get(),
                'today' => $clock->now(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
