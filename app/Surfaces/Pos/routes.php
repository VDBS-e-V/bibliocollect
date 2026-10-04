<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/betrieb', 'pages.surfaces.pos', ['preview' => false])
    ->middleware('permission:surface.pos.access')
    ->name('pos.home');

if (app()->environment('local')) {
    Route::view('/_preview/pos', 'pages.surfaces.pos', ['preview' => true])
        ->name('preview.pos');
}
