<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Reminders\Models\ReminderLog;
use App\Modules\Reminders\Notifications\LibraryReminder;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function reminderPatron(string $number, bool $withAccount = true, bool $verified = true): array
{
    $patron = Patron::query()->create([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Erinnerung',
        'last_name' => 'Leser'.$number,
        'birth_date' => '2010-01-01',
    ]);

    $user = $withAccount
        ? User::factory()->create(['patron_id' => $patron->getKey(), 'email_verified_at' => $verified ? now() : null])
        : null;

    return [$patron, $user];
}

function reminderCopy(string $name): Copy
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'RM-'.strtoupper(substr(md5($name), 0, 5)), 'status' => CopyStatus::Active]);
}

function reminderStaff(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, 'staff');

    return $user;
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:00:00', 'Europe/Berlin'));

    foreach (range(1, 5) as $dayOfWeek) {
        LibraryOpeningHour::query()->create(['day_of_week' => $dayOfWeek, 'is_open' => true, 'opens_at' => '09:00', 'closes_at' => '15:00']);
    }

    Notification::fake();
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('reminds shortly before the due date, only once', function (): void {
    [$patron, $user] = reminderPatron('S-RM-1');
    $loan = app(CheckoutCopyAction::class)->execute($patron, reminderCopy('Bald fällig')->barcode, reminderStaff());

    // Fällig am 19.10.: Zwei Tage vorher ist der 17.10., davor passiert nichts.
    $this->artisan('reminders:send')->expectsOutput('0 Erinnerung(en) verschickt.');
    Notification::assertNothingSent();

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-17 07:00:00', 'Europe/Berlin'));
    $this->artisan('reminders:send')->expectsOutput('1 Erinnerung(en) verschickt.');
    $this->artisan('reminders:send')->expectsOutput('0 Erinnerung(en) verschickt.');

    Notification::assertSentToTimes($user, LibraryReminder::class, 1);
    Notification::assertSentTo($user, LibraryReminder::class, static fn (LibraryReminder $n): bool => $n->kind === 'loan_due_soon' && $n->titleName === 'Bald fällig' && $n->date === '19.10.2026');

    expect(ReminderLog::query()->where('subject_id', (string) $loan->getKey())->count())->toBe(1);
});

it('repeats overdue reminders in weekly stages', function (): void {
    [$patron, $user] = reminderPatron('S-RM-1');
    app(CheckoutCopyAction::class)->execute($patron, reminderCopy('Überfällig')->barcode, reminderStaff());

    foreach (['2026-10-20', '2026-10-23', '2026-10-27', '2026-10-28'] as $day) {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($day.' 07:00:00', 'Europe/Berlin'));
        $this->artisan('reminders:send');
    }

    // 20.10. (1 Tag) und 27.10. (8 Tage, nächste Stufe); 23.10. und 28.10. liegen in derselben Stufe wie zuvor.
    Notification::assertSentToTimes($user, LibraryReminder::class, 2);
    Notification::assertSentTo($user, LibraryReminder::class, static fn (LibraryReminder $n): bool => $n->kind === 'loan_overdue' && $n->daysOverdue === 8);
});

it('announces a reservation ready for pickup', function (): void {
    $staff = reminderStaff();
    $copy = reminderCopy('Abholbereit');
    [$holder] = reminderPatron('S-RM-1', false);
    [$waiter, $waiterUser] = reminderPatron('S-RM-2');

    $loan = app(CheckoutCopyAction::class)->execute($holder, $copy->barcode, $staff);
    app(PlaceReservationAction::class)->execute($waiter, $copy->barcode, $staff);
    app(ReturnLoanAction::class)->execute($loan, $staff);

    $this->artisan('reminders:send')->expectsOutput('1 Erinnerung(en) verschickt.');

    Notification::assertSentTo($waiterUser, LibraryReminder::class, static fn (LibraryReminder $n): bool => $n->kind === 'reservation_ready' && $n->date === '12.10.2026');
});

it('skips patrons without a verified online account', function (): void {
    $staff = reminderStaff();
    [$noAccount] = reminderPatron('S-RM-1', false);
    [$unverified] = reminderPatron('S-RM-2', true, false);

    app(CheckoutCopyAction::class)->execute($noAccount, reminderCopy('Ohne Konto')->barcode, $staff);
    app(CheckoutCopyAction::class)->execute($unverified, reminderCopy('Unbestätigt')->barcode, $staff);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-18 07:00:00', 'Europe/Berlin'));
    $this->artisan('reminders:send')->expectsOutput('0 Erinnerung(en) verschickt.');

    Notification::assertNothingSent();
    expect(ReminderLog::query()->count())->toBe(0);
});

it('does not remind about returned loans', function (): void {
    $staff = reminderStaff();
    [$patron] = reminderPatron('S-RM-1');
    $loan = app(CheckoutCopyAction::class)->execute($patron, reminderCopy('Zurück')->barcode, $staff);
    app(ReturnLoanAction::class)->execute($loan, $staff);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-25 07:00:00', 'Europe/Berlin'));
    $this->artisan('reminders:send')->expectsOutput('0 Erinnerung(en) verschickt.');
});

it('renders the mail text in German', function (): void {
    $mail = (new LibraryReminder('loan_overdue', 'Momo', '19.10.2026', 3))->toMail(new User);

    expect($mail->subject)->toBe('Erinnerung: Rückgabe überfällig')
        ->and(implode(' ', $mail->introLines))->toContain('„Momo“')->toContain('3 Tage');
});
