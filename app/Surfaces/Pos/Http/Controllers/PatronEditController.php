<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Actions\UpdatePatronAction;
use App\Modules\Patrons\Queries\FindPatronQuery;
use App\Modules\School\Queries\ListAssignableSchoolClassesQuery;
use App\Surfaces\Pos\Http\Requests\PatronUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class PatronEditController
{
    public function edit(
        string $patronId,
        FindPatronQuery $findPatron,
        ListAssignableSchoolClassesQuery $schoolClasses,
    ): Response {
        return response()
            ->view('pages.surfaces.pos.patrons.form', [
                'mode' => 'edit',
                'patron' => $findPatron->byId($patronId),
                'schoolClasses' => $schoolClasses->execute(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(
        PatronUpdateRequest $request,
        string $patronId,
        FindPatronQuery $findPatron,
        UpdatePatronAction $update,
    ): RedirectResponse {
        $patron = $update->execute($findPatron->byId($patronId), $request->toData());
        $patron->forceFill(['reminders_enabled' => $request->boolean('reminders_enabled', true)])->save();

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', 'Die Stammdaten wurden gespeichert.');
    }
}
