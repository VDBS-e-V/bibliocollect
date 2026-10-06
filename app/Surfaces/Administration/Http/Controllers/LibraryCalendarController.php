<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Support\BusinessClock;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\School\Actions\CreateLibraryClosuresAction;
use App\Modules\School\Actions\UpdateLibraryOpeningHoursAction;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use App\Surfaces\Administration\Http\Requests\LibraryClosureStoreRequest;
use App\Surfaces\Administration\Http\Requests\LibraryOpeningHoursRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

final class LibraryCalendarController
{
    public function index(Request $request, BusinessClock $clock): Response
    {
        $showPast = $request->boolean('vergangene');
        $today = $clock->now()->toDateString();

        $closures = LibraryClosure::query()
            ->when(! $showPast, static fn ($query) => $query->whereDate('date', '>=', $today))
            ->orderBy('date')
            ->get();

        return response()
            ->view('pages.surfaces.administration.school.calendar', [
                'hours' => LibraryOpeningHour::query()->orderBy('opens_at')->get()->groupBy('day_of_week'),
                'closures' => $closures,
                'showPast' => $showPast,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function updateHours(LibraryOpeningHoursRequest $request, UpdateLibraryOpeningHoursAction $update): RedirectResponse
    {
        $update->execute($request->days());

        return redirect()
            ->route('administration.calendar.index')
            ->with('school_success', 'Die Öffnungszeiten wurden gespeichert.');
    }

    public function storeClosure(LibraryClosureStoreRequest $request, CreateLibraryClosuresAction $create): RedirectResponse
    {
        try {
            $created = $create->execute($request->from(), $request->to(), $request->reason());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('administration.calendar.index')
                ->withInput()
                ->withErrors(['from' => $exception->getMessage()]);
        }

        return redirect()
            ->route('administration.calendar.index')
            ->with('school_success', $created === 0
                ? 'Alle Tage waren bereits als Schließtage eingetragen.'
                : ($created === 1 ? 'Ein Schließtag wurde eingetragen.' : $created.' Schließtage wurden eingetragen.'));
    }

    public function destroyClosure(string $closureId, AuditRecorder $audit): RedirectResponse
    {
        $closure = LibraryClosure::query()->findOrFail($closureId);
        $closure->delete();

        $audit->record('school.closure.deleted', 'Schließtag '.$closure->date->format('d.m.Y').' entfernt.', null, ['date' => $closure->date->toDateString()]);

        return redirect()
            ->route('administration.calendar.index')
            ->with('school_success', 'Der Schließtag wurde entfernt.');
    }
}
