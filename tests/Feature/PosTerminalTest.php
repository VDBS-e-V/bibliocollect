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

it('starts on a screen that only scans a person or returns', function (): void {
    $user = terminalUser();

    $this->actingAs($user)->get(route('pos.terminal'))
        ->assertOk()
        ->assertSee('Ausweis oder Exemplar-Barcode scannen')
        ->assertDontSee('Ausgeliehene Medien');

    // Ohne Person gibt es keinen Personenbildschirm.
    $this->get(route('pos.terminal.person'))->assertRedirect(route('pos.terminal'));
});

it('moves to the person screen when a library number is scanned', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    $copy = terminalCopy('TM-OLD', 'Altes Buch');
    app(CheckoutCopyAction::class)->execute($patron, $copy->barcode, $user);

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => ' s-tm-1 '])->assertRedirect(route('pos.terminal.person'));

    $this->get(route('pos.terminal.person'))
        ->assertOk()
        ->assertSee('Kasse PersonS-TM-1')
        ->assertSee('Ausgeliehene Medien')
        ->assertSee('Altes Buch')
        ->assertSee('Verlängern')
        ->assertSee('Zurückgeben')
        ->assertSee('1 von 5 Medien ausgeliehen');

    // Der Start leitet bei gewählter Person auf deren Bildschirm.
    $this->get(route('pos.terminal'))->assertRedirect(route('pos.terminal.person'));
});

it('finds people by name on the start screen and selects one', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-2', ['last_name' => 'Anders']);
    terminalPatron('S-TM-3', ['last_name' => 'Weg', 'status' => PatronStatus::Departed, 'leaving_on' => '2026-01-01']);

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'Anders'])->assertRedirect(route('pos.terminal', ['suche' => 'Anders']));
    $this->get(route('pos.terminal', ['suche' => 'Anders']))->assertOk()->assertSee('Anders, Kasse');
    $this->get(route('pos.terminal', ['suche' => 'Weg']))->assertOk()->assertSee('Niemand gefunden');

    $this->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()])->assertRedirect(route('pos.terminal.person'));
    expect(session('pos.terminal.patron_id'))->toBe((string) $patron->getKey());
});

it('collects returns without a person on the start screen and books them on confirmation', function (): void {
    $user = terminalUser();
    $holder = terminalPatron('S-TM-1');
    $copy = terminalCopy('TM-BOX');
    $loan = app(CheckoutCopyAction::class)->execute($holder, $copy->barcode, $user);

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'TM-BOX'])->assertRedirect(route('pos.terminal'));

    expect($loan->fresh()->returned_at)->toBeNull();

    $this->get(route('pos.terminal'))->assertOk()->assertSee('Gescannte Rückgaben')->assertSee('Buch TM-BOX');

    $this->post(route('pos.terminal.confirm'))->assertRedirect();

    $transaction = LoanTransaction::query()->firstOrFail();

    expect($loan->fresh()->returned_at)->not->toBeNull()
        ->and($transaction->patron_id)->toBeNull()
        ->and($transaction->returned_count)->toBe(1);

    // Zweiter Scan desselben Exemplars: nicht ausgeliehen
    $this->post(route('pos.terminal.start'), ['code' => 'TM-BOX'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'nicht ausgeliehen'));
});

it('carries scanned returns over to their owner and refuses a foreign person', function (): void {
    $user = terminalUser();
    $owner = terminalPatron('S-TM-1');
    $other = terminalPatron('S-TM-2');
    app(CheckoutCopyAction::class)->execute($owner, terminalCopy('TM-A')->barcode, $user);

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'TM-A']);

    $this->post(route('pos.terminal.start'), ['code' => 'S-TM-2'])
        ->assertRedirect(route('pos.terminal'))
        ->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'Rückgaben anderer Personen'));

    $this->post(route('pos.terminal.start'), ['code' => 'S-TM-1'])->assertRedirect(route('pos.terminal.person'));
    $this->get(route('pos.terminal.person'))->assertOk()->assertSee('1 Position')->assertSee('Buch TM-A');

    expect($other->fresh()->exists)->toBeTrue();
});

