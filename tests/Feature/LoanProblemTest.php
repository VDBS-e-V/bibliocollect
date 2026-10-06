<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\ReportLoanProblemAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Services\CopyAvailabilityService;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function problemUser(string $role = 'staff'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function problemSetup(): array
{
    $patron = Patron::query()->create([
        'library_number' => 'S-PR-1',
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Problem',
        'last_name' => 'Person',
        'birth_date' => '2012-01-01',
    ]);

    $title = Title::query()->create(['preferred_title' => 'Problembuch', 'sort_title' => 'Problembuch']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    $copy = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'PR-001', 'status' => CopyStatus::Active]);
    $staff = problemUser();
    $loan = app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $staff);

    return [$patron, $copy, $loan, $staff, $title];
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:00:00', 'Europe/Berlin'));

    foreach (range(1, 5) as $dayOfWeek) {
        LibraryOpeningHour::query()->create(['day_of_week' => $dayOfWeek, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '15:00']);
    }
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('ends a loan as lost, takes the copy out of circulation and frees the patron', function (): void {
    [$patron, $copy, $loan, $staff, $title] = problemSetup();

    $result = app(ReportLoanProblemAction::class)->execute($loan, 'lost', $staff);

    expect($result->returned_at)->not->toBeNull()
        ->and($result->outcome)->toBe('lost')
        ->and($copy->fresh()->status)->toBe(CopyStatus::Lost)
        ->and(Loan::query()->where('patron_id', $patron->getKey())->whereNull('returned_at')->count())->toBe(0)
        ->and(app(CopyAvailabilityService::class)->forTitles([(string) $title->getKey()])[(string) $title->getKey()]->hasActiveCopies())->toBeFalse()
        ->and(AuditEvent::query()->where('action', 'circulation.loan.lost')->count())->toBe(1);

    // Das Exemplar ist nicht mehr ausleihbar.
    expect(fn () => app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $staff))
        ->toThrow(CirculationRuleViolation::class, 'Exemplarstatus');
});

it('takes a damaged copy back and marks it damaged', function (): void {
    [, $copy, $loan, $staff] = problemSetup();

    $result = app(ReportLoanProblemAction::class)->execute($loan, 'damaged', $staff);

    expect($result->outcome)->toBe('damaged')
        ->and($copy->fresh()->status)->toBe(CopyStatus::Damaged)
        ->and(AuditEvent::query()->where('action', 'circulation.loan.damaged')->count())->toBe(1);
});

it('refuses problems on closed loans or unknown problem types', function (): void {
    [, , $loan, $staff] = problemSetup();

    expect(fn () => app(ReportLoanProblemAction::class)->execute($loan, 'kaputt', $staff))->toThrow(InvalidArgumentException::class);

    app(ReturnLoanAction::class)->execute($loan, $staff);

    expect($loan->fresh()->outcome)->toBe('returned')
        ->and(fn () => app(ReportLoanProblemAction::class)->execute($loan, 'lost', $staff))->toThrow(LoanStateConflict::class);
});

it('reports problems from the patron workspace', function (): void {
    [$patron, $copy, $loan, $staff] = problemSetup();

    $this->actingAs($staff)->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertOk()
        ->assertSee('Problem melden')
        ->assertSee('Als verloren melden');

    $this->actingAs($staff)->post(route('pos.circulation.problem', ['patronId' => $patron->getKey(), 'loanId' => $loan->getKey()]), ['problem' => 'bogus'])
        ->assertSessionHasErrors('problem');

    $this->actingAs($staff)->post(route('pos.circulation.problem', ['patronId' => $patron->getKey(), 'loanId' => $loan->getKey()]), ['problem' => 'lost'])
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertSessionHas('workspace_success', static fn (string $message): bool => str_contains($message, 'als verloren gemeldet'));

    expect($copy->fresh()->status)->toBe(CopyStatus::Lost);
});

it('keeps problem reports away from roles without circulation rights', function (string $role): void {
    [$patron, , $loan] = problemSetup();

    $this->actingAs(problemUser($role))
        ->post(route('pos.circulation.problem', ['patronId' => $patron->getKey(), 'loanId' => $loan->getKey()]), ['problem' => 'lost'])
        ->assertForbidden();
})->with(['technical_admin', 'student', 'teacher']);
