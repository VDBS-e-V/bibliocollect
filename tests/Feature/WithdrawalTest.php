<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Actions\WithdrawCopiesAction;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Enums\WithdrawalFate;
use App\Modules\Catalog\Enums\WithdrawalReason;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\InventoryLabelPlanner;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function weedUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function weedCopy(string $barcode, string $title = 'Altes Buch'): Copy
{
    $titleModel = Title::query()->create(['preferred_title' => $title, 'sort_title' => $title]);
    $edition = Edition::query()->create(['title_id' => $titleModel->getKey(), 'media_type' => 'book', 'isbn' => '9783000000003']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => CopyStatus::Active]);
}

it('keeps weeding for staff and administration', function (): void {
    foreach (['staff', 'management', 'student_ag_basic', 'student_ag_extended'] as $role) {
        $this->actingAs(weedUser($role))->get(route('pos.withdrawal'))->assertOk()->assertSee('Inventarnummer des Buchs');
        $this->actingAs(weedUser($role))->get(route('pos.withdrawal.batch'))->assertOk()->assertSee('Inventarnummern');
    }

    foreach (['teacher'] as $role) {
        $this->actingAs(weedUser($role))->get(route('pos.withdrawal'))->assertForbidden();
        $this->actingAs(weedUser($role))->post(route('pos.withdrawal.preview'), ['numbers' => '0040001'])->assertForbidden();
    }
});

it('checks the numbers, asks for reason and fate and then withdraws the books', function (): void {
    $staff = weedUser('staff');
    $a = weedCopy('0040001', 'Erstes altes Buch');
    $b = weedCopy('0040002', 'Zweites altes Buch');
    $kept = weedCopy('0040003', 'Bleibt');

    $page = $this->actingAs($staff)->post(route('pos.withdrawal.preview'), ['numbers' => "0040001\n0040002, 9999999\n0040001"])->assertOk();
    $page->assertSee('Erstes altes Buch')->assertSee('Zweites altes Buch')->assertDontSee('Bleibt')->assertSee('Unbekannte Nummern: 9999999')->assertSee('2 Bücher können');
    expect(Copy::query()->where('status', CopyStatus::Withdrawn->value)->count())->toBe(0);

    $this->actingAs($staff)->post(route('pos.withdrawal.store'), ['copies' => [(string) $a->getKey(), (string) $b->getKey()], 'reason' => 'damaged', 'fate' => 'disposed', 'date' => now()->toDateString()])
        ->assertRedirect(route('pos.withdrawal.list'))->assertSessionHas('withdrawal_notice', '2 Exemplare wurden ausgesondert.');

    $a->refresh();
    expect($a->status)->toBe(CopyStatus::Withdrawn)->and($a->depreciation_reason)->toBe('damaged')->and($a->further_use)->toBe('disposed')->and($a->depreciated_at?->toDateString())->toBe(now()->toDateString())
        ->and($kept->refresh()->status)->toBe(CopyStatus::Active)
        ->and(AuditEvent::query()->where('action', 'catalog.copy.withdrawn')->count())->toBe(2);
});

it('validates reason, fate and date and refuses to withdraw nothing', function (): void {
    $staff = weedUser('staff');
    $copy = weedCopy('0040010');

    $this->actingAs($staff)->post(route('pos.withdrawal.preview'), ['numbers' => ''])->assertSessionHasErrors('numbers');
    $this->actingAs($staff)->post(route('pos.withdrawal.preview'), ['numbers' => '1111111'])->assertRedirect(route('pos.withdrawal.batch'))->assertSessionHasErrors('numbers')->assertSessionHas('withdrawal_report');

    $base = ['copies' => [(string) $copy->getKey()], 'reason' => 'damaged', 'fate' => 'disposed', 'date' => now()->toDateString()];
    $this->actingAs($staff)->post(route('pos.withdrawal.store'), [...$base, 'reason' => 'weil-ich-will'])->assertSessionHasErrors('reason');
    $this->actingAs($staff)->post(route('pos.withdrawal.store'), [...$base, 'fate' => 'unbekannt'])->assertSessionHasErrors('fate');
    $this->actingAs($staff)->post(route('pos.withdrawal.store'), [...$base, 'date' => now()->addDays(3)->toDateString()])->assertSessionHasErrors('date');
    $this->actingAs($staff)->post(route('pos.withdrawal.store'), [...$base, 'copies' => []])->assertSessionHasErrors('copies');

    expect($copy->refresh()->status)->toBe(CopyStatus::Active);
});

