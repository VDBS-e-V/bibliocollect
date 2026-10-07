<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Actions\CreateCopyAction;
use App\Modules\Catalog\Actions\UpdateCopyAction;
use App\Modules\Catalog\DTOs\CopyData;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function gapUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function gapPatron(string $number): Patron
{
    return Patron::query()->create(['library_number' => $number, 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Gerda', 'last_name' => 'Lücke'.$number, 'birth_date' => '2010-01-01']);
}

/** @return array{0: Edition, 1: list<Copy>} */
function gapTitle(string $name, int $copies, CopyStatus $status = CopyStatus::Active): array
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    $list = [];

    for ($i = 1; $i <= $copies; $i++) {
        $list[] = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => '01'.str_pad((string) crc32($name.$i), 5, '0', STR_PAD_LEFT), 'status' => $status]);
    }

    return [$edition, $list];
}

beforeEach(function (): void {
    foreach (range(1, 7) as $day) {
        LibraryOpeningHour::query()->create(['day_of_week' => $day, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00']);
    }
});

it('puts a found copy back into circulation and hands it to the first waiting reservation', function (): void {
    $aide = gapUser('student_ag_basic');
    [, $copies] = gapTitle('Wiedergefunden', 2);
    [$loaned, $lost] = $copies;
    $holder = gapPatron('410001');
    $waiter = gapPatron('410002');

    app(CheckoutCopyAction::class)->execute($holder, $loaned->barcode, $aide);
    $lost->forceFill(['status' => CopyStatus::Lost])->save();
    $reservation = app(PlaceReservationAction::class)->execute($waiter, $loaned->barcode, $aide);

    // Scannen am Arbeitsplatz führt zur Seite „wieder verfügbar“.
    $this->actingAs($aide)->post(route('pos.scan'), ['code' => $lost->barcode])->assertRedirect(route('pos.copy-found', ['barcode' => $lost->barcode]));
    $this->get(route('pos.copy-found', ['barcode' => $lost->barcode]))->assertOk()->assertSee('Wieder verfügbar machen')->assertSee('Verloren');

    $this->post(route('pos.copy-found.store', ['barcode' => $lost->barcode]))
        ->assertRedirect(route('pos.home'))
        ->assertSessionHas('workspace_success', static fn (string $text): bool => str_contains($text, 'Vormerkung bereit'));

    expect($lost->refresh()->status)->toBe(CopyStatus::Active)
        ->and($reservation->refresh()->status)->toBe(ReservationStatus::Ready)
        ->and($reservation->ready_copy_id)->toBe((string) $lost->getKey())
        ->and(AuditEvent::query()->where('action', 'catalog.copy.found')->count())->toBe(1);
});

it('refuses to restore copies that are not lost or damaged and keeps the page away from other roles', function (): void {
    [, [$copy]] = gapTitle('Normal', 1);

    $this->actingAs(gapUser('student_ag_basic'))->get(route('pos.copy-found', ['barcode' => $copy->barcode]))->assertRedirect(route('pos.home'))->assertSessionHas('workspace_error');
    $this->post(route('pos.copy-found.store', ['barcode' => $copy->barcode]))->assertRedirect(route('pos.home'))->assertSessionHas('workspace_error');

    foreach (['teacher', 'student'] as $role) {
        $this->actingAs(gapUser($role))->get(route('pos.copy-found', ['barcode' => $copy->barcode]))->assertForbidden();
    }
});

it('lets waiting reservations move up when a new or reactivated copy appears', function (): void {
    $staff = gapUser('staff');
    [$edition, $copies] = gapTitle('Nachrücken', 1);
    $holder = gapPatron('410003');
    $waiter = gapPatron('410004');
    $second = gapPatron('410005');
    config(['circulation.max_reservations_per_copy' => 5]);

    app(CheckoutCopyAction::class)->execute($holder, $copies[0]->barcode, $staff);
    $first = app(PlaceReservationAction::class)->execute($waiter, $copies[0]->barcode, $staff);
    $queued = app(PlaceReservationAction::class)->execute($second, $copies[0]->barcode, $staff);

    // Ein neues Exemplar erfassen: Die erste Wartende bekommt es.
    $new = app(CreateCopyAction::class)->execute($edition, new CopyData('0188001', null, CopyStatus::Active));
    expect($first->refresh()->status)->toBe(ReservationStatus::Ready)->and($first->ready_copy_id)->toBe((string) $new->getKey())->and($queued->refresh()->status)->toBe(ReservationStatus::Waiting);

    // Ein beschädigtes Exemplar wird repariert und wieder aktiv gesetzt: Die zweite Wartende rückt nach.
    $damaged = app(CreateCopyAction::class)->execute($edition, new CopyData('0188002', null, CopyStatus::Damaged));
    expect($queued->refresh()->status)->toBe(ReservationStatus::Waiting);

    app(UpdateCopyAction::class)->execute($edition, $damaged, new CopyData('0188002', null, CopyStatus::Active));
    expect($queued->refresh()->status)->toBe(ReservationStatus::Ready)->and($queued->ready_copy_id)->toBe((string) $damaged->getKey());
});

it('shows every overdue loan to everyone who may lend, longest overdue first', function (): void {
    $aide = gapUser('student_ag_basic');
    [, [$a, $b]] = gapTitle('Überfällig', 2);
    $one = gapPatron('410006');
    $two = gapPatron('410007');

    $old = app(CheckoutCopyAction::class)->execute($one, $a->barcode, $aide);
    $recent = app(CheckoutCopyAction::class)->execute($two, $b->barcode, $aide);
    Loan::query()->whereKey($old->getKey())->update(['due_on' => now()->subDays(20)->toDateString()]);
    Loan::query()->whereKey($recent->getKey())->update(['due_on' => now()->subDays(3)->toDateString()]);

    $html = $this->actingAs($aide)->get(route('pos.overdue'))->assertOk()->assertSee('Lücke410006')->assertSee('Lücke410007')->getContent();
    expect(strpos($html, 'Lücke410006'))->toBeLessThan(strpos($html, 'Lücke410007'));

    $this->get(route('pos.home'))->assertOk()->assertSee(route('pos.overdue'), false);

    $this->actingAs(gapUser('teacher'))->get(route('pos.overdue'))->assertForbidden();
});
