<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\LoanTransaction;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronBlockEvent;
use App\Modules\Reminders\Models\ReminderLog;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function privacyPatron(string $number, array $overrides = []): Patron
{
    return Patron::query()->create(array_merge([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Datenschutz',
        'last_name' => 'Person'.$number,
        'birth_date' => '2012-05-17',
        'email' => $number.'@example.invalid',
    ], $overrides));
}

function privacyCopy(): Copy
{
    $title = Title::query()->create(['preferred_title' => 'Datenschutzbuch', 'sort_title' => 'Datenschutzbuch']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'PV-'.uniqid(), 'status' => CopyStatus::Active]);
}

function privacyLoan(Patron $patron, ?string $returnedAt, ?User $actor = null): Loan
{
    return Loan::query()->create([
        'patron_id' => $patron->getKey(),
        'copy_id' => privacyCopy()->getKey(),
        'checked_out_at' => '2020-01-01 10:00:00',
        'due_on' => '2020-01-15',
        'returned_at' => $returnedAt,
        'checked_out_by_user_id' => $actor?->getKey(),
        'returned_by_user_id' => $returnedAt !== null ? $actor?->getKey() : null,
    ]);
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-06-01 10:00:00', 'Europe/Berlin'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('anonymizes closed loans after three years but keeps recent and open ones', function (): void {
    $staff = User::factory()->create();
    $patron = privacyPatron('S-PV-1');

    $old = privacyLoan($patron, '2027-05-31 12:00:00', $staff);   // älter als 3 Jahre
    $recent = privacyLoan($patron, '2027-06-02 12:00:00', $staff); // noch innerhalb der Frist
    $open = privacyLoan($patron, null, $staff);                    // offen

    $this->artisan('privacy:anonymize')->assertSuccessful();

    expect($old->fresh()->patron_id)->toBeNull()
        ->and($old->fresh()->checked_out_by_user_id)->toBeNull()
        ->and($old->fresh()->returned_by_user_id)->toBeNull()
        ->and($old->fresh()->copy_id)->not->toBeNull()
        ->and($recent->fresh()->patron_id)->toBe((string) $patron->getKey())
        ->and($open->fresh()->patron_id)->toBe((string) $patron->getKey());
});

it('counts without changing anything on a dry run', function (): void {
    $patron = privacyPatron('S-PV-1');
    $old = privacyLoan($patron, '2026-01-01 12:00:00');

    $this->artisan('privacy:anonymize', ['--dry-run' => true])->assertSuccessful();

    expect($old->fresh()->patron_id)->toBe((string) $patron->getKey())
        ->and(AuditEvent::query()->where('action', 'privacy.anonymization.run')->count())->toBe(0);
});

it('anonymizes closed reservations only', function (): void {
    $patron = privacyPatron('S-PV-1');
    $title = Title::query()->create(['preferred_title' => 'Vormerkung', 'sort_title' => 'Vormerkung']);

    $closed = Reservation::query()->create(['patron_id' => $patron->getKey(), 'title_id' => $title->getKey(), 'status' => ReservationStatus::Cancelled, 'requested_at' => '2026-01-01', 'closed_at' => '2026-02-01']);
    $open = Reservation::query()->create(['patron_id' => $patron->getKey(), 'title_id' => $title->getKey(), 'status' => ReservationStatus::Waiting, 'requested_at' => '2026-01-01']);

    $this->artisan('privacy:anonymize')->assertSuccessful();

    expect($closed->fresh()->patron_id)->toBeNull()
        ->and($open->fresh()->patron_id)->toBe((string) $patron->getKey());
});

