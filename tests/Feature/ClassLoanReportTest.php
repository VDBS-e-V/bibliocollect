<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reportUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function reportLoan(string $first, string $last, ?SchoolClass $class, string $title, string $dueOn, PatronKind $kind = PatronKind::Student): Loan
{
    $patron = Patron::query()->create([
        'library_number' => 'S-RP-'.strtoupper(substr(md5($first.$last), 0, 5)),
        'kind' => $kind,
        'status' => PatronStatus::Active,
        'first_name' => $first,
        'last_name' => $last,
        'birth_date' => '2012-01-01',
        'school_class_id' => $class?->getKey(),
    ]);

    $book = Title::query()->create(['preferred_title' => $title, 'sort_title' => $title]);
    $edition = Edition::query()->create(['title_id' => $book->getKey(), 'media_type' => 'book']);
    $copy = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'RP-'.strtoupper(substr(md5($title), 0, 5)), 'status' => CopyStatus::Active]);

    return Loan::query()->create(['patron_id' => $patron->getKey(), 'copy_id' => $copy->getKey(), 'checked_out_at' => '2026-09-01 10:00:00', 'due_on' => $dueOn]);
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-20 10:00:00', 'Europe/Berlin'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('lists overdue loans grouped by class with a page per class', function (): void {
    $year = SchoolYear::query()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-07-31', 'is_active' => true]);
    $oldYear = SchoolYear::query()->create(['name' => '2025/26', 'starts_on' => '2025-08-01', 'ends_on' => '2026-07-31', 'is_active' => false]);
    $a5 = SchoolClass::query()->create(['school_year_id' => $year->getKey(), 'name' => '5a', 'grade_level' => 5, 'is_active' => true]);
    $b10 = SchoolClass::query()->create(['school_year_id' => $year->getKey(), 'name' => '10b', 'grade_level' => 10, 'is_active' => true]);
    $old = SchoolClass::query()->create(['school_year_id' => $oldYear->getKey(), 'name' => '4z', 'grade_level' => 4, 'is_active' => true]);

    reportLoan('Mia', 'Zander', $a5, 'Buch Eins', '2026-10-10');
    reportLoan('Ben', 'Adler', $a5, 'Buch Zwei', '2026-10-12');
    reportLoan('Eva', 'Mitte', $b10, 'Buch Drei', '2026-10-01');
    reportLoan('Pünktlich', 'Kind', $a5, 'Buch Vier', '2026-10-25');                         // nicht überfällig
    reportLoan('Lehr', 'Kraft', null, 'Buch Fünf', '2026-10-05', PatronKind::Teacher);      // ohne Klasse
    reportLoan('Alt', 'Klasse', $old, 'Buch Sechs', '2026-10-05');                          // Klasse aus altem Jahr

    $response = $this->actingAs(reportUser('staff'))->get(route('pos.reports.class-loans'))->assertOk();

    $response->assertSeeInOrder(['Klasse 5a', 'Adler, Ben', 'Zander, Mia', 'Klasse 10b', 'Mitte, Eva', 'Ohne Klasse'])
        ->assertSee('10 Tage')
        ->assertDontSee('Buch Vier')
        ->assertSee('Buch Fünf')
        ->assertSee('Buch Sechs');

    $this->actingAs(reportUser('staff'))->get(route('pos.reports.class-loans', ['modus' => 'alle']))
        ->assertOk()
        ->assertSee('Buch Vier');

    $this->actingAs(reportUser('staff'))->get(route('pos.reports.class-loans', ['klasse' => (string) $b10->getKey()]))
        ->assertOk()
        ->assertSee('Mitte, Eva')
        ->assertDontSee('Adler, Ben');
});

it('shows a friendly message without overdue loans', function (): void {
    $this->actingAs(reportUser('management'))->get(route('pos.reports.class-loans'))
        ->assertOk()
        ->assertSee('keine überfälligen Ausleihen');
});

it('keeps the class lists away from the student AG and other roles', function (string $role): void {
    $this->actingAs(reportUser($role))->get(route('pos.reports.class-loans'))->assertForbidden();
})->with(['student_ag_basic', 'student_ag_extended', 'technical_admin', 'student', 'teacher']);