it('collects checkouts, renewals and returns on the person screen and confirms them together', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    $oldLoan = app(CheckoutCopyAction::class)->execute($patron, terminalCopy('TM-OLD', 'Altes Buch')->barcode, $user);
    $keepLoan = app(CheckoutCopyAction::class)->execute($patron, terminalCopy('TM-KEEP', 'Behaltenes Buch')->barcode, $user);
    $new = terminalCopy('TM-NEW', 'Neues Buch');

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'S-TM-1']);

    $this->post(route('pos.terminal.return', ['loanId' => $oldLoan->getKey()]))->assertRedirect(route('pos.terminal.person'));
    $this->post(route('pos.terminal.renew', ['loanId' => $keepLoan->getKey()]))->assertRedirect(route('pos.terminal.person'));
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-NEW'])->assertRedirect(route('pos.terminal.person'));

    expect($oldLoan->fresh()->returned_at)->toBeNull()
        ->and($keepLoan->fresh()->renewal_count)->toBe(0)
        ->and(Loan::query()->where('copy_id', $new->getKey())->exists())->toBeFalse()
        ->and(LoanTransaction::query()->count())->toBe(0);

    $this->get(route('pos.terminal.person'))
        ->assertOk()
        ->assertSee('3 Positionen')
        ->assertSee('1 ausleihen')
        ->assertSee('1 verlängern')
        ->assertSee('1 zurücknehmen')
        ->assertSee('wird zurückgegeben')
        ->assertSee('wird verlängert');

    $this->post(route('pos.terminal.confirm'))->assertRedirect();

    $transaction = LoanTransaction::query()->firstOrFail();

    expect($oldLoan->fresh()->returned_at)->not->toBeNull()
        ->and($keepLoan->fresh()->renewal_count)->toBe(1)
        ->and(Loan::query()->where('copy_id', $new->getKey())->whereNull('returned_at')->exists())->toBeTrue()
        ->and($transaction->number)->toBe('V-20261005-001')
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

it('treats a scan of a loaned copy of this person as return and a free copy as checkout', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    $other = terminalPatron('S-TM-2');
    app(CheckoutCopyAction::class)->execute($patron, terminalCopy('TM-MINE')->barcode, $user);
    app(CheckoutCopyAction::class)->execute($other, terminalCopy('TM-THEIRS')->barcode, $user);
    terminalCopy('TM-FREE');

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'S-TM-1']);

    $this->post(route('pos.terminal.scan'), ['code' => 'TM-MINE']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-FREE']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-THEIRS'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'bereits ausgeliehen'));
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-FREE'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'schon im Vorgang'));
    $this->post(route('pos.terminal.scan'), ['code' => 'GIBT-ES-NICHT'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'Kein Exemplar'));

    expect(session('pos.terminal.items'))->toHaveCount(2)
        ->and(session('pos.terminal.items.0.type'))->toBe('return')
        ->and(session('pos.terminal.items.1.type'))->toBe('checkout');
});

it('books nothing when one position fails on confirmation', function (): void {
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    $first = terminalCopy('TM-A');
    $second = terminalCopy('TM-B');

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'S-TM-1']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-A']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-B']);

    // Zwischen Sammeln und Bestätigen leiht jemand anderes das zweite Exemplar aus.
    app(CheckoutCopyAction::class)->execute(terminalPatron('S-TM-2'), $second->barcode, $user);

    $this->post(route('pos.terminal.confirm'))
        ->assertRedirect(route('pos.terminal.person'))
        ->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'Position 2') && str_contains($m, 'Es wurde nichts gebucht'));

    expect(Loan::query()->where('copy_id', $first->getKey())->exists())->toBeFalse()
        ->and(LoanTransaction::query()->count())->toBe(0)
        ->and(session('pos.terminal.items'))->toHaveCount(2)
        ->and($patron->fresh()->exists)->toBeTrue();
});

it('respects the loan limit and frees slots by returns in the same transaction', function (): void {
    config(['circulation.max_open_loans' => ['default' => 1, 'by_kind' => []]]);
    $user = terminalUser();
    $patron = terminalPatron('S-TM-1');
    $held = app(CheckoutCopyAction::class)->execute($patron, terminalCopy('TM-HELD')->barcode, $user);
    terminalCopy('TM-X');
    terminalCopy('TM-Y');

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'S-TM-1']);

    $this->post(route('pos.terminal.scan'), ['code' => 'TM-X'])->assertSessionHas('terminal_error', static fn (string $m): bool => str_contains($m, 'höchstens 1'));

    $this->post(route('pos.terminal.return', ['loanId' => $held->getKey()]));
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

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'TM-HOLD']);
    $this->post(route('pos.terminal.confirm'));

    $this->get(route('pos.terminal.receipt', ['transactionId' => LoanTransaction::query()->firstOrFail()->getKey()]))
        ->assertOk()
        ->assertSee('zurücklegen')
        ->assertSee('S-TM-2');
});

