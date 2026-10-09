<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Foundation\Support\BusinessClock;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use App\Surfaces\Public\Support\HomeShowcase;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;

/** Startseite: Suche, Öffnungszeiten und die wichtigsten Ausleihregeln, alles aus den Einstellungen der Verwaltung. */
final class PublicHomeController
{
    private const DAYS = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];

    public function __invoke(BusinessClock $clock, HomeShowcase $showcase): Response
    {
        // Die Startseite soll auch erscheinen, wenn die Datenbank gerade nicht erreichbar ist.
        try {
            $hours = LibraryOpeningHour::query()->where('is_open', true)->orderBy('day_of_week')->get()
                ->map(static fn (LibraryOpeningHour $hour): array => [
                    'day' => self::DAYS[$hour->day_of_week] ?? (string) $hour->day_of_week,
                    'time' => ($hour->opens_at !== null && $hour->closes_at !== null) ? substr($hour->opens_at, 0, 5).' bis '.substr($hour->closes_at, 0, 5).' Uhr' : 'geöffnet',
                ])->all();

            $closures = LibraryClosure::query()
                ->whereDate('date', '>=', $clock->now()->toDateString())
                ->whereDate('date', '<=', $clock->now()->addDays(60)->toDateString())
                ->orderBy('date')
                ->limit(12)
                ->get()
                ->map(static fn (LibraryClosure $closure): array => ['date' => $closure->date->format('d.m.Y'), 'reason' => $closure->reason])
                ->all();
        } catch (QueryException) {
            $hours = [];
            $closures = [];
        }

        try {
            $shelves = $showcase->build();
        } catch (QueryException) {
            $shelves = ['recommended' => [], 'new' => [], 'topics' => []];
        }

        return response()->view('pages.welcome', [
            'showcase' => $shelves,
            'hours' => $hours,
            'closures' => $closures,
            'loanDays' => (int) config('circulation.default_loan_period_days', 14),
            'maxLoans' => (int) config('circulation.max_open_loans.default', 5),
            'maxRenewals' => (int) config('circulation.max_renewals', 2),
            'reservations' => (int) config('circulation.max_open_reservations', 5) > 0,
        ]);
    }
}
