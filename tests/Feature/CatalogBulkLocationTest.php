<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bulkLocationUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function bulkLocationFixtures(): array
{
    CatalogShelf::query()->create(['code' => 'I. A 1 a', 'is_active' => true]);
    CatalogShelf::query()->create(['code' => 'I. A 1 b', 'is_active' => true]);
    $title = Title::query()->create(['preferred_title' => 'Stapeltest', 'sort_title' => 'Stapeltest']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);

    $copies = [];
    foreach (['1000201', '1000202'] as $barcode) {
        $copies[] = Copy::query()->create([
            'edition_id' => $edition->getKey(), 'barcode' => $barcode,
            'status' => 'active', 'shelf_location' => 'I. A 1 a',
        ]);
    }

    return $copies;
}

it('requires catalog.manage for preview and commit', function (): void {
    $copies = bulkLocationFixtures();
    $ag = bulkLocationUser('student_ag_basic');

    $this->actingAs($ag)->post(route('pos.catalog.bulk-location.preview'), [
        'copies' => [$copies[0]->getKey()], 'shelf' => 'I. A 1 b',
    ])->assertForbidden();

    $this->actingAs($ag)->post(route('pos.catalog.bulk-location.commit'), [
        'token' => str_repeat('a', 40), 'confirm' => '1',
    ])->assertForbidden();
});

it('previews without writing and commits atomically with a one-use token', function (): void {
    $copies = bulkLocationFixtures();
    $admin = bulkLocationUser('management');

    $preview = $this->actingAs($admin)->post(route('pos.catalog.bulk-location.preview'), [
        'copies' => array_map(static fn (Copy $copy): string => (string) $copy->getKey(), $copies),
        'shelf' => 'I. A 1 b',
    ]);
    $preview->assertOk()->assertSee('Stapeltest')->assertSee('1000201')->assertSee('1000202');
    expect($copies[0]->fresh()->shelf_location)->toBe('I. A 1 a');

    $token = session('catalog.bulk.location.draft')['token'];
    $this->actingAs($admin)->post(route('pos.catalog.bulk-location.commit'), [
        'token' => $token, 'confirm' => '1',
    ])->assertRedirect(route('pos.catalog.index'))->assertSessionHas('catalog_success');

    expect($copies[0]->fresh()->shelf_location)->toBe('I. A 1 b')
        ->and($copies[1]->fresh()->shelf_location)->toBe('I. A 1 b');

    $this->actingAs($admin)->post(route('pos.catalog.bulk-location.commit'), [
        'token' => $token, 'confirm' => '1',
    ])->assertSessionHasErrors('bulk');
});

it('aborts the entire batch when one selected copy changes after preview', function (): void {
    $copies = bulkLocationFixtures();
    $admin = bulkLocationUser('management');
    $this->actingAs($admin)->post(route('pos.catalog.bulk-location.preview'), [
        'copies' => array_map(static fn (Copy $copy): string => (string) $copy->getKey(), $copies),
        'shelf' => 'I. A 1 b',
    ])->assertOk();

    $token = session('catalog.bulk.location.draft')['token'];
    $copies[0]->shelf_location = null;
    $copies[0]->save();

    $this->actingAs($admin)->post(route('pos.catalog.bulk-location.commit'), [
        'token' => $token, 'confirm' => '1',
    ])->assertSessionHasErrors('bulk');

    expect($copies[0]->fresh()->shelf_location)->toBeNull()
        ->and($copies[1]->fresh()->shelf_location)->toBe('I. A 1 a');
});
