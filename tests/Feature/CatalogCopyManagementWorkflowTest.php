<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function catalogCopyManagementUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function catalogCopyEdition(string $title = 'Momo', ?string $isbn = '9783522202803'): Edition
{
    $catalogTitle = Title::query()->create(['preferred_title' => $title]);

    return Edition::query()->create([
        'title_id' => $catalogTitle->getKey(),
        'edition_statement' => 'Demo-Ausgabe',
        'isbn' => $isbn,
    ]);
}

it('lets catalog managers create and update physical copies', function (): void {
    $manager = catalogCopyManagementUser('student_ag_extended');
    $edition = catalogCopyEdition();
    CatalogShelf::query()->create(['code' => 'J 5 ENDE', 'label' => 'Ende und Verwandte']);
    CatalogShelf::query()->create(['code' => 'Reparaturregal']);

    $this->actingAs($manager)
        ->post(route('pos.catalog.copies.store', ['editionId' => $edition->getKey()]), [
            'barcode' => '  0099001  ',
            'shelf_location' => '  J 5 ENDE  ',
            'status' => 'ACTIVE',
        ])
        ->assertRedirect(route('pos.catalog.editions.edit', ['editionId' => $edition->getKey()]));

    $copy = Copy::query()->where('barcode', '0099001')->firstOrFail();

    expect($copy->edition_id)->toBe($edition->getKey())
        ->and($copy->shelf_location)->toBe('J 5 ENDE')
        ->and($copy->status)->toBe(CopyStatus::Active);

    $this->actingAs($manager)
        ->get(route('pos.catalog.copies.edit', [
            'editionId' => $edition->getKey(),
            'copyId' => $copy->getKey(),
        ]))
        ->assertOk()
        ->assertSee('0099001')
        ->assertSee('J 5 ENDE · Ende und Verwandte')
        ->assertSee('Reparaturregal');

    $this->actingAs($manager)
        ->patch(route('pos.catalog.copies.update', [
            'editionId' => $edition->getKey(),
            'copyId' => $copy->getKey(),
        ]), [
            'barcode' => '0099001',
            'shelf_location' => 'Reparaturregal',
            'status' => 'damaged',
        ])
        ->assertRedirect(route('pos.catalog.editions.edit', ['editionId' => $edition->getKey()]));

    $copy->refresh();

    expect($copy->shelf_location)->toBe('Reparaturregal')
        ->and($copy->status)->toBe(CopyStatus::Damaged);
});

it('rejects duplicate copy barcodes across editions', function (): void {
    $staff = catalogCopyManagementUser('staff');
    CatalogShelf::query()->create(['code' => 'J 1 TEST']);
    $firstEdition = catalogCopyEdition('Erster Titel', '9783000000001');
    $secondEdition = catalogCopyEdition('Zweiter Titel', '9783000000002');

    Copy::query()->create([
        'edition_id' => $firstEdition->getKey(),
        'barcode' => '0099002',
        'status' => CopyStatus::Active,
    ]);

    $this->actingAs($staff)
        ->post(route('pos.catalog.copies.store', ['editionId' => $secondEdition->getKey()]), [
            'barcode' => '0099002',
            'shelf_location' => 'J 1 TEST',
            'status' => 'active',
        ])
        ->assertRedirect(route('pos.catalog.editions.edit', ['editionId' => $secondEdition->getKey()]))
        ->assertSessionHasErrors('barcode');

    $secondCopy = Copy::query()->create([
        'edition_id' => $secondEdition->getKey(),
        'barcode' => '0099003',
        'status' => CopyStatus::Active,
    ]);

    $this->actingAs($staff)
        ->patch(route('pos.catalog.copies.update', [
            'editionId' => $secondEdition->getKey(),
            'copyId' => $secondCopy->getKey(),
        ]), [
            'barcode' => '0099002',
            'status' => 'active',
        ])
        ->assertRedirect(route('pos.catalog.copies.edit', [
            'editionId' => $secondEdition->getKey(),
            'copyId' => $secondCopy->getKey(),
        ]))
        ->assertSessionHasErrors('barcode');

    expect(Copy::query()->where('barcode', '0099002')->count())->toBe(1)
        ->and($secondCopy->fresh()->barcode)->toBe('0099003');
});

