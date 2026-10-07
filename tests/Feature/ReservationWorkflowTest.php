<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CancelReservationAction;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Actions\RenewLoanAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Circulation\Services\CopyAvailabilityService;
use App\Modules\Circulation\Services\LoanPolicy;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function rsvActor(string $role = 'staff'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function rsvPatron(string $number, array $overrides = []): Patron
{
    return Patron::query()->create(array_merge([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Vor',
        'last_name' => 'Merk '.$number,
        'birth_date' => '2010-01-01',
    ], $overrides));
}

/**
 * Titel mit einer Ausgabe und der gewünschten Zahl aktiver Exemplare.
 *
 * @return array{0: Title, 1: Edition, 2: list<Copy>}
 */
function rsvTitle(string $name = 'Vormerkbuch', int $copies = 1, ?string $isbn = '9783000000003'): array
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book', 'isbn' => $isbn]);
    $list = [];

    for ($i = 1; $i <= $copies; $i++) {
        $list[] = Copy::query()->create([
            'edition_id' => $edition->getKey(),
            'barcode' => 'RSV-'.strtoupper(substr(md5($name), 0, 5)).'-'.$i,
            'status' => CopyStatus::Active,
        ]);
    }

    return [$title, $edition, $list];
}

function rsvCheckout(Patron $patron, Copy $copy, User $actor): Loan
{
    return app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $actor);
}

function rsvPlace(Patron $patron, string $identifier, User $actor): Reservation
{
    return app(PlaceReservationAction::class)->execute($patron, $identifier, $actor);
}

