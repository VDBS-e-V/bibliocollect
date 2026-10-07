<?php

declare(strict_types=1);

use App\Surfaces\Administration\Http\Controllers\AuditIndexController;
use App\Surfaces\Administration\Http\Controllers\CatalogShelfController;
use App\Surfaces\Administration\Http\Controllers\ContentPageAdminController;
use App\Surfaces\Administration\Http\Controllers\InventoryRenumberController;
use App\Surfaces\Administration\Http\Controllers\LibraryCalendarController;
use App\Surfaces\Administration\Http\Controllers\SchoolClassController;
use App\Surfaces\Administration\Http\Controllers\SchoolIndexController;
use App\Surfaces\Administration\Http\Controllers\SchoolYearController;
use App\Surfaces\Administration\Http\Controllers\SchoolYearTransitionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:surface.administration.access'])->group(function (): void {
    Route::view('/verwaltung', 'pages.surfaces.administration', ['preview' => false])
        ->name('administration.home');

    Route::middleware('permission:content.manage')->group(function (): void {
        Route::get('/verwaltung/seiten', [ContentPageAdminController::class, 'index'])->name('administration.pages.index');
        Route::get('/verwaltung/seiten/{slug}', [ContentPageAdminController::class, 'edit'])->name('administration.pages.edit');
        Route::patch('/verwaltung/seiten/{slug}', [ContentPageAdminController::class, 'update'])->name('administration.pages.update');
    });

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

// Regalbretter und Inventarnummern pflegen auch Mitarbeiter:innen, nicht aber die Schüler-AG. Sie brauchen dafür nicht den
// gesamten Verwaltungsbereich, nur das jeweilige Recht.
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::middleware('permission:shelves.manage')->group(function (): void {
        Route::get('/verwaltung/regalbretter', [CatalogShelfController::class, 'index'])->name('administration.shelves.index');
        Route::post('/verwaltung/regalbretter', [CatalogShelfController::class, 'store'])->name('administration.shelves.store');
        Route::patch('/verwaltung/regalbretter/{shelfId}', [CatalogShelfController::class, 'update'])->name('administration.shelves.update');
        Route::delete('/verwaltung/regalbretter/{shelfId}', [CatalogShelfController::class, 'destroy'])->name('administration.shelves.destroy');
    });

    Route::middleware('permission:inventory.renumber')->group(function (): void {
        Route::get('/verwaltung/inventarnummern', [InventoryRenumberController::class, 'index'])->name('administration.inventory.index');
        Route::post('/verwaltung/inventarnummern', [InventoryRenumberController::class, 'store'])->name('administration.inventory.store');
    });
});

if (app()->environment('local')) {
    Route::view('/_preview/administration', 'pages.surfaces.administration', ['preview' => true])
        ->name('preview.administration');
}
