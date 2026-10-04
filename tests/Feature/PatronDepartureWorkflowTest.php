<?php

declare(strict_types=1);

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Actions\IssuePatronLinkCodeAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronAccountLinkToken;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function departureUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function departurePatron(): Patron
{
    $year = SchoolYear::query()->create([
        'name' => '2026/27',
        'starts_on' => '2026-08-01',
        'ends_on' => '2027-07-31',
        'is_active' => true,
    ]);

    $class = SchoolClass::query()->create([
        'school_year_id' => $year->getKey(),
        'name' => '9b',
        'grade_level' => 9,
        'is_active' => true,
    ]);

    return Patron::query()->create([
        'library_number' => 'S-64001',
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Sam',
        'last_name' => 'Austritt',
        'birth_date' => '2011-04-12',
        'school_class_id' => $class->getKey(),
    ]);
}

it('marks a patron as departed, revokes open link codes and disables the linked online account', function (): void {
    $staff = departureUser('staff');
    $patron = departurePatron();
    app(IssuePatronLinkCodeAction::class)->execute($patron, $staff);

    $linkedUser = User::factory()->create([
        'email' => 'sam.departure@example.test',
        'email_verified_at' => now(),
        'patron_id' => $patron->getKey(),
    ]);
    app(AssignRoleAction::class)->execute($linkedUser, 'student');
    app(AssignRoleAction::class)->execute($linkedUser, 'student_ag_basic');

    $effectiveOn = app(BusinessClock::class)->now()->toDateString();

    $this->actingAs($staff)
        ->post(route('pos.patrons.departure.store', ['patronId' => $patron->getKey()]), [
            'leaving_on' => $effectiveOn,
            'confirm_departure' => '1',
        ])
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]));

    $patron->refresh();
    $linkedUser->refresh();
    $token = PatronAccountLinkToken::query()->where('patron_id', $patron->getKey())->firstOrFail();

    expect($patron->status)->toBe(PatronStatus::Departed)
        ->and($patron->school_class_id)->toBeNull()
        ->and($patron->leaving_on?->toDateString())->toBe($effectiveOn)
        ->and($token->revoked_at)->not->toBeNull()
        ->and($linkedUser->disabled_at)->not->toBeNull()
        ->and($linkedUser->disabled_reason)->toBe('patron_departed')
        ->and($linkedUser->allowsPermission('surface.portal.access'))->toBeFalse();

    $this->assertDatabaseHas('patron_status_events', [
        'patron_id' => $patron->getKey(),
        'from_status' => 'active',
        'to_status' => 'departed',
        'actor_user_id' => $staff->getKey(),
    ]);

    $storedEffectiveOn = (string) DB::table('patron_status_events')
        ->where('patron_id', $patron->getKey())
        ->where('to_status', 'departed')
        ->value('effective_on');

    expect(substr($storedEffectiveOn, 0, 10))->toBe($effectiveOn);

    $this->actingAs($linkedUser)->get('/konto')->assertForbidden();
});

it('requires explicit confirmation and rejects future departure dates', function (): void {
    $staff = departureUser('staff');
    $patron = departurePatron();
    $tomorrow = app(BusinessClock::class)->now()->addDay()->toDateString();

    $this->actingAs($staff)
        ->post(route('pos.patrons.departure.store', ['patronId' => $patron->getKey()]), [
            'leaving_on' => $tomorrow,
        ])
        ->assertSessionHasErrors(['leaving_on', 'confirm_departure']);

    expect($patron->fresh()->status)->toBe(PatronStatus::Active);
});

it('keeps student AG roles out of permanent departure decisions', function (): void {
    $agUser = departureUser('student_ag_extended');
    $patron = departurePatron();

    $this->actingAs($agUser)
        ->post(route('pos.patrons.departure.store', ['patronId' => $patron->getKey()]), [
            'leaving_on' => app(BusinessClock::class)->now()->toDateString(),
            'confirm_departure' => '1',
        ])
        ->assertForbidden();

    expect($patron->fresh()->status)->toBe(PatronStatus::Active);
});

it('does not allow AG role changes after a patron has departed', function (): void {
    $staff = departureUser('staff');
    $patron = departurePatron();
    $target = User::factory()->create([
        'email_verified_at' => now(),
        'patron_id' => $patron->getKey(),
    ]);
    app(AssignRoleAction::class)->execute($target, 'student');

    $patron->forceFill([
        'status' => PatronStatus::Departed,
        'school_class_id' => null,
    ])->save();

    $this->actingAs($staff)
        ->put(route('pos.patrons.ag-roles.store', [
            'patronId' => $patron->getKey(),
            'roleKey' => 'student_ag_basic',
        ]))
        ->assertRedirect()
        ->assertSessionHas('workspace_error');

    expect($target->fresh()->roleKeys())->not->toContain('student_ag_basic');
});

it('rejects login attempts for disabled online accounts', function (): void {
    $user = User::factory()->create([
        'email' => 'disabled@example.test',
        'email_verified_at' => now(),
    ]);
    $user->forceFill([
        'disabled_at' => now(),
        'disabled_reason' => 'patron_departed',
    ])->save();

    $this->post(route('login.store'), [
        'email' => 'disabled@example.test',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
