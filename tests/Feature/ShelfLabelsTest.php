<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogShelfStructure;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Surfaces\Administration\Http\Controllers\ShelfLabelController;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function shelfLabelUser(string $role = 'management'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function shelfLabelSetup(): void
{
    $topic = CatalogTopic::query()->create(['name' => 'Rätsel & Knobeln']);
    CatalogShelf::query()->create(['code' => 'I. A 1 a', 'label' => 'Rätsel & Knobeln', 'sort_order' => 1])->topics()->attach($topic->getKey(), ['position' => 1]);
    CatalogShelf::query()->create(['code' => 'I. A 1 b', 'label' => 'Lesestart', 'sort_order' => 2]);
    CatalogShelf::query()->create(['code' => 'I. A 2 a', 'label' => 'Kochen', 'sort_order' => 3]);
    CatalogShelf::query()->create(['code' => 'II. B 1 a', 'label' => 'Comics', 'sort_order' => 4]);
    CatalogShelf::query()->create(['code' => 'HÖR ENDE', 'label' => 'Hörbücher']);
    CatalogShelf::query()->create(['code' => 'AUS', 'is_active' => false]);
    app(CatalogShelfStructure::class)->assignAll();
    CatalogShelfSection::query()->where('kind', 'rack')->where('code', '1')->update(['name' => 'Wand links']);
}

it('offers the shelf label page and keeps it away from roles without shelf rights', function (): void {
    shelfLabelSetup();

    $this->actingAs(shelfLabelUser())->get(route('administration.shelves.labels'))->assertOk()->assertSee('105 × 26 mm')->assertSee('Alle Regalbretter (5)')->assertSee('Ganze Bereichsgruppe I')->assertSee('Regalbretter ohne Regal');
    $this->actingAs(shelfLabelUser('staff'))->get(route('administration.shelves.labels'))->assertOk();
    $this->actingAs(shelfLabelUser('student_ag_extended'))->get(route('administration.shelves.labels'))->assertForbidden();
    $this->actingAs(shelfLabelUser('student_ag_extended'))->post(route('administration.shelves.labels.print'))->assertForbidden();
    $this->actingAs(shelfLabelUser())->get(route('administration.shelves.index'))->assertSee('Etiketten für Regalbretter drucken');
});

it('prints the topic big with the location small, a barcode and rack and area names on 22 places per sheet', function (): void {
    shelfLabelSetup();

    $html = $this->actingAs(shelfLabelUser())->post(route('administration.shelves.labels.print'), ['umfang' => 'alle', 'themen' => '1'])->assertOk()->getContent();

    // 5 aktive Regalbretter (ein ausgeschaltetes fehlt)
    expect(substr_count($html, 'class="label"'))->toBe(5)
        ->and($html)->toContain('<div class="loc ">I. A 1 a</div>')
        ->and($html)->toContain('Rätsel &amp; Knobeln')
        ->and($html)->toContain('Regal 1 · Wand links')->toContain('Bereich A')->toContain('Bereichsgruppe I')
        ->and($html)->toContain('Themen: Rätsel &amp; Knobeln')
        ->and($html)->toContain('aria-label="Strichcode I. A 1 a"')
        ->and($html)->toContain('repeat(2, 105mm)')->toContain('grid-auto-rows: 26mm')
        ->and($html)->not->toContain('<div class="loc ">AUS</div>')
        // Umlaut lässt sich nicht als Code 128 darstellen
        ->and($html)->toContain('Kein Strichcode');

    expect(ShelfLabelController::PER_SHEET)->toBe(22);
    $without = $this->actingAs(shelfLabelUser())->post(route('administration.shelves.labels.print'), ['umfang' => 'alle'])->getContent();
    expect($without)->not->toContain('Themen: ');
});

it('lets the code type be chosen: barcode, QR code or none', function (): void {
    shelfLabelSetup();
    $admin = shelfLabelUser();
    $post = fn (array $extra) => $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'alle', ...$extra])->getContent();

    $qr = $post(['code' => 'qr']);
    expect($qr)->toContain('aria-label="QR-Code I. A 1 a"')->not->toContain('aria-label="Strichcode')->not->toContain('Kein Strichcode');

    $none = $post(['code' => 'keiner']);
    expect($none)->not->toContain('aria-label="Strichcode')->not->toContain('aria-label="QR-Code')->toContain('<div class="loc big">I. A 1 a</div>');

    $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'alle', 'code' => 'quatsch'])->assertSessionHasErrors('code');
});

it('limits the print to a rack, an area or single shelves and repeats the labels', function (): void {
    shelfLabelSetup();
    $admin = shelfLabelUser();
    $rack = CatalogShelfSection::query()->where('kind', 'rack')->where('code', '2')->firstOrFail();
    $areaB = CatalogShelfSection::query()->where('kind', 'area')->where('code', 'B')->firstOrFail();
    $single = CatalogShelf::query()->where('code', 'I. A 1 b')->firstOrFail();

    $byRack = $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'r:'.$rack->getKey()])->getContent();
    expect(substr_count($byRack, 'class="label"'))->toBe(1)->and($byRack)->toContain('I. A 2 a');

    $byArea = $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'a:'.$areaB->getKey()])->getContent();
    expect(substr_count($byArea, 'class="label"'))->toBe(1)->and($byArea)->toContain('II. B 1 a');

    // Einzelne Regalbretter haben Vorrang, zweimal je Brett.
    $singles = $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'alle', 'bretter' => [(string) $single->getKey()], 'anzahl' => 2])->getContent();
    expect(substr_count($singles, 'class="label"'))->toBe(2)->and(substr_count($singles, '<div class="loc ">I. A 1 b</div>'))->toBe(2);

    // Ohne Regal, mit ausgeschalteten.
    $loose = $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'lose', 'inaktive' => '1'])->getContent();
    expect($loose)->toContain('HÖR ENDE')->toContain('AUS');
});