beforeEach(function (): void {
    // Die Warteschlangen-Tests brauchen mehrere Wartende je Exemplar; die Grenze selbst prüft ein eigener Test.
    config(['circulation.max_reservations_per_copy' => 5]);

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

it('reserves a title by barcode or isbn only while every copy is out', function (): void {
    [, , [$copy]] = rsvTitle();
    $actor = rsvActor();
    $holder = rsvPatron('S-RS-1');
    $waiter = rsvPatron('S-RS-2');

    expect(fn () => rsvPlace($waiter, $copy->barcode, $actor))
        ->toThrow(CirculationRuleViolation::class, 'verfügbar');

    rsvCheckout($holder, $copy, $actor);

    $reservation = rsvPlace($waiter, '978-3-00-000000-3', $actor);

    expect($reservation->status)->toBe(ReservationStatus::Waiting)
        ->and($reservation->created_by_user_id)->toBe($actor->getKey())
        ->and($reservation->title->preferred_title)->toBe('Vormerkbuch');
});

it('rejects unknown identifiers, duplicates, own loans, blocked patrons and too many reservations', function (): void {
    [, , [$copy]] = rsvTitle();
    $actor = rsvActor();
    $holder = rsvPatron('S-RS-1');
    $waiter = rsvPatron('S-RS-2');
    rsvCheckout($holder, $copy, $actor);

    expect(fn () => rsvPlace($waiter, 'gibt-es-nicht', $actor))->toThrow(CirculationRuleViolation::class, 'Kein Titel');
    expect(fn () => rsvPlace($holder, $copy->barcode, $actor))->toThrow(CirculationRuleViolation::class, 'bereits ausgeliehen');

    rsvPlace($waiter, $copy->barcode, $actor);
    expect(fn () => rsvPlace($waiter, $copy->barcode, $actor))->toThrow(CirculationRuleViolation::class, 'bereits vorgemerkt');

    $blocked = rsvPatron('S-RS-3', ['blocked_at' => now(), 'blocked_reason' => 'Test']);
    expect(fn () => rsvPlace($blocked, $copy->barcode, $actor))->toThrow(CirculationRuleViolation::class, 'gesperrt');

    config(['circulation.max_open_reservations' => 1]);
    [, , [$other]] = rsvTitle('Zweites Buch', 1, null);
    rsvCheckout($holder, $other, $actor);
    expect(fn () => rsvPlace($waiter, $other->barcode, $actor))->toThrow(CirculationRuleViolation::class, 'höchstens 1');
});

it('does not reserve a title the patron is too young for', function (): void {
    [, $edition, [$copy]] = rsvTitle();
    $edition->forceFill(['minimum_age' => 18])->save();
    $actor = rsvActor();
    rsvCheckout(rsvPatron('S-RS-1', ['birth_date' => '1990-01-01']), $copy, $actor);

    expect(fn () => rsvPlace(rsvPatron('S-RS-2'), $copy->barcode, $actor))
        ->toThrow(CirculationRuleViolation::class, 'Mindestalter');
});

it('holds a returned copy for the first waiting patron and blocks everyone else', function (): void {
    [, , [$copy]] = rsvTitle();
    $actor = rsvActor();
    $holder = rsvPatron('S-RS-1');
    $first = rsvPatron('S-RS-2');
    $second = rsvPatron('S-RS-3');
    $outsider = rsvPatron('S-RS-4');

    $loan = rsvCheckout($holder, $copy, $actor);
    $firstReservation = rsvPlace($first, $copy->barcode, $actor);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:05:00', 'Europe/Berlin'));
    $secondReservation = rsvPlace($second, $copy->barcode, $actor);

    app(ReturnLoanAction::class)->execute($loan, $actor);

    $firstReservation->refresh();
    $secondReservation->refresh();

    expect($firstReservation->status)->toBe(ReservationStatus::Ready)
        ->and($firstReservation->ready_copy_id)->toBe((string) $copy->getKey())
        ->and($firstReservation->pickup_until->toDateString())->toBe('2026-10-12')
        ->and($secondReservation->status)->toBe(ReservationStatus::Waiting);

    expect(fn () => rsvCheckout($outsider, $copy, $actor))
        ->toThrow(CirculationRuleViolation::class, 'zur Abholung bereit');
    expect(fn () => rsvCheckout($second, $copy, $actor))
        ->toThrow(CirculationRuleViolation::class, 'zur Abholung bereit');

    $pickup = rsvCheckout($first, $copy, $actor);

    expect($firstReservation->fresh()->status)->toBe(ReservationStatus::Fulfilled)
        ->and($firstReservation->fresh()->loan_id)->toBe((string) $pickup->getKey())
        ->and($secondReservation->fresh()->status)->toBe(ReservationStatus::Waiting);
});

it('passes a cancelled hold on to the next waiting patron', function (): void {
    [, , [$copy]] = rsvTitle();
    $actor = rsvActor();
    $loan = rsvCheckout(rsvPatron('S-RS-1'), $copy, $actor);
    $first = rsvPlace(rsvPatron('S-RS-2'), $copy->barcode, $actor);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:05:00', 'Europe/Berlin'));
    $second = rsvPlace(rsvPatron('S-RS-3'), $copy->barcode, $actor);

    app(ReturnLoanAction::class)->execute($loan, $actor);
    app(CancelReservationAction::class)->execute($first, $actor);

    expect($first->fresh()->status)->toBe(ReservationStatus::Cancelled)
        ->and($first->fresh()->closed_by_user_id)->toBe($actor->getKey())
        ->and($second->fresh()->status)->toBe(ReservationStatus::Ready)
        ->and($second->fresh()->ready_copy_id)->toBe((string) $copy->getKey());

    expect(fn () => app(CancelReservationAction::class)->execute($first, $actor))
        ->toThrow(LoanStateConflict::class);
});

it('skips waiting patrons who are no longer eligible when promoting', function (): void {
    [, , [$copy]] = rsvTitle();
    $actor = rsvActor();
    $loan = rsvCheckout(rsvPatron('S-RS-1'), $copy, $actor);
    $blocked = rsvPatron('S-RS-2');
    $blockedReservation = rsvPlace($blocked, $copy->barcode, $actor);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:05:00', 'Europe/Berlin'));
    $next = rsvPlace(rsvPatron('S-RS-3'), $copy->barcode, $actor);

    $blocked->forceFill(['blocked_at' => now(), 'blocked_reason' => 'Test'])->save();

    app(ReturnLoanAction::class)->execute($loan, $actor);

    expect($blockedReservation->fresh()->status)->toBe(ReservationStatus::Waiting)
        ->and($next->fresh()->status)->toBe(ReservationStatus::Ready);
});

it('fulfils the reservation and frees the held copy when the patron takes another copy', function (): void {
    [$title, , [$copyA, $copyB]] = rsvTitle('Zwei Exemplare', 2);
    $actor = rsvActor();
    $holderA = rsvPatron('S-RS-1');
    $holderB = rsvPatron('S-RS-2');
    $waiter = rsvPatron('S-RS-3');

    $loanA = rsvCheckout($holderA, $copyA, $actor);
    $loanB = rsvCheckout($holderB, $copyB, $actor);
    $reservation = rsvPlace($waiter, $copyA->barcode, $actor);

    app(ReturnLoanAction::class)->execute($loanA, $actor);
    expect($reservation->fresh()->ready_copy_id)->toBe((string) $copyA->getKey());

    // Das andere Exemplar kommt zurück und ist frei; die Person nimmt dieses statt des zurückgelegten mit.
    app(ReturnLoanAction::class)->execute($loanB, $actor);
    rsvCheckout($waiter, $copyB, $actor);

    $availability = app(CopyAvailabilityService::class)->forTitles([(string) $title->getKey()])[(string) $title->getKey()];

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Fulfilled)
        ->and($availability->heldCopies)->toBe(0)
        ->and($availability->availableCopies())->toBe(1);
});

it('expires holds after the pickup deadline and hands the copy over', function (): void {
    [, , [$copy]] = rsvTitle();
    $actor = rsvActor();
    $loan = rsvCheckout(rsvPatron('S-RS-1'), $copy, $actor);
    $first = rsvPlace(rsvPatron('S-RS-2'), $copy->barcode, $actor);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:05:00', 'Europe/Berlin'));
    $second = rsvPlace(rsvPatron('S-RS-3'), $copy->barcode, $actor);
    app(ReturnLoanAction::class)->execute($loan, $actor);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-12 10:00:00', 'Europe/Berlin'));
    $this->artisan('circulation:reservations:expire')->expectsOutput('0 Vormerkung(en) abgelaufen, 0 zurück in die Warteschlange.');
    expect($first->fresh()->status)->toBe(ReservationStatus::Ready);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-13 10:00:00', 'Europe/Berlin'));
    $this->artisan('circulation:reservations:expire')->expectsOutput('1 Vormerkung(en) abgelaufen, 0 zurück in die Warteschlange.');

    expect($first->fresh()->status)->toBe(ReservationStatus::Expired)
        ->and($second->fresh()->status)->toBe(ReservationStatus::Ready)
        ->and($second->fresh()->pickup_until->toDateString())->toBe('2026-10-20');
});

it('puts a hold back into the queue when the held copy is no longer lendable', function (): void {
    [, , [$copy]] = rsvTitle();
    $actor = rsvActor();
    $loan = rsvCheckout(rsvPatron('S-RS-1'), $copy, $actor);
    $reservation = rsvPlace(rsvPatron('S-RS-2'), $copy->barcode, $actor);
    app(ReturnLoanAction::class)->execute($loan, $actor);

    $copy->forceFill(['status' => CopyStatus::Damaged])->save();

    $this->artisan('circulation:reservations:expire')->expectsOutput('0 Vormerkung(en) abgelaufen, 1 zurück in die Warteschlange.');

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Waiting)
        ->and($reservation->fresh()->ready_copy_id)->toBeNull();
});

