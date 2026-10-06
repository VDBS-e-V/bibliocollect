<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\RenewLoanAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Services\ReservationBlockChecker;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function renewalActor(string $role = 'student_ag_basic'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** @return array{0: Patron, 1: Copy, 2: Loan, 3: User} */
function renewalLoan(): array
{
    $patron = Patron::query()->create([
        'library_number' => 'S-RN-10001',
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Verlängerung',
        'last_name' => 'Leserin',
        'birth_date' => '2010-01-01',
    ]);

    $title = Title::query()->create(['preferred_title' => 'Verlängerungsbuch', 'sort_title' => 'Verlängerungsbuch']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    $copy = Copy::query()->create([
        'edition_id' => $edition->getKey(),
        'barcode' => 'RN-COPY-001',
        'status' => CopyStatus::Active,
    ]);

    $actor = renewalActor();
    $loan = app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $actor);

    return [$patron, $copy, $loan, $actor];
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:00:00', 'Europe/Berlin'));

    foreach (range(1, 5) as $dayOfWeek) {
        LibraryOpeningHour::query()->create([
            'day_of_week' => $dayOfWeek,
            'is_open' => true,
            'opens_at' => '09:00',
            'closes_at' => '15:00',
        ]);
    }
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('renews an open loan from its current due date and counts the renewal', function (): void {
    [, , $loan, $actor] = renewalLoan();

    expect($loan->due_on->toDateString())->toBe('2026-10-19');

    $renewed = app(RenewLoanAction::class)->execute($loan, $actor);

    expect($renewed->due_on->toDateString())->toBe('2026-11-02')
        ->and($renewed->renewal_count)->toBe(1)
        ->and($renewed->last_renewed_by_user_id)->toBe($actor->getKey())
        ->and($renewed->last_renewed_at)->not->toBeNull();
});

it('moves a renewed due date off a closing day', function (): void {
    [, , $loan, $actor] = renewalLoan();

    LibraryClosure::query()->create([
        'date' => '2026-11-02',
        'reason' => 'Studientag',
    ]);

    $renewed = app(RenewLoanAction::class)->execute($loan, $actor);

    expect($renewed->due_on->toDateString())->toBe('2026-11-03');
});

it('limits the number of renewals', function (): void {
    [, , $loan, $actor] = renewalLoan();
    $action = app(RenewLoanAction::class);

    $action->execute($loan, $actor);
    $action->execute($loan, $actor);

    expect(fn () => $action->execute($loan, $actor))->toThrow(CirculationRuleViolation::class, 'höchstens 2');
    expect($loan->fresh()->renewal_count)->toBe(2);
});

it('refuses overdue loans unless configured otherwise', function (): void {
    [, , $loan, $actor] = renewalLoan();
    $loan->forceFill(['due_on' => '2026-10-01'])->save();

    expect(fn () => app(RenewLoanAction::class)->execute($loan, $actor))
        ->toThrow(CirculationRuleViolation::class, 'überfällig');

    config(['circulation.allow_overdue_renewal' => true]);

    $renewed = app(RenewLoanAction::class)->execute($loan, $actor);

    // Die neue Frist zählt ab heute, nicht ab dem versäumten Datum.
    expect($renewed->due_on->toDateString())->toBe('2026-10-19');
});

it('refuses blocked patrons, inactive copies and returned loans', function (): void {
    [$patron, $copy, $loan, $actor] = renewalLoan();
    $action = app(RenewLoanAction::class);

    $patron->forceFill(['blocked_at' => now(), 'blocked_reason' => 'Test'])->save();
    expect(fn () => $action->execute($loan, $actor))->toThrow(CirculationRuleViolation::class, 'gesperrt');
    $patron->forceFill(['blocked_at' => null, 'blocked_reason' => null])->save();

    $copy->forceFill(['status' => CopyStatus::Lost])->save();
    expect(fn () => $action->execute($loan, $actor))->toThrow(CirculationRuleViolation::class, 'Exemplarstatus');
    $copy->forceFill(['status' => CopyStatus::Active])->save();

    app(ReturnLoanAction::class)->execute($loan, $actor);
    expect(fn () => $action->execute($loan, $actor))->toThrow(LoanStateConflict::class);
});

it('refuses renewal while a reservation blocks the title', function (): void {
    [, , $loan, $actor] = renewalLoan();

    app()->instance(ReservationBlockChecker::class, new class extends ReservationBlockChecker
    {
        public function blocksRenewal(Loan $loan, Copy $copy): bool
        {
            return true;
        }
    });

    expect(fn () => app(RenewLoanAction::class)->execute($loan, $actor))
        ->toThrow(CirculationRuleViolation::class, 'Vormerkung');
});

it('renews through the patron workspace and explains blocked renewals', function (): void {
    [$patron, , $loan, $actor] = renewalLoan();

    $this->actingAs($actor)
        ->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertOk()
        ->assertSee('Verlängern');

    $this->actingAs($actor)
        ->post(route('pos.circulation.renew', ['patronId' => $patron->getKey(), 'loanId' => $loan->getKey()]))
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertSessionHas('workspace_success');

    expect($loan->fresh()->renewal_count)->toBe(1);

    $loan->forceFill(['renewal_count' => 2])->save();

    $this->actingAs($actor)
        ->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertOk()
        ->assertSee('Keine Verlängerung')
        ->assertDontSee('>Verlängern<', false);

    $this->actingAs($actor)
        ->post(route('pos.circulation.renew', ['patronId' => $patron->getKey(), 'loanId' => $loan->getKey()]))
        ->assertSessionHas('workspace_error');
});

it('keeps renewals away from users without circulation rights', function (): void {
    [$patron, , $loan] = renewalLoan();

    $this->actingAs(renewalActor('technical_admin'))
        ->post(route('pos.circulation.renew', ['patronId' => $patron->getKey(), 'loanId' => $loan->getKey()]))
        ->assertForbidden();
});

it('keeps the renewal migration rollback capable', function (): void {
    $migration = require app_path('Modules/Circulation/database/migrations/2026_10_06_100000_add_renewals_to_circulation_loans.php');

    $migration->down();

    expect(Schema::hasColumn('circulation_loans', 'renewal_count'))->toBeFalse();

    $migration->up();

    expect(Schema::hasColumn('circulation_loans', 'renewal_count'))->toBeTrue()
        ->and(Schema::hasColumn('circulation_loans', 'last_renewed_by_user_id'))->toBeTrue();
});
