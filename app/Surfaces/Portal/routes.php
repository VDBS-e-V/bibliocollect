<?php

declare(strict_types=1);

use App\Surfaces\Portal\Http\Controllers\PortalCirculationController;
use App\Surfaces\Portal\Http\Controllers\PortalHomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:surface.portal.access'])->group(function (): void {
    Route::get('/konto', PortalHomeController::class)->name('portal.home');

    Route::post('/konto/ausleihen/{loanId}/verlaengern', [PortalCirculationController::class, 'renew'])
        ->name('portal.loans.renew');

    Route::post('/konto/vormerkungen', [PortalCirculationController::class, 'reserve'])
        ->name('portal.reservations.store');

    Route::post('/konto/vormerkungen/{reservationId}/stornieren', [PortalCirculationController::class, 'cancel'])
        ->name('portal.reservations.cancel');
});

if (app()->environment('local')) {
    Route::view('/_preview/portal', 'pages.surfaces.portal', ['preview' => true])
        ->name('preview.portal');
}
