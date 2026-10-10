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
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function deleteOptionsUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, 'staff');

    return $user;
}

function deleteOptionsCopy(string $location, string $barcode): Copy
{
    $title = Title::query()->create(['preferred_title' => 'Buch '.$barcode, 'sort_title' => 'Buch '.$barcode]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => 'active', 'shelf_location' => $location]);
}

it('refuses to delete a shelf with copies by default and deletes it on explicit request', function (): void {
    $user = deleteOptionsUser();
    $shelf = CatalogShelf::query()->create(['code' => 'I. A 1 a']);
    $copy = deleteOptionsCopy('I. A 1 a', 'B1');
    $other = deleteOptionsCopy('I. A 1 b', 'B2');

    $this->actingAs($user)->delete(route('administration.shelves.destroy', ['shelfId' => $shelf->getKey()]))->assertSessionHasErrors('shelf');
    expect(CatalogShelf::query()->count())->toBe(1);

    $this->actingAs($user)->delete(route('administration.shelves.destroy', ['shelfId' => $shelf->getKey()]), ['release_copies' => '1'])
        ->assertSessionHas('shelf_success', static fn (string $text): bool => str_contains($text, '1 Exemplar(e) haben ihren Standort verloren'));

    expect(CatalogShelf::query()->count())->toBe(0)
        ->and($copy->refresh()->shelf_location)->toBeNull()
        ->and($copy->barcode)->toBe('B1')
        ->and($other->refresh()->shelf_location)->toBe('I. A 1 b')
        ->and(Copy::query()->count())->toBe(2)
        ->and(AuditEvent::query()->where('action', 'catalog.shelf.deleted')->count())->toBe(1);
});

it('offers the delete button for shelves with copies on the page', function (): void {
    $user = deleteOptionsUser();
    CatalogShelf::query()->create(['code' => 'I. A 1 a']);
    deleteOptionsCopy('I. A 1 a', 'B1');

    $this->actingAs($user)->get(route('administration.shelves.index'))->assertOk()->assertSee('Standort von 1 Exemplar entfernen');
});

it('deletes a topic with sub topics and shelf links when asked, moving or deleting the children', function (): void {
    $user = deleteOptionsUser();

    $make = static function (): array {
        $top = CatalogTopic::query()->create(['name' => 'Oben']);
        $middle = CatalogTopic::query()->create(['name' => 'Mitte', 'parent_id' => $top->getKey()]);
        $leaf = CatalogTopic::query()->create(['name' => 'Blatt', 'parent_id' => $middle->getKey()]);
        $shelf = CatalogShelf::query()->create(['code' => 'T'.random_int(1000, 9999)]);
        $shelf->topics()->attach($middle->getKey(), ['position' => 1]);

        return [$top, $middle, $leaf, $shelf];
    };

    // Standard bleibt gesperrt.
    [$top, $middle, $leaf, $shelf] = $make();
    $this->actingAs($user)->delete(route('administration.topics.destroy', ['topicId' => $middle->getKey()]))->assertSessionHasErrors('topic');
    $this->actingAs($user)->delete(route('administration.topics.destroy', ['topicId' => $middle->getKey()]), ['detach_shelves' => '1'])->assertSessionHasErrors('topic');

    // Unterbereiche nach oben verschieben.
    $this->actingAs($user)->delete(route('administration.topics.destroy', ['topicId' => $middle->getKey()]), ['children' => 'move', 'detach_shelves' => '1'])
        ->assertSessionHas('taxonomy_success');
    expect(CatalogTopic::query()->whereKey($middle->getKey())->exists())->toBeFalse()
        ->and($leaf->refresh()->parent_id)->toBe($top->getKey())
        ->and($shelf->topics()->count())->toBe(0);

    // Mit allem löschen.
    [$top2, $middle2, $leaf2] = $make();
    $before = CatalogTopic::query()->count();
    $this->actingAs($user)->delete(route('administration.topics.destroy', ['topicId' => $top2->getKey()]), ['children' => 'delete', 'detach_shelves' => '1'])->assertSessionHas('taxonomy_success');
    expect(CatalogTopic::query()->count())->toBe($before - 3)
        ->and(CatalogTopic::query()->whereIn('id', [$top2->getKey(), $middle2->getKey(), $leaf2->getKey()])->count())->toBe(0);

    $this->actingAs($user)->delete(route('administration.topics.destroy', ['topicId' => $top->getKey()]), ['children' => 'quatsch'])->assertStatus(422);
});

it('deletes a rack with its shelves only on explicit request and releases the copies', function (): void {
    $user = deleteOptionsUser();
    $group = CatalogShelfSection::query()->create(['kind' => 'group', 'code' => 'I']);
    $area = CatalogShelfSection::query()->create(['kind' => 'area', 'code' => 'A', 'parent_id' => $group->getKey()]);
    $rack = CatalogShelfSection::query()->create(['kind' => 'rack', 'code' => '1', 'parent_id' => $area->getKey()]);
    CatalogShelf::query()->create(['code' => 'I. A 1 a', 'section_id' => $rack->getKey(), 'board' => 'a']);
    CatalogShelf::query()->create(['code' => 'I. A 1 b', 'section_id' => $rack->getKey(), 'board' => 'b']);
    $copy = deleteOptionsCopy('I. A 1 a', 'B1');

    $this->actingAs($user)->delete(route('administration.sections.destroy', ['sectionId' => $rack->getKey()]))->assertSessionHasErrors('shelf');
    $this->actingAs($user)->delete(route('administration.sections.destroy', ['sectionId' => $rack->getKey()]), ['cascade' => '1'])->assertSessionHasErrors('shelf');
    expect(CatalogShelf::query()->count())->toBe(2);

    $this->actingAs($user)->delete(route('administration.sections.destroy', ['sectionId' => $group->getKey()]), ['cascade' => '1', 'release_copies' => '1'])
        ->assertSessionHas('shelf_success', static fn (string $text): bool => str_contains($text, '2 Regalbrett') && str_contains($text, '1 Exemplar'));

    expect(CatalogShelfSection::query()->count())->toBe(0)->and(CatalogShelf::query()->count())->toBe(0)
        ->and($copy->refresh()->shelf_location)->toBeNull()->and(Copy::query()->count())->toBe(1);
});
