<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Foundation\Support\BusinessClock;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Models\BookWish;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Arbeitsplatz: Scanfeld für Ausleihe und Rückgabe und eine Übersicht dessen, was heute ansteht. */
final class PosHomeController
{
    public function __invoke(BusinessClock $clock): Response
    {
        $today = $clock->now()->startOfDay();
        $todayDate = $today->toDateString();

        $hours = LibraryOpeningHour::query()
            ->where('day_of_week', $today->dayOfWeekIso)
            ->where('is_open', true)
            ->orderBy('opens_at')
            ->get()
            ->map(static fn (LibraryOpeningHour $hour): string => substr((string) $hour->opens_at, 0, 5).'–'.substr((string) $hour->closes_at, 0, 5))
            ->all();

        $closure = LibraryClosure::query()->whereDate('date', $todayDate)->first();

        $tiles = [];

        if (Gate::allows('circulation.manage')) {
            $open = Loan::query()->whereNull('returned_at');

            $tiles[] = ['label' => 'Überfällige Ausleihen', 'value' => (clone $open)->whereDate('due_on', '<', $todayDate)->count(), 'url' => Gate::allows('circulation.reports') ? route('pos.reports.class-loans') : null, 'warn' => true];
            $tiles[] = ['label' => 'Heute fällig', 'value' => (clone $open)->whereDate('due_on', $todayDate)->count(), 'url' => null, 'warn' => false];
            $tiles[] = ['label' => 'Zur Abholung zurückgelegt', 'value' => Reservation::query()->where('status', ReservationStatus::Ready->value)->count(), 'url' => route('pos.reservations.index'), 'warn' => false];
            $tiles[] = ['label' => 'Wartende Vormerkungen', 'value' => Reservation::query()->where('status', ReservationStatus::Waiting->value)->count(), 'url' => route('pos.reservations.index'), 'warn' => false];
        }

        if (Gate::allows('wishes.manage')) {
            $tiles[] = ['label' => 'Neue Buchwünsche', 'value' => BookWish::query()->where('status', WishStatus::New->value)->count(), 'url' => route('pos.wishes.index'), 'warn' => false];
        }

        if (Gate::allows('catalog.manage')) {
            $tiles[] = [
                'label' => 'Offene Katalogfälle',
                'value' => CatalogMetadataReview::query()->where('status', MetadataReviewStatus::Open->value)->where('severity', '>', 0)->count(),
                'url' => route('pos.catalog.quality.index'),
                'warn' => false,
            ];
        }

        return response()
            ->view('pages.surfaces.pos.home', [
                'tiles' => $tiles,
                'hours' => $hours,
                'closure' => $closure,
                'today' => $today,
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
