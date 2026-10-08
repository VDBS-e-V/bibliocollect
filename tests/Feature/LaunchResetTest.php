<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CreateBookWishAction;
use App\Modules\Circulation\Models\BookWish;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Actions\CreateStandardClassesAction;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function resetTitle(string $name, ?string $legacy): Copy
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book', 'legacy_source' => $legacy]);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $name, 'status' => 'active']);
}

it('removes demo data but keeps the legacy catalog', function (): void {
    resetTitle('1000001', 'vdbs-legacy');
    resetTitle('BC-DEMO-1', null);
    User::factory()->create();
    Patron::query()->create(['library_number' => '123456', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'A', 'last_name' => 'B', 'birth_date' => '2012-01-01']);
    $year = SchoolYear::query()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-07-31', 'is_active' => true]);
    app(CreateStandardClassesAction::class)->execute($year);
    app(CreateBookWishAction::class)->execute(null, 'Wunsch', null, null, null);

    $this->artisan('app:launch-reset', ['--force' => true, '--skip-backup' => true])->assertSuccessful();

    expect(Copy::query()->pluck('barcode')->all())->toBe(['1000001'])
        ->and(Title::query()->count())->toBe(1)
        ->and(User::query()->count())->toBe(0)
        ->and(Patron::query()->count())->toBe(0)
        ->and(SchoolClass::query()->count())->toBe(0)
        ->and(SchoolYear::query()->count())->toBe(0)
        ->and(BookWish::query()->count())->toBe(0);
});

it('creates all classes of the school once', function (): void {
    $year = SchoolYear::query()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-07-31', 'is_active' => true]);
    $create = app(CreateStandardClassesAction::class);

    expect($create->execute($year))->toBe(18 + 20 + 3 + 4 + 2)
        ->and($create->execute($year))->toBe(0)
        ->and(SchoolClass::query()->whereIn('name', ['1.1', '6.3', '7.5', '9.6', '10.6', 'WiKo', '11.4', '12', '13'])->count())->toBe(9);
});
