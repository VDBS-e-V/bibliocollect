<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Actions\RenewLoanAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function policyStaff(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, 'staff');

    return $user;
}

function policyPatron(string $number, PatronKind $kind = PatronKind::Student): Patron
{
    return Patron::query()->create([
        'library_number' => $number,
        'kind' => $kind,
        'status' => PatronStatus::Active,
        'first_name' => 'Regel',
        'last_name' => 'Person'.$number,
        'birth_date' => $kind === PatronKind::Student ? '2012-01-01' : '1980-01-01',
    ]);
}

function policyCopy(string $name, string $mediaType = 'book'): Copy
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => $mediaType]);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'LP-'.strtoupper(substr(md5($name), 0, 6)), 'status' => CopyStatus::Active]);
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

it('stops a student at the maximum number of simultaneous loans and frees the slot on return', function (): void {
    config(['circulation.max_open_loans' => ['default' => 2, 'by_kind' => []]]);
    $staff = policyStaff();
    $student = policyPatron('S-LP-1');
    $checkout = app(CheckoutCopyAction::class);

    $first = $checkout->execute($student, policyCopy('Eins')->barcode, $staff);
    $checkout->execute($student, policyCopy('Zwei')->barcode, $staff);

    expect(fn () => $checkout->execute($student, policyCopy('Drei')->barcode, $staff))
        ->toThrow(CirculationRuleViolation::class, 'höchstens 2 gleichzeitig');

    app(ReturnLoanAction::class)->execute($first, $staff);

    expect($checkout->execute($student, policyCopy('Vier')->barcode, $staff)->exists)->toBeTrue();
});

it('gives teachers and employees their own limit and a longer loan period', function (): void {
    config(['circulation.max_open_loans' => ['default' => 1, 'by_kind' => ['teacher' => 3]]]);
    $staff = policyStaff();
    $teacher = policyPatron('L-LP-1', PatronKind::Teacher);
    $student = policyPatron('S-LP-2');
    $checkout = app(CheckoutCopyAction::class);

    $teacherLoan = $checkout->execute($teacher, policyCopy('Lehrbuch Eins')->barcode, $staff);
    $checkout->execute($teacher, policyCopy('Lehrbuch Zwei')->barcode, $staff);
    $studentLoan = $checkout->execute($student, policyCopy('Schulbuch')->barcode, $staff);

    // 5.10. + 28 Tage = 2.11. (Montag); Schüler:in: 5.10. + 14 Tage = 19.10.
    expect($teacherLoan->due_on->toDateString())->toBe('2026-11-02')
        ->and($studentLoan->due_on->toDateString())->toBe('2026-10-19');

    expect(fn () => $checkout->execute($student, policyCopy('Noch eins')->barcode, $staff))
        ->toThrow(CirculationRuleViolation::class, 'höchstens 1 gleichzeitig');
});

it('lets the media type override the loan period and renews with the same period', function (): void {
    config(['circulation.loan_periods' => ['by_kind' => [], 'by_media_type' => ['audiobook' => 7]]]);
    $staff = policyStaff();
    $student = policyPatron('S-LP-1');

    $loan = app(CheckoutCopyAction::class)->execute($student, policyCopy('Hörbuch', 'audiobook')->barcode, $staff);

    expect($loan->due_on->toDateString())->toBe('2026-10-12');

    $renewed = app(RenewLoanAction::class)->execute($loan, $staff);

    expect($renewed->due_on->toDateString())->toBe('2026-10-19');
});

it('does not hold a returned copy for a waiting patron who is at the limit', function (): void {
    config(['circulation.max_open_loans' => ['default' => 1, 'by_kind' => []]]);
    $staff = policyStaff();
    $holder = policyPatron('S-LP-1');
    $waiter = policyPatron('S-LP-2');
    $copy = policyCopy('Begehrt');
    $other = policyCopy('Anderes');

    $loan = app(CheckoutCopyAction::class)->execute($holder, $copy->barcode, $staff);
    $reservation = app(PlaceReservationAction::class)->execute($waiter, $copy->barcode, $staff);
    app(CheckoutCopyAction::class)->execute($waiter, $other->barcode, $staff);

    app(ReturnLoanAction::class)->execute($loan, $staff);

    expect(Reservation::query()->find($reservation->getKey())->status)->toBe(ReservationStatus::Waiting);
});

it('treats zero as unlimited', function (): void {
    config(['circulation.max_open_loans' => ['default' => 0, 'by_kind' => []]]);
    $staff = policyStaff();
    $student = policyPatron('S-LP-1');

    foreach (range(1, 7) as $number) {
        app(CheckoutCopyAction::class)->execute($student, policyCopy('Buch '.$number)->barcode, $staff);
    }

    expect($student->fresh()->exists)->toBeTrue();
});
