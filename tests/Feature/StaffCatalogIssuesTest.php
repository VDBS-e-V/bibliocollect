<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function issueUser(string $role, ?Patron $patron = null): User
{
    $user = User::factory()->create(['email_verified_at' => now(), 'patron_id' => $patron?->getKey()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function issuePatron(string $number): Patron
{
    return Patron::query()->create(['library_number' => $number, 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Ida', 'last_name' => 'Issue'.$number, 'birth_date' => '2010-01-01']);
}

/** @return array{0: Title, 1: list<Copy>} */
function issueTitle(string $name, int $copies = 1, ?string $shelf = null): array
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book', 'publisher_name' => 'Testverlag', 'publication_year' => 2020]);
    $list = [];

    for ($i = 1; $i <= $copies; $i++) {
        $list[] = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => '07'.str_pad((string) (crc32($name) % 90000 + $i), 5, '0', STR_PAD_LEFT), 'status' => CopyStatus::Active, 'shelf_location' => $shelf]);
    }

    return [$title, $list];
}

beforeEach(function (): void {
    foreach (range(1, 7) as $day) {
        LibraryOpeningHour::query()->create(['day_of_week' => $day, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00']);
    }
});

it('loads titles in the internal catalog right away, without any search input', function (): void {
    issueTitle('Direkt geladen');
    issueTitle('Zweites Buch');

    $this->actingAs(issueUser('staff'))->get(route('pos.catalog.index'))
        ->assertOk()
        ->assertSee('Direkt geladen')
        ->assertSee('Zweites Buch')
        ->assertSee('Titel im Katalog')
        ->assertDontSee('Gezielte Recherche');

    // Mit Suchbegriff wird gefiltert.
    $this->get(route('pos.catalog.index', ['q' => 'Zweites']))->assertSee('Zweites Buch')->assertDontSee('Direkt geladen')->assertSee('gefunden');
});

it('lists the copies of a title directly in the results and on the title page', function (): void {
    $staff = issueUser('staff');
    [$title, [$loaned, $free]] = issueTitle('Mit Exemplaren', 2, 'R7-B2');
    $holder = issuePatron('510001');
    app(CheckoutCopyAction::class)->execute($holder, $loaned->barcode, $staff);
    $free->forceFill(['access_status' => 'nur_nachfrage'])->save();

    $list = $this->actingAs($staff)->get(route('pos.catalog.index'))->assertOk();
    $list->assertSee('2 Exemplare, 1 da')->assertSee($loaned->barcode)->assertSee($free->barcode)->assertSee('ausgeliehen, fällig')->assertSee('R7-B2');

    $page = $this->get(route('pos.catalog.titles.show', ['titleId' => $title->getKey()]))->assertOk();
    $page->assertSee('id="exemplare"', false)->assertSee($loaned->barcode)->assertSee('Nur auf Nachfrage (verschlossen)')->assertSee('Testverlag, 2020');

    // Der Exemplarabschnitt steht vor den Titeldaten.
    $html = $page->getContent();
    expect(strpos($html, 'catalog-copies-heading'))->toBeLessThan(strpos($html, 'catalog-title-data-heading'));
});

it('makes the whole selection cell of the quality review clickable', function (): void {
    $css = (string) file_get_contents(resource_path('css/patterns/catalog-quality.css'));

    preg_match('/\.bc-quality-check \{(.*?)\}/s', $css, $label);
    preg_match('/\.bc-quality-check-cell \{(.*?)\}/s', $css, $cell);

    expect($label[1] ?? '')->toContain('position: absolute')->toContain('inset: 0')
        ->and($cell[1] ?? '')->toContain('position: relative')->toContain('padding: 0');

    $view = (string) file_get_contents(resource_path('views/pages/surfaces/pos/catalog/quality/show.blade.php'));
    expect(substr_count($view, 'class="bc-quality-check-cell"'))->toBe(2);
});

it('explains on the public title page why there is no reserve button', function (): void {
    $staff = issueUser('staff');
    config(['circulation.max_reservations_per_copy' => 1]);

    // Verfügbar: Hinweis zum Ausleihen
    [$free] = issueTitle('Frei verfügbar');
    $reader = issuePatron('510002');
    $this->actingAs(issueUser('student', $reader))->get(route('public.catalog.show', ['titleId' => $free->getKey()]))->assertOk()->assertSee('Ein Exemplar ist da')->assertDontSee('Titel vormerken');

    // Ausgeliehen: Knopf
    [$taken, [$copy]] = issueTitle('Ausgeliehenes Buch');
    app(CheckoutCopyAction::class)->execute(issuePatron('510003'), $copy->barcode, $staff);
    $this->get(route('public.catalog.show', ['titleId' => $taken->getKey()]))->assertSee('Titel vormerken');

    // Warteschlange voll
    app(PlaceReservationAction::class)->execute(issuePatron('510004'), $copy->barcode, $staff);
    $this->get(route('public.catalog.show', ['titleId' => $taken->getKey()]))->assertDontSee('Titel vormerken')->assertSee('so viele Vormerkungen wie Exemplare');

    // Vormerken ausgeschaltet
    config(['circulation.max_open_reservations' => 0]);
    $this->get(route('public.catalog.show', ['titleId' => $taken->getKey()]))->assertSee('Vormerken ist zurzeit nicht möglich');

    // Schon vorgemerkt
    config(['circulation.max_open_reservations' => 5]);
    $mine = issuePatron('510005');
    config(['circulation.max_reservations_per_copy' => 5]);
    app(PlaceReservationAction::class)->execute($mine, $copy->barcode, $staff);
    $this->actingAs(issueUser('student', $mine))->get(route('public.catalog.show', ['titleId' => $taken->getKey()]))->assertSee('Vorgemerkt');
});
