<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Foundation\Support\BusinessClock;
use App\Modules\Circulation\Queries\LoanStatisticsQuery;
use App\Modules\School\Models\SchoolYear;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Statistik zur Ausleihe: Kennzahlen, Verlauf je Monat, beliebteste Titel, Auswertung nach Klasse und Medientyp. */
final class StatisticsController
{
    public function show(Request $request, BusinessClock $clock, LoanStatisticsQuery $query): Response
    {
        [$from, $to, $selected] = $this->period($request, $clock);

        return response()
            ->view('pages.surfaces.pos.statistics', [
                'stats' => $query->execute($from, $to, $clock->now()->startOfDay()),
                'from' => $from,
                'to' => $to,
                'selected' => $selected,
                'schoolYears' => SchoolYear::query()->orderByDesc('starts_on')->get(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function download(Request $request, BusinessClock $clock, LoanStatisticsQuery $query): StreamedResponse
    {
        [$from, $to] = $this->period($request, $clock);
        $stats = $query->execute($from, $to, $clock->now()->startOfDay());

        return response()->streamDownload(static function () use ($stats, $from, $to): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Zeitraum', $from->format('d.m.Y').' bis '.$to->format('d.m.Y')], ';');
            fputcsv($out, [], ';');
            fputcsv($out, ['Kennzahl', 'Wert'], ';');

            foreach (['Ausleihen' => 'loans', 'Rückgaben' => 'returns', 'Verlängerungen' => 'renewals', 'Aktive Leser:innen' => 'active_patrons', 'Aktuell ausgeliehen' => 'open', 'Überfällig' => 'overdue'] as $label => $key) {
                fputcsv($out, [$label, $stats[$key]], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, ['Monat', 'Ausleihen'], ';');

            foreach ($stats['by_month'] as $row) {
                fputcsv($out, [$row['month'], $row['loans']], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, ['Beliebteste Titel', 'Ausleihen'], ';');

            foreach ($stats['popular'] as $row) {
                fputcsv($out, [$row['title'], $row['loans']], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, ['Klasse (aktuell)', 'Ausleihen'], ';');

            foreach ($stats['by_class'] as $row) {
                fputcsv($out, [$row['class'], $row['loans']], ';');
            }

            fclose($out);
        }, 'statistik-'.$from->format('Y-m-d').'-'.$to->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} */
    private function period(Request $request, BusinessClock $clock): array
    {
        $today = $clock->now()->startOfDay();
        $choice = (string) $request->query('zeitraum', '');

        if ($choice === 'letzte12') {
            return [$today->subYear()->addDay(), $today, 'letzte12'];
        }

        if ($choice === 'alle') {
            $first = DB::table('circulation_loans')->min('checked_out_at');

            return [$first !== null ? CarbonImmutable::parse((string) $first)->startOfDay() : $today->startOfMonth(), $today, 'alle'];
        }

        $year = $choice !== '' && $choice !== 'aktiv'
            ? SchoolYear::query()->find($choice)
            : SchoolYear::query()->where('is_active', true)->first();

        if ($year instanceof SchoolYear) {
            $start = CarbonImmutable::parse($year->starts_on->toDateString());
            $end = CarbonImmutable::parse($year->ends_on->toDateString());

            return [$start, $end->greaterThan($today) ? $today : $end, (string) $year->getKey()];
        }

        return [$today->subYear()->addDay(), $today, 'letzte12'];
    }
}
