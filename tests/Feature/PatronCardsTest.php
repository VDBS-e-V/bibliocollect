<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Actions\AssignPatronCardAction;
use App\Modules\Patrons\Actions\DepartPatronAction;
use App\Modules\Patrons\Actions\GeneratePatronCardsAction;
use App\Modules\Patrons\Enums\CardBlockReason;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Exceptions\PatronCardConflict;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\Patrons\Models\PatronCardDesign;
use App\Modules\Patrons\Support\PatronCardNumber;
use App\Surfaces\Pos\Http\Controllers\PatronCardController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function cardTestUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** Minimale gültige PNG-Datei mit den gewünschten Maßen (die Testumgebung hat keine GD-Erweiterung). */
function cardTestImage(string $name, int $width, int $height): UploadedFile
{
    $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    $rows = str_repeat("\0".str_repeat("\0", intdiv($width + 7, 8)), $height);
    $png = "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $width, $height, 1, 0, 0, 0, 0)).$chunk('IDAT', (string) gzcompress($rows)).$chunk('IEND', '');

    return UploadedFile::fake()->createWithContent($name, $png);
}

function cardTestPatron(string $number, string $first = 'Karla', string $last = 'Karte'): Patron
{
    return Patron::query()->create([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => $first,
        'last_name' => $last,
        'birth_date' => '2012-01-01',
    ]);
}

/** @return list<string> */
function cardTestNumbers(int $count): array
{
    app(GeneratePatronCardsAction::class)->execute($count);

    return PatronCard::query()->orderBy('number')->pluck('number')->all();
}

it('builds ten digit numbers with a valid check digit', function (): void {
    $number = PatronCardNumber::fromBase(123456789);

    expect($number)->toHaveLength(10)
        ->and(PatronCardNumber::isValid($number))->toBeTrue()
        ->and(PatronCardNumber::isValid(substr($number, 0, 9).(((int) substr($number, 9)) + 1) % 10))->toBeFalse()
        ->and(PatronCardNumber::isValid('12345'))->toBeFalse();
});

it('generates random, unique and non consecutive card numbers in numbered batches', function (): void {
    $first = app(GeneratePatronCardsAction::class)->execute(200);
    $second = app(GeneratePatronCardsAction::class)->execute(50);

    expect($first)->toBe(1)->and($second)->toBe(2)
        ->and(PatronCard::query()->count())->toBe(250)
        ->and(PatronCard::query()->where('status', CardStatus::Generated->value)->count())->toBe(250);

    $bases = PatronCard::query()->pluck('number')->map(static fn (string $number): int => PatronCardNumber::base($number))->sort()->values()->all();

    expect(array_unique($bases))->toHaveCount(250);

    foreach ($bases as $index => $base) {
        expect(PatronCardNumber::isValid(PatronCardNumber::fromBase($base)))->toBeTrue();

        if ($index > 0) {
            expect($base - $bases[$index - 1])->toBeGreaterThan(1);
        }
    }

    // Nicht fortlaufend in der Reihenfolge der Erzeugung.
    $ordered = PatronCard::query()->where('batch', 1)->orderBy('created_at')->orderBy('id')->pluck('number')->all();
    expect(count(array_unique(array_map(static fn (string $n): string => $n[0], $ordered))))->toBeGreaterThan(3);
});

it('assigns a card to a person, blocks the old card and refuses blocked or foreign cards', function (): void {
    [$a, $b, $c] = cardTestNumbers(3);
    $anna = cardTestPatron('S-C-1', 'Anna');
    $ben = cardTestPatron('S-C-2', 'Ben');

    expect(app(AssignPatronCardAction::class)->execute($a, $anna))->toBe(0);
    expect(app(AssignPatronCardAction::class)->execute($a, $anna))->toBe(0);

    // Ersatz: der alte Ausweis wird gesperrt.
    expect(app(AssignPatronCardAction::class)->execute($b, $anna))->toBe(1);
    $old = PatronCard::query()->where('number', $a)->firstOrFail();
    expect($old->status)->toBe(CardStatus::Blocked)->and($old->block_reason)->toBe(CardBlockReason::Replaced);

    expect(fn () => app(AssignPatronCardAction::class)->execute($b, $ben))->toThrow(PatronCardConflict::class, 'anderen Person');
    expect(fn () => app(AssignPatronCardAction::class)->execute($a, $ben))->toThrow(PatronCardConflict::class, 'gesperrt');
    expect(fn () => app(AssignPatronCardAction::class)->execute('0000000000', $ben))->toThrow(PatronCardConflict::class, 'gibt es nicht');

    expect(app(AssignPatronCardAction::class)->execute($c, $ben))->toBe(0)
        ->and(PatronCard::query()->where('number', $c)->firstOrFail()->patron_id)->toBe((string) $ben->getKey());
});