it('validates copy input before persistence', function (): void {
    $staff = catalogCopyManagementUser('staff');
    $edition = catalogCopyEdition();

    $this->actingAs($staff)
        ->post(route('pos.catalog.copies.store', ['editionId' => $edition->getKey()]), [
            'barcode' => '   ',
            'shelf_location' => str_repeat('x', 121),
            'status' => 'available',
        ])
        ->assertSessionHasErrors(['barcode', 'shelf_location', 'status']);

    expect(Copy::query()->count())->toBe(0);
});

it('keeps copies bound to their edition while editing', function (): void {
    $staff = catalogCopyManagementUser('staff');
    $firstEdition = catalogCopyEdition('Erster Titel', '9783000000011');
    $secondEdition = catalogCopyEdition('Zweiter Titel', '9783000000012');

    $copy = Copy::query()->create([
        'edition_id' => $firstEdition->getKey(),
        'barcode' => 'BC-PARENT-001',
        'status' => CopyStatus::Active,
    ]);

    $this->actingAs($staff)
        ->get(route('pos.catalog.copies.edit', [
            'editionId' => $secondEdition->getKey(),
            'copyId' => $copy->getKey(),
        ]))
        ->assertNotFound();

    $this->actingAs($staff)
        ->patch(route('pos.catalog.copies.update', [
            'editionId' => $secondEdition->getKey(),
            'copyId' => $copy->getKey(),
        ]), [
            'barcode' => 'BC-PARENT-001',
            'status' => 'lost',
        ])
        ->assertNotFound();

    expect($copy->fresh()->edition_id)->toBe($firstEdition->getKey())
        ->and($copy->fresh()->status)->toBe(CopyStatus::Active);
});

it('keeps basic student AG and technical administration out of copy maintenance', function (): void {
    $basic = catalogCopyManagementUser('student_ag_basic');
    $technicalAdmin = catalogCopyManagementUser('technical_admin');
    $edition = catalogCopyEdition();

    $payload = [
        'barcode' => 'BC-FORBIDDEN-001',
        'status' => 'active',
    ];

    $this->actingAs($basic)
        ->post(route('pos.catalog.copies.store', ['editionId' => $edition->getKey()]), $payload)
        ->assertForbidden();

    $this->actingAs($technicalAdmin)
        ->post(route('pos.catalog.copies.store', ['editionId' => $edition->getKey()]), $payload)
        ->assertForbidden();

    expect(Copy::query()->count())->toBe(0);
});

it('requires seven digits for new inventory numbers but keeps legacy numbers editable', function (): void {
    $staff = catalogCopyManagementUser('staff');
    $edition = catalogCopyEdition();
    CatalogShelf::query()->create(['code' => 'J 5 ENDE']);

    foreach (['12345', '123456789', 'BC-0001', '12 34567'] as $invalid) {
        $this->actingAs($staff)
            ->post(route('pos.catalog.copies.store', ['editionId' => $edition->getKey()]), ['barcode' => $invalid, 'status' => 'active'])
            ->assertSessionHasErrors('barcode');
    }

    expect(Copy::query()->count())->toBe(0);

    // Ein Altbestand mit alter Nummer bleibt bearbeitbar, solange die Nummer unverändert bleibt.
    $legacy = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => '12482', 'status' => CopyStatus::Active, 'shelf_location' => 'Altes Regal']);

    $route = route('pos.catalog.copies.update', ['editionId' => $edition->getKey(), 'copyId' => $legacy->getKey()]);

    $this->actingAs($staff)->patch($route, ['barcode' => '12482', 'shelf_location' => 'Altes Regal', 'status' => 'damaged'])->assertSessionHasNoErrors();
    expect($legacy->refresh()->status)->toBe(CopyStatus::Damaged);

    // Ändern auf eine neue Nummer verlangt wieder 7 Ziffern; ein anderer Standort muss aus der Liste kommen.
    $this->actingAs($staff)->patch($route, ['barcode' => '999', 'status' => 'active'])->assertSessionHasErrors('barcode');
    $this->actingAs($staff)->patch($route, ['barcode' => '12482', 'shelf_location' => 'Frei erfunden', 'status' => 'active'])->assertSessionHasErrors('shelf_location');
    $this->actingAs($staff)->patch($route, ['barcode' => '0012482', 'shelf_location' => 'J 5 ENDE', 'status' => 'active'])->assertSessionHasNoErrors();
    expect($legacy->refresh()->barcode)->toBe('0012482')->and($legacy->shelf_location)->toBe('J 5 ENDE');
});
