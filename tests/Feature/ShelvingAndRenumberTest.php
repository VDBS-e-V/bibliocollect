<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Actions\ImportCatalogShelvesFromSignaturesAction;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogInventoryRenumberer;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stackUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function stackCopy(string $barcode, string $title = 'Stapelbuch', bool $onStack = false, ?string $location = null): Copy
{
    $titleModel = Title::query()->create(['preferred_title' => $title, 'sort_title' => $title]);
    $edition = Edition::query()->create(['title_id' => $titleModel->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => 'active', 'shelf_location' => $onStack ? null : $location]);
}

it('shelves books by scanning the book first and confirming the shelf', function (): void {
    $helper = stackUser('student_ag_basic');
    CatalogShelf::query()->create(['code' => 'R3-B2', 'label' => 'Fantasy']);
    CatalogShelf::query()->create(['code' => 'R1-B1']);
    $first = stackCopy('0020001', 'Erstes Buch', true);
    $second = stackCopy('0020002', 'Zweites Buch', true);
    stackCopy('0020003', 'Schon im Regal', false, 'R1-B1');

    $this->actingAs($helper)->get(route('pos.shelving'))->assertOk()->assertSee('1. Buch scannen')->assertSee('Stapel „Einsortieren“')->assertSee('Erstes Buch')->assertSee('Zweites Buch')->assertDontSee('Schon im Regal')->assertDontSee('2. Regalbrett');

    // Buch gescannt: Titel, Standort und Regalbrettauswahl erscheinen, noch ohne Vorauswahl.
    $this->actingAs($helper)->get(route('pos.shelving', ['buch' => '0020001']))->assertOk()->assertSee('Erstes Buch')->assertSee('noch kein Standort')->assertSee('2. Regalbrett')->assertSee('R3-B2 · Fantasy')->assertDontSee('Vorgewählt ist');

    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0020001', 'regalbrett' => 'R3-B2'])
        ->assertRedirect(route('pos.shelving'))->assertSessionHas('shelving_notice');

    $first->refresh();
    expect($first->shelf_location)->toBe('R3-B2')->and($first->shelved_at)->not->toBeNull()
        ->and(Copy::query()->awaitingShelving()->whereKey($first->getKey())->exists())->toBeFalse()
        ->and(Copy::query()->awaitingShelving()->whereKey($second->getKey())->exists())->toBeTrue();

    // Das zuletzt benutzte Regalbrett ist beim nächsten Buch vorgewählt.
    $this->actingAs($helper)->get(route('pos.shelving', ['buch' => '0020002']))->assertSee('Vorgewählt ist')->assertSee('<option value="R3-B2" selected>', false);
    $this->get(route('pos.shelving'))->assertSee('Zuletzt einsortiert')->assertSee('Erstes Buch');

    expect(AuditEvent::query()->where('action', 'catalog.copy.shelved')->count())->toBe(1);

    // Ein Buch, das schon im Regal steht, lässt sich umstellen; die Meldung nennt das alte Brett.
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0020001', 'regalbrett' => 'R1-B1'])->assertSessionHas('shelving_notice', static fn (string $text): bool => str_contains($text, 'R1-B1') && str_contains($text, 'Vorher: R3-B2'));
    expect($first->refresh()->shelf_location)->toBe('R1-B1');
});

it('takes the shelf from a scanned shelf label and suggests the shelves of the topic', function (): void {
    $helper = stackUser('student_ag_basic');
    $topic = CatalogTopic::query()->create(['name' => 'Rätsel & Knobeln']);
    $a = CatalogShelf::query()->create(['code' => 'I. A 1 d', 'sort_order' => 2]);
    $b = CatalogShelf::query()->create(['code' => 'I. A 1 e', 'sort_order' => 1]);
    $a->topics()->attach($topic->getKey(), ['position' => 1]);
    $b->topics()->attach($topic->getKey(), ['position' => 1]);
    CatalogShelf::query()->create(['code' => 'R1-B1']);
    $copy = stackCopy('0020005', 'Mit Thema', true);
    $copy->edition->forceFill(['local_classification' => 'Rätsel & Knobeln'])->save();

    // Das Thema hat zwei Regalbretter: beide werden genannt, das erste in der Reihenfolge ist vorgewählt, vor „zuletzt benutzt“.
    $page = $this->actingAs($helper)->withSession(['shelving.last_shelf' => 'R1-B1'])->get(route('pos.shelving', ['buch' => '0020005']));
    $page->assertSee('<option value="I. A 1 e" selected>', false)->assertSee('Rätsel &amp; Knobeln', false)->assertSee('I. A 1 e, I. A 1 d');

    // Ohne Thema gibt es keinen Vorschlag, aber den Hinweis darauf.
    stackCopy('0020006', 'Ohne Thema', true);
    $this->actingAs($helper)->get(route('pos.shelving', ['buch' => '0020006']))->assertSee('noch kein Thema');

    // Etikett gescannt, in anderer Schreibweise: hat Vorrang vor der Auswahl.
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0020005', 'regalbrett' => 'R1-B1', 'regalbrett_code' => 'ia1d'])->assertSessionHas('shelving_notice');
    expect($copy->refresh()->shelf_location)->toBe('I. A 1 d')->and($copy->signature_id)->toBeNull();

    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0020005', 'regalbrett_code' => 'gibt es nicht'])->assertSessionHas('shelving_error');
});