it('blocks renewals while somebody waits for the title', function (): void {
    [, , [$copy]] = rsvTitle();
    $actor = rsvActor();
    $loan = rsvCheckout(rsvPatron('S-RS-1'), $copy, $actor);

    expect(app(RenewLoanAction::class)->execute($loan, $actor)->renewal_count)->toBe(1);

    rsvPlace(rsvPatron('S-RS-2'), $copy->barcode, $actor);

    expect(fn () => app(RenewLoanAction::class)->execute($loan, $actor))
        ->toThrow(CirculationRuleViolation::class, 'Vormerkung');
});

it('shows reservations in the public view without exposing patrons and counts held copies as unavailable', function (): void {
    [$title, , [$copy]] = rsvTitle();
    $actor = rsvActor();
    $loan = rsvCheckout(rsvPatron('S-RS-1'), $copy, $actor);
    $waiter = rsvPatron('S-RS-2');
    rsvPlace($waiter, $copy->barcode, $actor);

    $this->get(route('public.catalog.show', $title->getKey()))
        ->assertOk()
        ->assertSee('Derzeit ausgeliehen')
        ->assertSee('1 Vormerkung')
        ->assertDontSee('S-RS-2')
        ->assertDontSee($waiter->last_name);

    app(ReturnLoanAction::class)->execute($loan, $actor);

    $this->get(route('public.catalog.show', $title->getKey()))
        ->assertOk()
        ->assertSee('Für Vormerkung zurückgelegt')
        ->assertDontSee('>Verfügbar<', false);
});

