<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function schoolManagementUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function schoolManagementYear(string $name, bool $active, string $startsOn, string $endsOn): SchoolYear
{
    return SchoolYear::query()->create([
        'name' => $name,
        'starts_on' => $startsOn,
        'ends_on' => $endsOn,
        'is_active' => $active,
    ]);
}

function schoolManagementClass(SchoolYear $year, string $name, int $gradeLevel, bool $active = true): SchoolClass
{
    return SchoolClass::query()->create([
        'school_year_id' => $year->getKey(),
        'name' => $name,
        'grade_level' => $gradeLevel,
        'is_active' => $active,
    ]);
}

it('lets management prepare a school year with classes and activate it after readiness checks', function (): void {
    $management = schoolManagementUser('management');
    $current = schoolManagementYear('2026/27', true, '2026-08-01', '2027-07-31');
    schoolManagementClass($current, '7a', 7);

    $this->actingAs($management)
        ->post(route('administration.school-years.store'), [
            'name' => '2027/28',
            'starts_on' => '2027-08-01',
            'ends_on' => '2028-07-31',
        ])
        ->assertRedirect();

    $target = SchoolYear::query()->where('name', '2027/28')->firstOrFail();

    expect($target->is_active)->toBeFalse();

    $this->actingAs($management)
        ->post(route('administration.school-classes.store', ['schoolYearId' => $target->getKey()]), [
            'name' => '8a',
            'grade_level' => 8,
            'is_active' => '1',
        ])
        ->assertRedirect();

    $this->actingAs($management)
        ->get(route('administration.school.index', ['target' => $target->getKey()]))
        ->assertOk()
        ->assertSee('Vorbereitung vollständig')
        ->assertSee('8a');

    $this->actingAs($management)
        ->post(route('administration.school-years.activate', ['schoolYearId' => $target->getKey()]), ['confirm_activation' => '1'])
        ->assertRedirect(route('administration.school.index'));

    expect($current->fresh()->is_active)->toBeFalse()
        ->and($target->fresh()->is_active)->toBeTrue();
});

it('refuses to activate a target year when a promoted grade is missing', function (): void {
    $management = schoolManagementUser('management');
    $current = schoolManagementYear('2026/27', true, '2026-08-01', '2027-07-31');
    schoolManagementClass($current, '7a', 7);

    $target = schoolManagementYear('2027/28', false, '2027-08-01', '2028-07-31');
    schoolManagementClass($target, '9a', 9);

    $this->actingAs($management)
        ->post(route('administration.school-years.activate', ['schoolYearId' => $target->getKey()]), ['confirm_activation' => '1'])
        ->assertRedirect(route('administration.school.index', ['target' => $target->getKey()]))
        ->assertSessionHas('school_error');

    expect($current->fresh()->is_active)->toBeTrue()
        ->and($target->fresh()->is_active)->toBeFalse();
});

it('keeps at least one active class in the active school year', function (): void {
    $management = schoolManagementUser('management');
    $year = schoolManagementYear('2026/27', true, '2026-08-01', '2027-07-31');
    $class = schoolManagementClass($year, '7a', 7);

    $this->actingAs($management)
        ->patch(route('administration.school-classes.update', ['schoolClassId' => $class->getKey()]), [
            'name' => '7a',
            'grade_level' => 7,
            'is_active' => '0',
        ])
        ->assertRedirect(route('administration.school.index', ['target' => $year->getKey()]))
        ->assertSessionHas('school_error');

    expect($class->fresh()->is_active)->toBeTrue();
});

it('keeps technical administration outside school governance', function (): void {
    $technicalAdmin = schoolManagementUser('technical_admin');

    $this->actingAs($technicalAdmin)
        ->get(route('administration.school.index'))
        ->assertForbidden();
});
