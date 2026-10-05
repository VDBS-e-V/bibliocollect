<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Circulation\Queries\ListOpenLoansForPatronQuery;
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

        return response()
            ->view('pages.surfaces.pos.patrons.show', [
                'patron' => $patron,
                'onlineAccount' => $onlineAccount,
                'hasOnlineAccount' => $hasOnlineAccount,
                'studentAgRoles' => $studentAgRoles->all(),
                'openLoans' => $openLoans,
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
