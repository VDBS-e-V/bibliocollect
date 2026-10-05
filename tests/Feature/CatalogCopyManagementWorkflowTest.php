<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
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

    $this->actingAs($manager)
        ->post(route('pos.catalog.copies.store', ['editionId' => $edition->getKey()]), [
            'barcode' => '  BC-COPY-001  ',
            'shelf_location' => '  J 5 ENDE  ',
            'status' => 'ACTIVE',
        ])
        ->assertRedirect(route('pos.catalog.editions.edit', ['editionId' => $edition->getKey()]));

    $copy = Copy::query()->where('barcode', 'BC-COPY-001')->firstOrFail();

    expect($copy->edition_id)->toBe($edition->getKey())
        ->and($copy->shelf_location)->toBe('J 5 ENDE')
        ->and($copy->status)->toBe(CopyStatus::Active);

    $this->actingAs($manager)
        ->get(route('pos.catalog.copies.edit', [
            'editionId' => $edition->getKey(),
            'copyId' => $copy->getKey(),
        ]))
        ->assertOk()
        ->assertSee('BC-COPY-001');

    $this->actingAs($manager)
        ->patch(route('pos.catalog.copies.update', [
            'editionId' => $edition->getKey(),
            'copyId' => $copy->getKey(),
        ]), [
            'barcode' => 'BC-COPY-001',
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
    $firstEdition = catalogCopyEdition('Erster Titel', '9783000000001');
    $secondEdition = catalogCopyEdition('Zweiter Titel', '9783000000002');

    Copy::query()->create([
        'edition_id' => $firstEdition->getKey(),
        'barcode' => 'BC-DUP-001',
        'status' => CopyStatus::Active,
    ]);

    $this->actingAs($staff)
        ->post(route('pos.catalog.copies.store', ['editionId' => $secondEdition->getKey()]), [
            'barcode' => 'BC-DUP-001',
            'shelf_location' => 'J 1 TEST',
            'status' => 'active',
        ])
        ->assertRedirect(route('pos.catalog.editions.edit', ['editionId' => $secondEdition->getKey()]))
        ->assertSessionHasErrors('barcode');

    $secondCopy = Copy::query()->create([
        'edition_id' => $secondEdition->getKey(),
        'barcode' => 'BC-DUP-002',
        'status' => CopyStatus::Active,
    ]);

    $this->actingAs($staff)
        ->patch(route('pos.catalog.copies.update', [
            'editionId' => $secondEdition->getKey(),
            'copyId' => $secondCopy->getKey(),
        ]), [
            'barcode' => 'BC-DUP-001',
            'status' => 'active',
        ])
        ->assertRedirect(route('pos.catalog.copies.edit', [
            'editionId' => $secondEdition->getKey(),
            'copyId' => $secondCopy->getKey(),
        ]))
        ->assertSessionHasErrors('barcode');

    expect(Copy::query()->where('barcode', 'BC-DUP-001')->count())->toBe(1)
        ->and($secondCopy->fresh()->barcode)->toBe('BC-DUP-002');
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
