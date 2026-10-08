<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function taxonomyUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function taxonomyCopy(string $barcode, ?string $location = null, ?Edition $edition = null, ?CatalogSignature $signature = null): Copy
{
    if ($edition === null) {
        $title = Title::query()->create(['preferred_title' => 'Buch '.$barcode, 'sort_title' => 'Buch '.$barcode]);
        $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    }

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => 'active', 'shelf_location' => $location, 'signature_id' => $signature?->getKey()]);
}

it('keeps topics and shelves for staff and administration only', function (): void {
    foreach (['administration.shelves.index', 'administration.topics.index'] as $route) {
        $this->actingAs(taxonomyUser('management'))->get(route($route))->assertOk();
        $this->actingAs(taxonomyUser('staff'))->get(route($route))->assertOk();
        $this->actingAs(taxonomyUser('student_ag_extended'))->get(route($route))->assertForbidden();
    }
});

it('assigns topics to shelves in the chosen order, one topic to several shelves', function (): void {
    $admin = taxonomyUser('management');
    $root = CatalogTopic::query()->create(['name' => 'Geschichten']);
    $one = CatalogTopic::query()->create(['name' => 'Abenteuer', 'parent_id' => $root->getKey()]);
    $two = CatalogTopic::query()->create(['name' => 'Sport', 'parent_id' => $root->getKey()]);

    $this->actingAs($admin)->get(route('administration.shelves.index'))->assertSee('Abenteuer')->assertSee('Geschichten')->assertDontSee('Signatur');

    $this->actingAs($admin)->post(route('administration.shelves.store'), ['code' => 'R4-A', 'topics' => [(string) $two->getKey(), (string) $one->getKey()]])->assertRedirect(route('administration.shelves.index'))->assertSessionHas('shelf_success');
    $this->actingAs($admin)->post(route('administration.shelves.store'), ['code' => 'R4-B', 'topics' => [(string) $one->getKey()]])->assertSessionHas('shelf_success');

    $shelf = CatalogShelf::query()->where('code', 'R4-A')->firstOrFail();
    expect($shelf->topics->pluck('name')->all())->toBe(['Sport', 'Abenteuer'])
        ->and($one->shelves()->pluck('code')->sort()->values()->all())->toBe(['R4-A', 'R4-B']);

    $this->actingAs($admin)->patch(route('administration.shelves.update', ['shelfId' => $shelf->getKey()]), ['code' => 'R4-A', 'sort_order' => 1, 'is_active' => 1, 'topics' => [(string) $one->getKey()]])->assertSessionHas('shelf_success');
    expect($shelf->refresh()->topics->pluck('name')->all())->toBe(['Abenteuer']);

    $this->actingAs($admin)->patch(route('administration.shelves.update', ['shelfId' => $shelf->getKey()]), ['code' => 'R4-A', 'sort_order' => 1, 'is_active' => 1])->assertSessionHas('shelf_success');
    expect($shelf->refresh()->topics)->toHaveCount(0);

    $this->actingAs($admin)->post(route('administration.shelves.store'), ['code' => 'X', 'topics' => ['gibt-es-nicht']])->assertSessionHasErrors('topics.0');
});

it('refuses to delete topics that are in use', function (): void {
    $admin = taxonomyUser('management');
    $root = CatalogTopic::query()->create(['name' => 'Wurzel']);
    $child = CatalogTopic::query()->create(['name' => 'Kind', 'parent_id' => $root->getKey()]);
    $shelf = CatalogShelf::query()->create(['code' => 'S1']);
    $shelf->topics()->attach($child->getKey(), ['position' => 1]);

    $this->actingAs($admin)->delete(route('administration.topics.destroy', ['topicId' => $root->getKey()]))->assertSessionHasErrors('topic');
    $this->actingAs($admin)->delete(route('administration.topics.destroy', ['topicId' => $child->getKey()]))->assertSessionHasErrors('topic');

    $shelf->topics()->detach();
    $this->actingAs($admin)->delete(route('administration.topics.destroy', ['topicId' => $child->getKey()]))->assertSessionHas('taxonomy_success');
    $this->actingAs($admin)->delete(route('administration.topics.destroy', ['topicId' => $root->getKey()]))->assertSessionHas('taxonomy_success');
    expect(CatalogTopic::query()->count())->toBe(0);
});

