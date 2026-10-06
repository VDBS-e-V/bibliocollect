<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function scanUser(string $role = 'staff'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function scanPatron(string $number): Patron
{
    return Patron::query()->create([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Scan',
        'last_name' => 'Person'.$number,
        'birth_date' => '2012-01-01',
    ]);
}

function scanCopy(string $barcode = 'SC-001'): Copy
{
    $title = Title::query()->create(['preferred_title' => 'Scanbuch '.$barcode, 'sort_title' => 'Scanbuch '.$barcode]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => CopyStatus::Active]);
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:00:00', 'Europe/Berlin'));

    foreach ([['08:00', '10:00'], ['13:00', '15:00']] as [$from, $to]) {
        LibraryOpeningHour::query()->create(['day_of_week' => 1, 'is_open' => true, 'opens_at' => $from, 'closes_at' => $to]);
    }
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('shows opening ranges of today and the open work on the workplace', function (): void {
    $staff = scanUser();
    $copy = scanCopy();
    $patron = scanPatron('S-SC-1');
    $loan = app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $staff);
    $loan->forceFill(['due_on' => '2026-10-01'])->save();

    $this->actingAs($staff)->get(route('pos.home'))
        ->assertOk()
        ->assertSee('08:00–10:00 und 13:00–15:00 Uhr')
        ->assertSee('Überfällige Ausleihen')
        ->assertSee('Offene Katalogfälle')
        ->assertSee('Bibliotheksnummer oder Exemplar-Barcode');

    LibraryClosure::query()->create(['date' => '2026-10-05', 'reason' => 'Studientag']);

    $this->actingAs($staff)->get(route('pos.home'))->assertSee('Studientag');
});

it('hides what a role may not use', function (): void {
    $this->actingAs(scanUser('technical_admin'))->get(route('pos.home'))->assertForbidden();

    $this->actingAs(scanUser('student_ag_basic'))->get(route('pos.home'))
        ->assertOk()
        ->assertSee('Bibliotheksnummer oder Exemplar-Barcode')
        ->assertDontSee('Offene Katalogfälle')
        ->assertDontSee('Klassenlisten');
});

it('opens the checkout terminal for a library number and books a return for a loaned barcode', function (): void {
    $staff = scanUser();
    $patron = scanPatron('S-SC-1');
    $copy = scanCopy();
    $loan = app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $staff);

    $this->actingAs($staff)->post(route('pos.scan'), ['code' => ' s-sc-1 '])
        ->assertRedirect(route('pos.terminal.person'))
        ->assertSessionHas('pos.terminal.patron_id', (string) $patron->getKey());

    $this->actingAs($staff)->post(route('pos.scan'), ['code' => 'SC-001'])
        ->assertRedirect(route('pos.home'))
        ->assertSessionHas('workspace_success');

    expect($loan->fresh()->returned_at)->not->toBeNull();

    $this->actingAs($staff)->post(route('pos.scan'), ['code' => 'SC-001'])
        ->assertSessionHas('workspace_error', 'Das Exemplar SC-001 ist nicht ausgeliehen.');

    $this->actingAs($staff)->post(route('pos.scan'), ['code' => 'gibt-es-nicht'])
        ->assertSessionHas('workspace_error');

    $this->actingAs($staff)->post(route('pos.scan'), ['code' => ''])->assertSessionHasErrors('code');
});

it('tells who the returned copy is held for', function (): void {
    $staff = scanUser();
    $copy = scanCopy();
    app(CheckoutCopyAction::class)->execute(scanPatron('S-SC-1'), $copy->barcode, $staff);
    app(PlaceReservationAction::class)->execute(scanPatron('S-SC-2'), $copy->barcode, $staff);

    $this->actingAs($staff)->post(route('pos.scan'), ['code' => 'SC-001'])
        ->assertSessionHas('workspace_success', static fn (string $message): bool => str_contains($message, 'zurücklegen') && str_contains($message, 'S-SC-2'));

    expect(Loan::query()->whereNull('returned_at')->count())->toBe(0);
});

it('keeps the scan away from roles without circulation rights', function (string $role): void {
    $this->actingAs(scanUser($role))->post(route('pos.scan'), ['code' => 'x'])->assertForbidden();
})->with(['technical_admin', 'student', 'teacher']);
