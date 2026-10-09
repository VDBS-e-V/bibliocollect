<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogShelfStructure;
use App\Modules\Catalog\Services\CatalogSummaryService;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function shelfUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function shelfCopy(string $barcode, ?string $location): Copy
{
    $title = Title::query()->create(['preferred_title' => 'Brett '.$barcode, 'sort_title' => 'Brett '.$barcode]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => 'active', 'shelf_location' => $location]);
}

it('lets the administration manage the list of shelves', function (): void {
    $admin = shelfUser('management');

    $this->actingAs($admin)->get(route('administration.shelves.index'))->assertOk()->assertSee('Regalbretter');

    $this->actingAs($admin)->post(route('administration.shelves.store'), ['code' => 'R3-B2', 'label' => 'Fantasy ab 10 Jahren', 'sort_order' => 5])
        ->assertRedirect(route('administration.shelves.index'))->assertSessionHas('shelf_success');

    $shelf = CatalogShelf::query()->where('code', 'R3-B2')->firstOrFail();
    expect($shelf->label)->toBe('Fantasy ab 10 Jahren')->and($shelf->is_active)->toBeTrue()->and($shelf->display())->toBe('R3-B2 · Fantasy ab 10 Jahren');

    $this->actingAs($admin)->get(route('administration.shelves.index'))->assertSee('R3-B2')->assertSee('Fantasy ab 10 Jahren');

    $this->actingAs($admin)->post(route('administration.shelves.store'), ['code' => 'R3-B2'])->assertSessionHasErrors('shelf');
    $this->actingAs($admin)->post(route('administration.shelves.store'), ['code' => ''])->assertSessionHasErrors('code');
    $this->actingAs($admin)->post(route('administration.shelves.store'), ['code' => str_repeat('x', 41)])->assertSessionHasErrors('code');
});

it('moves the copies along when a shelf is renamed and refuses to delete shelves in use', function (): void {
    $admin = shelfUser('management');
    $shelf = CatalogShelf::query()->create(['code' => 'R1-B1']);
    $empty = CatalogShelf::query()->create(['code' => 'R9-B9']);
    $copy = shelfCopy('0010001', 'R1-B1');
    $other = shelfCopy('0010002', 'R7-B7');

    $this->actingAs($admin)->patch(route('administration.shelves.update', ['shelfId' => $shelf->getKey()]), ['code' => 'R1-B2', 'label' => 'Neu beschriftet', 'sort_order' => 1, 'is_active' => 1])
        ->assertRedirect(route('administration.shelves.index'));

    expect($copy->refresh()->shelf_location)->toBe('R1-B2')->and($other->refresh()->shelf_location)->toBe('R7-B7');

    $this->actingAs($admin)->delete(route('administration.shelves.destroy', ['shelfId' => $shelf->getKey()]))->assertSessionHasErrors('shelf');
    expect(CatalogShelf::query()->whereKey($shelf->getKey())->exists())->toBeTrue();

    $this->actingAs($admin)->delete(route('administration.shelves.destroy', ['shelfId' => $empty->getKey()]))->assertSessionHas('shelf_success');
    expect(CatalogShelf::query()->whereKey($empty->getKey())->exists())->toBeFalse();

    // Ausgeschaltet: nicht mehr wählbar, am Exemplar bleibt der Wert.
    $this->actingAs($admin)->patch(route('administration.shelves.update', ['shelfId' => $shelf->getKey()]), ['code' => 'R1-B2', 'sort_order' => 1, 'is_active' => 0]);
    expect($shelf->refresh()->is_active)->toBeFalse();

    $staff = shelfUser('staff');
    $edition = $copy->edition;
    $this->actingAs($staff)->post(route('pos.catalog.copies.store', ['editionId' => $edition->getKey()]), ['barcode' => '0010003', 'shelf_location' => 'R1-B2', 'status' => 'active'])->assertSessionHasErrors('shelf_location');
});

it('keeps shelf management for the administration only', function (): void {
    $this->actingAs(shelfUser('student_ag_basic'))->get(route('administration.shelves.index'))->assertForbidden();
    $this->actingAs(shelfUser('staff'))->get(route('administration.shelves.index'))->assertOk()->assertSee('Regalbretter');
    $this->actingAs(shelfUser('student_ag_extended'))->get(route('administration.shelves.index'))->assertForbidden();
    $this->actingAs(shelfUser('management'))->get(route('administration.shelves.index'))->assertOk();
    $this->actingAs(shelfUser('management'))->get(route('pos.processes'))->assertSee('Regalbretter pflegen');
});