it('manages reservations from the patron workspace and the overview page', function (): void {
    [, , [$copy]] = rsvTitle();
    $actor = rsvActor('student_ag_basic');
    $holder = rsvPatron('S-RS-1');
    $waiter = rsvPatron('S-RS-2');
    $loan = rsvCheckout($holder, $copy, $actor);

    $this->actingAs($actor)
        ->post(route('pos.reservations.store', ['patronId' => $waiter->getKey()]), ['identifier' => 'nope'])
        ->assertSessionHasErrors('reservation');

    $this->actingAs($actor)
        ->post(route('pos.reservations.store', ['patronId' => $waiter->getKey()]), ['identifier' => $copy->barcode])
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $waiter->getKey()]))
        ->assertSessionHas('workspace_success');

    $this->actingAs($actor)
        ->get(route('pos.patrons.show', ['patronId' => $waiter->getKey()]))
        ->assertOk()
        ->assertSee('Vormerkungen')
        ->assertSee('Vormerkbuch')
        ->assertSee('Position');

    $this->actingAs($actor)
        ->post(route('pos.circulation.return', ['patronId' => $holder->getKey(), 'loanId' => $loan->getKey()]))
        ->assertSessionHas('workspace_success', static fn (string $message): bool => str_contains($message, 'zurücklegen') && str_contains($message, 'S-RS-2'));

    $this->actingAs($actor)
        ->get(route('pos.reservations.index'))
        ->assertOk()
        ->assertSee('Zur Abholung zurückgelegt')
        ->assertSee($copy->barcode);

    $reservation = Reservation::query()->firstOrFail();

    $this->actingAs($actor)
        ->post(route('pos.reservations.cancel', ['patronId' => $waiter->getKey(), 'reservationId' => $reservation->getKey()]))
        ->assertSessionHas('workspace_success');

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Cancelled);
});

it('keeps reservations away from users without circulation rights', function (string $role): void {
    $patron = rsvPatron('S-RS-9');

    $this->actingAs(rsvActor($role))
        ->get(route('pos.reservations.index'))
        ->assertForbidden();

    $this->actingAs(rsvActor($role))
        ->post(route('pos.reservations.store', ['patronId' => $patron->getKey()]), ['identifier' => 'x'])
        ->assertForbidden();
})->with(['student', 'teacher', 'technical_admin']);

it('keeps the reservation migration rollback capable', function (): void {
    $migration = require app_path('Modules/Circulation/database/migrations/2026_10_06_110000_create_circulation_reservations_table.php');

    $migration->down();

    expect(Schema::hasTable('circulation_reservations'))->toBeFalse();

    $migration->up();

    expect(Schema::hasTable('circulation_reservations'))->toBeTrue();
});

