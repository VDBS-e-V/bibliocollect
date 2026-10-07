<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Identity\Actions\AssignRoleAction;
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

it('keeps signatures and topics for staff and administration only', function (): void {
    foreach (['administration.signatures.index', 'administration.topics.index'] as $route) {
        $this->actingAs(taxonomyUser('management'))->get(route($route))->assertOk();
        $this->actingAs(taxonomyUser('staff'))->get(route($route))->assertOk();
        $this->actingAs(taxonomyUser('student_ag_extended'))->get(route($route))->assertForbidden();
    }
});

it('creates and changes signatures with the topics in the chosen order', function (): void {
    $admin = taxonomyUser('management');
    $root = CatalogTopic::query()->create(['name' => 'Geschichten']);
    $one = CatalogTopic::query()->create(['name' => 'Abenteuer', 'parent_id' => $root->getKey()]);
    $two = CatalogTopic::query()->create(['name' => 'Sport', 'parent_id' => $root->getKey()]);

    $this->actingAs($admin)->get(route('administration.signatures.index'))->assertSee('Abenteuer')->assertSee('Geschichten');

    $this->actingAs($admin)->post(route('administration.signatures.store'), ['signature' => 'I. A 4 a', 'topics' => [(string) $two->getKey(), (string) $one->getKey()]])->assertRedirect(route('administration.signatures.index'))->assertSessionHas('taxonomy_success');

    $signature = CatalogSignature::query()->where('signature', 'I. A 4 a')->firstOrFail();
    expect($signature->topics->pluck('name')->all())->toBe(['Sport', 'Abenteuer']);

    $this->actingAs($admin)->patch(route('administration.signatures.update', ['signatureId' => $signature->getKey()]), ['signature' => 'I. A 4 b', 'topics' => [(string) $one->getKey()]])->assertSessionHas('taxonomy_success');
    expect($signature->refresh()->signature)->toBe('I. A 4 b')->and($signature->topics()->pluck('name')->all())->toBe(['Abenteuer']);

    $this->actingAs($admin)->post(route('administration.signatures.store'), ['signature' => 'I. A 4 b'])->assertSessionHasErrors('signature');
    $this->actingAs($admin)->post(route('administration.signatures.store'), ['signature' => ''])->assertSessionHasErrors('signature');
    $this->actingAs($admin)->post(route('administration.signatures.store'), ['signature' => 'X', 'topics' => ['gibt-es-nicht']])->assertSessionHasErrors('topics.0');
});

