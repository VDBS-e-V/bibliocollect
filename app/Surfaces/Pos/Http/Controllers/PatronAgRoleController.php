<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Identity\Actions\RevokeRoleAction;
use App\Modules\Identity\Queries\FindUserByPatronIdQuery;
use App\Modules\Identity\Services\StudentAgRoleRegistry;
use App\Modules\Patrons\Queries\FindPatronQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PatronAgRoleController
{
    public function store(
        Request $request,
        string $patronId,
        string $roleKey,
        FindPatronQuery $findPatron,
        FindUserByPatronIdQuery $findUser,
        StudentAgRoleRegistry $roles,
        AssignRoleAction $assignRole,
    ): RedirectResponse {
        if (! $roles->contains($roleKey)) {
            abort(404);
        }

        $patron = $findPatron->byId($patronId);
        if (! $patron->isActive()) {
            return $this->inactivePatron((string) $patron->getKey());
        }

        $target = $findUser->execute((string) $patron->getKey());
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        if ($target === null) {
            return $this->withoutOnlineAccount((string) $patron->getKey());
        }

        $assignRole->execute($target, $roleKey, $actor);

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', 'Die AG-Rolle wurde zugewiesen.');
    }

    public function destroy(
        Request $request,
        string $patronId,
        string $roleKey,
        FindPatronQuery $findPatron,
        FindUserByPatronIdQuery $findUser,
        StudentAgRoleRegistry $roles,
        RevokeRoleAction $revokeRole,
    ): RedirectResponse {
        if (! $roles->contains($roleKey)) {
            abort(404);
        }

        $patron = $findPatron->byId($patronId);

        if (! $patron->isActive()) {
            return $this->inactivePatron((string) $patron->getKey());
        }

        $target = $findUser->execute((string) $patron->getKey());

        if (! $request->user() instanceof User) {
            abort(401);
        }

        if ($target === null) {
            return $this->withoutOnlineAccount((string) $patron->getKey());
        }

        $revokeRole->execute($target, $roleKey);

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', 'Die AG-Rolle wurde entfernt.');
    }

    private function inactivePatron(string $patronId): RedirectResponse
    {
        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patronId])
            ->with('workspace_error', 'AG-Rollen können nur für aktive Ausleihkonten geändert werden.');
    }

    private function withoutOnlineAccount(string $patronId): RedirectResponse
    {
        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patronId])
            ->with('workspace_error', 'Dieses Ausleihkonto ist noch mit keinem Onlinekonto verknüpft.');
    }
}
