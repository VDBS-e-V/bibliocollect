<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use App\Modules\School\Services\SchoolCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function calendarAdmin(string $role = 'management'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function weekdays(array $open = [1, 2, 3, 4, 5]): array
{
    $days = [];

    foreach (range(1, 7) as $day) {
        $days[$day] = in_array($day, $open, true)
            ? ['is_open' => '1', 'ranges' => [['from' => '09:00', 'to' => '15:00'], ['from' => '', 'to' => '']]]
            : ['ranges' => [['from' => '', 'to' => '']]];
    }

    return $days;
}

it('saves opening hours and clears times of closed days', function (): void {
    $this->actingAs(calendarAdmin())
        ->put(route('administration.calendar.hours'), ['days' => weekdays([1, 3])])
        ->assertRedirect(route('administration.calendar.index'))
        ->assertSessionHas('school_success');

    expect(LibraryOpeningHour::query()->where('is_open', true)->count())->toBe(2)
        ->and(LibraryOpeningHour::query()->where('day_of_week', 2)->first()->opens_at)->toBeNull()
        ->and(substr((string) LibraryOpeningHour::query()->where('day_of_week', 1)->first()->opens_at, 0, 5))->toBe('09:00');

    $this->actingAs(calendarAdmin())
        ->get(route('administration.calendar.index'))
        ->assertOk()
        ->assertSee('Montag')
        ->assertSee('value="09:00"', false);
});

it('rejects invalid opening hours', function (): void {
    $admin = calendarAdmin();

    $this->actingAs($admin)
        ->put(route('administration.calendar.hours'), ['days' => weekdays([])])
        ->assertSessionHasErrors('days');

    $days = weekdays([1]);
    $days[1]['ranges'][0]['to'] = '08:00';

    $this->actingAs($admin)
        ->put(route('administration.calendar.hours'), ['days' => $days])
        ->assertSessionHasErrors('days.1.ranges');

    $days = weekdays([1]);
    $days[1]['ranges'] = [['from' => '', 'to' => '']];

    $this->actingAs($admin)
        ->put(route('administration.calendar.hours'), ['days' => $days])
        ->assertSessionHasErrors('days.1.ranges');

    $days = weekdays([1]);
    $days[1]['ranges'] = [['from' => '08:00', 'to' => '10:00'], ['from' => '09:30', 'to' => '12:00']];

    $this->actingAs($admin)
        ->put(route('administration.calendar.hours'), ['days' => $days])
        ->assertSessionHasErrors('days.1.ranges');

    $days = weekdays([1]);
    $days[1]['ranges'] = [['from' => '08:00', 'to' => '']];

    $this->actingAs($admin)
        ->put(route('administration.calendar.hours'), ['days' => $days])
        ->assertSessionHasErrors('days.1.ranges');

    expect(LibraryOpeningHour::query()->count())->toBe(0);
});

it('stores several opening ranges per day and shows them again with a free slot', function (): void {
    $admin = calendarAdmin();
    $days = weekdays([1, 2]);
    // Bewusst unsortiert eingegeben; gespeichert wird nach Beginn sortiert.
    $days[1]['ranges'] = [['from' => '13:00', 'to' => '15:00'], ['from' => '08:00', 'to' => '10:00'], ['from' => '', 'to' => '']];

    $this->actingAs($admin)
        ->put(route('administration.calendar.hours'), ['days' => $days])
        ->assertSessionHasNoErrors();

    $monday = LibraryOpeningHour::query()->where('day_of_week', 1)->orderBy('opens_at')->get();

    expect($monday)->toHaveCount(2)
        ->and(substr((string) $monday[0]->opens_at, 0, 5))->toBe('08:00')
        ->and(substr((string) $monday[1]->closes_at, 0, 5))->toBe('15:00')
        ->and(LibraryOpeningHour::query()->where('day_of_week', 2)->count())->toBe(1);

    // Erneutes Speichern ersetzt die Zeiträume, statt sie zu vermehren.
    $this->actingAs($admin)->put(route('administration.calendar.hours'), ['days' => $days]);
    expect(LibraryOpeningHour::query()->where('day_of_week', 1)->count())->toBe(2);

    $this->actingAs($admin)
        ->get(route('administration.calendar.index'))
        ->assertOk()
        ->assertSee('value="08:00"', false)
        ->assertSee('value="13:00"', false)
        ->assertSee('days[1][ranges][2][from]', false);

    $calendar = app(SchoolCalendarService::class);

    expect($calendar->isOpeningDay(CarbonImmutable::parse('2026-10-05', 'Europe/Berlin')))->toBeTrue()
        ->and($calendar->isOpeningDay(CarbonImmutable::parse('2026-10-07', 'Europe/Berlin')))->toBeFalse();
});

it('adds closure ranges idempotently and removes single days', function (): void {
    $admin = calendarAdmin();

    $this->actingAs($admin)
        ->post(route('administration.calendar.closures.store'), ['from' => '2027-12-23', 'to' => '2027-12-27', 'reason' => 'Weihnachtsferien'])
        ->assertRedirect(route('administration.calendar.index'));

    expect(LibraryClosure::query()->count())->toBe(5);

    $this->actingAs($admin)
        ->post(route('administration.calendar.closures.store'), ['from' => '2027-12-26', 'to' => '2027-12-28'])->assertSessionHasNoErrors();

    expect(LibraryClosure::query()->count())->toBe(6);

    $this->actingAs($admin)
        ->post(route('administration.calendar.closures.store'), ['from' => '2028-01-05'])
        ->assertSessionHas('school_success', 'Ein Schließtag wurde eingetragen.');

    $closure = LibraryClosure::query()->whereDate('date', '2028-01-05')->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('administration.calendar.closures.destroy', ['closureId' => $closure->getKey()]))
        ->assertRedirect(route('administration.calendar.index'));

    expect(LibraryClosure::query()->whereDate('date', '2028-01-05')->exists())->toBeFalse();
});

it('rejects reversed and oversized closure ranges', function (): void {
    $admin = calendarAdmin();

    $this->actingAs($admin)
        ->post(route('administration.calendar.closures.store'), ['from' => '2027-12-27', 'to' => '2027-12-23'])
        ->assertSessionHasErrors('to');

    $this->actingAs($admin)
        ->post(route('administration.calendar.closures.store'), ['from' => '2027-01-01', 'to' => '2029-01-01'])
        ->assertSessionHasErrors('from');

    expect(LibraryClosure::query()->count())->toBe(0);
});

it('keeps the calendar away from users without school management rights', function (string $role): void {
    $user = calendarAdmin($role);

    $this->actingAs($user)->get(route('administration.calendar.index'))->assertForbidden();
    $this->actingAs($user)->put(route('administration.calendar.hours'), ['days' => weekdays()])->assertForbidden();
    $this->actingAs($user)->post(route('administration.calendar.closures.store'), ['from' => '2027-12-23'])->assertForbidden();
})->with(['student', 'teacher', 'staff', 'student_ag_basic']);
