<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\School\Actions\ActivateSchoolYearAction;
use App\Modules\School\Actions\CreateSchoolYearAction;
use App\Modules\School\Actions\UpdateSchoolYearAction;
use App\Modules\School\Exceptions\SchoolYearActivationConflict;
use App\Modules\School\Models\SchoolYear;
use App\Surfaces\Administration\Http\Requests\SchoolYearStoreRequest;
use App\Surfaces\Administration\Http\Requests\SchoolYearUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SchoolYearController
{
    public function store(SchoolYearStoreRequest $request, CreateSchoolYearAction $create): RedirectResponse
    {
        $schoolYear = $create->execute($request->toData());

        return redirect()
            ->route('administration.school.index', ['target' => $schoolYear->getKey()])
            ->with('school_success', 'Das Schuljahr wurde als Entwurf angelegt.');
    }

    public function update(
        SchoolYearUpdateRequest $request,
        string $schoolYearId,
        UpdateSchoolYearAction $update,
    ): RedirectResponse {
        $schoolYear = SchoolYear::query()->findOrFail($schoolYearId);
        $update->execute($schoolYear, $request->toData());

        return redirect()
            ->route('administration.school.index', ['target' => $schoolYear->getKey()])
            ->with('school_success', 'Das Schuljahr wurde gespeichert.');
    }

    public function activate(Request $request, string $schoolYearId, ActivateSchoolYearAction $activate): RedirectResponse
    {
        $request->validate([
            'confirm_activation' => ['accepted'],
        ], [
            'confirm_activation.accepted' => 'Bitte bestätige das Aktivieren des Zieljahres ausdrücklich.',
        ]);

        $schoolYear = SchoolYear::query()->findOrFail($schoolYearId);

        try {
            $activate->execute($schoolYear);
        } catch (SchoolYearActivationConflict $exception) {
            return redirect()
                ->route('administration.school.index', ['target' => $schoolYear->getKey()])
                ->with('school_error', $exception->getMessage());
        }

        return redirect()
            ->route('administration.school.index')
            ->with('school_success', 'Das Schuljahr ist jetzt aktiv. Bestehende Klassenzuordnungen wurden nicht automatisch verändert.');
    }
}
