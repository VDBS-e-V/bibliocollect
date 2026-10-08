<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Contracts\BibliographicLookupProvider;
use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Mail\WishStatusMail;
use App\Modules\Circulation\Models\BookWish;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Privacy\Services\AnonymizationService;
use App\Modules\Privacy\Services\PatronDataExport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function wishPatron(string $number, ?string $email = null): Patron
{
    return Patron::query()->create([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Wilma',
        'last_name' => 'Wunsch'.$number,
        'birth_date' => '2011-01-01',
        'email' => $email,
    ]);
}

function wishUser(string $role, ?Patron $patron = null): User
{
    $user = User::factory()->create(['email_verified_at' => now(), 'patron_id' => $patron?->getKey()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

it('lets a reader wish for a book, shows the status and lets them withdraw', function (): void {
    $patron = wishPatron('W-1');
    $user = wishUser('student', $patron);

    $this->actingAs($user)->get(route('portal.wishes.index'))->assertOk()->assertSee('Neuer Wunsch');

    $this->actingAs($user)->post(route('public.wishes.store'), ['title' => 'Der kleine Hobbit', 'author' => 'Tolkien', 'isbn' => '978-3-423-08000-5', 'note' => 'Habe den Film gesehen'])
        ->assertRedirect(route('portal.wishes.index'))->assertSessionHas('portal_success');

    $wish = BookWish::query()->firstOrFail();
    expect($wish->patron_id)->toBe((string) $patron->getKey())->and($wish->status)->toBe(WishStatus::New)->and($wish->isbn)->toBe('9783423080005');

    $this->actingAs($user)->get(route('portal.wishes.index'))->assertSee('Der kleine Hobbit')->assertSee('Neu');

    $this->actingAs($user)->post(route('portal.wishes.withdraw', ['wishId' => $wish->getKey()]))->assertSessionHas('portal_success');
    expect($wish->refresh()->status)->toBe(WishStatus::Withdrawn);
    $this->actingAs($user)->post(route('portal.wishes.withdraw', ['wishId' => $wish->getKey()]))->assertSessionHas('portal_error');
});

it('limits open wishes, refuses duplicates and bad isbns', function (): void {
    $this->withoutMiddleware(ThrottleRequests::class);

    $user = wishUser('student', wishPatron('W-2'));

    foreach (['Eins', 'Zwei', 'Drei'] as $title) {
        $this->actingAs($user)->post(route('public.wishes.store'), ['title' => $title])->assertSessionHas('portal_success');
    }

    $this->actingAs($user)->post(route('public.wishes.store'), ['title' => 'Vier'])->assertSessionHasErrors('title');
    expect(BookWish::query()->count())->toBe(3);

    BookWish::query()->where('title', 'Drei')->update(['status' => WishStatus::Declined->value]);
    $this->actingAs($user)->post(route('public.wishes.store'), ['title' => 'eins'])->assertSessionHasErrors('title');
    $this->actingAs($user)->post(route('public.wishes.store'), ['title' => 'Fünf', 'isbn' => '123'])->assertSessionHasErrors('title');
    $this->actingAs($user)->post(route('public.wishes.store'), ['title' => ''])->assertSessionHasErrors('title');
});

it('keeps wishes private and works only with a linked account', function (): void {
    $mine = wishUser('student', wishPatron('W-3'));
    $other = wishUser('student', wishPatron('W-4'));
    $unlinked = wishUser('student');

    $this->actingAs($mine)->post(route('public.wishes.store'), ['title' => 'Geheimwunsch']);
    $wish = BookWish::query()->firstOrFail();
    $this->flushSession();

    $this->actingAs($other)->get(route('portal.wishes.index'))->assertOk()->assertDontSee('Geheimwunsch');
    $this->actingAs($other)->post(route('portal.wishes.withdraw', ['wishId' => $wish->getKey()]))->assertNotFound();
    $this->actingAs($unlinked)->get(route('portal.wishes.index'))->assertOk()->assertSee('noch nicht mit einem Ausleihkonto verknüpft');
    $this->actingAs($unlinked)->post(route('public.wishes.store'), ['title' => 'Ohne Konto'])->assertSessionHas('wish_success');
    expect(BookWish::query()->count())->toBe(2)->and(BookWish::query()->where('title', 'Ohne Konto')->firstOrFail()->patron_id)->toBeNull();

});

it('lets staff review wishes, spot repeated wishes and answer by mail', function (): void {
    Mail::fake();
    $staff = wishUser('staff');
    $anna = wishPatron('W-5', 'anna@example.invalid');
    $ben = wishPatron('W-6');

    foreach ([$anna, $ben] as $patron) {
        $this->actingAs(wishUser('student', $patron))->post(route('public.wishes.store'), ['title' => 'Drachenreiter', 'isbn' => '9783791504650']);
    }

    $page = $this->actingAs($staff)->get(route('pos.wishes.index'))->assertOk();
    $page->assertSee('Drachenreiter')->assertSee('1 weiterer Wunsch');

    $annasWish = BookWish::query()->where('patron_id', $anna->getKey())->firstOrFail();

    $this->actingAs($staff)->patch(route('pos.wishes.update', ['wishId' => $annasWish->getKey()]), ['status' => 'ordered', 'answer' => 'Kommt nächste Woche'])->assertRedirect();
    expect($annasWish->refresh()->status)->toBe(WishStatus::Ordered)->and($annasWish->answer)->toBe('Kommt nächste Woche')->and($annasWish->decided_by_user_id)->toBe($staff->id);

    Mail::assertSent(WishStatusMail::class, static fn (WishStatusMail $mail): bool => $mail->hasTo('anna@example.invalid') && $mail->wish->is($annasWish));

    // Gleicher Stand noch einmal: keine zweite Mail.
    $this->actingAs($staff)->patch(route('pos.wishes.update', ['wishId' => $annasWish->getKey()]), ['status' => 'ordered', 'answer' => 'Kommt nächste Woche']);
    Mail::assertSent(WishStatusMail::class, 1);

    $this->actingAs($staff)->get(route('pos.wishes.index', ['status' => 'ordered']))->assertSee('Drachenreiter');
    $this->actingAs($staff)->get(route('pos.wishes.index', ['status' => 'declined']))->assertDontSee('Drachenreiter');
    $this->actingAs($staff)->get(route('pos.wishes.index', ['q' => '9783791504650']))->assertSee('Drachenreiter');

    // Die Person sieht den Stand und die Antwort in ihrem Konto.
    $this->actingAs(User::query()->where('patron_id', $anna->getKey())->firstOrFail())->get(route('portal.wishes.index'))->assertSee('Bestellt')->assertSee('Kommt nächste Woche');

    $this->actingAs($staff)->patch(route('pos.wishes.update', ['wishId' => $annasWish->getKey()]), ['status' => 'withdrawn'])->assertSessionHasErrors('status');
});

it('records wishes at the counter with or without a person', function (): void {
    $staff = wishUser('staff');
    wishPatron('W-7');

    $this->actingAs($staff)->post(route('pos.wishes.store'), ['title' => 'Tresenwunsch', 'library_number' => 'W-7'])->assertRedirect(route('pos.wishes.index'));
    $this->actingAs($staff)->post(route('pos.wishes.store'), ['title' => 'Anonymer Wunsch'])->assertRedirect(route('pos.wishes.index'));
    $this->actingAs($staff)->post(route('pos.wishes.store'), ['title' => 'Falsche Nummer', 'library_number' => 'X-0'])->assertSessionHasErrors('library_number');

    expect(BookWish::query()->count())->toBe(2)->and(BookWish::query()->whereNull('patron_id')->count())->toBe(1);
    $this->actingAs($staff)->get(route('pos.wishes.index'))->assertSee('ohne Person erfasst')->assertSee('Tresenwunsch')->assertSee('Buchwunsch erfassen')->assertSee(route('pos.wishes.create'), false);
    $this->actingAs($staff)->get(route('pos.wishes.create'))->assertOk()->assertSee('data-isbn-lookup', false)->assertSee(route('public.wishes.lookup'), false);
});

it('records a wish on its own page and picks the person through a name search', function (): void {
    $staff = wishUser('staff');
    $patron = wishPatron('W-20');
    $patron->forceFill(['first_name' => 'Zora', 'last_name' => 'Suchtreffer'])->save();
    $other = wishPatron('W-21');
    $other->forceFill(['first_name' => 'Zora', 'last_name' => 'Anders'])->save();

    // Namenssuche: Treffer als Auswahlliste, die Eingaben bleiben erhalten.
    $page = $this->actingAs($staff)->get(route('pos.wishes.create', ['person' => 'Suchtreffer', 'title' => 'Mein Wunschbuch', 'isbn' => '9783791504650']))->assertOk();
    $page->assertSee('Suchtreffer, Zora')->assertDontSee('Anders, Zora')->assertSee('Mein Wunschbuch')->assertSee('9783791504650')->assertSee('name="patron_id"', false);

    $this->get(route('pos.wishes.create', ['person' => 'xyzxyz']))->assertSee('Keine aktive Person gefunden');
    $this->get(route('pos.wishes.create', ['person' => 'Zora']))->assertSee('Suchtreffer, Zora')->assertSee('Anders, Zora');

    // Speichern mit der gewählten Person: Der Wunsch gehört ihr.
    $this->post(route('pos.wishes.store'), ['title' => 'Mein Wunschbuch', 'patron_id' => (string) $patron->getKey()])->assertRedirect(route('pos.wishes.index'));
    expect(BookWish::query()->where('title', 'Mein Wunschbuch')->value('patron_id'))->toBe((string) $patron->getKey());

    // Eine ungültige Person wird abgelehnt, ohne Person geht es weiterhin.
    $this->post(route('pos.wishes.store'), ['title' => 'Falsche Person', 'patron_id' => 'gibt-es-nicht'])->assertSessionHasErrors('person');
    $this->post(route('pos.wishes.store'), ['title' => 'Ohne Person', 'patron_id' => ''])->assertRedirect(route('pos.wishes.index'));
});

it('restricts wish handling to staff and management', function (): void {
    $this->actingAs(wishUser('student_ag_basic'))->get(route('pos.wishes.index'))->assertForbidden();
    $this->actingAs(wishUser('student_ag_extended'))->get(route('pos.wishes.index'))->assertOk();
    $this->actingAs(wishUser('student_ag_basic'))->get(route('pos.wishes.index'))->assertForbidden();
    $this->actingAs(wishUser('management'))->get(route('pos.wishes.index'))->assertOk();
});

it('anonymizes closed wishes after the retention period and includes them in the data export', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:00:00', 'Europe/Berlin'));
    $patron = wishPatron('W-8');
    $old = BookWish::query()->create(['patron_id' => $patron->getKey(), 'title' => 'Alt', 'status' => WishStatus::Fulfilled]);
    $fresh = BookWish::query()->create(['patron_id' => $patron->getKey(), 'title' => 'Frisch', 'status' => WishStatus::Declined]);
    $open = BookWish::query()->create(['patron_id' => $patron->getKey(), 'title' => 'Offen', 'status' => WishStatus::New]);
    BookWish::query()->whereKey([$old->getKey(), $open->getKey()])->update(['updated_at' => '2020-01-01 10:00:00']);

    expect(app(PatronDataExport::class)->export($patron)['buchwuensche'])->toHaveCount(3);

    $counts = app(AnonymizationService::class)->run();

    expect($counts['wishes'])->toBe(1)
        ->and($old->refresh()->patron_id)->toBeNull()
        ->and($fresh->refresh()->patron_id)->toBe((string) $patron->getKey())
        ->and($open->refresh()->patron_id)->toBe((string) $patron->getKey());

    CarbonImmutable::setTestNow();
});

it('shows a process oriented menu and a page with all processes', function (): void {
    $staff = wishUser('staff');

    $home = $this->actingAs($staff)->get(route('pos.home'))->assertOk();
    $home->assertSee('Ausleihe und Rückgabe')->assertSee('Ausweise ausgeben')->assertSee('Buchwünsche')->assertSee('Medium erfassen')->assertSee('Alle Vorgänge');

    $all = $this->actingAs($staff)->get(route('pos.processes'))->assertOk();
    $all->assertSeeInOrder(['id="area-betrieb-heading"', 'Ausleihe und Rückgabe', 'Rückgabe ohne Person', 'Ausleihkonto suchen', 'id="area-verwaltung-heading"', 'Klassendaten importieren', 'Ausweise erzeugen und drucken', 'Statistik'], false);
    $all->assertSee('Rückgabe ohne Person')->assertSee('Ausweise klassenweise ausgeben')->assertSee('Statistik')->assertSee('Metadaten prüfen')->assertDontSee('Schuljahreswechsel');

    $this->actingAs(wishUser('management'))->get(route('pos.processes'))->assertSee('Schuljahreswechsel')->assertSee('Informationsseiten');

    $basic = $this->actingAs(wishUser('student_ag_basic'))->get(route('pos.processes'))->assertOk();
    $basic->assertDontSee('area-verwaltung-heading', false);
    $basic->assertSee('Hilfe')->assertDontSee('Metadaten prüfen')->assertDontSee('Ausweise erzeugen')->assertDontSee('Buchwünsche bearbeiten');
});

it('points readers to fulfilled wishes on their overview', function (): void {
    $patron = wishPatron('W-9');
    $user = wishUser('student', $patron);
    BookWish::query()->create(['patron_id' => $patron->getKey(), 'title' => 'Endlich da', 'status' => WishStatus::Fulfilled, 'decided_at' => now()->subDays(2)]);
    BookWish::query()->create(['patron_id' => $patron->getKey(), 'title' => 'Schon lange her', 'status' => WishStatus::Fulfilled, 'decided_at' => now()->subDays(60)]);
    BookWish::query()->create(['patron_id' => $patron->getKey(), 'title' => 'Noch offen', 'status' => WishStatus::Ordered, 'decided_at' => now()]);

    $this->actingAs($user)->get(route('portal.home'))->assertOk()->assertSee('Dein Buchwunsch ist da')->assertSee('Endlich da')->assertDontSee('Schon lange her')->assertDontSee('Noch offen');
});

it('lets anybody wish for a book without logging in', function (): void {
    Mail::fake();

    $this->get(route('public.wishes.create'))->assertOk()->assertSee('Buchwunsch erfassen')->assertSee('ISBN')->assertSee('Name (optional)')->assertSee('E-Mail (optional)')->assertSee('Buchwunsch speichern');
    $this->get(route('public.home'))->assertSee('Buchwunsch');

    $this->post(route('public.wishes.store'), ['title' => 'Das doppelte Lottchen', 'author' => 'Kästner', 'isbn' => '9783855350261', 'note' => 'Bitte auch als Hörbuch', 'contact_name' => 'Gast', 'contact_email' => 'gast@example.invalid'])
        ->assertRedirect(route('public.wishes.create'))->assertSessionHas('wish_success');

    $wish = BookWish::query()->firstOrFail();
    expect($wish->patron_id)->toBeNull()->and($wish->contact_name)->toBe('Gast')->and($wish->contact_email)->toBe('gast@example.invalid')->and($wish->status)->toBe(WishStatus::New);

    // Dieselbe Person darf denselben Wunsch nicht noch einmal schicken.
    $this->post(route('public.wishes.store'), ['title' => 'Das doppelte Lottchen', 'contact_email' => 'gast@example.invalid'])->assertSessionHasErrors('title');

    // Ohne Angaben zur Person geht es auch, nur der Titel ist Pflicht.
    $this->post(route('public.wishes.store'), ['title' => 'Anonym gewünscht'])->assertSessionHas('wish_success');
    $this->post(route('public.wishes.store'), ['title' => ''])->assertSessionHasErrors('title');
    $this->post(route('public.wishes.store'), ['title' => 'Falsche Mail', 'contact_email' => 'keine-mail'])->assertSessionHasErrors('contact_email');
    expect(BookWish::query()->count())->toBe(2);

    // Die Antwort der Bibliothek geht an die angegebene Adresse.
    $staff = wishUser('staff');
    $this->actingAs($staff)->patch(route('pos.wishes.update', ['wishId' => $wish->getKey()]), ['status' => 'ordered', 'answer' => 'Bestellt'])->assertRedirect();
    Mail::assertSent(WishStatusMail::class, static fn (WishStatusMail $mail): bool => $mail->hasTo('gast@example.invalid'));
    $this->actingAs($staff)->get(route('pos.wishes.index'))->assertSee('Gast &lt;gast@example.invalid&gt;', false);
});

it('ignores bots that fill the hidden field and attaches wishes of logged in readers to their account', function (): void {
    $this->post(route('public.wishes.store'), ['title' => 'Spam', 'website' => 'http://spam.invalid'])->assertSessionHas('wish_success');
    expect(BookWish::query()->count())->toBe(0);

    $patron = wishPatron('W-10');
    $user = wishUser('student', $patron);
    $this->actingAs($user)->get(route('public.wishes.create'))->assertOk()->assertSee('Du bist angemeldet')->assertDontSee('E-Mail (optional)');
    $this->actingAs($user)->post(route('public.wishes.store'), ['title' => 'Mit Konto', 'contact_email' => 'ignoriert@example.invalid'])->assertRedirect(route('portal.wishes.index'));

    $wish = BookWish::query()->firstOrFail();
    expect($wish->patron_id)->toBe((string) $patron->getKey())->and($wish->contact_email)->toBeNull();
});

it('throttles the public form per address', function (): void {
    foreach (range(1, 5) as $number) {
        $this->post(route('public.wishes.store'), ['title' => 'Wunsch '.$number])->assertSessionHas('wish_success');
    }

    $this->post(route('public.wishes.store'), ['title' => 'Wunsch 6'])->assertStatus(429);
});

it('looks up an isbn and suggests title and author without saving anything', function (): void {
    $record = new BibliographicRecord('dnb', '1', null, 'Momo', 'oder die seltsame Geschichte', null, [['name' => 'Ende, Michael', 'role' => 'author', 'gnd_id' => null]], '9783522202602', null, null, 1973, null, null, 'ger', null, 'book', null, null, null, null);

    app()->bind(BibliographicLookupProvider::class, static fn () => new class($record) implements BibliographicLookupProvider
    {
        public function __construct(private readonly BibliographicRecord $record) {}

        public function findByIsbn(string $isbn): array
        {
            return $isbn === '9783522202602' ? [$this->record] : [];
        }

        public function search(?string $title, ?string $person): array
        {
            return [];
        }

        public function findByRecordId(string $recordId): array
        {
            return [];
        }
    });

    $this->getJson(route('public.wishes.lookup', ['isbn' => '978-3-522-20260-2']))->assertOk()->assertJson(['found' => true, 'title' => 'Momo: oder die seltsame Geschichte', 'author' => 'Ende, Michael']);
    $this->getJson(route('public.wishes.lookup', ['isbn' => '9783000000003']))->assertOk()->assertJson(['found' => false]);
    $this->getJson(route('public.wishes.lookup', ['isbn' => '12']))->assertOk()->assertJson(['found' => false, 'message' => 'Die ISBN muss 10 oder 13 Stellen haben.']);
    expect(BookWish::query()->count())->toBe(0);
});

it('removes the contact data of closed public wishes after the retention period', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:00:00', 'Europe/Berlin'));
    $old = BookWish::query()->create(['title' => 'Alt', 'contact_name' => 'Gast', 'contact_email' => 'gast@example.invalid', 'status' => WishStatus::Declined]);
    BookWish::query()->whereKey($old->getKey())->update(['updated_at' => '2020-01-01 10:00:00']);

    expect(app(AnonymizationService::class)->run()['wishes'])->toBe(1)
        ->and($old->refresh()->contact_email)->toBeNull()->and($old->contact_name)->toBeNull();

    CarbonImmutable::setTestNow();
});

it('zeigt die Gesamtanzahl und bietet eine Druckliste im Querformat mit Briefpapier', function (): void {
    $staff = wishUser('staff');

    foreach (['Erster Wunsch', 'Zweiter Wunsch', 'Dritter Wunsch'] as $title) {
        BookWish::query()->create(['title' => $title, 'status' => WishStatus::New]);
    }

    BookWish::query()->create(['title' => 'Erledigter Wunsch', 'status' => WishStatus::Declined]);

    $this->actingAs($staff)->get(route('pos.wishes.index'))->assertOk()
        ->assertSee('Insgesamt')->assertSee('4')->assertSee('Liste als PDF');

    $print = $this->actingAs($staff)->get(route('pos.wishes.print'))->assertOk();
    $print->assertSee('A4 landscape', false)->assertSee('briefpapier-quer-farbe.png', false)
        ->assertSee('Erster Wunsch')->assertDontSee('Erledigter Wunsch')->assertSee('insgesamt erfasst: 4');

    $this->actingAs($staff)->get(route('pos.wishes.print', ['status' => 'alle', 'briefpapier' => 'sw']))
        ->assertSee('briefpapier-quer-sw.png', false)->assertSee('Erledigter Wunsch');
});
