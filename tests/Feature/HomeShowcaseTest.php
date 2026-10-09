<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Surfaces\Public\Support\HomeShowcase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

function showcaseUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function showcaseTitle(string $name, array $copy = [], ?string $topic = null, string $status = 'active'): Title
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book', 'local_classification' => $topic]);
    Copy::query()->create(array_merge(['edition_id' => $edition->getKey(), 'barcode' => md5($name), 'status' => $status], $copy));

    return $title;
}

beforeEach(fn () => Cache::forget(HomeShowcase::CACHE_KEY));

it('hides the showcase blocks while there is nothing to show', function (): void {
    $this->get(route('public.home'))->assertOk()->assertDontSee('Empfohlene Medien')->assertDontSee('Neue Medien')->assertDontSee('Empfohlene Themen');
});

it('shows manually recommended titles first and fills up with the most borrowed ones', function (): void {
    $manual = showcaseTitle('Handverlesen');
    $popular = showcaseTitle('Oft ausgeliehen');
    showcaseTitle('Nie ausgeliehen');
    $patron = Patron::query()->create(['library_number' => 'S-1', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Lese', 'last_name' => 'Ratte', 'birth_date' => '2012-01-01']);

    foreach ([1, 2] as $day) {
        Loan::query()->create(['patron_id' => $patron->getKey(), 'copy_id' => Copy::query()->where('barcode', md5('Oft ausgeliehen'))->value('id'), 'checked_out_at' => now()->subDays($day), 'due_on' => now()->addDays(10)->toDateString(), 'returned_at' => now()->subDays($day)]);
    }

    $staff = showcaseUser('staff');
    $this->actingAs($staff)->post(route('pos.catalog.titles.feature', ['titleId' => $manual->getKey()]))->assertRedirect();
    expect($manual->refresh()->featured_position)->toBe(1);

    $html = $this->get(route('public.home'))->assertOk()->assertSee('Empfohlene Medien')->getContent();

    expect(strpos($html, 'Handverlesen'))->toBeLessThan(strpos($html, 'Oft ausgeliehen'))
        ->and($html)->not->toContain('Nie ausgeliehen');

    // Zurücknehmen
    $this->actingAs($staff)->post(route('pos.catalog.titles.feature', ['titleId' => $manual->getKey()]))->assertRedirect();
    expect($manual->refresh()->featured_position)->toBeNull();
    $this->get(route('public.home'))->assertDontSee('Handverlesen');
    expect($popular->refresh()->featured_position)->toBeNull();
});

it('lists new media by the time they were put on the shelf and only with a present copy', function (): void {
    showcaseTitle('Gerade eingestellt', ['shelved_at' => now()->subDays(3), 'shelf_location' => 'I. A 1 a']);
    showcaseTitle('Schon lange da', ['shelved_at' => now()->subDays(200), 'shelf_location' => 'I. A 1 b']);
    showcaseTitle('Aussortiert neu', ['shelved_at' => now()->subDays(2)], null, 'withdrawn');

    $this->get(route('public.home'))->assertOk()->assertSee('Neue Medien')->assertSee('Gerade eingestellt')->assertDontSee('Schon lange da')->assertDontSee('Aussortiert neu');
});

it('recommends marked topics first and then the topics with most titles', function (): void {
    $small = CatalogTopic::query()->create(['name' => 'Kleines Thema']);
    CatalogTopic::query()->create(['name' => 'Großes Thema']);
    CatalogTopic::query()->create(['name' => 'Leeres Thema']);
    showcaseTitle('A1', [], 'Kleines Thema');
    showcaseTitle('B1', [], 'Großes Thema');
    showcaseTitle('B2', [], 'Großes Thema');

    $admin = showcaseUser('management');
    $html = $this->get(route('public.home'))->assertOk()->assertSee('Empfohlene Themen')->assertDontSee('Leeres Thema')->getContent();
    expect(strpos($html, 'Großes Thema'))->toBeLessThan(strpos($html, 'Kleines Thema'));

    $this->actingAs($admin)->get(route('administration.topics.index'))->assertOk()->assertSee('Auf der Startseite empfehlen');
    $this->actingAs($admin)->post(route('administration.topics.feature', ['topicId' => $small->getKey()]))->assertRedirect(route('administration.topics.index'));

    $html = $this->get(route('public.home'))->getContent();
    expect(strpos($html, 'Kleines Thema'))->toBeLessThan(strpos($html, 'Großes Thema'))
        ->and($html)->toContain('/thema/Gro%C3%9Fes-Thema');
});

it('lets only staff mark recommendations', function (): void {
    $title = showcaseTitle('Geschützt');

    $this->post(route('pos.catalog.titles.feature', ['titleId' => $title->getKey()]))->assertRedirect();
    $this->actingAs(showcaseUser('teacher'))->post(route('pos.catalog.titles.feature', ['titleId' => $title->getKey()]))->assertForbidden();
    expect($title->refresh()->featured_position)->toBeNull();
});
