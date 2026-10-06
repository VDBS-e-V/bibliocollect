<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CancelReservationAction;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Actions\RenewLoanAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function auditUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function auditPatron(string $number): Patron
{
    return Patron::query()->create([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Protokoll',
        'last_name' => 'Geheimname',
        'birth_date' => '2010-01-01',
    ]);
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:00:00', 'Europe/Berlin'));

    foreach (range(1, 5) as $dayOfWeek) {
        LibraryOpeningHour::query()->create(['day_of_week' => $dayOfWeek, 'is_open' => true, 'opens_at' => '09:00', 'closes_at' => '15:00']);
    }
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('records the circulation lifecycle without personal names', function (): void {
    $actor = auditUser('staff');
    $title = Title::query()->create(['preferred_title' => 'Auditbuch', 'sort_title' => 'Auditbuch']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book', 'isbn' => '9783000000003']);
    $copy = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'AUD-001', 'status' => CopyStatus::Active]);
    $holder = auditPatron('S-AU-1');
    $waiter = auditPatron('S-AU-2');

    $loan = app(CheckoutCopyAction::class)->execute($holder, $copy->barcode, $actor);
    app(RenewLoanAction::class)->execute($loan, $actor);
    $reservation = app(PlaceReservationAction::class)->execute($waiter, $copy->barcode, $actor);
    app(ReturnLoanAction::class)->execute($loan, $actor);
    app(CancelReservationAction::class)->execute($reservation, $actor);

    $actions = AuditEvent::query()->orderBy('occurred_at')->orderBy('id')->pluck('action')->all();

    expect($actions)->toContain(
        'circulation.loan.checked_out',
        'circulation.loan.renewed',
        'circulation.reservation.placed',
        'circulation.loan.returned',
        'circulation.reservation.ready',
        'circulation.reservation.cancelled',
    );

    $checkout = AuditEvent::query()->where('action', 'circulation.loan.checked_out')->firstOrFail();

    expect($checkout->actor_user_id)->toBe($actor->getKey())
        ->and($checkout->subject_type)->toBe('Loan')
        ->and($checkout->subject_id)->toBe((string) $loan->getKey())
        ->and($checkout->summary)->toContain('AUD-001')
        ->and(json_encode(AuditEvent::query()->get()->toArray()))->not->toContain('Geheimname');
});

it('rolls the audit event back together with a failed circulation write', function (): void {
    $actor = auditUser('staff');
    $patron = auditPatron('S-AU-3');

    expect(fn () => app(CheckoutCopyAction::class)->execute($patron, 'UNBEKANNT', $actor))->toThrow(Exception::class);
    expect(AuditEvent::query()->count())->toBe(0);
});

it('shows the protocol to management only', function (): void {
    AuditEvent::query()->create([
        'occurred_at' => now(),
        'action' => 'circulation.loan.checked_out',
        'summary' => 'Exemplar XYZ-9 ausgeliehen.',
    ]);
    AuditEvent::query()->create([
        'occurred_at' => now(),
        'action' => 'school.closures.created',
        'summary' => '3 Schließtag(e) eingetragen.',
    ]);

    $this->actingAs(auditUser('management'))
        ->get(route('administration.audit.index'))
        ->assertOk()
        ->assertSee('XYZ-9')
        ->assertSee('Schließtag');

    $this->actingAs(auditUser('management'))
        ->get(route('administration.audit.index', ['bereich' => 'school']))
        ->assertOk()
        ->assertDontSee('XYZ-9')
        ->assertSee('Schließtag');

    foreach (['staff', 'technical_admin', 'student_ag_extended', 'student'] as $role) {
        $this->actingAs(auditUser($role))
            ->get(route('administration.audit.index'))
            ->assertForbidden();
    }
});
