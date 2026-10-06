<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Actions\DepartPatronAction;
use App\Modules\Patrons\Actions\TransitionSchoolYearAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Exceptions\PatronStatusStateConflict;
use App\Modules\Patrons\Exceptions\SchoolYearTransitionConflict;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Services\SchoolYearTransitionPlanner;
use App\Modules\School\Models\LibraryOpeningHour;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function transitionAdmin(string $role = 'management'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** @return array{from: SchoolYear, to: SchoolYear, classes: array<string, SchoolClass>} */
function transitionYears(): array
{
    $from = SchoolYear::query()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-07-31', 'is_active' => true]);
    $to = SchoolYear::query()->create(['name' => '2027/28', 'starts_on' => '2027-08-01', 'ends_on' => '2028-07-31', 'is_active' => false]);

    $classes = [];

    foreach ([['from', $from, '5a', 5], ['from', $from, '6b', 6], ['from', $from, '13', 13], ['to', $to, '6a', 6], ['to', $to, '7b', 7], ['to', $to, '5a', 5]] as [$key, $year, $name, $grade]) {
        $classes[$key.$name] = SchoolClass::query()->create(['school_year_id' => $year->getKey(), 'name' => $name, 'grade_level' => $grade, 'is_active' => true]);
    }

    return ['from' => $from, 'to' => $to, 'classes' => $classes];
}

function transitionPatron(string $number, ?SchoolClass $class): Patron
{
    return Patron::query()->create([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Wechsel',
        'last_name' => 'Kind'.$number,
        'birth_date' => '2012-01-01',
        'school_class_id' => $class?->getKey(),
    ]);
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2027-07-20 10:00:00', 'Europe/Berlin'));

    foreach (range(1, 5) as $dayOfWeek) {
        LibraryOpeningHour::query()->create(['day_of_week' => $dayOfWeek, 'is_open' => true, 'opens_at' => '09:00', 'closes_at' => '15:00']);
    }
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('suggests target classes by name, by single grade match and departure for grade 13', function (): void {
    $set = transitionYears();
    transitionPatron('S-1', $set['classes']['from5a']);
    transitionPatron('S-2', $set['classes']['from6b']);
    transitionPatron('S-3', $set['classes']['from13']);

    $plan = collect(app(SchoolYearTransitionPlanner::class)->plan($set['from'], $set['to']))
        ->keyBy(static fn (array $row): string => $row['class']->name);

    // 5a -> 6a (Name), 6b -> 7b (Name), 13 -> Abgang
    expect($plan['5a']['suggestion'])->toBe((string) $set['classes']['to6a']->getKey())
        ->and($plan['6b']['suggestion'])->toBe((string) $set['classes']['to7b']->getKey())
        ->and($plan['13']['suggestion'])->toBe('depart')
        ->and($plan['5a']['patron_count'])->toBe(1);
});

it('promotes, departs and activates the new year in one step', function (): void {
    $set = transitionYears();
    $actor = transitionAdmin();
    $a = transitionPatron('S-1', $set['classes']['from5a']);
    $b = transitionPatron('S-2', $set['classes']['from6b']);
    $leaver = transitionPatron('S-3', $set['classes']['from13']);
    $unchanged = transitionPatron('S-4', null);

    $result = app(TransitionSchoolYearAction::class)->execute($set['from'], $set['to'], [
        (string) $set['classes']['from5a']->getKey() => (string) $set['classes']['to6a']->getKey(),
        (string) $set['classes']['from6b']->getKey() => (string) $set['classes']['to7b']->getKey(),
        (string) $set['classes']['from13']->getKey() => 'depart',
    ], $actor);

    expect($result)->toBe(['promoted' => 2, 'departed' => 1, 'kept' => 0])
        ->and($a->fresh()->school_class_id)->toBe((string) $set['classes']['to6a']->getKey())
        ->and($b->fresh()->school_class_id)->toBe((string) $set['classes']['to7b']->getKey())
        ->and($leaver->fresh()->status)->toBe(PatronStatus::Departed)
        ->and($leaver->fresh()->school_class_id)->toBeNull()
        ->and($unchanged->fresh()->status)->toBe(PatronStatus::Active)
        ->and($set['to']->fresh()->is_active)->toBeTrue()
        ->and($set['from']->fresh()->is_active)->toBeFalse()
        ->and(AuditEvent::query()->where('action', 'school.year.transitioned')->count())->toBe(1);
});

it('rolls everything back when a departing patron still has loans', function (): void {
    $set = transitionYears();
    $actor = transitionAdmin();
    $promoted = transitionPatron('S-1', $set['classes']['from5a']);
    $leaver = transitionPatron('S-3', $set['classes']['from13']);

    $title = Title::query()->create(['preferred_title' => 'Noch ausgeliehen', 'sort_title' => 'Noch ausgeliehen']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    $copy = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'TR-001', 'status' => CopyStatus::Active]);
    app(CheckoutCopyAction::class)->execute($leaver, $copy->barcode, $actor);

    $mapping = [
        (string) $set['classes']['from5a']->getKey() => (string) $set['classes']['to6a']->getKey(),
        (string) $set['classes']['from13']->getKey() => 'depart',
    ];

    $plan = collect(app(SchoolYearTransitionPlanner::class)->plan($set['from'], $set['to']))->keyBy(static fn (array $row): string => $row['class']->name);

    expect($plan['13']['blocked'][0]['library_number'])->toBe('S-3');

    expect(fn () => app(TransitionSchoolYearAction::class)->execute($set['from'], $set['to'], $mapping, $actor))
        ->toThrow(SchoolYearTransitionConflict::class, 'S-3');

    expect($promoted->fresh()->school_class_id)->toBe((string) $set['classes']['from5a']->getKey())
        ->and($leaver->fresh()->status)->toBe(PatronStatus::Active)
        ->and($set['from']->fresh()->is_active)->toBeTrue();
});

it('requires a complete valid mapping and a draft target year', function (): void {
    $set = transitionYears();
    $actor = transitionAdmin();
    transitionPatron('S-1', $set['classes']['from5a']);

    expect(fn () => app(TransitionSchoolYearAction::class)->execute($set['from'], $set['to'], [], $actor))
        ->toThrow(SchoolYearTransitionConflict::class, '5a');

    expect(fn () => app(TransitionSchoolYearAction::class)->execute($set['from'], $set['to'], [
        (string) $set['classes']['from5a']->getKey() => (string) $set['classes']['from6b']->getKey(),
    ], $actor))->toThrow(SchoolYearTransitionConflict::class, 'ungültig');

    expect(fn () => app(TransitionSchoolYearAction::class)->execute($set['to'], $set['from'], [], $actor))
        ->toThrow(SchoolYearTransitionConflict::class);
});

it('keeps patrons in their class when told not to change them', function (): void {
    $set = transitionYears();
    $patron = transitionPatron('S-1', $set['classes']['from5a']);
    transitionPatron('S-2', $set['classes']['from6b']);

    $result = app(TransitionSchoolYearAction::class)->execute($set['from'], $set['to'], [
        (string) $set['classes']['from5a']->getKey() => 'keep',
        (string) $set['classes']['from6b']->getKey() => (string) $set['classes']['to7b']->getKey(),
    ], transitionAdmin());

    expect($result['kept'])->toBe(1)
        ->and($patron->fresh()->school_class_id)->toBe((string) $set['classes']['from5a']->getKey());
});

it('blocks a single departure while loans are open', function (): void {
    $set = transitionYears();
    $actor = transitionAdmin();
    $patron = transitionPatron('S-9', $set['classes']['from5a']);
    $title = Title::query()->create(['preferred_title' => 'Offen', 'sort_title' => 'Offen']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    $copy = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'TR-002', 'status' => CopyStatus::Active]);
    app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $actor);

    expect(fn () => app(DepartPatronAction::class)->execute($patron, '2027-07-20', $actor))
        ->toThrow(PatronStatusStateConflict::class, 'ausgeliehen');
});

