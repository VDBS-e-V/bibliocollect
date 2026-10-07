<?php

declare(strict_types=1);

use App\Models\User;
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

    $this->actingAs($user)->post(route('portal.wishes.store'), ['title' => 'Der kleine Hobbit', 'author' => 'Tolkien', 'isbn' => '978-3-423-08000-5', 'note' => 'Habe den Film gesehen'])
        ->assertRedirect(route('portal.wishes.index'))->assertSessionHas('portal_success');

    $wish = BookWish::query()->firstOrFail();
    expect($wish->patron_id)->toBe((string) $patron->getKey())->and($wish->status)->toBe(WishStatus::New)->and($wish->isbn)->toBe('9783423080005');

    $this->actingAs($user)->get(route('portal.wishes.index'))->assertSee('Der kleine Hobbit')->assertSee('Neu');

    $this->actingAs($user)->post(route('portal.wishes.withdraw', ['wishId' => $wish->getKey()]))->assertSessionHas('portal_success');
    expect($wish->refresh()->status)->toBe(WishStatus::Withdrawn);
    $this->actingAs($user)->post(route('portal.wishes.withdraw', ['wishId' => $wish->getKey()]))->assertSessionHas('portal_error');
});

it('limits open wishes, refuses duplicates and bad isbns', function (): void {
    $user = wishUser('student', wishPatron('W-2'));

    foreach (['Eins', 'Zwei', 'Drei'] as $title) {
        $this->actingAs($user)->post(route('portal.wishes.store'), ['title' => $title])->assertSessionHas('portal_success');
    }

    $this->actingAs($user)->post(route('portal.wishes.store'), ['title' => 'Vier'])->assertSessionHas('portal_error');
    expect(BookWish::query()->count())->toBe(3);

    BookWish::query()->where('title', 'Drei')->update(['status' => WishStatus::Declined->value]);
    $this->actingAs($user)->post(route('portal.wishes.store'), ['title' => 'eins'])->assertSessionHas('portal_error');
    $this->actingAs($user)->post(route('portal.wishes.store'), ['title' => 'Fünf', 'isbn' => '123'])->assertSessionHas('portal_error');
    $this->actingAs($user)->post(route('portal.wishes.store'), ['title' => ''])->assertSessionHasErrors('title');
});

it('keeps wishes private and works only with a linked account', function (): void {
    $mine = wishUser('student', wishPatron('W-3'));
    $other = wishUser('student', wishPatron('W-4'));
    $unlinked = wishUser('student');

    $this->actingAs($mine)->post(route('portal.wishes.store'), ['title' => 'Geheimwunsch']);
    $wish = BookWish::query()->firstOrFail();
    $this->flushSession();

    $this->actingAs($other)->get(route('portal.wishes.index'))->assertOk()->assertDontSee('Geheimwunsch');
    $this->actingAs($other)->post(route('portal.wishes.withdraw', ['wishId' => $wish->getKey()]))->assertNotFound();
    $this->actingAs($unlinked)->get(route('portal.wishes.index'))->assertOk()->assertSee('noch nicht mit einem Ausleihkonto verknüpft');
    $this->actingAs($unlinked)->post(route('portal.wishes.store'), ['title' => 'Ohne Konto'])->assertSessionHas('portal_error');
    expect(BookWish::query()->count())->toBe(1);

});

it('lets staff review wishes, spot repeated wishes and answer by mail', function (): void {
    Mail::fake();
    $staff = wishUser('staff');
    $anna = wishPatron('W-5', 'anna@example.invalid');
    $ben = wishPatron('W-6');

    foreach ([$anna, $ben] as $patron) {
        $this->actingAs(wishUser('student', $patron))->post(route('portal.wishes.store'), ['title' => 'Drachenreiter', 'isbn' => '9783791504650']);
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
    $this->actingAs($staff)->get(route('pos.wishes.index'))->assertSee('ohne Person erfasst')->assertSee('Tresenwunsch');
});

it('restricts wish handling to staff and management', function (): void {
    $this->actingAs(wishUser('student_ag_extended'))->get(route('pos.wishes.index'))->assertForbidden();
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
    $all->assertSeeInOrder(['id="area-betrieb-heading"', 'Ausleihe und Rückgabe', 'Rückgabe ohne Person', 'Ausleihkonto suchen', 'id="area-verwaltung-heading"', 'Ausleihkonten importieren', 'Ausweise erzeugen und drucken', 'Statistik'], false);
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