it('refuses to delete signatures and topics that are in use', function (): void {
    $admin = taxonomyUser('management');
    $root = CatalogTopic::query()->create(['name' => 'Wurzel']);
    $child = CatalogTopic::query()->create(['name' => 'Kind', 'parent_id' => $root->getKey()]);
    $used = CatalogSignature::query()->create(['signature' => 'S1']);
    $used->topics()->attach($child->getKey(), ['position' => 1]);
    taxonomyCopy('0030001', null, null, $used);
    $free = CatalogSignature::query()->create(['signature' => 'S2']);

    $this->actingAs($admin)->delete(route('administration.signatures.destroy', ['signatureId' => $used->getKey()]))->assertSessionHasErrors('signature');
    $this->actingAs($admin)->delete(route('administration.signatures.destroy', ['signatureId' => $free->getKey()]))->assertSessionHas('taxonomy_success');
    expect(CatalogSignature::query()->count())->toBe(1);

    $this->actingAs($admin)->delete(route('administration.topics.destroy', ['topicId' => $root->getKey()]))->assertSessionHasErrors('topic');
    $this->actingAs($admin)->delete(route('administration.topics.destroy', ['topicId' => $child->getKey()]))->assertSessionHasErrors('topic');

    $used->topics()->detach();
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

it('sets the signature when a copy is recorded, added and edited', function (): void {
    $staff = taxonomyUser('staff');
    $signature = CatalogSignature::query()->create(['signature' => 'I. A 1 d']);
    $topic = CatalogTopic::query()->create(['name' => 'Leicht zu lesen']);
    $signature->topics()->attach($topic->getKey(), ['position' => 1]);
    $copy = taxonomyCopy('0030010');

    $this->actingAs($staff)->get(route('pos.catalog.copies.edit', ['editionId' => $copy->edition_id, 'copyId' => $copy->getKey()]))->assertOk()->assertSee('I. A 1 d · Leicht zu lesen');
    $this->actingAs($staff)->get(route('pos.catalog.editions.edit', ['editionId' => $copy->edition_id]))->assertOk()->assertSee('I. A 1 d · Leicht zu lesen');

    $this->actingAs($staff)->patch(route('pos.catalog.copies.update', ['editionId' => $copy->edition_id, 'copyId' => $copy->getKey()]), ['barcode' => '0030010', 'status' => 'active', 'signature_id' => (string) $signature->getKey()])->assertSessionHasNoErrors();
    expect($copy->refresh()->signature_id)->toBe($signature->getKey());

    $this->actingAs($staff)->post(route('pos.catalog.copies.store', ['editionId' => $copy->edition_id]), ['barcode' => '0030011', 'status' => 'active', 'signature_id' => (string) $signature->getKey()])->assertSessionHasNoErrors();
    expect(Copy::query()->where('barcode', '0030011')->firstOrFail()->signature_id)->toBe($signature->getKey());

    $this->actingAs($staff)->post(route('pos.catalog.copies.store', ['editionId' => $copy->edition_id]), ['barcode' => '0030012', 'status' => 'active', 'signature_id' => 'gibt-es-nicht'])->assertSessionHasErrors('signature_id');
});

it('suggests the shelf from the signature, then from the other copies of the edition, and learns the signature', function (): void {
    $helper = taxonomyUser('student_ag_basic');
    $signature = CatalogSignature::query()->create(['signature' => 'I. A 4 a']);
    CatalogShelf::query()->create(['code' => 'I. A 4 a', 'signature_id' => $signature->getKey()]);
    CatalogShelf::query()->create(['code' => 'R7-B1']);
    CatalogShelf::query()->create(['code' => 'LETZTES']);

    // 1. Signatur
    $withSignature = taxonomyCopy('0030020', null, null, $signature);
    $this->actingAs($helper)->withSession(['shelving.last_shelf' => 'LETZTES'])->get(route('pos.shelving', ['buch' => '0030020']))->assertSee('<option value="I. A 4 a" selected>', false);

    // 2. Geschwister-Exemplar
    $first = taxonomyCopy('0030021', 'R7-B1');
    $first->forceFill(['shelved_at' => now()])->save();
    $second = taxonomyCopy('0030022', null, $first->edition);
    $this->actingAs($helper)->withSession(['shelving.last_shelf' => 'LETZTES'])->get(route('pos.shelving', ['buch' => '0030022']))->assertSee('<option value="R7-B1" selected>', false);

    // 3. Zuletzt benutzt
    $alone = taxonomyCopy('0030023');
    $this->actingAs($helper)->withSession(['shelving.last_shelf' => 'LETZTES'])->get(route('pos.shelving', ['buch' => '0030023']))->assertSee('<option value="LETZTES" selected>', false);

    // Lernen: Ein Buch ohne Signatur bekommt die des Regalbretts.
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0030023', 'regalbrett' => 'I. A 4 a'])->assertSessionHas('shelving_notice');
    expect($alone->refresh()->signature_id)->toBe($signature->getKey())->and($alone->shelf_location)->toBe('I. A 4 a');

    // Eine vorhandene Signatur bleibt.
    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0030020', 'regalbrett' => 'R7-B1']);
    expect($withSignature->refresh()->signature_id)->toBe($signature->getKey());
    expect($second->refresh()->shelf_location)->toBeNull();
});

it('connects a shelf with a signature on the shelf page', function (): void {
    $admin = taxonomyUser('management');
    $signature = CatalogSignature::query()->create(['signature' => 'I. A 6 a']);

    $this->actingAs($admin)->post(route('administration.shelves.store'), ['code' => 'Comics-1', 'signature_id' => (string) $signature->getKey()])->assertSessionHas('shelf_success');
    $shelf = CatalogShelf::query()->where('code', 'Comics-1')->firstOrFail();
    expect($shelf->signature_id)->toBe($signature->getKey());

    $this->actingAs($admin)->patch(route('administration.shelves.update', ['shelfId' => $shelf->getKey()]), ['code' => 'Comics-1', 'sort_order' => 1, 'is_active' => 1, 'signature_id' => ''])->assertSessionHas('shelf_success');
    expect($shelf->refresh()->signature_id)->toBeNull();

    $this->actingAs($admin)->post(route('administration.shelves.store'), ['code' => 'Falsch', 'signature_id' => 'gibt-es-nicht'])->assertSessionHasErrors('signature_id');
});

it('offers the signature in the intake and keeps it on the recorded copy', function (): void {
    Http::fake(['*' => Http::response('', 500)]);
    $staff = taxonomyUser('staff');
    $signature = CatalogSignature::query()->create(['signature' => 'I. A 5 b']);
    $topic = CatalogTopic::query()->create(['name' => 'Manga']);
    $signature->topics()->attach($topic->getKey(), ['position' => 1]);

    $this->actingAs($staff)->post(route('pos.catalog.intake.barcode'), ['barcode' => '0030030']);
    $this->post(route('pos.catalog.intake.lookup'), ['isbn' => '', 'title' => '', 'person' => '', 'action' => 'manual']);
    $this->post(route('pos.catalog.intake.details.store'), ['preferred_title' => 'Ein Manga', 'media_type' => 'book', 'language_code' => 'de']);

    $this->get(route('pos.catalog.intake.copy'))->assertOk()->assertSee('I. A 5 b · Manga')->assertSee('Themenbereich / Signatur');
    $this->post(route('pos.catalog.intake.copy.store'), ['status' => 'active', 'signature_id' => (string) $signature->getKey()])->assertRedirect(route('pos.catalog.intake.review'));
    $this->post(route('pos.catalog.intake.commit'), ['next' => 'open'])->assertRedirect();

    $copy = Copy::query()->where('barcode', '0030030')->firstOrFail();
    expect($copy->signature_id)->toBe($signature->getKey())->and($copy->shelf_location)->toBeNull();
});