it('never allows two open reservations of one title for one account, even past the application check', function (): void {
    [$title, , [$copy]] = rsvTitle('Doppelbuch');
    $actor = rsvActor();
    $holder = rsvPatron('S-DUP-1');
    $waiter = rsvPatron('S-DUP-2');

    rsvCheckout($holder, $copy, $actor);
    $first = rsvPlace($waiter, $copy->barcode, $actor);

    expect(fn () => rsvPlace($waiter, $copy->barcode, $actor))->toThrow(CirculationRuleViolation::class, 'bereits vorgemerkt');

    // Die Datenbank verhindert es auch, wenn die Anwendung umgangen wird.
    expect(fn () => Reservation::query()->create([
        'patron_id' => $waiter->getKey(),
        'title_id' => $title->getKey(),
        'status' => ReservationStatus::Waiting,
        'requested_at' => now(),
    ]))->toThrow(UniqueConstraintViolationException::class);

    expect(Reservation::query()->where('patron_id', $waiter->getKey())->count())->toBe(1);

    // Ist die erste Vormerkung abgeschlossen, ist eine neue wieder möglich.
    app(CancelReservationAction::class)->execute($first, $actor);
    expect(rsvPlace($waiter, $copy->barcode, $actor)->status)->toBe(ReservationStatus::Waiting);
});

it('stops reservations once the queue is as long as the number of copies and names the waiting time', function (): void {
    config(['circulation.max_reservations_per_copy' => 1, 'circulation.reservation_buffer_days' => 7, 'circulation.default_loan_period_days' => 14, 'circulation.renewal_period_days' => null]);

    [, , [$copy]] = rsvTitle('Wartebuch', 1, '9783000000100');
    $actor = rsvActor();
    $holder = rsvPatron('S-WZ-1');
    $first = rsvPatron('S-WZ-2');
    $second = rsvPatron('S-WZ-3');

    rsvCheckout($holder, $copy, $actor);
    rsvPlace($first, $copy->barcode, $actor);

    // Ein Exemplar, eine Wartende: weitere Vormerkungen würden über Leihfrist (14) + Verlängerung (14) + Puffer (7) = 35 Tage dauern.
    expect(fn () => rsvPlace($second, $copy->barcode, $actor))
        ->toThrow(CirculationRuleViolation::class, '35 Tage');

    expect(Reservation::query()->where('patron_id', $second->getKey())->exists())->toBeFalse();
});

it('allows as many reservations as there are copies and follows the rules settings', function (): void {
    config(['circulation.max_reservations_per_copy' => 1]);

    [, , $copies] = rsvTitle('Zweibuch', 2, '9783000000101');
    $actor = rsvActor();
    $holders = [rsvPatron('S-WZ-4'), rsvPatron('S-WZ-5')];
    $waiters = [rsvPatron('S-WZ-6'), rsvPatron('S-WZ-7'), rsvPatron('S-WZ-8')];

    rsvCheckout($holders[0], $copies[0], $actor);
    rsvCheckout($holders[1], $copies[1], $actor);

    rsvPlace($waiters[0], $copies[0]->barcode, $actor);
    rsvPlace($waiters[1], $copies[0]->barcode, $actor);

    // Zwei Exemplare, zwei Wartende: die dritte Vormerkung ist zu viel.
    expect(fn () => rsvPlace($waiters[2], $copies[0]->barcode, $actor))->toThrow(CirculationRuleViolation::class, 'Tage Puffer');

    // Die Verwaltung erlaubt zwei Wartende je Exemplar.
    config(['circulation.max_reservations_per_copy' => 2]);
    expect(rsvPlace($waiters[2], $copies[0]->barcode, $actor)->status)->toBe(ReservationStatus::Waiting);
});

it('calculates the waiting window from the current rules', function (): void {
    config(['circulation.default_loan_period_days' => 10, 'circulation.renewal_period_days' => 5, 'circulation.reservation_buffer_days' => 3]);

    $window = app(LoanPolicy::class)->reservationWindow(rsvPatron('S-WZ-9'));

    expect($window)->toBe(['loan' => 10, 'renewal' => 5, 'buffer' => 3, 'total' => 18]);

    config(['circulation.renewal_period_days' => null, 'circulation.reservation_buffer_days' => 0]);
    expect(app(LoanPolicy::class)->reservationWindow(rsvPatron('S-WZ-10'))['total'])->toBe(20);
});
