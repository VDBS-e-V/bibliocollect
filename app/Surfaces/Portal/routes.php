<?php

declare(strict_types=1);

use App\Surfaces\Portal\Http\Controllers\PortalCirculationController;
use App\Surfaces\Portal\Http\Controllers\PortalHomeController;
use App\Surfaces\Portal\Http\Controllers\PortalWishController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:surface.portal.access'])->group(function (): void {
    Route::get('/konto', PortalHomeController::class)->name('portal.home');

    Route::post('/konto/ausleihen/{loanId}/verlaengern', [PortalCirculationController::class, 'renew'])
        ->name('portal.loans.renew');

    Route::post('/konto/einstellungen', [PortalCirculationController::class, 'settings'])
        ->name('portal.settings');

    Route::get('/konto/meine-daten', [PortalCirculationController::class, 'myData'])
        ->name('portal.my-data');

    Route::get('/konto/buchwuensche', [PortalWishController::class, 'index'])->name('portal.wishes.index');
    Route::post('/konto/buchwuensche/{wishId}/zurueckziehen', [PortalWishController::class, 'withdraw'])->name('portal.wishes.withdraw');

    Route::post('/konto/vormerkungen', [PortalCirculationController::class, 'reserve'])
        ->name('portal.reservations.store');

    Route::post('/konto/vormerkungen/{reservationId}/stornieren', [PortalCirculationController::class, 'cancel'])
        ->name('portal.reservations.cancel');
});

if (app()->environment('local')) {
    Route::view('/_preview/portal', 'pages.surfaces.portal', ['preview' => true])
        ->name('preview.portal');
}
