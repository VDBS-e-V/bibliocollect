<?php

declare(strict_types=1);

use App\Surfaces\Public\Http\Controllers\CatalogAdvancedSearchController;
use App\Surfaces\Public\Http\Controllers\CatalogIndexController;
use App\Surfaces\Public\Http\Controllers\CatalogTitleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.welcome')->name('public.home');

Route::get('/katalog', CatalogIndexController::class)
    ->name('public.catalog.index');

Route::get('/katalog/erweiterte-suche', CatalogAdvancedSearchController::class)
    ->name('public.catalog.advanced');

Route::get('/katalog/titel/{titleId}', CatalogTitleController::class)
    ->name('public.catalog.show');