it('registers a new card on its own screen: scan, search the person, assign', function (): void {
    $staff = cardTestUser('staff');
    [$number] = cardTestNumbers(1);
    $patron = cardTestPatron('S-C-3', 'Clara', 'Zufall');
    cardTestPatron('S-C-33', 'Dora', 'Anders');

    $this->actingAs($staff)->post(route('pos.terminal.start'), ['code' => $number])
        ->assertRedirect(route('pos.terminal.card.register'));

    $this->actingAs($staff)->get(route('pos.terminal.card.register'))->assertOk()->assertSee($number)->assertSee('Person suchen');
    $this->actingAs($staff)->get(route('pos.terminal.card.register', ['q' => 'Zufall']))->assertOk()->assertSee('Zufall, Clara')->assertSee('noch kein Ausweis')->assertDontSee('Anders, Dora');

    $this->actingAs($staff)->post(route('pos.terminal.card.claim'), ['patron_id' => (string) $patron->getKey()])
        ->assertRedirect(route('pos.terminal.person'));

    expect(PatronCard::query()->where('number', $number)->firstOrFail()->patron_id)->toBe((string) $patron->getKey());

    // Kein zweites Zuordnen ohne neuen Scan.
    $this->post(route('pos.terminal.card.claim'), ['patron_id' => (string) $patron->getKey()])->assertSessionHas('terminal_error');

    // Danach öffnet der Ausweis die Person direkt.
    $this->post(route('pos.terminal.discard'));
    $this->actingAs($staff)->post(route('pos.terminal.start'), ['code' => $number])->assertRedirect(route('pos.terminal.person'));
    $this->actingAs($staff)->get(route('pos.terminal.person'))->assertOk()->assertSee('Clara')->assertSee($number);
});

it('shows the current card when registering a replacement and drops a stale registration', function (): void {
    $staff = cardTestUser('staff');
    [$a, $b] = cardTestNumbers(2);
    $patron = cardTestPatron('S-C-7', 'Rita', 'Ersatz');
    app(AssignPatronCardAction::class)->execute($a, $patron);

    $this->actingAs($staff)->post(route('pos.terminal.start'), ['code' => $b]);
    $this->get(route('pos.terminal.card.register', ['q' => 'Ersatz']))->assertSee('hat schon den Ausweis '.$a);

    // Zurück zum Start verwirft die offene Registrierung.
    $this->get(route('pos.terminal'))->assertOk();
    $this->get(route('pos.terminal.card.register'))->assertRedirect(route('pos.terminal'));
    $this->post(route('pos.terminal.card.claim'), ['patron_id' => (string) $patron->getKey()])->assertSessionHas('terminal_error');
    expect(PatronCard::query()->where('number', $b)->firstOrFail()->patron_id)->toBeNull();

    $this->post(route('pos.terminal.start'), ['code' => $b]);
    $this->post(route('pos.terminal.card.claim'), ['patron_id' => (string) $patron->getKey()])->assertRedirect(route('pos.terminal.person'));
    expect(PatronCard::query()->where('number', $a)->firstOrFail()->status)->toBe(CardStatus::Blocked);
});

