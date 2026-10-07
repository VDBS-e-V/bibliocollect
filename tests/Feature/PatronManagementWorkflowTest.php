<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function patronManagementUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function patronManagementClass(): SchoolClass
{
    $year = SchoolYear::query()->create([
        'name' => '2026/27',
        'starts_on' => '2026-08-01',
        'ends_on' => '2027-07-31',
        'is_active' => true,
    ]);

    return SchoolClass::query()->create([
        'school_year_id' => $year->getKey(),
        'name' => '7a',
        'grade_level' => 7,
        'is_active' => true,
    ]);
}

function patronManagementPatron(array $overrides = []): Patron
{
    return Patron::query()->create(array_merge([
        'library_number' => 'S-53001',
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Noa',
        'last_name' => 'Beispiel',
        'birth_date' => '2013-03-14',
        'email' => null,
    ], $overrides));
}

it('lets staff create a patron with mandatory birth date and current class assignment', function (): void {
    $staff = patronManagementUser('staff');
    $class = patronManagementClass();

    $response = $this->actingAs($staff)->post(route('pos.patrons.store'), [
        'library_number' => 'S-53010',
        'kind' => 'student',
        'first_name' => 'Lina',
        'last_name' => 'Neu',
        'birth_date' => '2014-02-03',
        'email' => '',
        'school_class_id' => $class->getKey(),
        'leaving_on' => '',
    ]);

    $patron = Patron::query()->where('library_number', 'S-53010')->firstOrFail();

    $response->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]));
    expect($patron->kind)->toBe(PatronKind::Student)
        ->and($patron->status)->toBe(PatronStatus::Active)
        ->and($patron->school_class_id)->toBe($class->getKey())
        ->and($patron->email)->toBeNull();
});

it('requires a full birth date when creating a patron', function (): void {
    $staff = patronManagementUser('staff');

    $this->actingAs($staff)->post(route('pos.patrons.store'), [
        'library_number' => 'S-53011',
        'kind' => 'student',
        'first_name' => 'Lina',
        'last_name' => 'Neu',
        'birth_date' => '',
    ])->assertSessionHasErrors('birth_date');

    $this->assertDatabaseMissing('patrons', ['library_number' => 'S-53011']);
});

it('does not persist a class assignment for non-student patrons', function (): void {
    $staff = patronManagementUser('staff');
    $class = patronManagementClass();

    $this->actingAs($staff)->post(route('pos.patrons.store'), [
        'library_number' => 'L-53012',
        'kind' => 'teacher',
        'first_name' => 'Mara',
        'last_name' => 'Lehrkraft',
        'birth_date' => '1988-01-02',
        'school_class_id' => $class->getKey(),
    ])->assertRedirect();

    $patron = Patron::query()->where('library_number', 'L-53012')->firstOrFail();
    expect($patron->school_class_id)->toBeNull();
});

it('lets staff update patron master data without changing patron kind or status', function (): void {
    $staff = patronManagementUser('staff');
    $class = patronManagementClass();
    $patron = patronManagementPatron();

    $this->actingAs($staff)->patch(route('pos.patrons.update', ['patronId' => $patron->getKey()]), [
        'library_number' => 'S-53001-A',
        'kind' => 'employee',
        'first_name' => 'Noa',
        'last_name' => 'Aktualisiert',
        'birth_date' => '2013-03-14',
        'email' => 'noa@example.test',
        'school_class_id' => $class->getKey(),
        'leaving_on' => '2027-07-31',
    ])->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]));

    $patron->refresh();

    expect($patron->library_number)->toBe('S-53001-A')
        ->and($patron->last_name)->toBe('Aktualisiert')
        ->and($patron->email)->toBe('noa@example.test')
        ->and($patron->school_class_id)->toBe($class->getKey())
        ->and($patron->kind)->toBe(PatronKind::Student)
        ->and($patron->status)->toBe(PatronStatus::Active);
});