it('seeds shelves from the free text locations that already exist', function (): void {
    shelfCopy('0010010', 'J 5 ENDE');
    shelfCopy('0010011', 'J 5 ENDE');
    shelfCopy('0010012', 'HÖR ENDE');
    shelfCopy('0010013', null);

    $migration = require base_path('app/Modules/Catalog/database/migrations/2026_10_07_150000_create_catalog_shelves_table.php');
    Schema::drop('catalog_shelves');
    $migration->up();

    expect(CatalogShelf::query()->orderBy('code')->pluck('code')->all())->toBe(['HÖR ENDE', 'J 5 ENDE']);
})->skip(fn (): bool => DB::connection()->getDriverName() !== 'sqlite', 'Löscht und legt die Tabelle neu an (DDL): nur mit SQLite im Speicher, wo das mit dem Test zurückgerollt wird.');

it('offers the shelves as a dropdown while editing a copy and keeps an old location selectable', function (): void {
    $staff = shelfUser('staff');
    CatalogShelf::query()->create(['code' => 'R3-B2', 'label' => 'Fantasy']);
    $copy = shelfCopy('0010020', 'Altes Regal');

    $this->actingAs($staff)
        ->get(route('pos.catalog.copies.edit', ['editionId' => $copy->edition_id, 'copyId' => $copy->getKey()]))
        ->assertOk()
        ->assertSee('<select', false)
        ->assertSee('R3-B2 · Fantasy')
        ->assertSee('Altes Regal (nicht mehr in der Liste)');

    $this->actingAs($staff)->get(route('pos.catalog.editions.edit', ['editionId' => $copy->edition_id]))->assertOk()->assertSee('beim Einsortieren ins Regal');
});

it('suggests a summary from google books', function (): void {
    Http::fake([
        'www.googleapis.com/*' => Http::response(['items' => [['volumeInfo' => ['description' => '<p>Ein <b>spannendes</b> Abenteuer um einen Jungen, der auszieht,<br>um die Welt zu retten und neue Freunde zu finden.</p>']]]]),
    ]);

    $found = app(CatalogSummaryService::class)->findByIsbn('978-3-522-20280-0');
    expect($found['source'])->toBe('Google Books')
        ->and($found['text'])->toBe("Ein spannendes Abenteuer um einen Jungen, der auszieht,\num die Welt zu retten und neue Freunde zu finden.");
});

it('falls back to open library when google books has no description', function (): void {
    Http::fake([
        'www.googleapis.com/*' => Http::response(['items' => []]),
        'openlibrary.org/isbn/*' => Http::response(['works' => [['key' => '/works/OL1W']]]),
        'openlibrary.org/works/*' => Http::response(['description' => ['type' => '/type/text', 'value' => 'A long enough description of this particular work that tells the story.']]),
    ]);

    expect(app(CatalogSummaryService::class)->findByIsbn('9783522202800')['source'])->toBe('Open Library');
});

it('returns nothing when no source has a usable summary or the isbn is invalid', function (): void {
    Http::fake([
        'www.googleapis.com/*' => Http::response(['items' => [['volumeInfo' => ['description' => 'Zu kurz.']]]]),
        'openlibrary.org/*' => Http::response('', 404),
    ]);

    expect(app(CatalogSummaryService::class)->findByIsbn('9783522202800'))->toBeNull()
        ->and(app(CatalogSummaryService::class)->findByIsbn('abc'))->toBeNull();

    Http::fake(['*' => Http::response('', 500)]);
    expect(app(CatalogSummaryService::class)->findByIsbn('9783522202800'))->toBeNull();
});

it('prefills the summary in the intake when the DNB record has none', function (): void {
    $staff = shelfUser('staff');
    CatalogShelf::query()->create(['code' => 'J 5 MORO']);

    Http::fake([
        'services.dnb.de/*' => Http::response((string) file_get_contents(__DIR__.'/../Fixtures/dnb/isbn-9783522202800.xml'), 200),
        'www.googleapis.com/*' => Http::response(['items' => [['volumeInfo' => ['description' => 'Der Klappentext zu diesem Buch erzählt von Piraten, Karate und einer mutigen Heldin.']]]]),
    ]);

    $this->actingAs($staff)->post(route('pos.catalog.intake.barcode'), ['barcode' => '0012999'])->assertRedirect(route('pos.catalog.intake.medium'));
    $this->post(route('pos.catalog.intake.lookup'), ['isbn' => '978-3-522-20280-0', 'title' => '', 'person' => '', 'action' => 'lookup'])->assertRedirect(route('pos.catalog.intake.details'));

    $this->get(route('pos.catalog.intake.details'))->assertOk()->assertSee('Der Klappentext zu diesem Buch')->assertSee('Automatisch von Google Books vorgeschlagen');

});

