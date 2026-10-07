<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Foundation\Support\BusinessClock;
use App\Modules\Circulation\Queries\ClassLoanReportQuery;
use Illuminate\Http\Response;

/** Alle überfälligen Ausleihen auf einen Blick, für alle, die ausleihen dürfen (der Klassenlisten-Druck bleibt Mitarbeitenden vorbehalten). */
final class OverdueController
{
    public function __invoke(ClassLoanReportQuery $report, BusinessClock $clock): Response
    {
        $rows = [];

        foreach ($report->groups(true) as $group) {
            foreach ($group['rows'] as $row) {
                $rows[] = $row + ['class' => $group['class_id'] === null ? '' : $group['label']];
            }
        }

        usort($rows, static fn (array $a, array $b): int => $b['days_overdue'] <=> $a['days_overdue'] ?: strcasecmp($a['patron'], $b['patron']));

        return response()
            ->view('pages.surfaces.pos.overdue', ['rows' => $rows, 'today' => $clock->now()])
            ->header('Cache-Control', 'private, no-store');
    }
}