it('can remove positions, discard and refuses an empty confirmation', function (): void {
    $user = terminalUser();
    terminalPatron('S-TM-1');
    terminalCopy('TM-A');
    terminalCopy('TM-B');

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'S-TM-1']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-A']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-B']);

    $this->post(route('pos.terminal.item.remove', ['index' => 0]))->assertRedirect(route('pos.terminal.person'));
    expect(session('pos.terminal.items'))->toHaveCount(1)
        ->and(session('pos.terminal.items.0.barcode'))->toBe('TM-B');

    $this->post(route('pos.terminal.discard'))->assertRedirect(route('pos.terminal'))->assertSessionHas('terminal_notice');
    expect(session('pos.terminal'))->toBeNull();

    $this->post(route('pos.terminal.confirm'))->assertSessionHas('terminal_error', 'Der Vorgang ist leer.');
});

it('mails the receipt to the address on the account or a typed one and remembers it', function (): void {
    Mail::fake();
    $user = terminalUser();
    terminalPatron('S-TM-1');
    terminalCopy('TM-MAIL');

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'S-TM-1']);
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
    terminalPatron('S-TM-1', ['first_name' => 'Mia']);
    terminalCopy('TM-R', 'Das Lesebuch');

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'S-TM-1']);
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
    $this->actingAs(terminalUser($role))->get(route('pos.terminal.person'))->assertForbidden();
    $this->actingAs(terminalUser($role))->post(route('pos.terminal.confirm'))->assertForbidden();
})->with(['technical_admin', 'student', 'teacher']);

it('mails the receipt automatically when the library account has an address and still allows printing', function (): void {
    Mail::fake();
    $user = terminalUser();
    terminalPatron('S-TM-30');
    terminalCopy('TM-AUTO');

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'S-TM-30']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-AUTO']);
    $response = $this->post(route('pos.terminal.confirm'));

    $transaction = LoanTransaction::query()->firstOrFail();
    $response->assertRedirect(route('pos.terminal.receipt', ['transactionId' => $transaction->getKey()]))
        ->assertSessionHas('terminal_notice', static fn (string $text): bool => str_contains($text, 'S-TM-30@example.invalid') && str_contains($text, 'Drucken'));

    Mail::assertSent(TransactionReceiptMail::class, 1);
    Mail::assertSent(TransactionReceiptMail::class, static fn (TransactionReceiptMail $mail): bool => $mail->hasTo('S-TM-30@example.invalid'));

    expect($transaction->fresh()->emailed_to)->toBe('S-TM-30@example.invalid')
        ->and($transaction->fresh()->emailed_at)->not->toBeNull()
        ->and(AuditEvent::query()->where('action', 'circulation.transaction.emailed')->count())->toBe(1);

    // Der Druck bleibt möglich.
    $this->get(route('pos.terminal.receipt', ['transactionId' => $transaction->getKey()]))->assertOk()->assertSee('Beleg drucken');
});

it('sends no automatic receipt without an address, without a person or when the rule is off', function (): void {
    Mail::fake();
    $user = terminalUser();
    terminalPatron('S-TM-31', ['email' => null]);
    terminalCopy('TM-NONE');

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'S-TM-31']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-NONE']);
    $this->post(route('pos.terminal.confirm'))->assertSessionMissing('terminal_notice');

    Mail::assertNothingSent();

    config(['circulation.auto_receipt_mail' => false]);
    terminalPatron('S-TM-32');
    terminalCopy('TM-OFF');
    $this->post(route('pos.terminal.start'), ['code' => 'S-TM-32']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-OFF']);
    $this->post(route('pos.terminal.confirm'));

    Mail::assertNothingSent();
});

it('does not disturb the counter when the mail server fails', function (): void {
    $user = terminalUser();
    terminalPatron('S-TM-33');
    terminalCopy('TM-FAIL');
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Mailserver nicht erreichbar'));

    $this->actingAs($user)->post(route('pos.terminal.start'), ['code' => 'S-TM-33']);
    $this->post(route('pos.terminal.scan'), ['code' => 'TM-FAIL']);
    $transaction = null;

    $this->post(route('pos.terminal.confirm'))->assertRedirect()->assertSessionHasNoErrors();

    $transaction = LoanTransaction::query()->firstOrFail();
    expect($transaction->emailed_at)->toBeNull()->and($transaction->checked_out_count)->toBe(1);
    $this->get(route('pos.terminal.receipt', ['transactionId' => $transaction->getKey()]))->assertOk();
});
