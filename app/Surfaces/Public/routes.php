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

// Designatlas nur auf lokalen/Test-Systemen – keine internen Beispielabläufe in Produktion.
if (app()->environment(['local', 'testing'])) {
    Route::view('/design-review', 'pages.design-review')->name('public.design-review');

    // Neue, getrennt prüfbare Musterseiten und Komponentenbibliothek.
    // Nur local/testing; keine Demodaten auf produktiven Schulinstanzen.
    Route::view('/design-lab', 'pages.design-lab', ['screen' => 'index'])->name('public.design-lab');
    Route::view('/design-lab/startseite', 'pages.design-lab', ['screen' => 'startseite'])->name('public.design-lab.home');
    Route::view('/design-lab/medien', 'pages.design-lab', ['screen' => 'medien'])->name('public.design-lab.media');
    Route::view('/design-lab/assistent', 'pages.design-lab', ['screen' => 'assistent'])->name('public.design-lab.wizard');
    Route::view('/design-lab/dashboard', 'pages.design-lab', ['screen' => 'dashboard'])->name('public.design-lab.dashboard');
    Route::view('/design-lab/portal', 'pages.design-lab', ['screen' => 'portal'])->name('public.design-lab.portal');
    Route::view('/design-lab/komponenten', 'pages.design-lab', ['screen' => 'komponenten'])->name('public.design-lab.components');
    Route::view('/design-lab/designsystem', 'pages.design-lab', ['screen' => 'designsystem'])->name('public.design-lab.designsystem');
}

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