it('starts at the chosen place, adds new sheets after 22 labels and records the print', function (): void {
    shelfLabelSetup();
    $admin = shelfLabelUser();

    $html = $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'alle', 'startplatz' => 21])->getContent();
    // 20 leere Plätze am Anfang, dann 2 Etiketten auf dem ersten Bogen, 3 auf dem zweiten.
    expect(substr_count($html, 'class="label empty"'))->toBe(20)->and(substr_count($html, 'class="sheet"'))->toBe(2);

    expect(AuditEvent::query()->where('action', 'catalog.labels.shelves_printed')->count())->toBe(1);

    $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'r:'.str_repeat('0', 26)])->assertStatus(422)->assertSee('Nichts zu drucken');
    $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'quatsch'])->assertSessionHasErrors('umfang');
    $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'alle', 'anzahl' => 9])->assertSessionHasErrors('anzahl');
});

it('links the QR code to the public catalog showing only the media of that shelf', function (): void {
    shelfLabelSetup();
    foreach (['Rätselbuch' => 'I. A 1 a', 'Kochbuch' => 'I. A 2 a'] as $name => $where) {
        $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
        $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
        Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => md5($name), 'status' => 'active', 'shelf_location' => $where]);
    }

    $shelf = CatalogShelf::query()->where('code', 'I. A 1 a')->firstOrFail();
    expect($shelf->publicSlug())->toBe('I-A-1-a');

    $this->get('/regal/I-A-1-a')->assertRedirect(route('public.catalog.index', ['regalbrett' => 'I. A 1 a']));
    $this->get('/regal/gibt-es-nicht')->assertRedirect(route('public.catalog.index'));

    $this->get(route('public.catalog.index', ['regalbrett' => 'I. A 1 a']))->assertOk()->assertSee('Regalbrett I. A 1 a')->assertSee('Rätselbuch')->assertDontSee('Kochbuch');
});

it('links a topic to the catalog showing its shelves and media including sub topics', function (): void {
    shelfLabelSetup();
    $parent = CatalogTopic::query()->where('name', 'Rätsel & Knobeln')->firstOrFail();
    $child = CatalogTopic::query()->create(['name' => 'Logicals', 'parent_id' => $parent->getKey()]);
    $other = CatalogTopic::query()->create(['name' => 'Kochen']);
    CatalogShelf::query()->where('code', 'I. A 2 a')->firstOrFail()->topics()->attach($other->getKey(), ['position' => 1]);

    $make = function (string $name, ?string $classification, ?string $where): void {
        $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
        $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book', 'local_classification' => $classification]);
        Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => md5($name), 'status' => 'active', 'shelf_location' => $where]);
    };
    $make('Rätselheft', 'Rätsel & Knobeln', null);
    $make('Logikbuch', 'Logicals', null);
    $make('Brettbuch', null, 'I. A 1 a');
    $make('Kochbuch', 'Kochen', 'I. A 2 a');

    expect($parent->publicSlug())->toBe('R%C3%A4tsel-Knobeln');

    $this->get('/thema/'.$parent->publicSlug())->assertRedirect(route('public.catalog.index', ['thema' => 'Rätsel & Knobeln']));
    $this->get('/thema/gibt-es-nicht')->assertRedirect(route('public.catalog.index'));

    $page = $this->get(route('public.catalog.index', ['thema' => 'Rätsel & Knobeln']))->assertOk();
    $page->assertSee('Thema Rätsel &amp; Knobeln', false)->assertSee('I. A 1 a')->assertSee('/regal/I-A-1-a', false)
        ->assertSee('Rätselheft')->assertSee('Logikbuch')->assertSee('Brettbuch')->assertDontSee('Kochbuch');

    $this->get(route('public.catalog.index', ['thema' => 'Unbekannt']))->assertOk()->assertSee('Keine passenden Titel');
});

it('lets the QR code point to the topic of the shelf instead of the shelf', function (): void {
    shelfLabelSetup();
    $admin = shelfLabelUser();
    $post = fn (array $extra) => $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'alle', 'code' => 'qr', ...$extra])->assertOk()->getContent();

    $shelf = $post([]);
    $topic = $post(['ziel' => 'thema']);

    // Der QR-Inhalt ist nicht lesbar im HTML, aber das SVG unterscheidet sich, sobald das Ziel ein anderes ist.
    expect($topic)->not->toBe($shelf)->and($topic)->toContain('aria-label="QR-Code I. A 1 a"');
    $this->actingAs($admin)->post(route('administration.shelves.labels.print'), ['umfang' => 'alle', 'ziel' => 'quatsch'])->assertSessionHasErrors('ziel');
});
