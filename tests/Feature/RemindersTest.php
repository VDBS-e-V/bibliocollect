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
use Illuminate\Notifications\AnonymousNotifiable;
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

it('reminds patrons without an online account at the address of their library account', function (): void {
    [$patron] = reminderPatron('S-RM-20', false);
    $patron->forceFill(['email' => 'eltern@example.org'])->save();
    $loan = app(CheckoutCopyAction::class)->execute($patron, reminderCopy('Nur Adresse')->barcode, reminderStaff());

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-17 07:00:00', 'Europe/Berlin'));
    $this->artisan('reminders:send')->expectsOutput('1 Erinnerung(en) verschickt.');
    $this->artisan('reminders:send')->expectsOutput('0 Erinnerung(en) verschickt.');

    Notification::assertSentOnDemandTimes(LibraryReminder::class, 1);
    Notification::assertSentOnDemand(LibraryReminder::class, static function (LibraryReminder $n, array $channels, object $notifiable): bool {
        return $n->kind === 'loan_due_soon' && $n->recipientName === 'Erinnerung' && ($notifiable->routes['mail'] ?? null) === ['eltern@example.org' => 'Erinnerung LeserS-RM-20'];
    });

    $log = ReminderLog::query()->where('subject_id', (string) $loan->getKey())->firstOrFail();
    expect($log->user_id)->toBeNull()->and($log->patron_id)->toBe((string) $patron->getKey());
});

it('addresses mails to a library account by first name and signs them as the school library', function (): void {
    $mail = (new LibraryReminder('loan_overdue', 'Momo', '19.10.2026', 3, 'Lina'))->toMail(new AnonymousNotifiable);

    expect($mail->greeting)->toBe('Hallo Lina,')->and($mail->salutation)->toBe('Viele Grüße, deine Schulbibliothek');
});

it('respects the switch on the library account and skips unusable addresses', function (): void {
    $staff = reminderStaff();

    [$off] = reminderPatron('S-RM-21', false);
    $off->forceFill(['email' => 'aus@example.org', 'reminders_enabled' => false])->save();
    [$broken] = reminderPatron('S-RM-22', false);
    $broken->forceFill(['email' => 'keine-adresse'])->save();
    [$left] = reminderPatron('S-RM-23', false);
    $left->forceFill(['email' => 'weg@example.org'])->save();

    foreach ([$off, $broken, $left] as $index => $patron) {
        app(CheckoutCopyAction::class)->execute($patron, reminderCopy('Buch '.$index)->barcode, $staff);
    }

    $left->forceFill(['status' => PatronStatus::Departed])->save();

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-18 07:00:00', 'Europe/Berlin'));
    $this->artisan('reminders:send')->expectsOutput('0 Erinnerung(en) verschickt.');

    Notification::assertNothingSent();
});

it('keeps the opt-out of an online account even when the library account has an address', function (): void {
    [$patron, $user] = reminderPatron('S-RM-24');
    $patron->forceFill(['email' => 'trotzdem@example.org'])->save();
    $user->forceFill(['reminders_enabled' => false])->save();
    app(CheckoutCopyAction::class)->execute($patron, reminderCopy('Abgemeldet')->barcode, reminderStaff());

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-18 07:00:00', 'Europe/Berlin'));
    $this->artisan('reminders:send')->expectsOutput('0 Erinnerung(en) verschickt.');

    Notification::assertNothingSent();
});

it('sends at most the given number of reminders per run and finishes the rest next time', function (): void {
    $staff = reminderStaff();

    foreach (range(30, 32) as $number) {
        [$patron] = reminderPatron('S-RM-'.$number, false);
        $patron->forceFill(['email' => 'p'.$number.'@example.org'])->save();
        app(CheckoutCopyAction::class)->execute($patron, reminderCopy('Menge '.$number)->barcode, $staff);
    }

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-17 07:00:00', 'Europe/Berlin'));
    $this->artisan('reminders:send', ['--limit' => 2])->expectsOutput('2 Erinnerung(en) verschickt.');
    $this->artisan('reminders:send', ['--limit' => 2])->expectsOutput('1 Erinnerung(en) verschickt.');

    Notification::assertSentOnDemandTimes(LibraryReminder::class, 3);
});

it('lets staff switch the reminders of a library account off and on', function (): void {
    $staff = reminderStaff();
    $class = null;
    [$patron] = reminderPatron('S-RM-40', false);

    $payload = ['library_number' => $patron->library_number, 'kind' => 'student', 'first_name' => 'Erinnerung', 'last_name' => 'Leser', 'birth_date' => '2010-01-01', 'email' => 'x@example.org', 'school_class_id' => '', 'leaving_on' => ''];

    $this->actingAs($staff)->patch(route('pos.patrons.update', ['patronId' => $patron->getKey()]), $payload + ['reminders_enabled' => '0']);
    expect($patron->refresh()->reminders_enabled)->toBeFalse();

    $this->patch(route('pos.patrons.update', ['patronId' => $patron->getKey()]), $payload + ['reminders_enabled' => '1']);
    expect($patron->refresh()->reminders_enabled)->toBeTrue();
});
