<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
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

function t4CirculationActor(string $role = 'student_ag_basic'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function t4CirculationPatron(array $overrides = []): Patron
{
    return Patron::query()->create(array_merge([
        'library_number' => 'S-T4-10001',
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'T4',
        'last_name' => 'Leserin',
        'birth_date' => '2010-01-01',
        'email' => null,
    ], $overrides));
}

function t4CirculationCopy(
    string $barcode = 'T4-COPY-001',
    ?int $minimumAge = null,
    CopyStatus $status = CopyStatus::Active,
    string $titleName = 'T4 Testtitel',
): Copy {
    $title = Title::query()->create([
        'preferred_title' => $titleName,
        'sort_title' => $titleName,
    ]);

    $edition = Edition::query()->create([
        'title_id' => $title->getKey(),
        'edition_statement' => 'Testausgabe',
        'minimum_age' => $minimumAge,
        'media_type' => 'book',
        'language_code' => 'de',
    ]);

    return Copy::query()->create([
        'edition_id' => $edition->getKey(),
        'barcode' => $barcode,
        'status' => $status,
        'shelf_location' => 'T4 TEST',
    ]);
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

it('lets student AG users checkout by barcode and shows the open loan in the patron workspace', function (): void {
    $patron = t4CirculationPatron();
    $copy = t4CirculationCopy();
    $actor = t4CirculationActor();

    $this->actingAs($actor)
        ->post(route('pos.circulation.checkout', ['patronId' => $patron->getKey()]), [
            'barcode' => '  '.$copy->barcode.'  ',
        ])
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]));

    $loan = Loan::query()->where('copy_id', $copy->getKey())->firstOrFail();

    expect($loan->patron_id)->toBe((string) $patron->getKey())
        ->and($loan->checked_out_by_user_id)->toBe($actor->getKey())
        ->and($loan->due_on->toDateString())->toBe('2026-10-19')
        ->and($loan->returned_at)->toBeNull();

    $this->actingAs($actor)
        ->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertOk()
        ->assertSee('Ausleihe und Rückgabe')
        ->assertSee('T4 Testtitel')
        ->assertSee('T4-COPY-001')
        ->assertSee('19.10.2026');
});

it('returns an open loan while preserving the record and hiding completed reading history from the workspace', function (): void {
    $patron = t4CirculationPatron();
    $copy = t4CirculationCopy();
    $actor = t4CirculationActor('staff');

    $loan = app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $actor);

    $this->actingAs($actor)
        ->post(route('pos.circulation.return', [
            'patronId' => $patron->getKey(),
            'loanId' => $loan->getKey(),
        ]))
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]));

    $loan->refresh();

    expect($loan->returned_at)->not->toBeNull()
        ->and($loan->returned_by_user_id)->toBe($actor->getKey())
        ->and(Loan::query()->count())->toBe(1);

    $this->actingAs($actor)
        ->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertOk()
        ->assertDontSee('T4 Testtitel')
        ->assertDontSee('T4-COPY-001')
        ->assertSee('Derzeit sind keine Exemplare auf dieses Ausleihkonto ausgeliehen.');
});

it('keeps students teachers and technical administration out of circulation routes', function (string $role): void {
    $patron = t4CirculationPatron();
    $copy = t4CirculationCopy();

    $this->actingAs(t4CirculationActor($role))
        ->post(route('pos.circulation.checkout', ['patronId' => $patron->getKey()]), [
            'barcode' => $copy->barcode,
        ])
        ->assertForbidden();

    expect(Loan::query()->count())->toBe(0);
})->with(['student', 'teacher', 'technical_admin']);

it('blocks checkout for patron states that are not allowed to borrow', function (array $overrides, string $message): void {
    $patron = t4CirculationPatron($overrides);
    $copy = t4CirculationCopy();
    $actor = t4CirculationActor('staff');

    expect(fn () => app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $actor))
        ->toThrow(CirculationRuleViolation::class, $message);

    expect(Loan::query()->count())->toBe(0);
})->with([
    'blocked' => [['blocked_at' => '2026-10-01 08:00:00', 'blocked_reason' => 'Test'], 'gesperrt'],
    'departed' => [['status' => PatronStatus::Departed], 'nicht aktiv'],
    'archived' => [['status' => PatronStatus::Archived], 'nicht aktiv'],
]);

it('blocks checkout for non active copy states', function (CopyStatus $status): void {
    $patron = t4CirculationPatron();
    $copy = t4CirculationCopy(status: $status);
    $actor = t4CirculationActor('staff');

    expect(fn () => app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $actor))
        ->toThrow(CirculationRuleViolation::class, 'Exemplarstatus');

    expect(Loan::query()->count())->toBe(0);
})->with([
    CopyStatus::Damaged,
    CopyStatus::Lost,
    CopyStatus::Withdrawn,
]);

it('allows checkout on the minimum-age birthday and blocks the day before eligibility', function (): void {
    $actor = t4CirculationActor('staff');

    $eligiblePatron = t4CirculationPatron([
        'library_number' => 'S-T4-AGE-OK',
        'birth_date' => '2012-10-05',
    ]);
    $eligibleCopy = t4CirculationCopy('T4-AGE-OK', 14, titleName: 'Altersfreigabe erlaubt');

    $loan = app(CheckoutCopyAction::class)->execute($eligiblePatron, $eligibleCopy->barcode, $actor);

    expect($loan->returned_at)->toBeNull();

    $youngPatron = t4CirculationPatron([
        'library_number' => 'S-T4-AGE-NO',
        'birth_date' => '2012-10-06',
    ]);
    $youngCopy = t4CirculationCopy('T4-AGE-NO', 14, titleName: 'Altersfreigabe blockiert');

    expect(fn () => app(CheckoutCopyAction::class)->execute($youngPatron, $youngCopy->barcode, $actor))
        ->toThrow(CirculationRuleViolation::class, 'Mindestalter von 14 Jahren');
});

