<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Patrons\Actions\DepartPatronAction;
use App\Modules\Patrons\Exceptions\PatronStatusStateConflict;
use App\Modules\Patrons\Queries\FindPatronQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PatronDepartureController
{
    public function store(
        Request $request,
        string $patronId,
        FindPatronQuery $findPatron,
        DepartPatronAction $depart,
        BusinessClock $clock,
    ): RedirectResponse {
        $data = $request->validate([
            'leaving_on' => ['required', 'date', 'before_or_equal:'.$clock->now()->toDateString()],
            'confirm_departure' => ['accepted'],
        ], [
            'confirm_departure.accepted' => 'Bitte bestätige den dauerhaften Austritt ausdrücklich.',
        ]);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        try {
            $patron = $depart->execute(
                patron: $findPatron->byId($patronId),
                effectiveOn: (string) $data['leaving_on'],
                actor: $actor,
            );
        } catch (PatronStatusStateConflict $exception) {
            return redirect()
                ->route('pos.patrons.show', ['patronId' => $patronId])
                ->with('workspace_error', $exception->getMessage());
        }

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', 'Das Ausleihkonto wurde als ausgeschieden markiert. Offene Aktivierungscodes wurden widerrufen.');
    }
}