it('manages topics and prevents loops in the tree', function (): void {
    $admin = taxonomyUser('staff');

    $this->actingAs($admin)->post(route('administration.topics.store'), ['name' => 'Sachbücher', 'public_key' => '2000', 'description' => 'Wissen'])->assertSessionHas('taxonomy_success');
    $root = CatalogTopic::query()->where('name', 'Sachbücher')->firstOrFail();

    $this->actingAs($admin)->post(route('administration.topics.store'), ['name' => 'Weltraum', 'parent_id' => (string) $root->getKey()])->assertSessionHas('taxonomy_success');
    $child = CatalogTopic::query()->where('name', 'Weltraum')->firstOrFail();
    expect($child->parent_id)->toBe($root->getKey());

    $this->actingAs($admin)->patch(route('administration.topics.update', ['topicId' => $root->getKey()]), ['name' => 'Sachbücher', 'parent_id' => (string) $child->getKey()])->assertSessionHasErrors('topic');
    expect($root->refresh()->parent_id)->toBeNull();

    $this->actingAs($admin)->patch(route('administration.topics.update', ['topicId' => $child->getKey()]), ['name' => 'Raumfahrt', 'description' => 'Sterne'])->assertSessionHas('taxonomy_success');
    expect($child->refresh()->name)->toBe('Raumfahrt')->and($child->description)->toBe('Sterne');

    $this->actingAs($admin)->post(route('administration.topics.store'), ['name' => ''])->assertSessionHasErrors('name');
});

it('sets the access when a copy is added and edited and keeps the learned signature', function (): void {
    $staff = taxonomyUser('staff');
    $signature = CatalogSignature::query()->create(['signature' => 'I. A 1 d']);
    $copy = taxonomyCopy('0030010', null, null, $signature);

    // Keine Signatur-Auswahl mehr, dafür die Zugänglichkeit.
    $this->actingAs($staff)->get(route('pos.catalog.copies.edit', ['editionId' => $copy->edition_id, 'copyId' => $copy->getKey()]))->assertOk()->assertSee('Zugänglichkeit')->assertDontSee('Themenbereich / Signatur')->assertSee('Nur auf Nachfrage (verschlossen)');
    $this->actingAs($staff)->get(route('pos.catalog.editions.edit', ['editionId' => $copy->edition_id]))->assertOk()->assertSee('Zugänglichkeit')->assertDontSee('Themenbereich / Signatur');

    $this->actingAs($staff)->patch(route('pos.catalog.copies.update', ['editionId' => $copy->edition_id, 'copyId' => $copy->getKey()]), ['barcode' => '0030010', 'status' => 'active', 'access_status' => 'nur_nachfrage'])->assertSessionHasNoErrors();
    expect($copy->refresh()->access_status)->toBe('nur_nachfrage')->and($copy->signature_id)->toBe($signature->getKey());

    $this->actingAs($staff)->post(route('pos.catalog.copies.store', ['editionId' => $copy->edition_id]), ['barcode' => '0030011', 'status' => 'active', 'access_status' => 'nur_bibliothek'])->assertSessionHasNoErrors();
    expect(Copy::query()->where('barcode', '0030011')->firstOrFail()->access_status)->toBe('nur_bibliothek');

    $this->actingAs($staff)->post(route('pos.catalog.copies.store', ['editionId' => $copy->edition_id]), ['barcode' => '0030012', 'status' => 'active', 'access_status' => 'geheim'])->assertSessionHasErrors('access_status');
});

