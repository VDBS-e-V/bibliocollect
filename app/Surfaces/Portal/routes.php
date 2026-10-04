<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/konto', 'pages.surfaces.portal', ['preview' => false])
    ->middleware(['auth', 'verified', 'permission:surface.portal.access'])
    ->name('portal.home');

if (app()->environment('local')) {
    Route::view('/_preview/portal', 'pages.surfaces.portal', ['preview' => true])
        ->name('preview.portal');
}