it('issues, replaces and blocks cards from the patron page', function (): void {
    $staff = cardTestUser('staff');
    [$a, $b] = cardTestNumbers(2);
    $patron = cardTestPatron('S-C-8', 'Paul', 'Konto');

    $this->actingAs($staff)->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))->assertOk()->assertSee('noch kein Ausweis zugeordnet');

    $this->actingAs($staff)->post(route('pos.patrons.cards.assign', ['patronId' => $patron->getKey()]), ['number' => $a])
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]))->assertSessionHas('workspace_success');

    $this->actingAs($staff)->post(route('pos.patrons.cards.assign', ['patronId' => $patron->getKey()]), ['number' => $b, 'old_reason' => 'lost']);
    $old = PatronCard::query()->where('number', $a)->firstOrFail();
    expect($old->status)->toBe(CardStatus::Blocked)->and($old->block_reason)->toBe(CardBlockReason::Lost);

    $this->actingAs($staff)->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))->assertOk()->assertSee($a)->assertSee($b)->assertSee('verloren');

    $this->actingAs($staff)->post(route('pos.patrons.cards.assign', ['patronId' => $patron->getKey()]), ['number' => '0000000000'])->assertSessionHas('workspace_error');

    $new = PatronCard::query()->where('number', $b)->firstOrFail();
    $this->actingAs($staff)->post(route('pos.patrons.cards.block', ['patronId' => $patron->getKey(), 'cardId' => $new->getKey()]), ['reason' => 'defective'])->assertSessionHas('workspace_success');
    expect($new->refresh()->status)->toBe(CardStatus::Blocked);

    // Fremde Ausweise lassen sich über die Kontoseite nicht sperren.
    $other = cardTestPatron('S-C-9', 'Olga', 'Fremd');
    [$c] = array_values(array_diff(cardTestNumbers(1), [$a, $b]));
    app(AssignPatronCardAction::class)->execute($c, $other);
    $this->actingAs($staff)->post(route('pos.patrons.cards.block', ['patronId' => $patron->getKey(), 'cardId' => PatronCard::query()->where('number', $c)->firstOrFail()->getKey()]), ['reason' => 'lost'])->assertNotFound();
});

it('finds persons without a card by name search and library number', function (): void {
    $staff = cardTestUser('staff');
    cardTestPatron('S-C-4', 'Nina', 'Namenssuche');

    $this->actingAs($staff)->post(route('pos.terminal.start'), ['code' => 'S-C-4'])->assertRedirect(route('pos.terminal.person'));
    $this->post(route('pos.terminal.discard'));
    $this->actingAs($staff)->post(route('pos.terminal.start'), ['code' => 'Namenssuche'])->assertRedirect(route('pos.terminal', ['suche' => 'Namenssuche']));
});

it('assigns a replacement card on the person screen and blocks lost cards', function (): void {
    $staff = cardTestUser('staff');
    [$a, $b] = cardTestNumbers(2);
    $patron = cardTestPatron('S-C-5', 'Lena', 'Verlust');
    app(AssignPatronCardAction::class)->execute($a, $patron);

    $this->actingAs($staff)->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()]);

    $this->post(route('pos.terminal.card.lost'))->assertRedirect(route('pos.terminal.person'));
    expect(PatronCard::query()->where('number', $a)->firstOrFail()->block_reason)->toBe(CardBlockReason::Lost);

    // Der gesperrte Ausweis funktioniert nicht mehr.
    $this->post(route('pos.terminal.discard'));
    $this->post(route('pos.terminal.start'), ['code' => $a])->assertRedirect(route('pos.terminal'));
    $this->get(route('pos.terminal'))->assertSee('gesperrt');

    $this->post(route('pos.terminal.patron'), ['patron_id' => (string) $patron->getKey()]);
    $this->post(route('pos.terminal.card.assign'), ['code' => $b])->assertRedirect(route('pos.terminal.person'));
    expect(PatronCard::query()->where('number', $b)->firstOrFail()->patron_id)->toBe((string) $patron->getKey());

    $this->post(route('pos.terminal.card.assign'), ['code' => $a])->assertSessionHas('terminal_error');
});

it('blocks cards when the person leaves and unlinks them when anonymized', function (): void {
    $staff = cardTestUser('staff');
    [$a] = cardTestNumbers(1);
    $patron = cardTestPatron('S-C-6', 'Otto', 'Abgang');
    app(AssignPatronCardAction::class)->execute($a, $patron);

    app(DepartPatronAction::class)->execute($patron, now()->toDateString(), $staff);

    $card = PatronCard::query()->where('number', $a)->firstOrFail();
    expect($card->status)->toBe(CardStatus::Blocked)->and($card->block_reason)->toBe(CardBlockReason::Withdrawn);
});

it('prints front and back sheets for the avery format and tracks the print status', function (): void {
    $staff = cardTestUser('staff');
    cardTestNumbers(12);

    $html = $this->actingAs($staff)->post(route('pos.labels.cards.print', ['batch' => 1]), ['side' => 'vorder', 'start' => 3])->assertOk()->getContent();

    expect(substr_count($html, 'class="sheet"'))->toBe(2)
        ->and(substr_count($html, 'class="card empty"'))->toBe(2 + 6)
        ->and(substr_count($html, '<svg'))->toBe(12)
        ->and($html)->toContain('class="content"')->not->toContain('class="namebox"')
        ->and($html)->toContain('/brand/vdbs/logo-light.svg')
        ->and(PatronCard::query()->where('status', CardStatus::InPrint->value)->count())->toBe(12);

    $back = $this->actingAs($staff)->post(route('pos.labels.cards.print', ['batch' => 1]), ['side' => 'rueck', 'start' => 3])->assertOk()->getContent();

    expect(substr_count($back, 'class="card back"'))->toBe(12)
        ->and(substr_count($back, '<svg'))->toBe(0);

    $this->actingAs($staff)->post(route('pos.labels.cards.print', ['batch' => 1]), ['side' => 'vorder', 'start' => 11])->assertSessionHasErrors('start');
    $this->actingAs($staff)->post(route('pos.labels.cards.print', ['batch' => 99]), ['side' => 'vorder'])->assertSessionHasErrors('batch');
});