it('prevents a second open loan for the same physical copy', function (): void {
    $firstPatron = t4CirculationPatron(['library_number' => 'S-T4-FIRST']);
    $secondPatron = t4CirculationPatron(['library_number' => 'S-T4-SECOND']);
    $copy = t4CirculationCopy();
    $actor = t4CirculationActor('staff');

    app(CheckoutCopyAction::class)->execute($firstPatron, $copy->barcode, $actor);

    expect(fn () => app(CheckoutCopyAction::class)->execute($secondPatron, $copy->barcode, $actor))
        ->toThrow(CirculationRuleViolation::class, 'bereits ausgeliehen');

    expect(Loan::query()->whereNull('returned_at')->count())->toBe(1);
});

it('moves a due date on library closures to the next real opening day', function (): void {
    LibraryClosure::query()->create([
        'date' => '2026-10-19',
        'reason' => 'Herbstferien',
    ]);
    LibraryClosure::query()->create([
        'date' => '2026-10-20',
        'reason' => 'Herbstferien',
    ]);

    $patron = t4CirculationPatron();
    $copy = t4CirculationCopy();
    $actor = t4CirculationActor('staff');

    $loan = app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $actor);

    expect($loan->due_on->toDateString())->toBe('2026-10-21');
});

it('allows a physical copy to be borrowed again after return while retaining both loan records', function (): void {
    $firstPatron = t4CirculationPatron(['library_number' => 'S-T4-RELOAN-1']);
    $secondPatron = t4CirculationPatron(['library_number' => 'S-T4-RELOAN-2']);
    $copy = t4CirculationCopy();
    $actor = t4CirculationActor('staff');

    $firstLoan = app(CheckoutCopyAction::class)->execute($firstPatron, $copy->barcode, $actor);
    app(ReturnLoanAction::class)->execute($firstLoan, $actor);
    $secondLoan = app(CheckoutCopyAction::class)->execute($secondPatron, $copy->barcode, $actor);

    expect(Loan::query()->count())->toBe(2)
        ->and(Loan::query()->whereNull('returned_at')->count())->toBe(1)
        ->and($secondLoan->patron_id)->toBe((string) $secondPatron->getKey());
});

it('does not reset a catalog copy state when an open loan is returned', function (): void {
    $patron = t4CirculationPatron();
    $copy = t4CirculationCopy();
    $actor = t4CirculationActor('staff');

    $loan = app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $actor);

    $copy->forceFill(['status' => CopyStatus::Damaged])->save();

    app(ReturnLoanAction::class)->execute($loan, $actor);

    expect($copy->fresh()->status)->toBe(CopyStatus::Damaged);
});

it('rejects duplicate return attempts as a state conflict', function (): void {
    $patron = t4CirculationPatron();
    $copy = t4CirculationCopy();
    $actor = t4CirculationActor('staff');

    $loan = app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $actor);
    app(ReturnLoanAction::class)->execute($loan, $actor);

    expect(fn () => app(ReturnLoanAction::class)->execute($loan, $actor))
        ->toThrow(LoanStateConflict::class, 'bereits zurückgegeben');
});

it('rejects unknown and empty barcodes without creating a loan', function (): void {
    $patron = t4CirculationPatron();
    $actor = t4CirculationActor('staff');

    expect(fn () => app(CheckoutCopyAction::class)->execute($patron, 'UNKNOWN-T4', $actor))
        ->toThrow(CirculationRuleViolation::class, 'Kein Exemplar');

    $this->actingAs($actor)
        ->post(route('pos.circulation.checkout', ['patronId' => $patron->getKey()]), [
            'barcode' => '   ',
        ])
        ->assertSessionHasErrors('barcode');

    expect(Loan::query()->count())->toBe(0);
});

it('does not allow returning a loan through another patron route', function (): void {
    $owner = t4CirculationPatron(['library_number' => 'S-T4-OWNER']);
    $other = t4CirculationPatron(['library_number' => 'S-T4-OTHER']);
    $copy = t4CirculationCopy();
    $actor = t4CirculationActor('staff');

    $loan = app(CheckoutCopyAction::class)->execute($owner, $copy->barcode, $actor);

    $this->actingAs($actor)
        ->post(route('pos.circulation.return', [
            'patronId' => $other->getKey(),
            'loanId' => $loan->getKey(),
        ]))
        ->assertNotFound();

    expect($loan->fresh()->returned_at)->toBeNull();
});

it('keeps the circulation migration rollback capable', function (): void {
    expect(Schema::hasTable('circulation_loans'))->toBeTrue();

    $migration = require app_path('Modules/Circulation/database/migrations/2026_10_05_004000_create_circulation_loans_table.php');

    $migration->down();

    expect(Schema::hasTable('circulation_loans'))->toBeFalse();

    $migration->up();

    expect(Schema::hasTable('circulation_loans'))->toBeTrue();
});
