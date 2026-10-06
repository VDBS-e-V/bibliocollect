<?php

declare(strict_types=1);

use App\Surfaces\Administration\Http\Controllers\AuditIndexController;
use App\Surfaces\Administration\Http\Controllers\LibraryCalendarController;
use App\Surfaces\Administration\Http\Controllers\SchoolClassController;
use App\Surfaces\Administration\Http\Controllers\SchoolIndexController;
use App\Surfaces\Administration\Http\Controllers\SchoolYearController;
use App\Surfaces\Administration\Http\Controllers\SchoolYearTransitionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:surface.administration.access'])->group(function (): void {
    Route::view('/verwaltung', 'pages.surfaces.administration', ['preview' => false])
        ->name('administration.home');

    Route::get('/verwaltung/protokoll', AuditIndexController::class)
        ->middleware('permission:audit.view')
        ->name('administration.audit.index');

    Route::middleware('permission:school.manage')->group(function (): void {
        Route::get('/verwaltung/schule', SchoolIndexController::class)
            ->name('administration.school.index');

        Route::get('/verwaltung/oeffnungszeiten', [LibraryCalendarController::class, 'index'])
            ->name('administration.calendar.index');

        Route::put('/verwaltung/oeffnungszeiten', [LibraryCalendarController::class, 'updateHours'])
            ->name('administration.calendar.hours');

        Route::post('/verwaltung/schliesstage', [LibraryCalendarController::class, 'storeClosure'])
            ->name('administration.calendar.closures.store');

        Route::delete('/verwaltung/schliesstage/{closureId}', [LibraryCalendarController::class, 'destroyClosure'])
            ->name('administration.calendar.closures.destroy');

        Route::get('/verwaltung/schuljahreswechsel', [SchoolYearTransitionController::class, 'show'])
            ->name('administration.transition.show');

        Route::post('/verwaltung/schuljahreswechsel', [SchoolYearTransitionController::class, 'commit'])
            ->name('administration.transition.commit');

        Route::post('/verwaltung/schuljahre', [SchoolYearController::class, 'store'])
            ->name('administration.school-years.store');

        Route::patch('/verwaltung/schuljahre/{schoolYearId}', [SchoolYearController::class, 'update'])
            ->name('administration.school-years.update');

        Route::post('/verwaltung/schuljahre/{schoolYearId}/aktivieren', [SchoolYearController::class, 'activate'])
            ->name('administration.school-years.activate');

        Route::post('/verwaltung/schuljahre/{schoolYearId}/klassen', [SchoolClassController::class, 'store'])
            ->name('administration.school-classes.store');

        Route::patch('/verwaltung/klassen/{schoolClassId}', [SchoolClassController::class, 'update'])
            ->name('administration.school-classes.update');
    });
});

if (app()->environment('local')) {
    Route::view('/_preview/administration', 'pages.surfaces.administration', ['preview' => true])
        ->name('preview.administration');
}