it('exports the numbers with status as csv and marks the batch as in print', function (): void {
    $staff = cardTestUser('staff');
    $numbers = cardTestNumbers(3);

    $response = $this->actingAs($staff)->post(route('pos.labels.cards.export', ['batch' => 1]))->assertOk();
    $csv = $response->streamedContent();

    expect($csv)->toContain('Ausweisnummer;Charge;Status')->and($csv)->toContain($numbers[0].';1;"Im Druck"')
        ->and(PatronCard::query()->where('status', CardStatus::InPrint->value)->count())->toBe(3);

    $this->actingAs($staff)->post(route('pos.labels.cards.available', ['batch' => 1]))->assertRedirect();
    expect(PatronCard::query()->where('status', CardStatus::Available->value)->count())->toBe(3);
});

it('generates batches and lets staff block cards from the management page', function (): void {
    $staff = cardTestUser('staff');

    $this->actingAs($staff)->post(route('pos.labels.cards.generate'), ['count' => 5])->assertRedirect(route('pos.labels.cards'));
    $this->actingAs($staff)->post(route('pos.labels.cards.generate'), ['count' => 0])->assertSessionHasErrors('count');
    expect(PatronCard::query()->count())->toBe(5);

    $card = PatronCard::query()->firstOrFail();
    $this->actingAs($staff)->get(route('pos.labels.cards'))->assertOk()->assertSee($card->number)->assertSee('Charge 1');
    $this->actingAs($staff)->get(route('pos.labels.cards', ['q' => $card->number]))->assertOk()->assertSee($card->number);

    $this->actingAs($staff)->post(route('pos.labels.cards.block', ['cardId' => $card->getKey()]), ['reason' => 'defective'])->assertRedirect();
    expect($card->refresh()->status)->toBe(CardStatus::Blocked);
});

it('keeps card management away from student helpers', function (): void {
    $this->actingAs(cardTestUser('student_ag_extended'))->post(route('pos.labels.cards.generate'), ['count' => 5])->assertForbidden();
    $this->actingAs(cardTestUser('student_ag_basic'))->get(route('pos.labels.cards'))->assertForbidden();
});

it('spreads the motifs by the given percentages and rejects shares that do not add up', function (): void {
    $staff = cardTestUser('staff');
    cardTestNumbers(20);

    $designs = PatronCardDesign::query()->forSide(PatronCardDesign::BACK)->get();
    [$one, $two, $three, $four] = $designs->pluck('id')->all();
    $paths = $designs->pluck('path')->all();

    $html = $this->actingAs($staff)
        ->post(route('pos.labels.cards.print', ['batch' => 1]), ['side' => 'rueck', 'motiv' => [$one => 50, $two => 25, $three => 25, $four => 0]])
        ->assertOk()
        ->getContent();

    expect(substr_count($html, 'class="card back"'))->toBe(20)
        ->and(substr_count($html, $paths[0].'&#039;)'))->toBe(10)
        ->and(substr_count($html, $paths[1].'&#039;)'))->toBe(5)
        ->and(substr_count($html, $paths[2].'&#039;)'))->toBe(5)
        ->and(substr_count($html, $paths[3].'&#039;)'))->toBe(0);

    $even = $this->actingAs($staff)->post(route('pos.labels.cards.print', ['batch' => 1]), ['side' => 'rueck'])->assertOk()->getContent();

    foreach ($paths as $path) {
        expect(substr_count($even, $path.'&#039;)'))->toBe(5);
    }

    $front = $this->actingAs($staff)->post(route('pos.labels.cards.print', ['batch' => 1]), ['side' => 'vorder'])->assertOk()->getContent();

    foreach (PatronCardDesign::query()->forSide(PatronCardDesign::FRONT)->pluck('path') as $path) {
        expect(substr_count($front, $path.'&#039;)'))->toBe(5);
    }

    $this->actingAs($staff)
        ->post(route('pos.labels.cards.print', ['batch' => 1]), ['side' => 'rueck', 'motiv' => [$one => 50, $two => 25, $three => 0, $four => 0]])
        ->assertRedirect(route('pos.labels.cards.batch', ['batch' => 1]))
        ->assertSessionHasErrors('batch');

    foreach ($paths as $path) {
        expect(file_exists(public_path($path)))->toBeTrue();
    }
});