it('builds the location from group, area, rack and board and keeps it in step when a level is renamed', function (): void {
    $admin = shelfUser('management');

    $this->actingAs($admin)->post(route('administration.sections.store'), ['kind' => 'group', 'code' => 'I', 'name' => 'Kinderbibliothek'])->assertSessionHas('shelf_success');
    $group = CatalogShelfSection::query()->where('kind', 'group')->firstOrFail();
    $this->actingAs($admin)->post(route('administration.sections.store'), ['kind' => 'area', 'parent_id' => (string) $group->getKey(), 'code' => 'A'])->assertSessionHas('shelf_success');
    $area = CatalogShelfSection::query()->where('kind', 'area')->firstOrFail();
    $this->actingAs($admin)->post(route('administration.sections.store'), ['kind' => 'rack', 'parent_id' => (string) $area->getKey(), 'code' => '1', 'name' => 'Wand links'])->assertSessionHas('shelf_success');
    $rack = CatalogShelfSection::query()->where('kind', 'rack')->firstOrFail();

    $this->actingAs($admin)->post(route('administration.shelves.store'), ['rack_id' => (string) $rack->getKey(), 'board' => 'a', 'label' => 'Lesestart'])->assertSessionHas('shelf_success');
    $shelf = CatalogShelf::query()->firstOrFail();
    expect($shelf->code)->toBe('I. A 1 a')->and($shelf->board)->toBe('a')->and($shelf->section_id)->toBe((string) $rack->getKey());

    $page = $this->actingAs($admin)->get(route('administration.shelves.index'))->assertOk();
    $page->assertSee('Kinderbibliothek')->assertSee('Wand links')->assertSee('I. A 1 a')->assertSee('Bereichsgruppe')->assertSee('Regalbrett');

    // Gleicher Standort doppelt, Regal ohne Bezeichnung des Bretts, falsche Ebene.
    $this->actingAs($admin)->post(route('administration.shelves.store'), ['rack_id' => (string) $rack->getKey(), 'board' => 'a'])->assertSessionHasErrors('shelf');
    $this->actingAs($admin)->post(route('administration.shelves.store'), ['rack_id' => (string) $rack->getKey()])->assertSessionHasErrors('board');
    $this->actingAs($admin)->post(route('administration.shelves.store'), ['rack_id' => (string) $area->getKey(), 'board' => 'b'])->assertSessionHasErrors('rack_id');
    $this->actingAs($admin)->post(route('administration.sections.store'), ['kind' => 'area', 'parent_id' => (string) $rack->getKey(), 'code' => 'X'])->assertSessionHasErrors('shelf');
    $this->actingAs($admin)->post(route('administration.sections.store'), ['kind' => 'rack', 'parent_id' => (string) $area->getKey(), 'code' => '1'])->assertSessionHasErrors('shelf');

    // Das Exemplar zieht mit, wenn das Regal umbenannt wird.
    $copy = shelfCopy('0010100', 'I. A 1 a');
    $this->actingAs($admin)->patch(route('administration.sections.update', ['sectionId' => $rack->getKey()]), ['code' => '2', 'name' => 'Wand links'])->assertSessionHas('shelf_success');
    expect($shelf->refresh()->code)->toBe('I. A 2 a')->and($copy->refresh()->shelf_location)->toBe('I. A 2 a');

    $this->actingAs($admin)->patch(route('administration.sections.update', ['sectionId' => $group->getKey()]), ['code' => 'II'])->assertSessionHas('shelf_success');
    expect($shelf->refresh()->code)->toBe('II. A 2 a')->and($copy->refresh()->shelf_location)->toBe('II. A 2 a');

    // Nur leere Ebenen lassen sich löschen.
    $this->actingAs($admin)->delete(route('administration.sections.destroy', ['sectionId' => $rack->getKey()]))->assertSessionHasErrors('shelf');
    $this->actingAs($admin)->delete(route('administration.sections.destroy', ['sectionId' => $group->getKey()]))->assertSessionHasErrors('shelf');
});

