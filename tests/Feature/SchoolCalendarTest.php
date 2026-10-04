<?php

declare(strict_types=1);

use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use App\Modules\School\Services\SchoolCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates real library opening days from weekly hours and closures', function (): void {
    LibraryOpeningHour::query()->create([
        'day_of_week' => 1,
        'is_open' => true,
        'opens_at' => '08:00',
        'closes_at' => '15:00',
    ]);

    $calendar = app(SchoolCalendarService::class);
    $monday = CarbonImmutable::parse('2026-10-05', 'Europe/Berlin');

    expect($calendar->isOpeningDay($monday))->toBeTrue();

    LibraryClosure::query()->create([
        'date' => '2026-10-05',
        'reason' => 'Schließtag',
    ]);

    expect($calendar->isOpeningDay($monday))->toBeFalse()
        ->and($calendar->nextOpeningDay($monday)->toDateString())->toBe('2026-10-12')
        ->and($calendar->openingDaysBetween($monday, CarbonImmutable::parse('2026-10-12', 'Europe/Berlin')))->toBe(1);
});