it('suggests the shelf from the other copies of the edition, then from the topic, then the last used', function (): void {
    $helper = taxonomyUser('student_ag_basic');
    $topic = CatalogTopic::query()->create(['name' => 'Abenteuer']);
    $suggested = CatalogShelf::query()->create(['code' => 'I. A 4 a']);
    $suggested->topics()->attach($topic->getKey(), ['position' => 1]);
    CatalogShelf::query()->create(['code' => 'R7-B1']);
    CatalogShelf::query()->create(['code' => 'LETZTES']);

    // 1. Geschwister-Exemplar steht schon im Regal (vor dem Thema)
    $first = taxonomyCopy('0030021', 'R7-B1');
    $first->edition->forceFill(['local_classification' => 'Abenteuer'])->save();
    $first->forceFill(['shelved_at' => now()])->save();
    $second = taxonomyCopy('0030022', null, $first->edition);
    $this->actingAs($helper)->withSession(['shelving.last_shelf' => 'LETZTES'])->get(route('pos.shelving', ['buch' => '0030022']))->assertSee('<option value="R7-B1" selected>', false);

    // 2. Thema des Mediums
    $withTopic = taxonomyCopy('0030020');
    $withTopic->edition->forceFill(['local_classification' => 'Abenteuer'])->save();
    $this->actingAs($helper)->withSession(['shelving.last_shelf' => 'LETZTES'])->get(route('pos.shelving', ['buch' => '0030020']))->assertSee('<option value="I. A 4 a" selected>', false);

    // 3. Zuletzt benutzt
    $alone = taxonomyCopy('0030023');
    $this->actingAs($helper)->withSession(['shelving.last_shelf' => 'LETZTES'])->get(route('pos.shelving', ['buch' => '0030023']))->assertSee('<option value="LETZTES" selected>', false);

    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0030023', 'regalbrett' => 'I. A 4 a'])->assertSessionHas('shelving_notice');
    expect($alone->refresh()->shelf_location)->toBe('I. A 4 a')->and($alone->signature_id)->toBeNull()->and($second->refresh()->shelf_location)->toBeNull();
});

it('offers topics only in the details step and the access in the copy step of the intake', function (): void {
    Http::fake(['*' => Http::response('', 500)]);
    $staff = taxonomyUser('staff');
    $signature = CatalogSignature::query()->create(['signature' => 'I. A 5 b']);
    $parent = CatalogTopic::query()->create(['name' => 'Geschichten']);
    $child = CatalogTopic::query()->create(['name' => 'Manga', 'parent_id' => $parent->getKey()]);
    $signature->topics()->attach($child->getKey(), ['position' => 1]);

    $this->actingAs($staff)->post(route('pos.catalog.intake.barcode'), ['barcode' => '0030030']);
    $this->post(route('pos.catalog.intake.lookup'), ['isbn' => '', 'title' => '', 'person' => '', 'action' => 'manual']);

    $this->get(route('pos.catalog.intake.details'))->assertOk()
        ->assertSee('Themenbereich')
        ->assertDontSee('Lokale Klassifikation')
        ->assertSee('Geschichten')
        ->assertSee('– Manga')
        ->assertDontSee('I. A 5 b');

    $this->post(route('pos.catalog.intake.details.store'), ['preferred_title' => 'Ein Manga', 'media_type' => 'book', 'language_code' => 'de', 'local_classification' => 'Manga']);

    $this->get(route('pos.catalog.intake.copy'))->assertOk()
        ->assertSee('Zugänglichkeit')
        ->assertSee('Frei zugänglich')
        ->assertSee('Nur Nutzung in der Bibliothek')
        ->assertDontSee('Themenbereich / Signatur')
        ->assertDontSee('I. A 5 b');

    $this->post(route('pos.catalog.intake.copy.store'), ['status' => 'active', 'access_status' => 'nur_nachfrage'])->assertRedirect(route('pos.catalog.intake.review'));
    $this->get(route('pos.catalog.intake.review'))->assertOk()->assertSee('Nur auf Nachfrage (verschlossen)')->assertSee('Manga');
    $this->post(route('pos.catalog.intake.commit'), ['next' => 'open'])->assertRedirect();

    $copy = Copy::query()->where('barcode', '0030030')->with('edition')->firstOrFail();
    expect($copy->access_status)->toBe('nur_nachfrage')
        ->and($copy->edition->local_classification)->toBe('Manga')
        ->and($copy->signature_id)->toBeNull()
        ->and($copy->shelf_location)->toBeNull();
});

it('does not lend copies that may only be used in the library', function (): void {
    $staff = taxonomyUser('staff');
    $copy = taxonomyCopy('0030040');
    $copy->forceFill(['access_status' => 'nur_bibliothek'])->save();
    $patron = Patron::query()->create(['library_number' => '111222', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Lea', 'last_name' => 'Lesesaal', 'birth_date' => '2010-01-01']);
    LibraryOpeningHour::query()->create(['day_of_week' => 1, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00']);

    expect(fn () => app(CheckoutCopyAction::class)->execute($patron, '0030040', $staff))
        ->toThrow(CirculationRuleViolation::class, 'nur in der Bibliothek');

    $copy->forceFill(['access_status' => 'nur_nachfrage'])->save();
    expect(app(CheckoutCopyAction::class)->execute($patron, '0030040', $staff)->copy_id)->toBe((string) $copy->getKey());
});
