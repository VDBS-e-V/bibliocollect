<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Actions\CreatePatronAction;
use App\Modules\School\Queries\ListAssignableSchoolClassesQuery;
use App\Surfaces\Pos\Http\Requests\PatronStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class PatronCreateController
{
    public function create(ListAssignableSchoolClassesQuery $schoolClasses): Response
    {
        return response()
            ->view('pages.surfaces.pos.patrons.form', [
                'mode' => 'create',
                'patron' => null,
                'schoolClasses' => $schoolClasses->execute(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(PatronStoreRequest $request, CreatePatronAction $create): RedirectResponse
    {
        $patron = $create->execute($request->toData());

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', 'Das Ausleihkonto wurde angelegt.');
    }
}
