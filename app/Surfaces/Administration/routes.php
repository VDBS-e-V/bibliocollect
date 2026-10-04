<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/verwaltung', 'pages.surfaces.administration', ['preview' => false])
    ->middleware('permission:surface.administration.access')
    ->name('administration.home');

if (app()->environment('local')) {
    Route::view('/_preview/administration', 'pages.surfaces.administration', ['preview' => true])
        ->name('preview.administration');
}
