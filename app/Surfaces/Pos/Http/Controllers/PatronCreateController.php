<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Actions\AssignPatronCardAction;
use App\Modules\Patrons\Actions\CreatePatronAction;
use App\Modules\Patrons\Exceptions\PatronCardConflict;
use App\Modules\School\Queries\ListAssignableSchoolClassesQuery;
use App\Surfaces\Pos\Http\Requests\PatronStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class PatronCreateController
{
    public function create(Request $request, ListAssignableSchoolClassesQuery $schoolClasses): Response
    {
        return response()
            ->view('pages.surfaces.pos.patrons.form', [
                'mode' => 'create',
                'patron' => null,
                'schoolClasses' => $schoolClasses->execute(),
                'pendingCard' => (string) $request->session()->get('pos.pending_card', ''),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Die Person steht vor uns: Konto und Ausweis werden zusammen angelegt, scheitert eines, entsteht nichts. */
    public function store(PatronStoreRequest $request, CreatePatronAction $create, AssignPatronCardAction $assignCard): RedirectResponse
    {
        try {
            $patron = DB::transaction(function () use ($request, $create, $assignCard) {
                $patron = $create->execute($request->toData());
                $patron->forceFill(['reminders_enabled' => $request->boolean('reminders_enabled', true)])->save();
                $assignCard->execute($request->cardNumber(), $patron);

                return $patron;
            });
        } catch (PatronCardConflict $exception) {
            return back()->withInput()->withErrors(['card_number' => $exception->getMessage()]);
        }

        $request->session()->forget('pos.pending_card');

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patron->getKey()])
            ->with('workspace_success', 'Das Ausleihkonto wurde angelegt und der Ausweis ist zugeordnet.');
    }
}
