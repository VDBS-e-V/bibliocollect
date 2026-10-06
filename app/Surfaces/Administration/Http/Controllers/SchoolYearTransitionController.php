<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Models\User;
use App\Modules\Patrons\Actions\TransitionSchoolYearAction;
use App\Modules\Patrons\Exceptions\SchoolYearTransitionConflict;
use App\Modules\Patrons\Services\SchoolYearTransitionPlanner;
use App\Modules\School\Exceptions\SchoolYearActivationConflict;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class SchoolYearTransitionController
{
    public function show(Request $request, SchoolYearTransitionPlanner $planner): Response
    {
        $from = SchoolYear::query()->where('is_active', true)->first();
        // Ziel kann nur ein noch nicht aktives Schuljahr sein, das nach dem laufenden beginnt.
        $candidates = SchoolYear::query()
            ->where('is_active', false)
            ->when($from !== null, static fn ($query) => $query->where('starts_on', '>', $from->starts_on))
            ->orderBy('starts_on')
            ->get();

        $targetId = $request->query('target');
        $target = is_string($targetId) ? $candidates->firstWhere('id', $targetId) : $candidates->first();

        $plan = $from !== null && $target !== null ? $planner->plan($from, $target) : [];

        return response()
            ->view('pages.surfaces.administration.school.transition', [
                'from' => $from,
                'target' => $target,
                'candidates' => $candidates,
                'plan' => $plan,
                'targetClasses' => $target?->classes()->where('is_active', true)->orderBy('grade_level')->orderBy('name')->get() ?? collect(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function commit(Request $request, TransitionSchoolYearAction $transition): RedirectResponse
    {
        $data = $request->validate([
            'from_id' => ['required', 'string'],
            'target_id' => ['required', 'string'],
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'string', 'max:40'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Bitte bestätige den Schuljahreswechsel.']);

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $from = SchoolYear::query()->findOrFail($data['from_id']);
        $target = SchoolYear::query()->findOrFail($data['target_id']);

        try {
            $result = $transition->execute($from, $target, array_map('strval', $data['mapping']), $actor);
        } catch (SchoolYearTransitionConflict|SchoolYearActivationConflict $exception) {
            return redirect()
                ->route('administration.transition.show', ['target' => $target->getKey()])
                ->withInput()
                ->with('school_error', $exception->getMessage());
        }

        return redirect()
            ->route('administration.school.index')
            ->with('school_success', "Schuljahreswechsel abgeschlossen: {$result['promoted']} versetzt, {$result['departed']} ausgeschieden, {$result['kept']} unverändert. {$target->name} ist jetzt aktiv.");
    }
}
