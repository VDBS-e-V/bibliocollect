<?php

declare(strict_types=1);

namespace App\Surfaces\Portal\Http\Controllers;

use App\Models\User;
use App\Modules\Circulation\Queries\ListOpenLoansForPatronQuery;
use App\Modules\Circulation\Queries\ListOpenReservationsQuery;
use App\Modules\Circulation\Services\CirculationRuleEvaluator;
use App\Modules\Circulation\Services\ReservationBlockChecker;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PortalHomeController
{
    public function __invoke(
        Request $request,
        ListOpenLoansForPatronQuery $loans,
        ListOpenReservationsQuery $reservations,
        CirculationRuleEvaluator $rules,
        ReservationBlockChecker $blocks,
    ): Response {
        $user = $request->user();
        $patron = $user instanceof User && $user->patron_id !== null
            ? Patron::query()->find($user->patron_id)
            : null;

        $openLoans = $patron instanceof Patron ? $loans->execute($patron) : collect();
        $openReservations = $patron instanceof Patron ? $reservations->forPatron($patron) : collect();

        $renewalBlocks = [];
        $positions = [];

        if ($patron instanceof Patron) {
            foreach ($openLoans as $loan) {
                $renewalBlocks[(string) $loan->getKey()] = $rules->renewalViolations(
                    $loan,
                    $patron,
                    $loan->copy,
                    $blocks->blocksRenewal($loan, $loan->copy),
                );
            }

            foreach ($openReservations as $reservation) {
                $positions[(string) $reservation->getKey()] = $reservations->position($reservation);
            }
        }

        return response()
            ->view('pages.surfaces.portal', [
                'preview' => false,
                'patron' => $patron,
                'openLoans' => $openLoans,
                'openReservations' => $openReservations,
                'renewalBlocks' => $renewalBlocks,
                'reservationPositions' => $positions,
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
