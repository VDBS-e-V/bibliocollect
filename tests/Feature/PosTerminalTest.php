<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Mail\TransactionReceiptMail;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\LoanTransaction;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function terminalUser(string $role = 'student_ag_basic'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function terminalPatron(string $number, array $overrides = []): Patron
{
    return Patron::query()->create(array_merge([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Kasse',
        'last_name' => 'Person'.$number,
        'birth_date' => '2012-01-01',
        'email' => $number.'@example.invalid',
    ], $overrides));
}

function terminalCopy(string $barcode, ?string $title = null): Copy
{
    $name = $title ?? 'Buch '.$barcode;
    $titleModel = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $titleModel->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => CopyStatus::Active]);
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:00:00', 'Europe/Berlin'));

    foreach (range(1, 5) as $dayOfWeek) {
        LibraryOpeningHour::query()->create(['day_of_week' => $dayOfWeek, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '15:00']);
    }
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('selects a person by library number or by name search', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    terminalPatron('S-TM-2', ['last_name' => 'Anders']);

    $this->actingAs($user)->post(route('pos.terminal.patron'), ['code' => 's-tm-1'])->assertRedirect(route('pos.terminal'));
    $this->actingAs($user)->get(route('pos.terminal'))->assertOk()->assertSee('Kasse PersonS-TM-1')->assertSee('Andere Person wählen');

    $this->actingAs($user)->post(route('pos.terminal.patron.clear'));
    $this->actingAs($user)->post(route('pos.terminal.patron'), ['code' => 'Anders'])->assertRedirect(route('pos.terminal', ['suche' => 'Anders']));
    $this->actingAs($user)->get(route('pos.terminal', ['suche' => 'Anders']))->assertOk()->assertSee('Anders, Kasse');

    $this->actingAs($user)->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()])->assertRedirect(route('pos.terminal'));
    expect(session('pos.terminal.patron_id'))->toBe((string) $patron->getKey());
});

it('collects checkouts, renewals and returns without booking and then confirms them together', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    $old = terminalCopy('TM-OLD', 'Altes Buch');
    $keep = terminalCopy('TM-KEEP', 'Behaltenes Buch');
    $new = terminalCopy('TM-NEW', 'Neues Buch');

    $oldLoan = app(CheckoutCopyAction::class)->execute($patron, $old->barcode, $user);
    $keepLoan = app(CheckoutCopyAction::class)->execute($patron, $keep->barcode, $user);

    $this->actingAs($user)->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()]);

    // Rückgabe, Verlängerung und Ausleihe in einem Vorgang
    $this->post(route('pos.terminal.mode'), ['mode' => 'return']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-OLD'])->assertRedirect(route('pos.terminal'));
    $this->post(route('pos.terminal.renew', ['loanId' => $keepLoan->getKey()]))->assertRedirect(route('pos.terminal'));
    $this->post(route('pos.terminal.mode'), ['mode' => 'checkout']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-NEW'])->assertRedirect(route('pos.terminal'));

    // Bis zum Bestätigen ist nichts gebucht.
    expect($oldLoan->fresh()->returned_at)->toBeNull()
        ->and($keepLoan->fresh()->renewal_count)->toBe(0)
        ->and(Loan::query()->where('copy_id', $new->getKey())->exists())->toBeFalse()
        ->and(LoanTransaction::query()->count())->toBe(0);

    $this->get(route('pos.terminal'))
        ->assertOk()
        ->assertSee('3 Positionen')
        ->assertSee('1 ausleihen')
        ->assertSee('1 verlängern')
        ->assertSee('1 zurücknehmen');

    $response = $this->post(route('pos.terminal.confirm'))->assertRedirect();

    $transaction = LoanTransaction::query()->firstOrFail();

    $response->assertRedirect(route('pos.terminal.receipt', ['transactionId' => $transaction->getKey()]));

    expect($oldLoan->fresh()->returned_at)->not->toBeNull()
        ->and($keepLoan->fresh()->renewal_count)->toBe(1)
        ->and(Loan::query()->where('copy_id', $new->getKey())->whereNull('returned_at')->exists())->toBeTrue()
        ->and($transaction->number)->toBe('V-20261005-001')
        ->and($transaction->checked_out_count)->toBe(1)
        ->and($transaction->renewed_count)->toBe(1)
        ->and($transaction->returned_count)->toBe(1)
        ->and($transaction->patron_id)->toBe((string) $patron->getKey())
        ->and(session('pos.terminal'))->toBeNull()
        ->and(AuditEvent::query()->where('action', 'circulation.transaction.confirmed')->count())->toBe(1);

    $this->get(route('pos.terminal.receipt', ['transactionId' => $transaction->getKey()]))
        ->assertOk()
        ->assertSee('V-20261005-001')
        ->assertSee('Altes Buch')
        ->assertSee('Neues Buch')
        ->assertSee('fällig am');
});

