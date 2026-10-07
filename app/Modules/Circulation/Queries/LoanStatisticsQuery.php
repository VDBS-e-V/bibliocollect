<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Queries;

use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Kennzahlen zur Ausleihe für einen Zeitraum. Enthält nur Zählwerte, keine Personen. Anonymisierte Ausleihen
 * (ohne Ausleihkonto) zählen mit, fehlen aber in den Auswertungen nach Klasse.
 */
final class LoanStatisticsQuery
{
    /**
     * @return array{
     *     loans: int, returns: int, renewals: int, active_patrons: int,
     *     open: int, overdue: int, waiting_reservations: int, ready_reservations: int,
     *     by_month: list<array{month: string, loans: int}>,
     *     popular: list<array{title: string, loans: int}>,
     *     by_class: list<array{class: string, loans: int}>,
     *     by_media_type: list<array{media_type: ?string, loans: int}>,
     *     stock: array{titles: int, editions: int, copies: array<string, int>, withdrawn_in_period: int}
     * }
     */
    public function execute(CarbonImmutable $from, CarbonImmutable $to, CarbonImmutable $today): array
    {
        $range = [$from->startOfDay(), $to->endOfDay()];

        $loans = DB::table('circulation_loans')->whereBetween('checked_out_at', $range);

        $byMonth = [];

        foreach ((clone $loans)->pluck('checked_out_at') as $at) {
            $month = CarbonImmutable::parse((string) $at)->format('Y-m');
            $byMonth[$month] = ($byMonth[$month] ?? 0) + 1;
        }

        ksort($byMonth);

        // Alle Monate des Zeitraums zeigen, auch ohne Ausleihen.
        $months = [];

        for ($cursor = $from->startOfMonth(); $cursor->lessThanOrEqualTo($to->startOfMonth()); $cursor = $cursor->addMonth()) {
            $months[] = ['month' => $cursor->format('Y-m'), 'loans' => $byMonth[$cursor->format('Y-m')] ?? 0];
        }

        $base = DB::table('circulation_loans')
            ->join('catalog_copies', 'catalog_copies.id', '=', 'circulation_loans.copy_id')
            ->join('catalog_editions', 'catalog_editions.id', '=', 'catalog_copies.edition_id')
            ->whereBetween('circulation_loans.checked_out_at', $range);

        $popular = (clone $base)
            ->join('catalog_titles', 'catalog_titles.id', '=', 'catalog_editions.title_id')
            ->groupBy('catalog_titles.id', 'catalog_titles.preferred_title')
            ->selectRaw('catalog_titles.preferred_title as label, count(*) as total')
            ->orderByDesc('total')
            ->orderBy('label')
            ->limit(10)
            ->get()
            ->map(static fn (object $row): array => ['title' => (string) $row->label, 'loans' => (int) $row->total])
            ->all();

        $byMediaType = (clone $base)
            ->groupBy('catalog_editions.media_type')
            ->selectRaw('catalog_editions.media_type as label, count(*) as total')
            ->orderByDesc('total')
            ->get()
            ->map(static fn (object $row): array => ['media_type' => $row->label !== null ? (string) $row->label : null, 'loans' => (int) $row->total])
            ->all();

        $byClass = DB::table('circulation_loans')
            ->join('patrons', 'patrons.id', '=', 'circulation_loans.patron_id')
            ->join('school_classes', 'school_classes.id', '=', 'patrons.school_class_id')
            ->whereBetween('circulation_loans.checked_out_at', $range)
            ->groupBy('school_classes.id', 'school_classes.name', 'school_classes.grade_level')
            ->selectRaw('school_classes.name as label, school_classes.grade_level as grade, count(*) as total')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(static fn (object $row): array => ['class' => (string) $row->label, 'loans' => (int) $row->total])
            ->all();

        $open = Loan::query()->whereNull('returned_at');

        return [
            'loans' => (clone $loans)->count(),
            'returns' => DB::table('circulation_loans')->whereBetween('returned_at', $range)->count(),
            'renewals' => (int) DB::table('circulation_loans')->whereBetween('checked_out_at', $range)->sum('renewal_count'),
            'active_patrons' => (int) (clone $loans)->whereNotNull('patron_id')->distinct()->count('patron_id'),
            'open' => (clone $open)->count(),
            'overdue' => (clone $open)->whereDate('due_on', '<', $today->toDateString())->count(),
            'waiting_reservations' => Reservation::query()->where('status', ReservationStatus::Waiting->value)->count(),
            'ready_reservations' => Reservation::query()->where('status', ReservationStatus::Ready->value)->count(),
            'by_month' => $months,
            'popular' => $popular,
            'by_class' => $byClass,
            'by_media_type' => $byMediaType,
            'stock' => [
                'titles' => DB::table('catalog_titles')->count(),
                'editions' => DB::table('catalog_editions')->count(),
                'withdrawn_in_period' => DB::table('catalog_copies')->where('status', 'withdrawn')->whereBetween('depreciated_at', [$from->toDateString(), $to->toDateString()])->count(),
                'copies' => DB::table('catalog_copies')->groupBy('status')->selectRaw('status, count(*) as total')->pluck('total', 'status')->map(static fn (mixed $count): int => (int) $count)->all(),
            ],
        ];
    }
}