it('serves the preview page and commits through the administration surface', function (): void {
    $set = transitionYears();
    $admin = transitionAdmin();
    transitionPatron('S-1', $set['classes']['from5a']);

    $this->actingAs($admin)
        ->get(route('administration.transition.show'))
        ->assertOk()
        ->assertSee('2026/27 → 2027/28')
        ->assertSee('5a');

    $mapping = [(string) $set['classes']['from5a']->getKey() => (string) $set['classes']['to6a']->getKey()];

    $this->actingAs($admin)
        ->post(route('administration.transition.commit'), ['from_id' => $set['from']->getKey(), 'target_id' => $set['to']->getKey(), 'mapping' => $mapping])
        ->assertSessionHasErrors('confirm');

    expect($set['to']->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->post(route('administration.transition.commit'), ['from_id' => $set['from']->getKey(), 'target_id' => $set['to']->getKey(), 'mapping' => $mapping, 'confirm' => '1'])
        ->assertRedirect(route('administration.school.index'))
        ->assertSessionHas('school_success');

    expect($set['to']->fresh()->is_active)->toBeTrue();
});

it('keeps the transition away from users without school management rights', function (string $role): void {
    transitionYears();

    $this->actingAs(transitionAdmin($role))->get(route('administration.transition.show'))->assertForbidden();
    $this->actingAs(transitionAdmin($role))->post(route('administration.transition.commit'), [])->assertForbidden();
})->with(['staff', 'technical_admin', 'student']);

it('offers only school years that start after the running one as target', function (): void {
    $set = transitionYears();
    SchoolYear::query()->create(['name' => '2025/26', 'starts_on' => '2025-08-01', 'ends_on' => '2026-07-31', 'is_active' => false]);

    $this->actingAs(transitionAdmin())
        ->get(route('administration.transition.show'))
        ->assertOk()
        ->assertSee('2026/27 → 2027/28')
        ->assertDontSee('2025/26');
});
