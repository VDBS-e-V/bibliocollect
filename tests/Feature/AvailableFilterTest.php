<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{0: Title, 1: list<Copy>} */
function availableTitle(string $name, array $statuses = ['active']): array
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    $copies = [];

    foreach ($statuses as $index => $status) {
        $copies[] = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => '5'.str_pad((string) (crc32($name.$index) % 1000000), 6, '0', STR_PAD_LEFT), 'status' => $status]);
    }

    return [$title, $copies];
}

beforeEach(function (): void {
    foreach (range(1, 5) as $day) {
        LibraryOpeningHour::query()->create(['day_of_week' => $day, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00']);
    }
});

it('filters the public catalog to titles that can be borrowed right now', function (): void {
    $staff = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($staff, 'staff');
    $patron = Patron::query()->create(['library_number' => 'V-1', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Vera', 'last_name' => 'Verfügbar', 'birth_date' => '2010-01-01']);

    [, $free] = availableTitle('Freies Buch');
    [, $loaned] = availableTitle('Verliehenes Buch');
    [$heldTitle, $held] = availableTitle('Zurückgelegtes Buch');
    [, $mixed] = availableTitle('Teils ausgeliehenes Buch', ['active', 'active']);
    [, $damaged] = availableTitle('Beschädigtes Buch', ['damaged']);

    app(CheckoutCopyAction::class)->execute($patron, $loaned[0]->barcode, $staff);
    app(CheckoutCopyAction::class)->execute($patron, $mixed[0]->barcode, $staff);
    Reservation::query()->create(['patron_id' => $patron->getKey(), 'title_id' => $heldTitle->getKey(), 'status' => ReservationStatus::Ready, 'requested_at' => now(), 'ready_copy_id' => $held[0]->getKey(), 'ready_at' => now(), 'pickup_until' => now()->addDays(7)]);

    $all = $this->get(route('public.catalog.index', ['q' => 'buch']))->assertOk();
    $all->assertSee('Freies Buch')->assertSee('Verliehenes Buch')->assertSee('Zurückgelegtes Buch')->assertSee('Teils ausgeliehenes Buch')->assertSee('Beschädigtes Buch');

    $this->get(route('public.catalog.index', ['q' => 'buch', 'available_only' => 1]))->assertOk()
        ->assertSee('Freies Buch')->assertSee('Teils ausgeliehenes Buch')
        ->assertDontSee('Verliehenes Buch')->assertDontSee('Zurückgelegtes Buch')->assertDontSee('Beschädigtes Buch');

    // Sobald das Buch zurückgegeben ist, taucht es wieder auf.
    expect($free[0]->exists)->toBeTrue();
    DB::table('circulation_loans')->whereNull('returned_at')->where('copy_id', $loaned[0]->getKey())->update(['returned_at' => now()]);
    $this->get(route('public.catalog.index', ['q' => 'buch', 'available_only' => 1]))->assertSee('Verliehenes Buch');
});

it('offers the filter on the search pages for the public and for staff', function (): void {
    $this->get(route('public.catalog.index'))->assertOk()->assertSee('Nur jetzt verfügbare Titel')->assertSee('name="available_only"', false);
    $this->get(route('public.catalog.advanced'))->assertOk()->assertSee('Nur jetzt verfügbare Titel');

    $staff = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($staff, 'staff');
    $this->actingAs($staff)->get(route('pos.catalog.index'))->assertOk()->assertSee('Nur jetzt verfügbare Titel');
    $this->actingAs($staff)->get(route('pos.catalog.index', ['available_only' => 1]))->assertOk();

    // Das Filterkennzeichen bleibt beim Blättern erhalten und ungültige Werte werden abgelehnt.
    $this->get(route('public.catalog.index', ['available_only' => 'vielleicht']))->assertSessionHasErrors('available_only');
});
