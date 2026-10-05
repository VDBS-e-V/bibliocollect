<?php

declare(strict_types=1);

use App\Surfaces\Pos\Http\Controllers\CatalogContributionController;
use App\Surfaces\Pos\Http\Controllers\CatalogCopyController;
use App\Surfaces\Pos\Http\Controllers\CatalogEditionController;
use App\Surfaces\Pos\Http\Controllers\CatalogImportController;
use App\Surfaces\Pos\Http\Controllers\CatalogIndexController;
use App\Surfaces\Pos\Http\Controllers\CatalogTitleController;
use App\Surfaces\Pos\Http\Controllers\IssuePatronLinkCodeController;
use App\Surfaces\Pos\Http\Controllers\PatronAgRoleController;
use App\Surfaces\Pos\Http\Controllers\PatronBlockController;
use App\Surfaces\Pos\Http\Controllers\PatronCreateController;
use App\Surfaces\Pos\Http\Controllers\PatronDepartureController;
use App\Surfaces\Pos\Http\Controllers\PatronEditController;
use App\Surfaces\Pos\Http\Controllers\PatronIndexController;
use App\Surfaces\Pos\Http\Controllers\PatronShowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:surface.pos.access'])->group(function (): void {
    Route::view('/betrieb', 'pages.surfaces.pos', ['preview' => false])
        ->name('pos.home');

    Route::middleware('permission:catalog.import')->group(function (): void {
        Route::get('/betrieb/katalog/import', [CatalogImportController::class, 'create'])
            ->name('pos.catalog.import.create');

        Route::post('/betrieb/katalog/import', [CatalogImportController::class, 'store'])
            ->name('pos.catalog.import.store');

        Route::get('/betrieb/katalog/import/{batchId}', [CatalogImportController::class, 'show'])
            ->name('pos.catalog.import.show');

        Route::post('/betrieb/katalog/import/{batchId}/vorschau', [CatalogImportController::class, 'preview'])
            ->name('pos.catalog.import.preview');

        Route::post('/betrieb/katalog/import/{batchId}/uebernehmen', [CatalogImportController::class, 'commit'])
            ->name('pos.catalog.import.commit');
    });

    Route::middleware('permission:catalog.manage')->group(function (): void {
        Route::get('/betrieb/katalog', CatalogIndexController::class)
            ->name('pos.catalog.index');

        Route::post('/betrieb/katalog/titel', [CatalogTitleController::class, 'store'])
            ->name('pos.catalog.titles.store');

        Route::get('/betrieb/katalog/titel/{titleId}', [CatalogTitleController::class, 'show'])
            ->name('pos.catalog.titles.show');

        Route::patch('/betrieb/katalog/titel/{titleId}', [CatalogTitleController::class, 'update'])
            ->name('pos.catalog.titles.update');

        Route::post('/betrieb/katalog/titel/{titleId}/verantwortliche', [CatalogContributionController::class, 'store'])
            ->name('pos.catalog.contributions.store');

        Route::get('/betrieb/katalog/titel/{titleId}/verantwortliche/{contributionId}/bearbeiten', [CatalogContributionController::class, 'edit'])
            ->name('pos.catalog.contributions.edit');

        Route::patch('/betrieb/katalog/titel/{titleId}/verantwortliche/{contributionId}', [CatalogContributionController::class, 'update'])
            ->name('pos.catalog.contributions.update');

        Route::delete('/betrieb/katalog/titel/{titleId}/verantwortliche/{contributionId}', [CatalogContributionController::class, 'destroy'])
            ->name('pos.catalog.contributions.destroy');

        Route::post('/betrieb/katalog/titel/{titleId}/ausgaben', [CatalogEditionController::class, 'store'])
            ->name('pos.catalog.editions.store');

        Route::get('/betrieb/katalog/ausgaben/{editionId}/bearbeiten', [CatalogEditionController::class, 'edit'])
            ->name('pos.catalog.editions.edit');

        Route::patch('/betrieb/katalog/ausgaben/{editionId}', [CatalogEditionController::class, 'update'])
            ->name('pos.catalog.editions.update');

        Route::post('/betrieb/katalog/ausgaben/{editionId}/exemplare', [CatalogCopyController::class, 'store'])
            ->name('pos.catalog.copies.store');

        Route::get('/betrieb/katalog/ausgaben/{editionId}/exemplare/{copyId}/bearbeiten', [CatalogCopyController::class, 'edit'])
            ->name('pos.catalog.copies.edit');

        Route::patch('/betrieb/katalog/ausgaben/{editionId}/exemplare/{copyId}', [CatalogCopyController::class, 'update'])
            ->name('pos.catalog.copies.update');
    });

    Route::get('/betrieb/ausleihkonten', PatronIndexController::class)
        ->middleware('permission:patrons.lookup')
        ->name('pos.patrons.index');

    Route::get('/betrieb/ausleihkonten/neu', [PatronCreateController::class, 'create'])
        ->middleware('permission:patrons.manage')
        ->name('pos.patrons.create');

    Route::post('/betrieb/ausleihkonten', [PatronCreateController::class, 'store'])
        ->middleware('permission:patrons.manage')
        ->name('pos.patrons.store');

    Route::get('/betrieb/ausleihkonten/{patronId}', PatronShowController::class)
        ->middleware('permission:patrons.lookup')
        ->name('pos.patrons.show');

    Route::get('/betrieb/ausleihkonten/{patronId}/bearbeiten', [PatronEditController::class, 'edit'])
        ->middleware('permission:patrons.manage')
        ->name('pos.patrons.edit');

    Route::patch('/betrieb/ausleihkonten/{patronId}', [PatronEditController::class, 'update'])
        ->middleware('permission:patrons.manage')
        ->name('pos.patrons.update');

    Route::post('/betrieb/ausleihkonten/{patronId}/sperren', [PatronBlockController::class, 'store'])
        ->middleware('permission:patrons.block')
        ->name('pos.patrons.block.store');

    Route::delete('/betrieb/ausleihkonten/{patronId}/sperre', [PatronBlockController::class, 'destroy'])
        ->middleware('permission:patrons.block')
        ->name('pos.patrons.block.destroy');

    Route::post('/betrieb/ausleihkonten/{patronId}/austritt', [PatronDepartureController::class, 'store'])
        ->middleware('permission:patrons.depart')
        ->name('pos.patrons.departure.store');

    Route::post('/betrieb/ausleihkonten/{patronId}/onlinekonto-code', IssuePatronLinkCodeController::class)
        ->middleware('permission:patrons.link-code.issue')
        ->name('pos.patrons.link-code.issue');

    Route::put('/betrieb/ausleihkonten/{patronId}/ag-rollen/{roleKey}', [PatronAgRoleController::class, 'store'])
        ->middleware('permission:identity.roles.assign')
        ->name('pos.patrons.ag-roles.store');

    Route::delete('/betrieb/ausleihkonten/{patronId}/ag-rollen/{roleKey}', [PatronAgRoleController::class, 'destroy'])
        ->middleware('permission:identity.roles.assign')
        ->name('pos.patrons.ag-roles.destroy');
});

if (app()->environment('local')) {
    Route::view('/_preview/pos', 'pages.surfaces.pos', ['preview' => true])
        ->name('preview.pos');
}
