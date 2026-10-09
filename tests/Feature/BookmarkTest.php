<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Models\Bookmark;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Privacy\Services\AnonymizationService;
use App\Modules\Privacy\Services\PatronDataExport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bookmarkUser(string $role = 'student_ag_basic'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function bookmarkTitle(string $name, string $status = 'active'): Title
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => md5($name), 'status' => $status]);

    return $title;
}

it('offers guests a login hint and members the bookmark button', function (): void {
    $title = bookmarkTitle('Merkbuch');

    $this->get(route('public.catalog.show', ['titleId' => $title->getKey()]))->assertOk()->assertSee('Zum Merken anmelden');
    $this->get(route('public.catalog.index'))->assertOk()->assertSee('Zum Merken anmelden');
    $this->post(route('portal.bookmarks.toggle', ['titleId' => $title->getKey()]))->assertRedirect(route('login'));

    $user = bookmarkUser();
    $this->actingAs($user)->get(route('public.catalog.show', ['titleId' => $title->getKey()]))->assertOk()->assertSee('Merken')->assertDontSee('Zum Merken anmelden');
});

it('bookmarks and removes a title and shows the list with availability', function (): void {
    $user = bookmarkUser();
    $title = bookmarkTitle('Gemerktes Buch');
    $gone = bookmarkTitle('Später ausgesondert');

    $this->actingAs($user)->from(route('public.catalog.show', ['titleId' => $title->getKey()]))->post(route('portal.bookmarks.toggle', ['titleId' => $title->getKey()]))
        ->assertRedirect(route('public.catalog.show', ['titleId' => $title->getKey()]))->assertSessionHas('bookmark_notice');
    $this->actingAs($user)->post(route('portal.bookmarks.toggle', ['titleId' => $gone->getKey()]));

    $this->actingAs($user)->get(route('public.catalog.show', ['titleId' => $title->getKey()]))->assertSee('Gemerkt');
    $this->actingAs($user)->get(route('portal.bookmarks'))->assertOk()->assertSee('Gemerktes Buch')->assertSee('Später ausgesondert')->assertSee('Verfügbar');

    // Wird ein Buch ausgesondert, verschwindet es aus der Liste, bleibt aber gemerkt.
    Copy::query()->where('barcode', md5('Später ausgesondert'))->update(['status' => 'withdrawn']);
    $this->actingAs($user)->get(route('portal.bookmarks'))->assertSee('Gemerktes Buch')->assertDontSee('Später ausgesondert')->assertSee('nicht im Bestand');
    expect(Bookmark::query()->where('user_id', $user->getKey())->count())->toBe(2);

    $this->actingAs($user)->post(route('portal.bookmarks.toggle', ['titleId' => $title->getKey()]))->assertSessionHas('bookmark_notice');
    expect(Bookmark::query()->where('user_id', $user->getKey())->where('title_id', $title->getKey())->exists())->toBeFalse();
});

it('keeps every list private and limits it to one hundred titles', function (): void {
    $mine = bookmarkUser();
    $other = bookmarkUser();
    $title = bookmarkTitle('Mein Geheimtipp');

    $this->flushSession();
    $this->actingAs($other)->get(route('portal.bookmarks'))->assertOk()->assertDontSee('Mein Geheimtipp');
    $this->actingAs($other)->get(route('portal.bookmarks'))->assertOk()->assertDontSee('Mein Geheimtipp');

    foreach (range(1, Bookmark::LIMIT) as $number) {
        Bookmark::query()->create(['user_id' => $other->getKey(), 'title_id' => bookmarkTitle('Füller '.$number)->getKey()]);
    }

    $extra = bookmarkTitle('Zu viel');
    $this->actingAs($other)->post(route('portal.bookmarks.toggle', ['titleId' => $extra->getKey()]))->assertSessionHas('bookmark_error');
    expect(Bookmark::query()->where('user_id', $other->getKey())->count())->toBe(Bookmark::LIMIT);
});

it('includes the list in the data export and deletes it when the account is anonymized', function (): void {
    $user = bookmarkUser();
    $patron = Patron::query()->create(['library_number' => '700001', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Mia', 'last_name' => 'Merk', 'birth_date' => '2012-05-05']);
    $user->forceFill(['patron_id' => $patron->getKey()])->save();
    $title = bookmarkTitle('Datenschutzbuch');
    $this->actingAs($user)->post(route('portal.bookmarks.toggle', ['titleId' => $title->getKey()]));

    $export = app(PatronDataExport::class)->export($patron);
    expect($export['merkliste'])->toHaveCount(1)->and($export['merkliste'][0]['titel'])->toBe('Datenschutzbuch');

    $patron->forceFill(['status' => PatronStatus::Departed, 'leaving_on' => now()->subDays(5)->toDateString()])->save();
    app(AnonymizationService::class)->anonymizePatron($patron->refresh());

    expect(Bookmark::query()->where('user_id', $user->getKey())->count())->toBe(0);
});
