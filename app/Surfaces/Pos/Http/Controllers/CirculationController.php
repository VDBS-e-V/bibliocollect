<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\RenewLoanAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Circulation\Queries\FindOpenLoanQuery;
use App\Modules\Patrons\Queries\FindPatronQuery;
use App\Surfaces\Pos\Http\Requests\CirculationCheckoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CirculationController
{
    public function checkout(
        CirculationCheckoutRequest $request,
        string $patronId,
        FindPatronQuery $findPatron,
        CheckoutCopyAction $checkout,
    ): RedirectResponse {
        $patron = $findPatron->byId($patronId);
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        try {
            $loan = $checkout->execute($patron, $request->barcode(), $actor);
        } catch (CirculationRuleViolation $exception) {
            return redirect()
                ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
                ->withInput()
                ->withErrors(['barcode' => $exception->getMessage()]);
        }

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', 'Ausleihe erfasst. Fällig am '.$loan->due_on->format('d.m.Y').'.');
    }

    public function return(
        Request $request,
        string $patronId,
        string $loanId,
        FindPatronQuery $findPatron,
        FindOpenLoanQuery $findLoan,
        ReturnLoanAction $returnLoan,
    ): RedirectResponse {
        $patron = $findPatron->byId($patronId);
        $loan = $findLoan->forPatron($loanId, $patron);
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        try {
            $returnLoan->execute($loan, $actor);
        } catch (LoanStateConflict $exception) {
            return redirect()
                ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
                ->with('workspace_error', $exception->getMessage());
        }

        $hold = Reservation::query()
            ->where('ready_copy_id', $loan->copy_id)
            ->where('status', ReservationStatus::Ready->value)
            ->with('patron')
            ->first();

        $message = 'Rückgabe wurde erfasst.';

        if ($hold instanceof Reservation) {
            $message .= ' Das Exemplar bitte für '.$hold->patron->displayName()
                .' ('.$hold->patron->library_number.') zurücklegen, Abholung bis '
                .($hold->pickup_until?->format('d.m.Y') ?? '—').'.';
        }

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', $message);
    }

    public function renew(
        Request $request,
        string $patronId,
        string $loanId,
        FindPatronQuery $findPatron,
        FindOpenLoanQuery $findLoan,
        RenewLoanAction $renewLoan,
    ): RedirectResponse {
        $patron = $findPatron->byId($patronId);
        $loan = $findLoan->forPatron($loanId, $patron);
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        try {
            $renewed = $renewLoan->execute($loan, $actor);
        } catch (CirculationRuleViolation|LoanStateConflict $exception) {
            return redirect()
                ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
                ->with('workspace_error', $exception->getMessage());
        }

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', 'Ausleihe verlängert. Neu fällig am '.$renewed->due_on->format('d.m.Y').'.');
    }
}