it('refuses unknown books, unknown shelves and missing input while shelving', function (): void {
    $helper = stackUser('student_ag_basic');
    CatalogShelf::query()->create(['code' => 'R3-B2']);
    CatalogShelf::query()->create(['code' => 'AUS', 'is_active' => false]);
    $copy = stackCopy('0020010', 'Stapelbuch', true);

    $this->actingAs($helper)->get(route('pos.shelving', ['buch' => '9999999']))->assertOk()->assertSee('gibt es kein Exemplar');
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '9999999', 'regalbrett' => 'R3-B2'])->assertSessionHas('shelving_error');
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0020010', 'regalbrett' => 'Erfunden'])->assertSessionHas('shelving_error');
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0020010', 'regalbrett' => 'AUS'])->assertSessionHas('shelving_error');
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0020010', 'regalbrett' => ''])->assertSessionHas('shelving_error');
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '', 'regalbrett' => 'R3-B2'])->assertSessionHasErrors('buch');

    expect(Copy::query()->awaitingShelving()->whereKey($copy->getKey())->exists())->toBeTrue()->and($copy->refresh()->shelf_location)->toBeNull();
});

it('takes the shelves of the old system over with their topics and merges old spellings', function (): void {
    $admin = stackUser('management');
    $topic = CatalogTopic::query()->create(['name' => 'Rätsel & Knobeln']);
    $other = CatalogTopic::query()->create(['name' => 'Lesestart']);
    $a = CatalogSignature::query()->create(['signature' => 'I. A 1 b']);
    $a->topics()->attach($topic->getKey(), ['position' => 1]);
    $b = CatalogSignature::query()->create(['signature' => 'I. A 1 d']);
    $b->topics()->attach([$topic->getKey() => ['position' => 1], $other->getKey() => ['position' => 2]]);

    // Ein alter Freitext-Standort mit anderer Schreibweise samt Buch.
    CatalogShelf::query()->create(['code' => 'IA1d']);
    $copy = stackCopy('0020020', 'Altbuch', false, 'IA1d');

    app(ImportCatalogShelvesFromSignaturesAction::class)->execute();

    $shelf = CatalogShelf::query()->where('code', 'I. A 1 d')->firstOrFail();
    expect(CatalogShelf::query()->orderBy('sort_order')->pluck('code')->all())->toBe(['I. A 1 b', 'I. A 1 d'])
        ->and($shelf->label)->toBe('Rätsel & Knobeln / Lesestart')
        ->and($shelf->topics->pluck('name')->all())->toBe(['Rätsel & Knobeln', 'Lesestart'])
        ->and($copy->refresh()->shelf_location)->toBe('I. A 1 d');

    // Wiederholbar, ändert nichts Eigenes.
    $shelf->forceFill(['label' => 'Eigene Beschriftung', 'is_active' => false])->save();
    app(ImportCatalogShelvesFromSignaturesAction::class)->execute();
    expect(CatalogShelf::query()->count())->toBe(2)->and($shelf->refresh()->label)->toBe('Eigene Beschriftung')->and($shelf->is_active)->toBeFalse();

    $this->actingAs($admin)->get(route('administration.shelves.index'))->assertSee('Rätsel &amp; Knobeln', false)->assertSee('Lesestart')->assertSee('Bereichsgruppe')->assertDontSee('Signatur');
});

it('shows the stack on the workplace and in the menu', function (): void {
    stackCopy('0020020', 'Auf dem Stapel', true);
    stackCopy('0020021', 'Auch auf dem Stapel', true);

    $this->actingAs(stackUser('student_ag_basic'))->get(route('pos.home'))->assertOk()->assertSee('Zum Einsortieren (Stapel)')->assertSee('Medien einsortieren');
    $this->actingAs(stackUser('staff'))->get(route('pos.processes'))->assertSee('Medien einsortieren');
});

it('lets staff but not students maintain shelves and renumber inventory numbers', function (): void {
    foreach (['administration.shelves.index', 'administration.inventory.index'] as $route) {
        $this->actingAs(stackUser('student_ag_basic'))->get(route($route))->assertForbidden();
        $this->actingAs(stackUser('student_ag_extended'))->get(route($route))->assertForbidden();
        $this->actingAs(stackUser('staff'))->get(route($route))->assertOk();
        $this->actingAs(stackUser('management'))->get(route($route))->assertOk();
    }

    $this->actingAs(stackUser('staff'))->post(route('administration.shelves.store'), ['code' => 'R5-B1'])->assertRedirect();
    $this->actingAs(stackUser('student_ag_extended'))->post(route('administration.shelves.store'), ['code' => 'R5-B2'])->assertForbidden();
    expect(CatalogShelf::query()->pluck('code')->all())->toBe(['R5-B1']);
});