it('keeps student AG roles out of patron master-data changes and blocking', function (): void {
    $agUser = patronManagementUser('student_ag_extended');
    $patron = patronManagementPatron();

    $this->actingAs($agUser)
        ->get(route('pos.patrons.create'))
        ->assertForbidden();

    $this->actingAs($agUser)
        ->get(route('pos.patrons.edit', ['patronId' => $patron->getKey()]))
        ->assertForbidden();

    $this->actingAs($agUser)
        ->post(route('pos.patrons.block.store', ['patronId' => $patron->getKey()]), ['reason' => 'Test'])
        ->assertForbidden();
});

it('blocks and unblocks patrons with an append-only actor trail', function (): void {
    $staff = patronManagementUser('staff');
    $patron = patronManagementPatron();

    $this->actingAs($staff)
        ->post(route('pos.patrons.block.store', ['patronId' => $patron->getKey()]), [
            'reason' => 'Offener Ersatzfall',
        ])
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]));

    $patron->refresh();
    expect($patron->blocked_at)->not->toBeNull()
        ->and($patron->blocked_reason)->toBe('Offener Ersatzfall');

    $this->assertDatabaseHas('patron_block_events', [
        'patron_id' => $patron->getKey(),
        'action' => 'blocked',
        'reason' => 'Offener Ersatzfall',
        'actor_user_id' => $staff->getKey(),
    ]);

    $this->actingAs($staff)
        ->delete(route('pos.patrons.block.destroy', ['patronId' => $patron->getKey()]))
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]));

    $patron->refresh();
    expect($patron->blocked_at)->toBeNull()
        ->and($patron->blocked_reason)->toBeNull();

    $this->assertDatabaseHas('patron_block_events', [
        'patron_id' => $patron->getKey(),
        'action' => 'unblocked',
        'reason' => 'Offener Ersatzfall',
        'actor_user_id' => $staff->getKey(),
    ]);
});

it('does not block departed patrons', function (): void {
    $staff = patronManagementUser('staff');
    $patron = patronManagementPatron(['status' => PatronStatus::Departed]);

    $this->actingAs($staff)
        ->post(route('pos.patrons.block.store', ['patronId' => $patron->getKey()]), [
            'reason' => 'Soll nicht greifen',
        ])
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertSessionHas('workspace_error');

    expect($patron->fresh()->blocked_at)->toBeNull();
    $this->assertDatabaseCount('patron_block_events', 0);
});

it('gives a new patron a random six-digit library number without a prefix when none is entered', function (): void {
    $staff = patronManagementUser('staff');
    $class = patronManagementClass();
    $numbers = [];

    foreach (['Eins', 'Zwei', 'Drei'] as $name) {
        $this->actingAs($staff)->post(route('pos.patrons.store'), [
            'library_number' => '',
            'kind' => 'student',
            'first_name' => $name,
            'last_name' => 'Zufall',
            'birth_date' => '2014-02-03',
            'email' => '',
            'school_class_id' => $class->getKey(),
            'leaving_on' => '',
        ])->assertSessionHasNoErrors();

        $numbers[] = Patron::query()->where('first_name', $name)->firstOrFail()->library_number;
    }

    foreach ($numbers as $number) {
        expect($number)->toMatch('/^[1-9]\d{5}$/');
    }

    expect(array_unique($numbers))->toHaveCount(3);
});

it('still accepts a library number typed by hand and keeps it unique', function (): void {
    $staff = patronManagementUser('staff');
    $class = patronManagementClass();
    patronManagementPatron(['library_number' => '123456']);

    $payload = ['kind' => 'student', 'first_name' => 'Hand', 'last_name' => 'Nummer', 'birth_date' => '2014-02-03', 'email' => '', 'school_class_id' => $class->getKey(), 'leaving_on' => ''];

    $this->actingAs($staff)->post(route('pos.patrons.store'), $payload + ['library_number' => '123456'])->assertSessionHasErrors('library_number');
    $this->actingAs($staff)->post(route('pos.patrons.store'), $payload + ['library_number' => '654321'])->assertSessionHasNoErrors();
});