it('rejects invalid positions right away with the reason', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    $other = terminalPatron('S-TM-2');
    $loaned = terminalCopy('TM-LOANED');
    app(CheckoutCopyAction::class)->execute($other, $loaned->barcode, $user);
    $free = terminalCopy('TM-FREE');

    // Ohne Person keine Ausleihe
    $this->actingAs($user)->post(route('pos.terminal.scan'), ['code' => 'TM-FREE'])->assertSessionHas('terminal_error', 'Bitte zuerst eine Person wählen.');

    $this->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()]);

    $this->post(route('pos.terminal.scan'), ['code' => 'TM-LOANED'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'bereits ausgeliehen'));
    $this->post(route('pos.terminal.scan'), ['code' => 'GIBT-ES-NICHT'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'Kein Exemplar'));

    $this->post(route('pos.terminal.scan'), ['code' => 'TM-FREE']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-FREE'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'schon im Vorgang'));

    // Rückgabe fremder Ausleihe in einem Vorgang mit Person
    $this->post(route('pos.terminal.mode'), ['mode' => 'return']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-LOANED'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'anderes Ausleihkonto'));
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-FREE'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'schon im Vorgang'));

    expect($free->fresh()->exists)->toBeTrue();
});

it('lets returns be booked without choosing a person and stores no person on the receipt', function (): void {
    $user = terminalUser();
    $holder = terminalPatron('S-TM-1');
    $copy = terminalCopy('TM-BOX');
    $loan = app(CheckoutCopyAction::class)->execute($holder, $copy->barcode, $user);

    $this->actingAs($user)->post(route('pos.terminal.mode'), ['mode' => 'return']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-BOX']);
    $this->post(route('pos.terminal.confirm'))->assertRedirect();

    $transaction = LoanTransaction::query()->firstOrFail();

    expect($loan->fresh()->returned_at)->not->toBeNull()
        ->and($transaction->patron_id)->toBeNull()
        ->and($transaction->returned_count)->toBe(1);
});

it('books nothing when one position fails on confirmation', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    $first = terminalCopy('TM-A');
    $second = terminalCopy('TM-B');

    $this->actingAs($user)->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()]);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-A']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-B']);

    // Zwischen Sammeln und Bestätigen leiht jemand anderes das zweite Exemplar aus.
    app(CheckoutCopyAction::class)->execute(terminalPatron('S-TM-2'), $second->barcode, $user);

    $this->post(route('pos.terminal.confirm'))
        ->assertRedirect(route('pos.terminal'))
        ->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'Position 2') && str_contains($m, 'Es wurde nichts gebucht'));

    expect(Loan::query()->where('copy_id', $first->getKey())->exists())->toBeFalse()
        ->and(LoanTransaction::query()->count())->toBe(0)
        ->and(session('pos.terminal.items'))->toHaveCount(2);
});