it('renumbers old inventory numbers only on explicit request and continues after the highest new number', function (): void {
    $staff = stackUser('staff');
    $legacyA = stackCopy('12482', 'Alt A');
    $legacyB = stackCopy('BC-MOMO-001', 'Alt B');
    $legacyC = stackCopy('7', 'Alt C');
    $modern = stackCopy('0000042', 'Schon neu');

    // Nichts passiert von selbst.
    $this->actingAs($staff)->get(route('administration.inventory.index'))->assertOk()->assertSee('Alt A')->assertSee('Alt B')->assertSee('Alt C')->assertDontSee('Schon neu')->assertSee('0000043');
    expect($legacyA->refresh()->barcode)->toBe('12482')->and(app(CatalogInventoryRenumberer::class)->legacyCount())->toBe(3);

    $this->actingAs($staff)->post(route('administration.inventory.store'), ['copies' => []])->assertSessionHasErrors('copies');

    // Nur die ausgewählten werden umgestellt, in der Reihenfolge ihrer alten Nummern.
    $this->actingAs($staff)->post(route('administration.inventory.store'), ['copies' => [(string) $legacyA->getKey(), (string) $legacyB->getKey(), (string) $modern->getKey()]])
        ->assertRedirect(route('administration.inventory.index'))->assertSessionHas('renumbered');

    expect($legacyA->refresh()->barcode)->toBe('0000043')
        ->and($legacyB->refresh()->barcode)->toBe('0000044')
        ->and($legacyC->refresh()->barcode)->toBe('7')
        ->and($modern->refresh()->barcode)->toBe('0000042');

    $event = AuditEvent::query()->where('action', 'catalog.copy.renumbered')->orderBy('id')->firstOrFail();
    expect(AuditEvent::query()->where('action', 'catalog.copy.renumbered')->count())->toBe(2)->and($event->context)->toHaveKeys(['old', 'new']);

    $this->actingAs($staff)->get(route('administration.inventory.index'))->assertSee('Neue Etiketten drucken')->assertSee('Alt C');
    $this->actingAs($staff)->get(route('administration.inventory.index', ['q' => 'Alt C']))->assertSee('Alt C');
});

it('does not touch copies that already have a seven digit number', function (): void {
    $staff = stackUser('staff');
    $modern = stackCopy('0001234', 'Neu');

    $this->actingAs($staff)->post(route('administration.inventory.store'), ['copies' => [(string) $modern->getKey()]])->assertSessionHasErrors('copies');
    expect($modern->refresh()->barcode)->toBe('0001234');
});

it('counts every copy without a location as not yet shelved, but not weeded out or lost ones', function (): void {
    $helper = stackUser('student_ag_basic');
    stackCopy('0020030', 'Altbestand ohne Standort', true);
    stackCopy('0020031', 'Mit leerem Standort', false, '');
    stackCopy('0020032', 'Im Regal', false, 'R1-B1');
    $lost = stackCopy('0020033', 'Verloren', true);
    $lost->forceFill(['status' => 'lost'])->save();
    $withdrawn = stackCopy('0020034', 'Ausgesondert', true);
    $withdrawn->forceFill(['status' => 'withdrawn'])->save();

    expect(Copy::query()->awaitingShelving()->pluck('barcode')->sort()->values()->all())->toBe(['0020030', '0020031']);

    $this->actingAs($helper)->get(route('pos.shelving'))->assertOk()->assertSee('Altbestand ohne Standort')->assertSee('Mit leerem Standort')->assertDontSee('Im Regal')->assertDontSee('Ausgesondert');
});

it('puts copies that are added by hand in the catalog on the stack, whatever is sent as location', function (): void {
    $staff = stackUser('staff');
    CatalogShelf::query()->create(['code' => 'R3-B2']);
    $copy = stackCopy('0020040', 'Bestehendes Exemplar', false, 'R3-B2');

    $this->actingAs($staff)->post(route('pos.catalog.copies.store', ['editionId' => $copy->edition_id]), ['barcode' => '0020041', 'shelf_location' => 'R3-B2', 'status' => 'active'])->assertSessionHasNoErrors();

    $new = Copy::query()->where('barcode', '0020041')->firstOrFail();
    expect($new->shelf_location)->toBeNull()->and(Copy::query()->awaitingShelving()->whereKey($new->getKey())->exists())->toBeTrue();

    $this->actingAs($staff)->get(route('pos.catalog.editions.edit', ['editionId' => $copy->edition_id]))->assertOk()->assertSee('beim Einsortieren ins Regal');
});
