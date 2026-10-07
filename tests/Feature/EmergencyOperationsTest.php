<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function emergencyUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function emergencyCopy(string $barcode, string $title = 'Notbuch'): Copy
{
    $titleModel = Title::query()->create(['preferred_title' => $title.' '.$barcode, 'sort_title' => $title.' '.$barcode]);
    $edition = Edition::query()->create(['title_id' => $titleModel->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => CopyStatus::Active]);
}

function emergencyPatron(string $number, string $last = 'Notfall'): Patron
{
    return Patron::query()->create(['library_number' => $number, 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Nora', 'last_name' => $last, 'birth_date' => '2010-01-01']);
}

beforeEach(function (): void {
    foreach (range(1, 7) as $day) {
        LibraryOpeningHour::query()->create(['day_of_week' => $day, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00']);
    }
});

it('limits the emergency pages by permission', function (): void {
    $this->actingAs(emergencyUser('staff'))->get(route('pos.emergency'))->assertOk()->assertSee('Notbetrieb');
    $this->actingAs(emergencyUser('staff'))->get(route('pos.emergency.list'))->assertOk();

    $aide = emergencyUser('student_ag_basic');
    $this->actingAs($aide)->get(route('pos.emergency'))->assertOk()->assertDontSee('Liste ansehen und drucken');
    $this->actingAs($aide)->get(route('pos.emergency.list'))->assertForbidden();
    $this->actingAs($aide)->get(route('pos.emergency.export'))->assertForbidden();
    $this->actingAs(emergencyUser('teacher'))->get(route('pos.emergency'))->assertForbidden();
});

it('lists open loans and reservations sorted by name and exports them', function (): void {
    $staff = emergencyUser('staff');
    $zed = emergencyPatron('N-1', 'Zander');
    $abe = emergencyPatron('N-2', 'Abel');
    app(CheckoutCopyAction::class)->execute($zed, emergencyCopy('0060001')->barcode, $staff);
    app(CheckoutCopyAction::class)->execute($abe, emergencyCopy('0060002')->barcode, $staff);

    $html = $this->actingAs($staff)->get(route('pos.emergency.list'))->assertOk()->assertSee('Offene Ausleihen')->assertSee('0060001')->getContent();
    expect(strpos($html, 'Abel, Nora'))->toBeLessThan(strpos($html, 'Zander, Nora'));

    $csv = $this->get(route('pos.emergency.export'))->assertOk()->streamedContent();
    expect($csv)->toContain('Ausleihe;"Abel, Nora"')->toContain('0060002');
});

it('books paper loans with the chosen date and due date from that day', function (): void {
    $staff = emergencyUser('staff');
    $patron = emergencyPatron('N-3');
    emergencyCopy('0060010');
    emergencyCopy('0060011');
    $day = now()->subDays(20)->toDateString();

    $this->actingAs($staff)->post(route('pos.emergency.loans'), ['person' => 'N-3', 'date' => $day, 'barcodes' => "0060010\n0060011\n"])
        ->assertRedirect(route('pos.emergency'))
        ->assertSessionHas('emergency_success', static fn (string $text): bool => str_contains($text, '2 Ausleihe(n)'));

    $loans = Loan::query()->where('patron_id', $patron->getKey())->get();
    expect($loans)->toHaveCount(2)
        ->and($loans->first()->checked_out_at->timezone(config('foundation.business_timezone'))->toDateString())->toBe($day)
        ->and($loans->first()->due_on->toDateString())->toBeLessThan(now()->toDateString());
});

it('books nothing when one line is wrong and names that line', function (): void {
    $staff = emergencyUser('staff');
    emergencyPatron('N-4');
    emergencyCopy('0060020');
    $day = now()->subDay()->toDateString();

    $this->actingAs($staff)->from(route('pos.emergency'))->post(route('pos.emergency.loans'), ['person' => 'N-4', 'date' => $day, 'barcodes' => "0060020\n9999999"])
        ->assertRedirect(route('pos.emergency'))
        ->assertSessionHasErrors([], null, 'loans');

    expect(session('errors')->getBag('loans')->first())->toContain('9999999');
    expect(Loan::query()->count())->toBe(0);
});

it('rejects future dates, dates too far back and unknown people', function (): void {
    $staff = emergencyUser('staff');
    emergencyPatron('N-5');
    emergencyCopy('0060030');

    $post = fn (string $person, string $date) => $this->actingAs($staff)->from(route('pos.emergency'))->post(route('pos.emergency.loans'), ['person' => $person, 'date' => $date, 'barcodes' => '0060030']);

    $post('N-5', now()->addDay()->toDateString())->assertSessionHasErrors([], null, 'loans');
    $post('N-5', now()->subDays(400)->toDateString())->assertSessionHasErrors([], null, 'loans');
    $post('N-5', '31.12.2026')->assertSessionHasErrors([], null, 'loans');
    $post('gibtsnicht', now()->toDateString())->assertSessionHasErrors(['person'], null, 'loans');

    expect(Loan::query()->count())->toBe(0);
});

it('books paper returns with the chosen date and rejects returns before the loan', function (): void {
    $staff = emergencyUser('staff');
    $patron = emergencyPatron('N-6');
    emergencyCopy('0060040');
    $loanDay = now()->subDays(8)->toDateString();
    $returnDay = now()->subDays(3)->toDateString();

    $this->actingAs($staff)->post(route('pos.emergency.loans'), ['person' => 'N-6', 'date' => $loanDay, 'barcodes' => '0060040']);

    $this->from(route('pos.emergency'))->post(route('pos.emergency.returns'), ['date' => now()->subDays(9)->toDateString(), 'barcodes' => '0060040'])->assertSessionHasErrors([], null, 'returns');
    expect(Loan::query()->whereNull('returned_at')->count())->toBe(1);

    $this->post(route('pos.emergency.returns'), ['date' => $returnDay, 'barcodes' => '0060040'])->assertSessionHas('emergency_success');

    $loan = Loan::query()->where('patron_id', $patron->getKey())->firstOrFail();
    expect($loan->returned_at?->timezone(config('foundation.business_timezone'))->toDateString())->toBe($returnDay);

    // Ein Exemplar, das nicht ausgeliehen ist, lässt sich nicht zurückgeben.
    $this->from(route('pos.emergency'))->post(route('pos.emergency.returns'), ['date' => $returnDay, 'barcodes' => '0060040'])->assertSessionHasErrors([], null, 'returns');
});

it('keeps the usual rules for paper loans', function (): void {
    $staff = emergencyUser('staff');
    $first = emergencyPatron('N-7');
    emergencyPatron('N-8', 'Zweit');
    emergencyCopy('0060050');
    $day = now()->subDay()->toDateString();

    $this->actingAs($staff)->post(route('pos.emergency.loans'), ['person' => 'N-7', 'date' => $day, 'barcodes' => '0060050'])->assertSessionHas('emergency_success');

    // Dasselbe Exemplar kann nicht noch einmal an eine andere Person gehen.
    $this->from(route('pos.emergency'))->post(route('pos.emergency.loans'), ['person' => 'N-8', 'date' => $day, 'barcodes' => '0060050'])->assertSessionHasErrors([], null, 'loans');
    expect(Loan::query()->count())->toBe(1)->and(Loan::query()->firstOrFail()->patron_id)->toBe((string) $first->getKey());
});