it('respects the loan limit across the whole transaction and frees slots by returns in the same transaction', function (): void {
    config(['circulation.max_open_loans' => ['default' => 1, 'by_kind' => []]]);
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    $held = terminalCopy('TM-HELD');
    terminalCopy('TM-X');
    terminalCopy('TM-Y');
    app(CheckoutCopyAction::class)->execute($patron, $held->barcode, $user);

    $this->actingAs($user)->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()]);

    $this->post(route('pos.terminal.scan'), ['code' => 'TM-X'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'höchstens 1'));

    $this->post(route('pos.terminal.mode'), ['mode' => 'return']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-HELD']);
    $this->post(route('pos.terminal.mode'), ['mode' => 'checkout']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-X'])->assertSessionMissing('terminal_error');
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-Y'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'höchstens 1'));

    $this->post(route('pos.terminal.confirm'))->assertRedirect();

    expect(Loan::query()->where('patron_id', $patron->getKey())->whereNull('returned_at')->count())->toBe(1);
});

it('shows who a returned copy is held for on the receipt', function (): void {
    $user = terminalUser();
    $copy = terminalCopy('TM-HOLD');
    app(CheckoutCopyAction::class)->execute(terminalPatron('S-TM-1'), $copy->barcode, $user);
    app(PlaceReservationAction::class)->execute(terminalPatron('S-TM-2'), $copy->barcode, $user);

    $this->actingAs($user)->post(route('pos.terminal.mode'), ['mode' => 'return']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-HOLD']);
    $this->post(route('pos.terminal.confirm'));

    $this->get(route('pos.terminal.receipt', ['transactionId' => LoanTransaction::query()->firstOrFail()->getKey()]))
        ->assertOk()
        ->assertSee('zurücklegen')
        ->assertSee('S-TM-2');
});

it('can discard and remove positions', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    terminalCopy('TM-A');
    terminalCopy('TM-B');

    $this->actingAs($user)->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()]);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-A']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-B']);

    $this->post(route('pos.terminal.item.remove', ['index' => 0]));
    expect(session('pos.terminal.items'))->toHaveCount(1)
        ->and(session('pos.terminal.items.0.barcode'))->toBe('TM-B');

    $this->post(route('pos.terminal.discard'))->assertSessionHas('terminal_notice');
    expect(session('pos.terminal'))->toBeNull();

    $this->post(route('pos.terminal.confirm'))->assertSessionHas('terminal_error', 'Der Vorgang ist leer.');
});

it('mails the receipt to the address on the account or a typed one and remembers it', function (): void {
    Mail::fake();
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    terminalCopy('TM-MAIL');

    $this->actingAs($user)->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()]);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-MAIL']);
    $this->post(route('pos.terminal.confirm'));

    $transaction = LoanTransaction::query()->firstOrFail();

    $this->get(route('pos.terminal.receipt', ['transactionId' => $transaction->getKey()]))->assertOk()->assertSee('S-TM-1@example.invalid');

    $this->post(route('pos.terminal.receipt.mail', ['transactionId' => $transaction->getKey()]), ['email' => 'kein-mail'])->assertSessionHasErrors('email');

    $this->post(route('pos.terminal.receipt.mail', ['transactionId' => $transaction->getKey()]), ['email' => 'eltern@example.org'])
        ->assertRedirect(route('pos.terminal.receipt', ['transactionId' => $transaction->getKey()]));

    Mail::assertSent(TransactionReceiptMail::class, static fn (TransactionReceiptMail $mail): bool => $mail->hasTo('eltern@example.org'));

    expect($transaction->fresh()->emailed_to)->toBe('eltern@example.org')
        ->and($transaction->fresh()->emailed_at)->not->toBeNull();
});

it('renders the receipt mail with the booked positions', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1', ['first_name' => 'Mia']);
    terminalCopy('TM-R', 'Das Lesebuch');

    $this->actingAs($user)->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()]);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-R']);
    $this->post(route('pos.terminal.confirm'));

    $html = (new TransactionReceiptMail(LoanTransaction::query()->with('patron')->firstOrFail()))->render();

    expect($html)->toContain('Hallo Mia')
        ->toContain('V-20261005-001')
        ->toContain('Das Lesebuch')
        ->toContain('19.10.2026');
});

it('keeps the terminal away from roles without circulation rights', function (string $role): void {
    $this->actingAs(terminalUser($role))->get(route('pos.terminal'))->assertForbidden();
    $this->actingAs(terminalUser($role))->post(route('pos.terminal.confirm'))->assertForbidden();
})->with(['technical_admin', 'student', 'teacher']);
