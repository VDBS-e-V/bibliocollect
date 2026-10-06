<?php

declare(strict_types=1);

namespace App\Surfaces\Portal\Http\Controllers;

use App\Models\User;
use App\Modules\Circulation\Actions\CancelReservationAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Actions\RenewLoanAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Selbstbedienung: Jede Aktion wirkt ausschließlich auf das mit dem Onlinekonto verknüpfte Ausleihkonto. */
final class PortalCirculationController
{
    public function renew(Request $request, string $loanId, RenewLoanAction $renew): RedirectResponse
    {
        [$user, $patron] = $this->identity($request);

        $loan = Loan::query()
            ->where('patron_id', $patron->getKey())
            ->whereNull('returned_at')
            ->findOrFail($loanId);

        try {
            $renewed = $renew->execute($loan, $user);
        } catch (CirculationRuleViolation|LoanStateConflict $exception) {
            return redirect()->route('portal.home')->with('portal_error', $exception->getMessage());
        }

        return redirect()->route('portal.home')
            ->with('portal_success', 'Die Ausleihe wurde verlängert. Neu fällig am '.$renewed->due_on->format('d.m.Y').'.');
    }

    public function reserve(Request $request, PlaceReservationAction $place): RedirectResponse
    {
        [$user, $patron] = $this->identity($request);

        $titleId = $request->validate(['title_id' => ['required', 'string', 'max:40']])['title_id'];

        try {
            $reservation = $place->executeForTitle($patron, $titleId, $user);
        } catch (CirculationRuleViolation $exception) {
            return redirect()->back()->with('portal_error', $exception->getMessage());
        }

        return redirect()->route('portal.home')
            ->with('portal_success', '„'.$reservation->title->preferred_title.'“ wurde für dich vorgemerkt.');
    }

    public function cancel(Request $request, string $reservationId, CancelReservationAction $cancel): RedirectResponse
    {
        [$user, $patron] = $this->identity($request);

        $reservation = Reservation::query()
            ->where('patron_id', $patron->getKey())
            ->findOrFail($reservationId);

        try {
            $cancel->execute($reservation, $user);
        } catch (LoanStateConflict $exception) {
            return redirect()->route('portal.home')->with('portal_error', $exception->getMessage());
        }

        return redirect()->route('portal.home')->with('portal_success', 'Die Vormerkung wurde storniert.');
    }

    /** @return array{0: User, 1: Patron} */
    private function identity(Request $request): array
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->patron_id !== null, 403);

        $patron = Patron::query()->find($user->patron_id);

        abort_unless($patron instanceof Patron, 403);

        return [$user, $patron];
    }
}
