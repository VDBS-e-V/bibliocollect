<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Queries;

use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Database\Eloquent\Collection;

final class ListOpenReservationsQuery
{
    /** @return Collection<int, Reservation> */
    public function forPatron(Patron $patron): Collection
    {
        return Reservation::query()
            ->where('patron_id', $patron->getKey())
            ->whereIn('status', ReservationStatus::openValues())
            ->with(['title', 'readyCopy'])
            ->orderBy('requested_at')
            ->get();
    }

    /** @return Collection<int, Reservation> Zurückgelegte Exemplare, die abgeholt werden sollen */
    public function ready(): Collection
    {
        return Reservation::query()
            ->where('status', ReservationStatus::Ready->value)
            ->with(['patron', 'title', 'readyCopy'])
            ->orderBy('pickup_until')
            ->orderBy('ready_at')
            ->get();
    }

    /** @return Collection<int, Reservation> Wartende Vormerkungen in Warteschlangenreihenfolge */
    public function waiting(): Collection
    {
        return Reservation::query()
            ->where('status', ReservationStatus::Waiting->value)
            ->with(['patron', 'title'])
            ->orderBy('title_id')
            ->orderBy('requested_at')
            ->get();
    }

    /** Position in der Warteschlange des Titels (1 = nächste Person). */
    public function position(Reservation $reservation): int
    {
        return Reservation::query()
            ->where('title_id', $reservation->title_id)
            ->where('status', ReservationStatus::Waiting->value)
            ->where(static function ($query) use ($reservation): void {
                $query->where('requested_at', '<', $reservation->requested_at)
                    ->orWhere(static function ($same) use ($reservation): void {
                        $same->where('requested_at', $reservation->requested_at)
                            ->where('id', '<=', $reservation->getKey());
                    });
            })
            ->count();
    }
}