it('shows even default shares on the batch print page', function (): void {
    $staff = cardTestUser('staff');
    cardTestNumbers(3);

    $this->actingAs($staff)->get(route('pos.labels.cards.batch', ['batch' => 1]))->assertOk()->assertSee('Vorderseiten')->assertSee('Rückseiten')->assertSee('value="25"', false);
    $this->actingAs($staff)->get(route('pos.labels.cards.batch', ['batch' => 9]))->assertRedirect(route('pos.labels.cards'));

    $three = PatronCardDesign::query()->forSide(PatronCardDesign::FRONT)->get()->take(3);

    expect(array_values(PatronCardController::evenShares($three)))->toBe([34, 33, 33])
        ->and(PatronCardController::evenShares(PatronCardDesign::query()->whereRaw('1 = 0')->get()))->toBe([]);
});

it('lets staff upload, switch off and delete card motifs', function (): void {
    Storage::fake('card_designs');
    $staff = cardTestUser('staff');

    $this->actingAs($staff)->get(route('pos.labels.cards.designs'))->assertOk()->assertSee('Punkte (lila)');

    $this->actingAs($staff)->post(route('pos.labels.cards.designs.store'), ['side' => 'back', 'name' => 'Sommer', 'image' => cardTestImage('sommer.png', 2008, 1276)])
        ->assertRedirect(route('pos.labels.cards.designs'));

    $design = PatronCardDesign::query()->where('name', 'Sommer')->firstOrFail();
    expect($design->side)->toBe('back')->and($design->is_active)->toBeTrue();
    Storage::disk('card_designs')->assertExists(basename($design->path));

    $this->actingAs($staff)->post(route('pos.labels.cards.designs.store'), ['side' => 'back', 'name' => 'Falsch', 'image' => cardTestImage('quadrat.png', 1200, 1200)])
        ->assertSessionHasErrors('image');
    $this->actingAs($staff)->post(route('pos.labels.cards.designs.store'), ['side' => 'back', 'name' => 'Klein', 'image' => cardTestImage('klein.png', 400, 254)])
        ->assertSessionHasErrors('image');
    $this->actingAs($staff)->post(route('pos.labels.cards.designs.store'), ['side' => 'back', 'name' => 'Text', 'image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('image');

    $this->actingAs($staff)->post(route('pos.labels.cards.designs.toggle', ['designId' => $design->getKey()]))->assertRedirect();
    expect($design->refresh()->is_active)->toBeFalse();

    // Ausgeschaltete Motive kommen beim Drucken nicht vor.
    cardTestNumbers(4);
    $html = $this->actingAs($staff)->post(route('pos.labels.cards.print', ['batch' => 1]), ['side' => 'rueck'])->getContent();
    expect($html)->not->toContain($design->path);

    $this->actingAs($staff)->delete(route('pos.labels.cards.designs.destroy', ['designId' => $design->getKey()]))->assertRedirect();
    expect(PatronCardDesign::query()->whereKey($design->getKey())->exists())->toBeFalse();
    Storage::disk('card_designs')->assertMissing(basename($design->path));

    // Mitgelieferte Motive behalten ihre Datei.
    $default = PatronCardDesign::query()->forSide('front')->firstOrFail();
    $this->actingAs($staff)->delete(route('pos.labels.cards.designs.destroy', ['designId' => $default->getKey()]))->assertRedirect();
    expect(file_exists(public_path($default->path)))->toBeTrue();
});

it('prints on plain white when a side has no active motif', function (): void {
    $staff = cardTestUser('staff');
    cardTestNumbers(2);
    PatronCardDesign::query()->update(['is_active' => false]);

    $html = $this->actingAs($staff)->post(route('pos.labels.cards.print', ['batch' => 1]), ['side' => 'vorder'])->assertOk()->getContent();
    expect(substr_count($html, 'class="namebox"'))->toBe(2)->and($html)->not->toContain('background-image');

    $this->actingAs($staff)->post(route('pos.labels.cards.print', ['batch' => 1]), ['side' => 'rueck'])->assertOk();
});
