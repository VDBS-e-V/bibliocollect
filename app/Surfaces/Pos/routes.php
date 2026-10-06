<?php

declare(strict_types=1);

use App\Surfaces\Pos\Http\Controllers\CatalogContributionController;
use App\Surfaces\Pos\Http\Controllers\CatalogCopyController;
use App\Surfaces\Pos\Http\Controllers\CatalogEditionController;
use App\Surfaces\Pos\Http\Controllers\CatalogImportController;
use App\Surfaces\Pos\Http\Controllers\CatalogIndexController;
use App\Surfaces\Pos\Http\Controllers\CatalogIntakeController;
use App\Surfaces\Pos\Http\Controllers\CatalogQualityController;
use App\Surfaces\Pos\Http\Controllers\CatalogTitleController;
use App\Surfaces\Pos\Http\Controllers\CirculationController;
use App\Surfaces\Pos\Http\Controllers\ClassLoanReportController;
use App\Surfaces\Pos\Http\Controllers\CopyLabelController;
use App\Surfaces\Pos\Http\Controllers\IssuePatronLinkCodeController;
use App\Surfaces\Pos\Http\Controllers\PatronAgRoleController;
use App\Surfaces\Pos\Http\Controllers\PatronBlockController;
use App\Surfaces\Pos\Http\Controllers\PatronCardController;
use App\Surfaces\Pos\Http\Controllers\PatronCreateController;
use App\Surfaces\Pos\Http\Controllers\PatronDataExportController;
use App\Surfaces\Pos\Http\Controllers\PatronDepartureController;
use App\Surfaces\Pos\Http\Controllers\PatronEditController;
use App\Surfaces\Pos\Http\Controllers\PatronImportController;
use App\Surfaces\Pos\Http\Controllers\PatronIndexController;
use App\Surfaces\Pos\Http\Controllers\PatronShowController;
use App\Surfaces\Pos\Http\Controllers\PosHomeController;
use App\Surfaces\Pos\Http\Controllers\PosScanController;
use App\Surfaces\Pos\Http\Controllers\ReservationController;
use App\Surfaces\Pos\Http\Controllers\ReservationIndexController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:surface.pos.access'])->group(function (): void {
    Route::get('/betrieb', PosHomeController::class)
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

        Route::prefix('/betrieb/katalog/erfassen')->group(function (): void {
            Route::get('/', [CatalogIntakeController::class, 'identify'])->name('pos.catalog.intake.identify');
            Route::post('/', [CatalogIntakeController::class, 'lookup'])->name('pos.catalog.intake.lookup');
            Route::delete('/', [CatalogIntakeController::class, 'cancel'])->name('pos.catalog.intake.cancel');

            Route::get('/treffer', [CatalogIntakeController::class, 'matches'])->name('pos.catalog.intake.matches');
            Route::post('/treffer', [CatalogIntakeController::class, 'choose'])->name('pos.catalog.intake.choose');

            Route::get('/daten', [CatalogIntakeController::class, 'details'])->name('pos.catalog.intake.details');
            Route::post('/daten', [CatalogIntakeController::class, 'storeDetails'])->name('pos.catalog.intake.details.store');

            Route::get('/exemplar', [CatalogIntakeController::class, 'copy'])->name('pos.catalog.intake.copy');
            Route::post('/exemplar', [CatalogIntakeController::class, 'storeCopy'])->name('pos.catalog.intake.copy.store');

            Route::get('/pruefen', [CatalogIntakeController::class, 'review'])->name('pos.catalog.intake.review');
            Route::post('/speichern', [CatalogIntakeController::class, 'commit'])->name('pos.catalog.intake.commit');
        });

        Route::prefix('/betrieb/katalog/qualitaet')->group(function (): void {
            Route::get('/', [CatalogQualityController::class, 'index'])->name('pos.catalog.quality.index');
            Route::post('/scan', [CatalogQualityController::class, 'scan'])->name('pos.catalog.quality.scan');
            Route::get('/sicher', [CatalogQualityController::class, 'safe'])->name('pos.catalog.quality.safe');
            Route::post('/sicher', [CatalogQualityController::class, 'applySafe'])->name('pos.catalog.quality.safe.apply');
            Route::get('/{reviewId}', [CatalogQualityController::class, 'show'])->name('pos.catalog.quality.show');
            Route::get('/{reviewId}/weiter', [CatalogQualityController::class, 'skip'])->name('pos.catalog.quality.skip');
            Route::post('/{reviewId}/vorschlag', [CatalogQualityController::class, 'refresh'])->name('pos.catalog.quality.refresh');
            Route::post('/{reviewId}/uebernehmen', [CatalogQualityController::class, 'apply'])->name('pos.catalog.quality.apply');
            Route::post('/{reviewId}/abweisen', [CatalogQualityController::class, 'dismiss'])->name('pos.catalog.quality.dismiss');
            Route::post('/{reviewId}/oeffnen', [CatalogQualityController::class, 'reopen'])->name('pos.catalog.quality.reopen');
        });

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

    Route::middleware('permission:catalog.manage')->group(function (): void {
        Route::get('/betrieb/etiketten', [CopyLabelController::class, 'index'])->name('pos.labels.copies');
        Route::post('/betrieb/etiketten', [CopyLabelController::class, 'print'])->name('pos.labels.copies.print');
    });

    Route::middleware('permission:patrons.manage')->group(function (): void {
        Route::get('/betrieb/ausweise', [PatronCardController::class, 'index'])->name('pos.labels.cards');
        Route::post('/betrieb/ausweise', [PatronCardController::class, 'print'])->name('pos.labels.cards.print');
    });

    Route::get('/betrieb/klassenlisten', ClassLoanReportController::class)
        ->middleware('permission:circulation.reports')
        ->name('pos.reports.class-loans');

    Route::middleware('permission:circulation.manage')->group(function (): void {
        Route::post('/betrieb/scan', PosScanController::class)
            ->name('pos.scan');

        Route::post('/betrieb/ausleihkonten/{patronId}/ausleihen', [CirculationController::class, 'checkout'])
            ->name('pos.circulation.checkout');

        Route::post('/betrieb/ausleihkonten/{patronId}/ausleihen/{loanId}/rueckgabe', [CirculationController::class, 'return'])
            ->name('pos.circulation.return');

        Route::post('/betrieb/ausleihkonten/{patronId}/ausleihen/{loanId}/problem', [CirculationController::class, 'problem'])
            ->name('pos.circulation.problem');

        Route::post('/betrieb/ausleihkonten/{patronId}/ausleihen/{loanId}/verlaengerung', [CirculationController::class, 'renew'])
            ->name('pos.circulation.renew');

        Route::get('/betrieb/vormerkungen', ReservationIndexController::class)
            ->name('pos.reservations.index');

        Route::post('/betrieb/ausleihkonten/{patronId}/vormerkungen', [ReservationController::class, 'store'])
            ->name('pos.reservations.store');

        Route::post('/betrieb/ausleihkonten/{patronId}/vormerkungen/{reservationId}/stornieren', [ReservationController::class, 'cancel'])
            ->name('pos.reservations.cancel');
    });

    Route::get('/betrieb/ausleihkonten', PatronIndexController::class)
        ->middleware('permission:patrons.lookup')
        ->name('pos.patrons.index');

    Route::middleware('permission:patrons.manage')->group(function (): void {
        Route::get('/betrieb/ausleihkonten/import', [PatronImportController::class, 'create'])
            ->name('pos.patrons.import.create');

        Route::get('/betrieb/ausleihkonten/import/vorlage', [PatronImportController::class, 'template'])
            ->name('pos.patrons.import.template');

        Route::post('/betrieb/ausleihkonten/import', [PatronImportController::class, 'store'])
            ->name('pos.patrons.import.store');

        Route::get('/betrieb/ausleihkonten/import/{token}', [PatronImportController::class, 'show'])
            ->name('pos.patrons.import.show');

        Route::post('/betrieb/ausleihkonten/import/{token}', [PatronImportController::class, 'commit'])
            ->name('pos.patrons.import.commit');
    });

    Route::get('/betrieb/ausleihkonten/neu', [PatronCreateController::class, 'create'])
        ->middleware('permission:patrons.manage')
        ->name('pos.patrons.create');

    Route::post('/betrieb/ausleihkonten', [PatronCreateController::class, 'store'])
        ->middleware('permission:patrons.manage')
        ->name('pos.patrons.store');

    Route::get('/betrieb/ausleihkonten/{patronId}/auskunft', [PatronDataExportController::class, 'show'])
        ->middleware('permission:patrons.sensitive.view')
        ->name('pos.patrons.data-export');

    Route::get('/betrieb/ausleihkonten/{patronId}/auskunft.json', [PatronDataExportController::class, 'download'])
        ->middleware('permission:patrons.sensitive.view')
        ->name('pos.patrons.data-export.download');

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