it('anonymizes departed patrons and their online accounts after three years', function (): void {
    $departed = privacyPatron('S-PV-1', ['status' => PatronStatus::Departed, 'leaving_on' => '2026-06-30']);
    $recent = privacyPatron('S-PV-2', ['status' => PatronStatus::Departed, 'leaving_on' => '2028-06-30']);
    $active = privacyPatron('S-PV-3');
    $user = User::factory()->create(['patron_id' => $departed->getKey(), 'name' => 'Echter Name', 'email' => 'echt@example.invalid']);
    PatronBlockEvent::query()->create(['patron_id' => $departed->getKey(), 'action' => 'blocked', 'reason' => 'Geheimer Grund', 'created_at' => '2026-02-01']);

    $this->artisan('privacy:anonymize')->assertSuccessful();

    $departed->refresh();

    expect($departed->library_number)->toBe('ANON-'.$departed->getKey())
        ->and($departed->first_name)->toBe('Anonymisiert')
        ->and($departed->last_name)->toBe('Anonymisiert')
        ->and($departed->email)->toBeNull()
        ->and($departed->birth_date->toDateString())->toBe('2012-01-01')
        ->and($user->fresh()->name)->toBe('Anonymisiert')
        ->and($user->fresh()->email)->toContain('@anonym.invalid')
        ->and($user->fresh()->patron_id)->toBeNull()
        ->and(PatronBlockEvent::query()->first()->reason)->toBeNull()
        ->and($recent->fresh()->first_name)->toBe('Datenschutz')
        ->and($active->fresh()->first_name)->toBe('Datenschutz');

    // Wiederholbar: Ein zweiter Lauf ändert nichts mehr.
    $this->artisan('privacy:anonymize')->assertSuccessful();
    expect($departed->fresh()->library_number)->toBe('ANON-'.$departed->getKey());
});

it('strips actors and patron references from old audit events and deletes old reminder logs', function (): void {
    $actor = User::factory()->create();

    $old = AuditEvent::query()->create(['occurred_at' => '2026-01-01 10:00:00', 'actor_user_id' => $actor->getKey(), 'action' => 'circulation.loan.checked_out', 'summary' => 'x', 'context' => ['patron_id' => 'P1', 'copy_id' => 'C1']]);
    $recent = AuditEvent::query()->create(['occurred_at' => '2029-01-01 10:00:00', 'actor_user_id' => $actor->getKey(), 'action' => 'circulation.loan.returned', 'summary' => 'y', 'context' => ['patron_id' => 'P2']]);
    ReminderLog::query()->create(['user_id' => $actor->getKey(), 'kind' => 'loan_due_soon', 'subject_id' => 'L1', 'stage' => '', 'sent_at' => '2026-01-01']);
    ReminderLog::query()->create(['user_id' => $actor->getKey(), 'kind' => 'loan_due_soon', 'subject_id' => 'L2', 'stage' => '', 'sent_at' => '2029-12-01']);

    $this->artisan('privacy:anonymize')->assertSuccessful();

    expect($old->fresh()->actor_user_id)->toBeNull()
        ->and($old->fresh()->context)->toBe(['copy_id' => 'C1'])
        ->and($recent->fresh()->actor_user_id)->toBe($actor->getKey())
        ->and($recent->fresh()->context)->toBe(['patron_id' => 'P2'])
        ->and(ReminderLog::query()->count())->toBe(1);

    $this->artisan('privacy:anonymize')->assertSuccessful();

    expect(AuditEvent::query()->where('action', 'privacy.anonymization.run')->count())->toBe(1);
});

it('strips person, actor and email address from old receipts', function (): void {
    $patron = privacyPatron('S-PV-9');
    $staff = User::factory()->create();

    $old = LoanTransaction::query()->create([
        'number' => 'V-20260101-001', 'patron_id' => $patron->getKey(), 'created_by_user_id' => $staff->getKey(),
        'items' => [['type' => 'return', 'title' => 'Buch', 'barcode' => 'X-1', 'returned_on' => '2026-01-01']],
        'returned_count' => 1, 'emailed_to' => 'eltern@example.org',
    ]);
    $old->forceFill(['created_at' => '2026-01-01 10:00:00'])->save();

    $recent = LoanTransaction::query()->create([
        'number' => 'V-20300101-001', 'patron_id' => $patron->getKey(), 'created_by_user_id' => $staff->getKey(),
        'items' => [], 'emailed_to' => 'eltern@example.org',
    ]);

    $this->artisan('privacy:anonymize')->assertSuccessful();

    expect($old->fresh()->patron_id)->toBeNull()
        ->and($old->fresh()->emailed_to)->toBeNull()
        ->and($old->fresh()->created_by_user_id)->toBeNull()
        ->and($old->fresh()->items)->toHaveCount(1)
        ->and($recent->fresh()->emailed_to)->toBe('eltern@example.org');
});
