<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\School\Actions\CreateSchoolClassAction;
use App\Modules\School\Actions\UpdateSchoolClassAction;
use App\Modules\School\Exceptions\SchoolClassStateConflict;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use App\Surfaces\Administration\Http\Requests\SchoolClassStoreRequest;
use App\Surfaces\Administration\Http\Requests\SchoolClassUpdateRequest;
use Illuminate\Http\RedirectResponse;

final class SchoolClassController
{
    public function store(
        SchoolClassStoreRequest $request,
        string $schoolYearId,
        CreateSchoolClassAction $create,
    ): RedirectResponse {
        $schoolYear = SchoolYear::query()->findOrFail($schoolYearId);
        $create->execute($schoolYear, $request->toData());

        return redirect()
            ->route('administration.school.index', ['target' => $schoolYear->getKey()])
            ->with('school_success', 'Die Klasse wurde angelegt.');
    }

    public function update(
        SchoolClassUpdateRequest $request,
        string $schoolClassId,
        UpdateSchoolClassAction $update,
    ): RedirectResponse {
        $schoolClass = SchoolClass::query()->findOrFail($schoolClassId);

        try {
            $updatedClass = $update->execute($schoolClass, $request->toData());
        } catch (SchoolClassStateConflict $exception) {
            return redirect()
                ->route('administration.school.index', ['target' => $schoolClass->school_year_id])
                ->with('school_error', $exception->getMessage());
        }

        return redirect()
            ->route('administration.school.index', ['target' => $updatedClass->school_year_id])
            ->with('school_success', 'Die Klasse wurde gespeichert.');
    }
}
