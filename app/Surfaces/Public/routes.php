<?php

declare(strict_types=1);

use App\Surfaces\Public\Http\Controllers\CatalogAdvancedSearchController;
use App\Surfaces\Public\Http\Controllers\CatalogIndexController;
use App\Surfaces\Public\Http\Controllers\CatalogSuggestController;
use App\Surfaces\Public\Http\Controllers\CatalogTitleController;
use App\Surfaces\Public\Http\Controllers\ContentPageController;
use App\Surfaces\Public\Http\Controllers\PublicHomeController;
use App\Surfaces\Public\Http\Controllers\ReadingListLinkController;
use App\Surfaces\Public\Http\Controllers\SeriesController;
use App\Surfaces\Public\Http\Controllers\SeriesIndexController;
use App\Surfaces\Public\Http\Controllers\ShelfLinkController;
use App\Surfaces\Public\Http\Controllers\TopicLinkController;
use App\Surfaces\Public\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicHomeController::class)->name('public.home');

Route::get('/katalog', CatalogIndexController::class)
    ->name('public.catalog.index');

Route::get('/leseliste/{token}', ReadingListLinkController::class)->where('token', '[A-Za-z0-9]{20,40}')->name('public.reading-list');

Route::get('/reihen', SeriesIndexController::class)->name('public.series.index');
Route::get('/reihe/{slug}', SeriesController::class)->where('slug', '[a-z0-9\-]+')->name('public.series');

Route::get('/regal/{code}', ShelfLinkController::class)->where('code', '.+')->name('public.shelf');

Route::get('/thema/{key}', TopicLinkController::class)->where('key', '.+')->name('public.topic');

Route::get('/katalog/vorschlaege', CatalogSuggestController::class)->middleware('throttle:90,1')->name('public.catalog.suggest');

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
