<?php

declare(strict_types=1);

use App\Surfaces\Administration\Http\Controllers\AdminHomeController;
use App\Surfaces\Administration\Http\Controllers\AuditIndexController;
use App\Surfaces\Administration\Http\Controllers\CatalogShelfController;
use App\Surfaces\Administration\Http\Controllers\CatalogShelfSectionController;
use App\Surfaces\Administration\Http\Controllers\CatalogTopicController;
use App\Surfaces\Administration\Http\Controllers\ContentPageAdminController;
use App\Surfaces\Administration\Http\Controllers\InventoryRenumberController;
use App\Surfaces\Administration\Http\Controllers\LibraryCalendarController;
use App\Surfaces\Administration\Http\Controllers\MailPreviewController;
use App\Surfaces\Administration\Http\Controllers\RulesController;
use App\Surfaces\Administration\Http\Controllers\SchoolClassController;
use App\Surfaces\Administration\Http\Controllers\SchoolIndexController;
use App\Surfaces\Administration\Http\Controllers\SchoolYearController;
use App\Surfaces\Administration\Http\Controllers\SchoolYearTransitionController;
use App\Surfaces\Administration\Http\Controllers\SystemHealthController;
use App\Surfaces\Administration\Http\Controllers\UpdateController;
use App\Surfaces\Administration\Http\Controllers\UserAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:surface.administration.access'])->group(function (): void {
    Route::get('/verwaltung', AdminHomeController::class)
        ->name('administration.home');

    Route::middleware('permission:content.manage')->group(function (): void {
        Route::get('/verwaltung/seiten', [ContentPageAdminController::class, 'index'])->name('administration.pages.index');
        Route::get('/verwaltung/seiten/{slug}', [ContentPageAdminController::class, 'edit'])->name('administration.pages.edit');
        Route::patch('/verwaltung/seiten/{slug}', [ContentPageAdminController::class, 'update'])->name('administration.pages.update');
    });

    Route::middleware('permission:users.manage')->group(function (): void {
        Route::get('/verwaltung/benutzer', [UserAccountController::class, 'index'])->name('administration.users.index');
        Route::get('/verwaltung/benutzer/neu', [UserAccountController::class, 'create'])->name('administration.users.create');
        Route::post('/verwaltung/benutzer', [UserAccountController::class, 'store'])->middleware('throttle:20,1')->name('administration.users.store');
        Route::get('/verwaltung/benutzer/{userId}', [UserAccountController::class, 'edit'])->whereNumber('userId')->name('administration.users.edit');
        Route::put('/verwaltung/benutzer/{userId}/rollen', [UserAccountController::class, 'updateRoles'])->whereNumber('userId')->name('administration.users.roles');
        Route::post('/verwaltung/benutzer/{userId}/deaktivieren', [UserAccountController::class, 'disable'])->whereNumber('userId')->name('administration.users.disable');
        Route::post('/verwaltung/benutzer/{userId}/aktivieren', [UserAccountController::class, 'enable'])->whereNumber('userId')->name('administration.users.enable');
        Route::post('/verwaltung/benutzer/{userId}/einladen', [UserAccountController::class, 'invite'])->whereNumber('userId')->middleware('throttle:10,1')->name('administration.users.invite');
    });

    Route::middleware('permission:settings.manage')->group(function (): void {
        Route::get('/verwaltung/regeln', [RulesController::class, 'index'])->name('administration.rules.index');
        Route::put('/verwaltung/regeln', [RulesController::class, 'update'])->middleware('throttle:20,1')->name('administration.rules.update');
        Route::post('/verwaltung/regeln/zuruecksetzen', [RulesController::class, 'reset'])->middleware('throttle:5,1')->name('administration.rules.reset');
    });

    Route::middleware('permission:system.update')->group(function (): void {
        Route::get('/verwaltung/update', [UpdateController::class, 'index'])->name('administration.update.index');
        Route::post('/verwaltung/update/hochladen', [UpdateController::class, 'upload'])->middleware('throttle:10,1')->name('administration.update.upload');
        Route::post('/verwaltung/update/einspielen', [UpdateController::class, 'apply'])->middleware('throttle:5,1')->name('administration.update.apply');
        Route::post('/verwaltung/update/automatisch', [UpdateController::class, 'auto'])->middleware('throttle:10,1')->name('administration.update.auto');
        Route::delete('/verwaltung/update/paket/{name}', [UpdateController::class, 'destroy'])->middleware('throttle:10,1')->name('administration.update.destroy');
    });

    Route::middleware('permission:system.view')->group(function (): void {
        Route::get('/verwaltung/systemzustand', [SystemHealthController::class, 'index'])->name('administration.system.index');
        Route::post('/verwaltung/systemzustand/sicherung', [SystemHealthController::class, 'createBackup'])->middleware('throttle:4,1')->name('administration.system.backup');
        Route::get('/verwaltung/systemzustand/sicherung/{file}', [SystemHealthController::class, 'downloadBackup'])->middleware('throttle:20,1')->name('administration.system.backup.download');
        Route::post('/verwaltung/systemzustand/cron', [SystemHealthController::class, 'runCron'])->middleware('throttle:6,1')->name('administration.system.run-cron');
        Route::post('/verwaltung/systemzustand/aufgaben/{job}', [SystemHealthController::class, 'runJob'])->where('job', '[a-z0-9:_\-]+')->middleware('throttle:10,1')->name('administration.system.run-job');
        Route::post('/verwaltung/systemzustand/cover', [SystemHealthController::class, 'queueCovers'])->middleware('throttle:6,1')->name('administration.system.queue-covers');
        Route::get('/verwaltung/mail-vorschau', [MailPreviewController::class, 'index'])->name('administration.mail-preview');
        Route::post('/verwaltung/mail-vorschau', [MailPreviewController::class, 'send'])->middleware('throttle:5,1')->name('administration.mail-preview.send');
        Route::post('/verwaltung/systemzustand/testmeldung', [SystemHealthController::class, 'testAlert'])->middleware('throttle:5,1')->name('administration.system.test-alert');
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

        Route::post('/verwaltung/schuljahre/{schoolYearId}/standardklassen', [SchoolClassController::class, 'storeStandard'])
            ->name('administration.school-classes.store-standard');

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
        Route::get('/verwaltung/themenbereiche', [CatalogTopicController::class, 'index'])->name('administration.topics.index');
        Route::post('/verwaltung/themenbereiche', [CatalogTopicController::class, 'store'])->name('administration.topics.store');
        Route::patch('/verwaltung/themenbereiche/{topicId}', [CatalogTopicController::class, 'update'])->name('administration.topics.update');
        Route::delete('/verwaltung/themenbereiche/{topicId}', [CatalogTopicController::class, 'destroy'])->name('administration.topics.destroy');

        Route::post('/verwaltung/standorte', [CatalogShelfSectionController::class, 'store'])->name('administration.sections.store');
        Route::post('/verwaltung/standorte/zuordnen', [CatalogShelfSectionController::class, 'assign'])->name('administration.sections.assign');
        Route::patch('/verwaltung/standorte/{sectionId}', [CatalogShelfSectionController::class, 'update'])->name('administration.sections.update');
        Route::delete('/verwaltung/standorte/{sectionId}', [CatalogShelfSectionController::class, 'destroy'])->name('administration.sections.destroy');

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
