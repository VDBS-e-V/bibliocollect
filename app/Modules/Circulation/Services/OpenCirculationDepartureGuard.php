<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Patrons\Contracts\PatronDepartureGuard;
use App\Modules\Patrons\Models\Patron;

/** Ein Ausleihkonto mit offenen Ausleihen oder Vormerkungen darf nicht ausscheiden. */
final class OpenCirculationDepartureGuard implements PatronDepartureGuard
{
    public function blockReasons(Patron $patron): array
    {
        $reasons = [];

        $loans = Loan::query()->where('patron_id', $patron->getKey())->whereNull('returned_at')->count();

        if ($loans > 0) {
            $reasons[] = $loans === 1 ? 'Es ist noch 1 Medium ausgeliehen.' : "Es sind noch {$loans} Medien ausgeliehen.";
        }

        $reservations = Reservation::query()
            ->where('patron_id', $patron->getKey())
            ->whereIn('status', ReservationStatus::openValues())
            ->count();

        if ($reservations > 0) {
            $reasons[] = $reservations === 1 ? 'Es gibt noch 1 offene Vormerkung.' : "Es gibt noch {$reservations} offene Vormerkungen.";
        }

        return $reasons;
    }
}
