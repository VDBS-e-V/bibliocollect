<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Circulation\Actions\CancelReservationAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Patrons\Queries\FindPatronQuery;
use App\Surfaces\Pos\Http\Requests\ReservationStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ReservationController
{
    public function store(
        ReservationStoreRequest $request,
        string $patronId,
        FindPatronQuery $findPatron,
        PlaceReservationAction $place,
    ): RedirectResponse {
        $patron = $findPatron->byId($patronId);
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        try {
            $reservation = $place->execute($patron, $request->identifier(), $actor);
        } catch (CirculationRuleViolation $exception) {
            return redirect()
                ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
                ->withInput()
                ->withErrors(['reservation' => $exception->getMessage()]);
        }

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', '„'.$reservation->title->preferred_title.'“ wurde vorgemerkt.');
    }

    public function cancel(
        Request $request,
        string $patronId,
        string $reservationId,
        FindPatronQuery $findPatron,
        CancelReservationAction $cancel,
    ): RedirectResponse {
        $patron = $findPatron->byId($patronId);
        $reservation = Reservation::query()
            ->where('patron_id', $patron->getKey())
            ->findOrFail($reservationId);
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        try {
            $cancel->execute($reservation, $actor);
        } catch (LoanStateConflict $exception) {
            return redirect()
                ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
                ->with('workspace_error', $exception->getMessage());
        }

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', 'Vormerkung wurde storniert.');
    }
}
