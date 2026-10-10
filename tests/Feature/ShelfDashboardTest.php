<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function shelfDashboardUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

it('protects the dashboard and CSV export using shelves.manage', function (): void {
    foreach (['administration.shelves.dashboard', 'administration.shelves.dashboard.export'] as $routeName) {
        $this->actingAs(shelfDashboardUser('student_ag_extended'))->get(route($routeName))->assertForbidden();
        $this->actingAs(shelfDashboardUser('staff'))->get(route($routeName))->assertOk();
    }
});

it('reports shelf occupancy, unknown locations and missing capacities without modifying copies', function (): void {
    $admin = shelfDashboardUser('management');
    $shelf = CatalogShelf::query()->create(['code' => 'I. A 1 a', 'capacity' => 1]);
    CatalogShelf::query()->create(['code' => 'I. A 1 b']);

    $topic = CatalogTopic::query()->create(['name' => 'Abenteuer']);
    $shelf->topics()->attach($topic->getKey(), ['position' => 1]);
    CatalogTopic::query()->create(['name' => 'Ohne Standort']);

    $title = Title::query()->create(['preferred_title' => 'Testbuch', 'sort_title' => 'Testbuch']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);

    foreach (['I. A 1 a', 'I. A 1 a', 'Nicht vorhanden', null] as $i => $location) {
        Copy::query()->create([
            'edition_id' => $edition->getKey(),
            'barcode' => str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT),
            'status' => 'active',
            'shelf_location' => $location,
        ]);
    }

    $this->actingAs($admin)->get(route('administration.shelves.dashboard'))
        ->assertOk()
        ->assertSee('Regal-Dashboard')
        ->assertSee('Kapazität überschritten')
        ->assertSee('Ohne Thema')
        ->assertSee('1 Exemplare ohne Standort')
        ->assertSee('1 Exemplare mit unbekanntem Standort')
        ->assertSee('Nicht erfasst');

    $this->actingAs($admin)->get(route('administration.shelves.dashboard', ['ohne_thema' => 1]))
        ->assertOk()->assertSee('I. A 1 b')->assertDontSee('Kapazität überschritten');

    $this->actingAs($admin)->get(route('administration.shelves.dashboard.export'))
        ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect(Copy::query()->count())->toBe(4);
});
