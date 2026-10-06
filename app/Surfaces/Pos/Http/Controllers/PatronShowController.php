<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Circulation\Queries\ListOpenLoansForPatronQuery;
use App\Modules\Circulation\Queries\ListOpenReservationsQuery;
use App\Modules\Circulation\Services\CirculationRuleEvaluator;
use App\Modules\Circulation\Services\LoanPolicy;
use App\Modules\Circulation\Services\ReservationBlockChecker;
use App\Modules\Identity\Queries\FindUserByPatronIdQuery;
use App\Modules\Identity\Queries\HasUserByPatronIdQuery;
use App\Modules\Identity\Services\StudentAgRoleRegistry;
use App\Modules\Patrons\Queries\FindPatronQuery;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class PatronShowController
{
    public function __invoke(
        string $patronId,
        FindPatronQuery $findPatron,
        FindUserByPatronIdQuery $findUser,
        HasUserByPatronIdQuery $hasUser,
        StudentAgRoleRegistry $studentAgRoles,
        ListOpenLoansForPatronQuery $listOpenLoans,
        CirculationRuleEvaluator $rules,
        ReservationBlockChecker $reservations,
        ListOpenReservationsQuery $listReservations,
        LoanPolicy $policy,
    ): Response {
        $patron = $findPatron->byId($patronId);
        $mayInspectOnlineAccount = Gate::allows('patrons.sensitive.view')
            || Gate::allows('identity.roles.assign');

        $onlineAccount = $mayInspectOnlineAccount
            ? $findUser->execute((string) $patron->getKey())
            : null;

        $hasOnlineAccount = $onlineAccount !== null
            || $hasUser->execute((string) $patron->getKey());

        $openLoans = Gate::allows('circulation.manage')
            ? $listOpenLoans->execute($patron)
            : collect();

        $openReservations = Gate::allows('circulation.manage')
            ? $listReservations->forPatron($patron)
            : collect();

        /** @var array<string, int> $reservationPositions */
        $reservationPositions = [];

        foreach ($openReservations as $reservation) {
            $reservationPositions[(string) $reservation->getKey()] = $listReservations->position($reservation);
        }

        /** @var array<string, list<string>> $renewalBlocks Gründe, warum eine offene Ausleihe nicht verlängert werden kann */
        $renewalBlocks = [];

        foreach ($openLoans as $loan) {
            $renewalBlocks[(string) $loan->getKey()] = $rules->renewalViolations(
                $loan,
                $patron,
                $loan->copy,
                $reservations->blocksRenewal($loan, $loan->copy),
            );
        }

        return response()
            ->view('pages.surfaces.pos.patrons.show', [
                'patron' => $patron,
                'onlineAccount' => $onlineAccount,
                'hasOnlineAccount' => $hasOnlineAccount,
                'studentAgRoles' => $studentAgRoles->all(),
                'openLoans' => $openLoans,
                'maxOpenLoans' => $policy->maxOpenLoans($patron),
                'renewalBlocks' => $renewalBlocks,
                'openReservations' => $openReservations,
                'reservationPositions' => $reservationPositions,
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
