<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function catalogManagementUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

it('lets extended student AG users maintain titles and editions', function (): void {
    $agUser = catalogManagementUser('student_ag_extended');

    $response = $this->actingAs($agUser)
        ->post(route('pos.catalog.titles.store'), [
            'preferred_title' => 'Die unendliche Geschichte',
            'subtitle' => '',
            'sort_title' => 'Unendliche Geschichte',
        ]);

    $title = Title::query()->where('preferred_title', 'Die unendliche Geschichte')->firstOrFail();

    $response->assertRedirect(route('pos.catalog.titles.show', ['titleId' => $title->getKey()]));

    $this->actingAs($agUser)
        ->post(route('pos.catalog.editions.store', ['titleId' => $title->getKey()]), [
            'edition_statement' => 'Neuausgabe',
            'isbn' => '9783522202605',
            'publisher_name' => 'Thienemann',
            'publication_year' => '2024',
            'media_type' => 'book',
            'language_code' => 'de',
            'minimum_age' => '12',
            'age_rating_label' => 'ab 12',
        ])
        ->assertRedirect(route('pos.catalog.titles.show', ['titleId' => $title->getKey()]));

    $edition = Edition::query()->where('title_id', $title->getKey())->firstOrFail();

    expect($edition->publication_year)->toBe(2024)
        ->and($edition->minimum_age)->toBe(12);

    $this->actingAs($agUser)
        ->patch(route('pos.catalog.editions.update', ['editionId' => $edition->getKey()]), [
            'edition_statement' => 'Überarbeitete Ausgabe',
            'isbn' => '9783522202605',
            'publisher_name' => 'Thienemann',
            'publication_year' => '2025',
            'media_type' => 'book',
            'language_code' => 'de',
            'minimum_age' => '10',
            'age_rating_label' => 'ab 10',
        ])
        ->assertRedirect(route('pos.catalog.titles.show', ['titleId' => $title->getKey()]));

    expect($edition->fresh()->edition_statement)->toBe('Überarbeitete Ausgabe')
        ->and($edition->fresh()->publication_year)->toBe(2025)
        ->and($edition->fresh()->minimum_age)->toBe(10);
});

it('lets staff search and update catalog titles through the protected workspace', function (): void {
    $staff = catalogManagementUser('staff');
    $title = Title::query()->create([
        'preferred_title' => 'Momo',
        'sort_title' => 'Momo',
    ]);

    $this->actingAs($staff)
        ->get(route('pos.catalog.index', ['q' => 'Momo']))
        ->assertOk()
        ->assertSee('Momo')
        ->assertSee('Öffnen');

    $this->actingAs($staff)
        ->patch(route('pos.catalog.titles.update', ['titleId' => $title->getKey()]), [
            'preferred_title' => 'Momo',
            'subtitle' => 'Ein Märchen-Roman',
            'sort_title' => 'Momo',
        ])
        ->assertRedirect(route('pos.catalog.titles.show', ['titleId' => $title->getKey()]));

    expect($title->fresh()->subtitle)->toBe('Ein Märchen-Roman');
});

it('keeps basic student AG and technical administration out of catalog maintenance', function (): void {
    $basic = catalogManagementUser('student_ag_basic');
    $technicalAdmin = catalogManagementUser('technical_admin');

    $this->actingAs($basic)
        ->get(route('pos.catalog.index'))
        ->assertForbidden();

    $this->actingAs($technicalAdmin)
        ->get(route('pos.catalog.index'))
        ->assertForbidden();
});

it('validates catalog title and edition input before persistence', function (): void {
    $staff = catalogManagementUser('staff');

    $this->actingAs($staff)
        ->post(route('pos.catalog.titles.store'), [
            'preferred_title' => '   ',
        ])
        ->assertSessionHasErrors('preferred_title');

    $this->assertDatabaseCount('catalog_titles', 0);

    $title = Title::query()->create(['preferred_title' => 'Altersprüfung']);

    $this->actingAs($staff)
        ->post(route('pos.catalog.editions.store', ['titleId' => $title->getKey()]), [
            'publication_year' => '999',
            'minimum_age' => '19',
        ])
        ->assertSessionHasErrors(['publication_year', 'minimum_age']);

    $this->assertDatabaseCount('catalog_editions', 0);
});
