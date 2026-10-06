<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Queries\LoanStatisticsQuery;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function statsUser(string $role = 'staff'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function statsCopy(string $barcode, string $title, string $mediaType = 'book'): Copy
{
    $titleModel = Title::query()->firstOrCreate(['preferred_title' => $title], ['sort_title' => $title]);
    $edition = Edition::query()->create(['title_id' => $titleModel->getKey(), 'media_type' => $mediaType]);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => CopyStatus::Active]);
}

function statsLoan(Patron $patron, Copy $copy, string $checkedOut, ?string $returned = null, int $renewals = 0): Loan
{
    return Loan::query()->create([
        'patron_id' => $patron->getKey(),
        'copy_id' => $copy->getKey(),
        'checked_out_at' => $checkedOut,
        'due_on' => CarbonImmutable::parse($checkedOut)->addDays(14)->toDateString(),
        'returned_at' => $returned,
        'renewal_count' => $renewals,
    ]);
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:00:00', 'Europe/Berlin'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('summarizes loans of the school year without exposing persons', function (): void {
    $year = SchoolYear::query()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-07-31', 'is_active' => true]);
    $class = SchoolClass::query()->create(['school_year_id' => $year->getKey(), 'name' => '5a', 'grade_level' => 5, 'is_active' => true]);

    $mia = Patron::query()->create(['library_number' => 'S-ST-1', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Mia', 'last_name' => 'Statistikerin', 'birth_date' => '2015-01-01', 'school_class_id' => $class->getKey()]);
    $ben = Patron::query()->create(['library_number' => 'S-ST-2', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Ben', 'last_name' => 'Zähler', 'birth_date' => '2015-01-01']);

    $momo = statsCopy('ST-1', 'Momo');
    $momo2 = statsCopy('ST-2', 'Momo');
    $hoer = statsCopy('ST-3', 'Hörbuchtitel', 'audiobook');

    statsLoan($mia, $momo, '2026-09-02 10:00:00', '2026-09-10 10:00:00', 1);
    statsLoan($mia, $momo2, '2026-09-20 10:00:00');
    statsLoan($ben, $hoer, '2026-10-01 10:00:00');
    statsLoan($ben, statsCopy('ST-4', 'Alt'), '2026-06-01 10:00:00', '2026-06-10 10:00:00'); // vor dem Schuljahr

    $this->actingAs(statsUser())->get(route('pos.statistics'))
        ->assertOk()
        ->assertSee('Schuljahr 2026/27')
        ->assertSee('Momo')
        ->assertSee('Hörbuchtitel')
        ->assertSee('5a')
        ->assertSee('Hörbuch')
        ->assertDontSee('Statistikerin')
        ->assertDontSee('Zähler');
});

it('computes the key figures and the monthly series', function (): void {
    $patron = Patron::query()->create(['library_number' => 'S-ST-1', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'A', 'last_name' => 'B', 'birth_date' => '2015-01-01']);

    statsLoan($patron, statsCopy('ST-1', 'Eins'), '2026-08-15 10:00:00', '2026-08-20 10:00:00', 2);
    statsLoan($patron, statsCopy('ST-2', 'Zwei'), '2026-08-20 10:00:00');
    statsLoan($patron, statsCopy('ST-3', 'Drei'), '2026-09-25 10:00:00');
    $overdue = statsLoan($patron, statsCopy('ST-4', 'Vier'), '2026-09-01 10:00:00');
    $overdue->forceFill(['due_on' => '2026-09-15'])->save();

    $stats = app(LoanStatisticsQuery::class)
        ->execute(CarbonImmutable::parse('2026-08-01'), CarbonImmutable::parse('2026-10-05'), CarbonImmutable::parse('2026-10-05'));

    expect($stats['loans'])->toBe(4)
        ->and($stats['returns'])->toBe(1)
        ->and($stats['renewals'])->toBe(2)
        ->and($stats['active_patrons'])->toBe(1)
        ->and($stats['open'])->toBe(3)
        ->and($stats['overdue'])->toBe(2)
        ->and(collect($stats['by_month'])->pluck('loans', 'month')->all())->toBe(['2026-08' => 2, '2026-09' => 2, '2026-10' => 0])
        ->and($stats['stock']['copies']['active'])->toBe(4);
});

it('offers other periods and a csv download', function (): void {
    SchoolYear::query()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-07-31', 'is_active' => true]);
    $earlier = SchoolYear::query()->create(['name' => '2025/26', 'starts_on' => '2025-08-01', 'ends_on' => '2026-07-31', 'is_active' => false]);
    $patron = Patron::query()->create(['library_number' => 'S-ST-1', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'A', 'last_name' => 'B', 'birth_date' => '2015-01-01']);
    statsLoan($patron, statsCopy('ST-1', 'Altes Jahr'), '2026-01-10 10:00:00', '2026-01-20 10:00:00');
    $staff = statsUser();

    $this->actingAs($staff)->get(route('pos.statistics', ['zeitraum' => (string) $earlier->getKey()]))->assertOk()->assertSee('Altes Jahr');
    $this->actingAs($staff)->get(route('pos.statistics', ['zeitraum' => 'letzte12']))->assertOk()->assertSee('Altes Jahr');
    $this->actingAs($staff)->get(route('pos.statistics', ['zeitraum' => 'alle']))->assertOk()->assertSee('Altes Jahr');

    $csv = $this->actingAs($staff)->get(route('pos.statistics.download', ['zeitraum' => 'alle']))->assertOk();

    expect($csv->streamedContent())->toContain('Ausleihen;1')->toContain('"Altes Jahr";1');
});

it('keeps the statistics away from roles without the permission', function (string $role): void {
    $this->actingAs(statsUser($role))->get(route('pos.statistics'))->assertForbidden();
    $this->actingAs(statsUser($role))->get(route('pos.statistics.download'))->assertForbidden();
})->with(['student_ag_basic', 'student_ag_extended', 'technical_admin', 'student']);

it('works without any loans', function (): void {
    $this->actingAs(statsUser('management'))->get(route('pos.statistics'))
        ->assertOk()
        ->assertSee('Keine Ausleihen im Zeitraum');
});