it('assigns shelves with an old location code to their rack', function (): void {
    $admin = shelfUser('management');
    CatalogShelf::query()->create(['code' => 'I. B 2 c']);
    CatalogShelf::query()->create(['code' => 'I. B 2 d']);
    CatalogShelf::query()->create(['code' => 'Sonderregal']);

    $this->actingAs($admin)->get(route('administration.shelves.index'))->assertSee('Regalbretter ohne Regal');
    $this->actingAs($admin)->post(route('administration.sections.assign'))->assertSessionHas('shelf_success', '2 Regalbretter wurden ihrem Regal zugeordnet.');

    expect(CatalogShelfSection::query()->where('kind', 'group')->count())->toBe(1)
        ->and(CatalogShelfSection::query()->where('kind', 'area')->count())->toBe(1)
        ->and(CatalogShelfSection::query()->where('kind', 'rack')->count())->toBe(1)
        ->and(CatalogShelf::query()->where('code', 'I. B 2 d')->firstOrFail()->board)->toBe('d')
        ->and(CatalogShelf::query()->whereNull('section_id')->pluck('code')->all())->toBe(['Sonderregal']);
});

it('lists the shelves grouped by rack when shelving', function (): void {
    $helper = shelfUser('student_ag_basic');
    $structure = app(CatalogShelfStructure::class);
    CatalogShelf::query()->create(['code' => 'I. A 1 a']);
    CatalogShelf::query()->create(['code' => 'Sonderregal']);
    $structure->assignAll();
    shelfCopy('0010200', null);

    $this->actingAs($helper)->get(route('pos.shelving', ['buch' => '0010200']))->assertOk()
        ->assertSee('<optgroup label="I › A › 1">', false)->assertSee('<optgroup label="Ohne Regal">', false);
});

it('searches the shelves by location, label, topic and rack name and filters shelves without a topic', function (): void {
    $admin = shelfUser('management');
    $topic = CatalogTopic::query()->create(['name' => 'Weltraum']);
    $a = CatalogShelf::query()->create(['code' => 'I. A 1 a', 'label' => 'Rätsel & Knobeln']);
    $b = CatalogShelf::query()->create(['code' => 'I. A 2 b', 'label' => 'Sterne und Planeten']);
    $b->topics()->attach($topic->getKey(), ['position' => 1]);
    CatalogShelf::query()->create(['code' => 'Sonderregal', 'label' => 'Lose Bücher']);
    app(CatalogShelfStructure::class)->assignAll();
    CatalogShelfSection::query()->where('kind', 'rack')->where('code', '2')->update(['name' => 'Wand links']);

    $page = fn (array $query) => $this->actingAs($admin)->get(route('administration.shelves.index', $query))->assertOk();

    $page([])->assertSee('Rätsel &amp; Knobeln', false)->assertSee('Sterne und Planeten')->assertSee('Lose Bücher');
    $page(['q' => 'knobeln'])->assertSee('Rätsel &amp; Knobeln', false)->assertDontSee('Sterne und Planeten')->assertDontSee('Lose Bücher')->assertSeeText('1 Regalbrett passt');
    $page(['q' => 'weltraum'])->assertSee('Sterne und Planeten')->assertDontSee('Rätsel &amp; Knobeln', false);
    $page(['q' => 'wand links'])->assertSee('Sterne und Planeten')->assertDontSee('Lose Bücher');
    $page(['q' => 'I. A'])->assertSee('Sterne und Planeten')->assertSee('Rätsel &amp; Knobeln', false)->assertDontSee('Lose Bücher');
    $page(['ohne_thema' => 1])->assertSee('Rätsel &amp; Knobeln', false)->assertSee('Lose Bücher')->assertDontSee('Sterne und Planeten')->assertSeeText('2 Regalbretter passen');
    $page(['q' => 'gibt es nicht'])->assertSeeText('0 Regalbretter passen');
});

it('shows how full a shelf is when a capacity is set', function (): void {
    $admin = shelfUser('management');
    $this->actingAs($admin)->post(route('administration.shelves.store'), ['code' => 'R1', 'capacity' => 2])->assertSessionHas('shelf_success');
    shelfCopy('0010300', 'R1');
    shelfCopy('0010301', 'R1');

    $this->actingAs($admin)->get(route('administration.shelves.index'))->assertSee('2 von 2')->assertSee('voll');

    $shelf = CatalogShelf::query()->where('code', 'R1')->firstOrFail();
    expect($shelf->capacity)->toBe(2);
    $this->actingAs($admin)->patch(route('administration.shelves.update', ['shelfId' => $shelf->getKey()]), ['code' => 'R1', 'capacity' => 5, 'is_active' => 1])->assertSessionHas('shelf_success');
    $this->actingAs($admin)->get(route('administration.shelves.index'))->assertSee('3 frei')->assertDontSee('>voll<', false);
});
