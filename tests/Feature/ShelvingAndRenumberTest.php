<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Models\CatalogShelf;
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

it('puts new copies on the stack and shelves them with a shelf and a scan', function (): void {
    $helper = stackUser('student_ag_basic');
    CatalogShelf::query()->create(['code' => 'R3-B2', 'label' => 'Fantasy']);
    CatalogShelf::query()->create(['code' => 'R1-B1']);
    $first = stackCopy('0020001', 'Erstes Buch', true);
    $second = stackCopy('0020002', 'Zweites Buch', true);
    stackCopy('0020003', 'Schon im Regal', false, 'R1-B1');

    $this->actingAs($helper)->get(route('pos.shelving'))->assertOk()->assertSee('Stapel „Einsortieren“')->assertSee('Erstes Buch')->assertSee('Zweites Buch')->assertDontSee('Schon im Regal')->assertDontSee('für R3-B2');

    // Regalbrett gewählt: Scanfeld erscheint.
    $this->actingAs($helper)->get(route('pos.shelving', ['regalbrett' => 'R3-B2']))->assertOk()->assertSee('Inventarnummer des Buchs für R3-B2');

    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['regalbrett' => 'R3-B2', 'code' => '0020001'])
        ->assertRedirect(route('pos.shelving', ['regalbrett' => 'R3-B2']))->assertSessionHas('shelving_notice');

    $first->refresh();
    expect($first->shelf_location)->toBe('R3-B2')->and(Copy::query()->awaitingShelving()->whereKey($first->getKey())->exists())->toBeFalse()->and($first->shelved_at)->not->toBeNull()
        ->and(Copy::query()->awaitingShelving()->whereKey($second->getKey())->exists())->toBeTrue();

    $this->actingAs($helper)->get(route('pos.shelving', ['regalbrett' => 'R3-B2']))->assertSee('Zuletzt einsortiert')->assertSee('Erstes Buch')->assertSee('Stapel „Einsortieren“');

    expect(AuditEvent::query()->where('action', 'catalog.copy.shelved')->count())->toBe(1);

    // Ein Buch, das schon im Regal steht, lässt sich umstellen.
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['regalbrett' => 'R1-B1', 'code' => '0020001'])->assertSessionHas('shelving_notice');
    expect($first->refresh()->shelf_location)->toBe('R1-B1');
});

it('refuses unknown books, unknown shelves and missing input while shelving', function (): void {
    $helper = stackUser('student_ag_basic');
    CatalogShelf::query()->create(['code' => 'R3-B2']);
    CatalogShelf::query()->create(['code' => 'AUS', 'is_active' => false]);
    $copy = stackCopy('0020010', 'Stapelbuch', true);

    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['regalbrett' => 'R3-B2', 'code' => '9999999'])->assertSessionHas('shelving_error');
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['regalbrett' => 'Erfunden', 'code' => '0020010'])->assertSessionHas('shelving_error');
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['regalbrett' => 'AUS', 'code' => '0020010'])->assertSessionHas('shelving_error');
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['regalbrett' => '', 'code' => '0020010'])->assertSessionHasErrors('regalbrett');
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['regalbrett' => 'R3-B2', 'code' => ''])->assertSessionHasErrors('code');

    expect(Copy::query()->awaitingShelving()->whereKey($copy->getKey())->exists())->toBeTrue()->and($copy->refresh()->shelf_location)->toBeNull();

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
