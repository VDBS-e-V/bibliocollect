<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function portalPatron(string $number): Patron
{
    return Patron::query()->create([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Portal',
        'last_name' => 'Leser'.$number,
        'birth_date' => '2010-01-01',
    ]);
}

function portalUser(?Patron $patron, string $role = 'student'): User
{
    $user = User::factory()->create(['email_verified_at' => now(), 'patron_id' => $patron?->getKey()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** @return array{0: Title, 1: Copy} */
function portalTitle(string $name = 'Portalbuch'): array
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    $copy = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'PT-'.strtoupper(substr(md5($name), 0, 5)), 'status' => CopyStatus::Active]);

    return [$title, $copy];
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

it('shows only the own loans and reservations in the portal', function (): void {
    [, $copy] = portalTitle('Mein Buch');
    [, $otherCopy] = portalTitle('Fremdes Buch');
    $mine = portalPatron('S-PO-1');
    $other = portalPatron('S-PO-2');
    $staff = portalUser(null, 'staff');

    app(CheckoutCopyAction::class)->execute($mine, $copy->barcode, $staff);
    app(CheckoutCopyAction::class)->execute($other, $otherCopy->barcode, $staff);

    $this->actingAs(portalUser($mine))
        ->get(route('portal.home'))
        ->assertOk()
        ->assertSee('Mein Buch')
        ->assertDontSee('Fremdes Buch')
        ->assertSee('Verlängern');
});

it('explains an unlinked online account', function (): void {
    $this->actingAs(portalUser(null))
        ->get(route('portal.home'))
        ->assertOk()
        ->assertSee('noch nicht mit einem Ausleihkonto verknüpft');

    $this->actingAs(portalUser(null))
        ->post(route('portal.reservations.store'), ['title_id' => 'x'])
        ->assertForbidden();
});

it('lets patrons renew their own loans but never those of others', function (): void {
    [, $copy] = portalTitle();
    $mine = portalPatron('S-PO-1');
    $other = portalPatron('S-PO-2');
    $staff = portalUser(null, 'staff');
    $loan = app(CheckoutCopyAction::class)->execute($mine, $copy->barcode, $staff);
    $mineUser = portalUser($mine);

    $this->actingAs(portalUser($other))
        ->post(route('portal.loans.renew', ['loanId' => $loan->getKey()]))
        ->assertNotFound();

    $this->actingAs($mineUser)
        ->post(route('portal.loans.renew', ['loanId' => $loan->getKey()]))
        ->assertRedirect(route('portal.home'))
        ->assertSessionHas('portal_success');

    expect($loan->fresh()->renewal_count)->toBe(1);

    $loan->forceFill(['renewal_count' => 2])->save();

    $this->actingAs($mineUser)
        ->post(route('portal.loans.renew', ['loanId' => $loan->getKey()]))
        ->assertSessionHas('portal_error');
});

it('reserves a fully loaned title from the title page and cancels it again', function (): void {
    [$title, $copy] = portalTitle();
    $holder = portalPatron('S-PO-1');
    $reader = portalPatron('S-PO-2');
    app(CheckoutCopyAction::class)->execute($holder, $copy->barcode, portalUser(null, 'staff'));
    $user = portalUser($reader);

    $this->get(route('public.catalog.show', $title->getKey()))
        ->assertOk()
        ->assertSee('Melde dich an');

    $this->actingAs($user)
        ->get(route('public.catalog.show', $title->getKey()))
        ->assertOk()
        ->assertSee('Titel vormerken');

    $this->actingAs($user)
        ->post(route('portal.reservations.store'), ['title_id' => (string) $title->getKey()])
        ->assertRedirect(route('portal.home'))
        ->assertSessionHas('portal_success');

    $reservation = Reservation::query()->firstOrFail();

    expect($reservation->patron_id)->toBe((string) $reader->getKey())
        ->and($reservation->created_by_user_id)->toBe($user->getKey());

    $this->actingAs($user)
        ->get(route('public.catalog.show', $title->getKey()))
        ->assertSee('Zu meinen Vormerkungen');

    $this->actingAs($user)
        ->get(route('portal.home'))
        ->assertSee('Portalbuch')
        ->assertSee('Position');

    $this->actingAs($portalOther = portalUser(portalPatron('S-PO-3')))
        ->post(route('portal.reservations.cancel', ['reservationId' => $reservation->getKey()]))
        ->assertNotFound();

    $this->actingAs($user)
        ->post(route('portal.reservations.cancel', ['reservationId' => $reservation->getKey()]))
        ->assertRedirect(route('portal.home'));

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Cancelled);
});

it('does not reserve a title that is available', function (): void {
    [$title] = portalTitle();
    $user = portalUser(portalPatron('S-PO-1'));

    $this->actingAs($user)
        ->get(route('public.catalog.show', $title->getKey()))
        ->assertDontSee('Titel vormerken');

    $this->actingAs($user)
        ->post(route('portal.reservations.store'), ['title_id' => (string) $title->getKey()])
        ->assertSessionHas('portal_error');

    expect(Reservation::query()->count())->toBe(0)
        ->and(Loan::query()->count())->toBe(0);
});
