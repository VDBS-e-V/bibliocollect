<?php

declare(strict_types=1);

use App\Surfaces\Pos\Http\Controllers\IssuePatronLinkCodeController;
use App\Surfaces\Pos\Http\Controllers\PatronAgRoleController;
use App\Surfaces\Pos\Http\Controllers\PatronIndexController;
use App\Surfaces\Pos\Http\Controllers\PatronShowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:surface.pos.access'])->group(function (): void {
    Route::view('/betrieb', 'pages.surfaces.pos', ['preview' => false])
        ->name('pos.home');

    Route::get('/betrieb/ausleihkonten', PatronIndexController::class)
        ->middleware('permission:patrons.lookup')
        ->name('pos.patrons.index');

    Route::get('/betrieb/ausleihkonten/{patronId}', PatronShowController::class)
        ->middleware('permission:patrons.lookup')
        ->name('pos.patrons.show');

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
