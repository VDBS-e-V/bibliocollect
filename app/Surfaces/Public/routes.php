<?php

declare(strict_types=1);

use App\Surfaces\Public\Http\Controllers\CatalogAdvancedSearchController;
use App\Surfaces\Public\Http\Controllers\CatalogIndexController;
use App\Surfaces\Public\Http\Controllers\CatalogTitleController;
use App\Surfaces\Public\Http\Controllers\ContentPageController;
use App\Surfaces\Public\Http\Controllers\PublicHomeController;
use App\Surfaces\Public\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicHomeController::class)->name('public.home');

Route::get('/katalog', CatalogIndexController::class)
    ->name('public.catalog.index');

Route::get('/katalog/erweiterte-suche', CatalogAdvancedSearchController::class)
    ->name('public.catalog.advanced');

Route::get('/buchwunsch', [WishController::class, 'create'])->name('public.wishes.create');
Route::post('/buchwunsch', [WishController::class, 'store'])->middleware('throttle:5,60')->name('public.wishes.store');
Route::get('/buchwunsch/isbn', [WishController::class, 'lookup'])->middleware('throttle:15,1')->name('public.wishes.lookup');

Route::get('/{slug}', ContentPageController::class)
    ->whereIn('slug', ['impressum', 'datenschutz', 'barrierefreiheit'])
    ->name('public.page');

Route::get('/katalog/titel/{titleId}', CatalogTitleController::class)
    ->name('public.catalog.show');
