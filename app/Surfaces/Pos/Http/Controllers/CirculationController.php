<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
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

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', 'Rückgabe wurde erfasst.');
    }
}
