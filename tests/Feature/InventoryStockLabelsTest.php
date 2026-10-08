<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
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

it('lays out every label with the logo top left, the name top right, the barcode at the bottom and the number below it', function (): void {
    $html = $this->actingAs(stockUser())->post(route('pos.labels.stock.print'), ['modus' => 'reihe', 'start' => 100001, 'anzahl' => 1])->assertOk()->getContent();

    // Reihenfolge im Etikett: Kopf (Logo, Name), Strichcode, Nummer.
    expect($html)->toContain('<img class="logo" src="/brand/vdbs/mark.svg"')
        ->and($html)->toContain('<span class="name">BiblioCollect</span>')
        ->and(strpos($html, 'class="logo"'))->toBeLessThan(strpos($html, 'class="name"'))
        ->and(strpos($html, 'class="name"'))->toBeLessThan(strpos($html, '<svg'))
        ->and(strpos($html, '<svg'))->toBeLessThan(strpos($html, '<div class="code">0100001</div>'));

    $css = view('pages.surfaces.pos.labels._sheet-style')->render();
    expect($css)->toContain('.label .head')->toContain('justify-content: space-between')->toContain('.label .code');

    stockCopy('0100050');
    $copy = $this->post(route('pos.labels.copies.print'), ['copies' => Copy::query()->pluck('id')->all()])->assertOk()->getContent();
    expect($copy)->toContain('<span class="name">BiblioCollect</span>')->toContain('<div class="code">0100050</div>');
});

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

it('lists the latest print jobs and takes a single job back so that its numbers become free again', function (): void {
    $staff = stockUser();
    $this->actingAs($staff);

    $this->post(route('pos.labels.stock.print'), ['modus' => 'reihe', 'start' => 100001, 'anzahl' => 3])->assertOk();
    $this->post(route('pos.labels.stock.print'), ['modus' => 'reihe', 'start' => 100001, 'anzahl' => 2])->assertOk();

    expect(DB::table('catalog_label_runs')->count())->toBe(2)
        ->and(DB::table('catalog_printed_labels')->count())->toBe(5);

    $page = $this->get(route('pos.labels.stock'))->assertOk();
    $page->assertSee('Letzte Drucke')->assertSee('0100001 bis 0100003')->assertSee('0100004 bis 0100005')->assertSee('5 Nummern als gedruckt gespeichert')->assertSee('Reihe fortgesetzt')->assertSee($staff->name);

    // Den zweiten Auftrag zurücknehmen: nur seine Nummern werden frei.
    $second = DB::table('catalog_label_runs')->orderByDesc('id')->value('id');
    $this->delete(route('pos.labels.stock.run.destroy', ['runId' => $second]))->assertRedirect(route('pos.labels.stock'))->assertSessionHas('stock_notice');

    expect(DB::table('catalog_label_runs')->count())->toBe(1)
        ->and(DB::table('catalog_printed_labels')->orderBy('number')->pluck('number')->all())->toBe(['0100001', '0100002', '0100003']);
    expect(app(InventoryLabelPlanner::class)->sequence(100001, 2)['numbers'])->toBe(['0100004', '0100005']);

    expect(AuditEvent::query()->where('action', 'catalog.labels.run_deleted')->count())->toBe(1);

    $this->delete(route('pos.labels.stock.run.destroy', ['runId' => 9999]))->assertNotFound();
});

it('deletes all numbers saved as printed at once, including old ones without a print job', function (): void {
    $staff = stockUser();
    $this->actingAs($staff);

    $this->post(route('pos.labels.stock.print'), ['modus' => 'reihe', 'start' => 100001, 'anzahl' => 3])->assertOk();
    DB::table('catalog_printed_labels')->insert(['number' => '0100100', 'printed_at' => now()]);

    $this->get(route('pos.labels.stock'))->assertSee('Alle gedruckten Nummern löschen (4)');

    $this->delete(route('pos.labels.stock.clear'))->assertRedirect(route('pos.labels.stock'))->assertSessionHas('stock_notice', static fn (string $text): bool => str_contains($text, 'Alle 4'));

    expect(DB::table('catalog_printed_labels')->count())->toBe(0)->and(DB::table('catalog_label_runs')->count())->toBe(0);
    expect(app(InventoryLabelPlanner::class)->sequence(100001, 3)['numbers'])->toBe(['0100001', '0100002', '0100003']);
    expect(AuditEvent::query()->where('action', 'catalog.labels.all_cleared')->count())->toBe(1);

    // Leere Liste: kein Knopf, kein Fehler.
    $this->get(route('pos.labels.stock'))->assertOk()->assertDontSee('Alle gedruckten Nummern löschen')->assertSee('noch nichts auf Vorrat gedruckt');
});

it('keeps a reprinted number with the newest job and keeps the delete functions away from other roles', function (): void {
    $this->actingAs(stockUser());
    $this->post(route('pos.labels.stock.print'), ['modus' => 'reihe', 'start' => 100001, 'anzahl' => 2])->assertOk();
    $this->post(route('pos.labels.stock.print'), ['modus' => 'reihe', 'start' => 100001, 'anzahl' => 2, 'erneut' => 1])->assertOk();

    $first = DB::table('catalog_label_runs')->orderBy('id')->value('id');
    $this->delete(route('pos.labels.stock.run.destroy', ['runId' => $first]));

    // Der Neudruck gehört dem zweiten Auftrag; das Zurücknehmen des ersten lässt die Nummern gedruckt.
    expect(DB::table('catalog_printed_labels')->count())->toBe(2);

    foreach (['student_ag_basic', 'teacher'] as $role) {
        $this->actingAs(stockUser($role))->delete(route('pos.labels.stock.clear'))->assertForbidden();
        $this->actingAs(stockUser($role))->delete(route('pos.labels.stock.run.destroy', ['runId' => 1]))->assertForbidden();
    }
});
