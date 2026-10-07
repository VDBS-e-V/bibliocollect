<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\InventoryCount;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\InventoryReport;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function countUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function countCopy(string $barcode, ?string $location, string $title = 'Zählbuch', CopyStatus $status = CopyStatus::Active): Copy
{
    $titleModel = Title::query()->create(['preferred_title' => $title.' '.$barcode, 'sort_title' => $title.' '.$barcode]);
    $edition = Edition::query()->create(['title_id' => $titleModel->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => $status, 'shelf_location' => $location]);
}

it('keeps the inventory count for staff, administration and the extended student group', function (): void {
    foreach (['staff', 'management', 'student_ag_extended'] as $role) {
        $this->actingAs(countUser($role))->get(route('pos.inventory'))->assertOk()->assertSee('Inventur');
    }

    $this->actingAs(countUser('student_ag_basic'))->get(route('pos.inventory'))->assertForbidden();
    $this->actingAs(countUser('teacher'))->post(route('pos.inventory.start'))->assertForbidden();
});

it('runs a count shelf by shelf and compares it with the stock', function (): void {
    $staff = countUser('staff');
    foreach (range(1, 5) as $day) {
        LibraryOpeningHour::query()->create(['day_of_week' => $day, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00']);
    }

    CatalogShelf::query()->create(['code' => 'R1']);
    CatalogShelf::query()->create(['code' => 'R2']);
    CatalogShelf::query()->create(['code' => 'R3']);

    $ok = countCopy('0050001', 'R1');
    $missing = countCopy('0050002', 'R1');
    $loaned = countCopy('0050003', 'R1');
    $misplaced = countCopy('0050004', 'R2');
    $unplaced = countCopy('0050005', null);
    $weeded = countCopy('0050006', null, 'Ausgesondert', CopyStatus::Withdrawn);
    $elsewhere = countCopy('0050007', 'R3', 'Anderes Regal');

    $patron = Patron::query()->create(['library_number' => 'I-1', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Iris', 'last_name' => 'Inventur', 'birth_date' => '2010-01-01']);
    app(CheckoutCopyAction::class)->execute($patron, $loaned->barcode, $staff);

    $this->actingAs($staff)->post(route('pos.inventory.start'), ['name' => 'Testinventur'])->assertRedirect();
    $count = InventoryCount::query()->firstOrFail();
    expect($count->name)->toBe('Testinventur')->and($count->isOpen())->toBeTrue();

    // Nur eine Inventur gleichzeitig.
    $this->post(route('pos.inventory.start'))->assertSessionHasErrors('name');

    $this->get(route('pos.inventory.show', ['countId' => $count->getKey()]))->assertOk()->assertSee('Ich stehe am Regalbrett')->assertDontSee('Inventarnummer des Buchs auf');
    $this->get(route('pos.inventory.show', ['countId' => $count->getKey(), 'regalbrett' => 'R1']))->assertSee('Inventarnummer des Buchs auf R1');

    $scan = fn (string $shelf, string $code) => $this->post(route('pos.inventory.scan', ['countId' => $count->getKey()]), ['regalbrett' => $shelf, 'code' => $code]);

    $scan('R1', '0050001')->assertSessionHas('inventory_notice');
    $scan('R1', '0050004')->assertSessionHas('inventory_warning', static fn (string $text): bool => str_contains($text, 'R2'));
    $scan('R1', '0050005')->assertSessionHas('inventory_warning', static fn (string $text): bool => str_contains($text, 'keinen Standort'));
    $scan('R1', '0050006')->assertSessionHas('inventory_warning', static fn (string $text): bool => str_contains($text, 'verloren oder ausgesondert'));
    $scan('R1', '9999999')->assertSessionHas('inventory_error');
    $scan('Erfunden', '0050001')->assertSessionHas('inventory_error');
    $this->post(route('pos.inventory.scan', ['countId' => $count->getKey()]), ['regalbrett' => 'R1', 'code' => ''])->assertSessionHasErrors('code');

    // Doppelt gescannt zählt einmal.
    $scan('R1', '0050001');
    expect($count->items()->count())->toBe(5);

    $report = $this->get(route('pos.inventory.report', ['countId' => $count->getKey()]))->assertOk();
    $report->assertSee('Zwischenstand')->assertSee('0050002')->assertDontSee('0050003')->assertDontSee('Anderes Regal 0050007')->assertSee('Unbekannt')->assertSee('9999999');

    // Ausgeliehenes fehlt nicht, nicht geprüfte Regalbretter bleiben außen vor.
    $data = app(InventoryReport::class)->build($count->refresh());
    expect($data['shelves'])->toBe(['R1'])
        ->and($data['ok']->pluck('barcode')->all())->toBe(['0050001'])
        ->and($data['misplaced']->pluck('barcode')->all())->toBe(['0050004'])
        ->and($data['unplaced']->pluck('barcode')->all())->toBe(['0050005'])
        ->and($data['inactive']->pluck('barcode')->all())->toBe(['0050006'])
        ->and($data['unknown']->pluck('barcode')->all())->toBe(['9999999'])
        ->and($data['missing']->pluck('barcode')->all())->toBe(['0050002'])
        ->and($data['onLoan'])->toBe(1);

    $this->post(route('pos.inventory.close', ['countId' => $count->getKey()]))->assertRedirect(route('pos.inventory.report', ['countId' => $count->getKey()]));
    expect($count->refresh()->isOpen())->toBeFalse();
    $scan('R1', '0050002')->assertRedirect(route('pos.inventory.report', ['countId' => $count->getKey()]));
    expect($count->items()->count())->toBe(5);

    $csv = $this->get(route('pos.inventory.export', ['countId' => $count->getKey()]))->assertOk()->streamedContent();
    expect($csv)->toContain('Fehlt;0050002')->and($csv)->toContain('"Falsch einsortiert";0050004')->and($csv)->toContain('Unbekannt;9999999')->and($csv)->toContain('Richtig;0050001');

    // Standorte korrigieren: Das falsch einsortierte und das Buch ohne Standort stehen danach auf R1.
    $this->post(route('pos.inventory.apply', ['countId' => $count->getKey()]))->assertSessionHas('inventory_notice', '2 Standort(e) wurden korrigiert.');
    expect($misplaced->refresh()->shelf_location)->toBe('R1')->and($unplaced->refresh()->shelf_location)->toBe('R1')->and($missing->refresh()->shelf_location)->toBe('R1')->and($elsewhere->refresh()->shelf_location)->toBe('R3');

    // Eine neue Inventur ist danach wieder möglich.
    $this->post(route('pos.inventory.start'))->assertRedirect();
    expect(InventoryCount::query()->count())->toBe(2);
    $this->get(route('pos.inventory'))->assertSee('Laufende Inventur')->assertSee('Testinventur');
});
