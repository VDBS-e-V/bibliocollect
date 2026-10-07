<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\InventoryLabelPlanner;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Surfaces\Pos\Http\Controllers\CopyLabelController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function stockUser(string $role = 'staff'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function stockCopy(string $barcode): Copy
{
    $title = Title::query()->create(['preferred_title' => 'Vorratsbuch '.$barcode, 'sort_title' => 'Vorratsbuch '.$barcode]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => CopyStatus::Active]);
}

it('uses 24 labels of 70 x 36 mm per sheet for every label print', function (): void {
    $css = view('pages.surfaces.pos.labels._sheet-style')->render();

    expect(CopyLabelController::PER_SHEET)->toBe(24)
        ->and($css)->toContain('repeat(3, 70mm)')->toContain('grid-auto-rows: 36mm');

    stockCopy('0100001');
    $html = $this->actingAs(stockUser())->post(route('pos.labels.copies.print'), ['copies' => Copy::query()->pluck('id')->all(), 'start' => 24])->assertOk()->getContent();

    // Start auf dem letzten Platz: 23 leere Plätze, ein Etikett.
    expect(substr_count($html, 'class="label empty"'))->toBe(23)->and(substr_count($html, '<svg'))->toBe(1);
});

it('skips numbers that belong to a copy or were printed before and keeps the series running', function (): void {
    stockCopy('0100002');
    stockCopy('0100004');
    DB::table('catalog_printed_labels')->insert(['number' => '0100003', 'printed_at' => now()]);

    $plan = app(InventoryLabelPlanner::class)->sequence(100001, 4);

    expect($plan['numbers'])->toBe(['0100001', '0100005', '0100006', '0100007'])
        ->and($plan['used'])->toBe(['0100002', '0100004'])
        ->and($plan['printed'])->toBe(['0100003'])
        ->and($plan['last'])->toBe(100007);

    // Neudruck erlaubt schon gedruckte Nummern.
    expect(app(InventoryLabelPlanner::class)->sequence(100001, 3, true)['numbers'])->toBe(['0100001', '0100003', '0100005']);
});

it('finds the gaps of the running series once', function (): void {
    foreach (['0100001', '0100002', '0100005', '0100008'] as $barcode) {
        stockCopy($barcode);
    }

    $gaps = app(InventoryLabelPlanner::class)->gaps(100001, 100008);

    expect($gaps['numbers'])->toBe(['0100003', '0100004', '0100006', '0100007'])->and($gaps['total'])->toBe(4)->and($gaps['truncated'])->toBeFalse();

    DB::table('catalog_printed_labels')->insert(['number' => '0100004', 'printed_at' => now()]);
    expect(app(InventoryLabelPlanner::class)->gaps(100001, 100008)['numbers'])->toBe(['0100003', '0100006', '0100007']);

    $big = app(InventoryLabelPlanner::class)->gaps(200001, 200700);
    expect($big['numbers'])->toHaveCount(480)->and($big['truncated'])->toBeTrue()->and($big['total'])->toBe(700);
});

it('shows the check result before printing and records the printed numbers', function (): void {
    stockCopy('0100002');
    $staff = stockUser();

    $this->actingAs($staff)->get(route('pos.labels.stock'))->assertOk()->assertSee('Etiketten auf Vorrat drucken')->assertSee('Reihe fortsetzen')->assertSee('Lücken der laufenden Reihe füllen');

    $this->get(route('pos.labels.stock', ['plan' => 1, 'modus' => 'reihe', 'start' => 100001, 'anzahl' => 3]))
        ->assertOk()
        ->assertSee('Ergebnis der Prüfung')
        ->assertSee('0100001')
        ->assertSee('Übersprungen: schon vergeben')
        ->assertSee('0100002');

    $html = $this->post(route('pos.labels.stock.print'), ['modus' => 'reihe', 'start' => 100001, 'anzahl' => 3, 'startplatz' => 2])->assertOk()->getContent();

    expect(substr_count($html, '<svg'))->toBe(3)
        ->and(substr_count($html, 'class="label empty"'))->toBe(1)
        ->and($html)->toContain('0100001')->toContain('0100003')->toContain('0100004');

    expect(DB::table('catalog_printed_labels')->orderBy('number')->pluck('number')->all())->toBe(['0100001', '0100003', '0100004']);

    // Der nächste Druck beginnt nach den gedruckten Nummern.
    $next = $this->get(route('pos.labels.stock', ['plan' => 1, 'modus' => 'reihe', 'start' => 100001, 'anzahl' => 2]))->assertSee('Übersprungen: schon gedruckt');
    $next->assertSee('0100005')->assertSee('0100006');
});

it('prints the gap labels and refuses an empty plan', function (): void {
    foreach (['0100001', '0100004'] as $barcode) {
        stockCopy($barcode);
    }

    $staff = stockUser();
    $html = $this->actingAs($staff)->post(route('pos.labels.stock.print'), ['modus' => 'luecken', 'von' => 100001, 'bis' => 100004])->assertOk()->getContent();
    expect(substr_count($html, '<svg'))->toBe(2)->and($html)->toContain('0100002')->toContain('0100003');

    $this->post(route('pos.labels.stock.print'), ['modus' => 'luecken', 'von' => 100001, 'bis' => 100004])->assertStatus(422);
    $this->post(route('pos.labels.stock.print'), ['modus' => 'kaputt'])->assertSessionHasErrors('modus');
});

it('keeps the stock labels away from roles without catalog rights', function (): void {
    $this->actingAs(stockUser('student_ag_basic'))->get(route('pos.labels.stock'))->assertForbidden();
    $this->actingAs(stockUser('teacher'))->post(route('pos.labels.stock.print'), ['modus' => 'reihe'])->assertForbidden();
    $this->actingAs(stockUser('student_ag_extended'))->get(route('pos.labels.stock'))->assertOk();
});