it('does not withdraw books that are on loan or reserved for someone', function (): void {
    $staff = weedUser('staff');
    foreach (range(1, 5) as $day) {
        LibraryOpeningHour::query()->create(['day_of_week' => $day, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00']);
    }

    $loaned = weedCopy('0040020', 'Ausgeliehen');
    $free = weedCopy('0040021', 'Frei');
    $patron = Patron::query()->create(['library_number' => 'W-40', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Leihende', 'last_name' => 'Person', 'birth_date' => '2010-01-01']);
    app(CheckoutCopyAction::class)->execute($patron, $loaned->barcode, $staff);

    $page = $this->actingAs($staff)->post(route('pos.withdrawal.preview'), ['numbers' => '0040020 0040021'])->assertOk();
    $page->assertSee('0040020')->assertSee('ist noch ausgeliehen')->assertSee('Frei');

    // Auch beim Bestätigen wird noch einmal geprüft.
    $this->actingAs($staff)->post(route('pos.withdrawal.store'), ['copies' => [(string) $loaned->getKey(), (string) $free->getKey()], 'reason' => 'outdated', 'fate' => 'donated', 'date' => now()->toDateString()])
        ->assertSessionHas('withdrawal_notice', static fn (string $text): bool => str_contains($text, '1 Exemplar wurde') && str_contains($text, 'Der Rest war inzwischen ausgeliehen'));

    expect($loaned->refresh()->status)->toBe(CopyStatus::Active)->and($free->refresh()->status)->toBe(CopyStatus::Withdrawn);

    $this->actingAs($staff)->post(route('pos.withdrawal.preview'), ['numbers' => '0040021'])->assertRedirect(route('pos.withdrawal.batch'))->assertSessionHas('withdrawal_report', static fn (array $report): bool => $report['already'] === ['0040021']);
});

it('lists the weeded books by period and reason, exports them and brings a book back', function (): void {
    $staff = weedUser('staff');
    $old = weedCopy('0040030', 'Vorjahresbuch');
    $new = weedCopy('0040031', 'Neues Aussortiertes');
    $other = weedCopy('0040032', 'Doppeltes Buch');
    $this->actingAs($staff);

    app(WithdrawCopiesAction::class)->execute([(string) $new->getKey()], WithdrawalReason::Damaged, WithdrawalFate::Disposed, now()->subDays(5)->toDateString());
    app(WithdrawCopiesAction::class)->execute([(string) $other->getKey()], WithdrawalReason::Duplicate, WithdrawalFate::Donated, now()->subDays(2)->toDateString());
    app(WithdrawCopiesAction::class)->execute([(string) $old->getKey()], WithdrawalReason::Outdated, WithdrawalFate::Sold, now()->subYears(2)->toDateString());

    $page = $this->get(route('pos.withdrawal.list'))->assertOk();
    $page->assertSee('Neues Aussortiertes')->assertSee('Doppeltes Buch')->assertSee('Beschädigt oder abgenutzt')->assertSee('Verschenkt oder gespendet')->assertDontSee('Vorjahresbuch');

    $this->get(route('pos.withdrawal.list', ['grund' => 'duplicate']))->assertSee('Doppeltes Buch')->assertDontSee('Neues Aussortiertes');
    $this->get(route('pos.withdrawal.list', ['von' => now()->subYears(3)->toDateString()]))->assertSee('Vorjahresbuch');

    $csv = $this->get(route('pos.withdrawal.export'))->assertOk()->streamedContent();
    expect($csv)->toContain('Inventarnummer;Titel;ISBN;"Ausgesondert am";Grund;Verbleib')->and($csv)->toContain('0040031;"Neues Aussortiertes"')->and($csv)->toContain('Beschädigt oder abgenutzt')->and($csv)->not->toContain('0040030');

    $this->post(route('pos.withdrawal.restore', ['copyId' => $new->getKey()]))->assertSessionHas('withdrawal_notice');
    $new->refresh();
    expect($new->status)->toBe(CopyStatus::Active)->and($new->depreciation_reason)->toBeNull()->and($new->depreciated_at)->toBeNull()
        ->and(AuditEvent::query()->where('action', 'catalog.copy.restored')->count())->toBe(1);

    // Ein nicht ausgesondertes Exemplar lässt sich nicht „zurückholen“.
    $this->post(route('pos.withdrawal.restore', ['copyId' => $new->getKey()]))->assertSessionHas('withdrawal_notice', 'Dieses Exemplar war nicht ausgesondert.');
});

it('shows legacy free text reasons unchanged and counts weeded books in the statistics', function (): void {
    $staff = weedUser('staff');
    $legacy = weedCopy('0040040', 'Altsystembuch');
    $legacy->forceFill(['status' => CopyStatus::Withdrawn, 'depreciation_reason' => 'Wasserschaden', 'depreciated_at' => now()->subDays(3)->toDateString(), 'further_use' => 'Altpapier'])->save();

    $this->actingAs($staff)->get(route('pos.withdrawal.list'))->assertSee('Wasserschaden')->assertSee('Altpapier');
    $this->actingAs($staff)->get(route('pos.statistics', ['zeitraum' => 'letzte12']))->assertOk()->assertSee('Im Zeitraum ausgesondert');
});

it('weeds out a scanned book at once by the chosen kind and reports problems without changing anything', function (): void {
    $helper = weedUser('student_ag_basic');
    $book = weedCopy('0040050', 'Aussortiertes Buch');
    $loaned = weedCopy('0040051', 'Ausgeliehenes Buch');
    foreach (range(1, 5) as $day) {
        LibraryOpeningHour::query()->create(['day_of_week' => $day, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00']);
    }
    $patron = Patron::query()->create(['library_number' => 'W-50', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Leihende', 'last_name' => 'Person', 'birth_date' => '2010-01-01']);
    app(CheckoutCopyAction::class)->execute($patron, $loaned->barcode, weedUser('staff'));

    $this->actingAs($helper)->get(route('pos.withdrawal'))->assertSee('Entsorgung (Müll)')->assertSee('Zum Verschenken')->assertSee('Verkauf');

    $this->actingAs($helper)->post(route('pos.withdrawal.scan'), ['code' => '0040050'])->assertSessionHasErrors('art');
    expect($book->refresh()->status)->toBe(CopyStatus::Active);

    $this->actingAs($helper)->post(route('pos.withdrawal.scan'), ['art' => 'donated', 'code' => ' 0040050 '])
        ->assertRedirect(route('pos.withdrawal', ['art' => 'donated']))->assertSessionHas('withdrawal_notice');
    $book->refresh();
    expect($book->status)->toBe(CopyStatus::Withdrawn)->and($book->further_use)->toBe('donated')->and($book->depreciation_reason)->toBe('unused')
        ->and(AuditEvent::query()->where('action', 'catalog.copy.withdrawn')->count())->toBe(1);

    $this->actingAs($helper)->get(route('pos.withdrawal', ['art' => 'donated']))->assertSee('Zuletzt aussortiert')->assertSee('Aussortiertes Buch')->assertSee('Zurückholen')->assertSee('value="donated" checked', false);

    $this->actingAs($helper)->post(route('pos.withdrawal.scan'), ['art' => 'sold', 'code' => '0040050'])->assertSessionHasErrors('code');
    $this->actingAs($helper)->post(route('pos.withdrawal.scan'), ['art' => 'sold', 'code' => '0040051'])->assertSessionHasErrors('code');
    $this->actingAs($helper)->post(route('pos.withdrawal.scan'), ['art' => 'sold', 'code' => '9999999'])->assertSessionHasErrors('code');
    $this->actingAs($helper)->post(route('pos.withdrawal.scan'), ['art' => 'quatsch', 'code' => '0040051'])->assertSessionHasErrors('art');
    expect($loaned->refresh()->status)->toBe(CopyStatus::Active);

    // Rückgängig aus der Liste heraus.
    $this->actingAs($helper)->from(route('pos.withdrawal'))->post(route('pos.withdrawal.restore', ['copyId' => $book->getKey()]))->assertRedirect(route('pos.withdrawal'));
    expect($book->refresh()->status)->toBe(CopyStatus::Active);
});

it('hides weeded books in the public catalog but keeps the inventory number and the medium in the system', function (): void {
    $staff = weedUser('staff');
    $gone = weedCopy('0040070', 'Nur Ausgesondertes');
    $kept = weedCopy('0040071', 'Teils vorhanden');
    $otherEdition = Edition::query()->create(['title_id' => $kept->edition->title_id, 'media_type' => 'book', 'isbn' => '9783111111111', 'publisher_name' => 'Zweiter Verlag']);
    $weeded = Copy::query()->create(['edition_id' => $otherEdition->getKey(), 'barcode' => '0040072', 'status' => CopyStatus::Active]);

    $this->actingAs($staff)->post(route('pos.withdrawal.scan'), ['art' => 'disposed', 'code' => '0040070'])->assertSessionHas('withdrawal_notice');
    $this->actingAs($staff)->post(route('pos.withdrawal.scan'), ['art' => 'sold', 'code' => '0040072'])->assertSessionHas('withdrawal_notice');

    // Öffentlich: Titel ohne vorhandenes Exemplar fehlt, bei Titeln mit Rest entfallen nur die Ausgaben ohne Exemplar.
    $this->get(route('public.catalog.index'))->assertOk()->assertSee('Teils vorhanden')->assertDontSee('Nur Ausgesondertes');
    $this->get(route('public.catalog.show', ['titleId' => $gone->edition->title_id]))->assertNotFound();
    $this->get(route('public.catalog.show', ['titleId' => $kept->edition->title_id]))->assertOk()->assertSee('9783000000003')->assertDontSee('9783111111111')->assertDontSee('Zweiter Verlag');

    // Intern bleibt alles sichtbar und gespeichert.
    $this->actingAs($staff)->get(route('pos.catalog.index', ['q' => 'Ausgesondertes']))->assertOk()->assertSee('Nur Ausgesondertes');
    expect(Copy::query()->where('barcode', '0040070')->value('status'))->toBe(CopyStatus::Withdrawn);

    // Die Inventarnummer bleibt vergeben: Datenbank, Anlegen und Etikettenvorrat.
    expect(fn () => Copy::query()->create(['edition_id' => $kept->edition_id, 'barcode' => '0040070', 'status' => CopyStatus::Active]))->toThrow(QueryException::class);
    $plan = app(InventoryLabelPlanner::class)->gaps(40069, 40073, true);
    expect($plan['numbers'])->toBe(['0040069', '0040073'])->and($weeded->refresh()->status)->toBe(CopyStatus::Withdrawn);
});
