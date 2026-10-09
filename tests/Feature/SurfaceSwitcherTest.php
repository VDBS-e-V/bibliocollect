<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function switcherUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** Linktexte der Bereichsleiste aus der Kopfzeile. */
function switcherAreas(string $html): array
{
    preg_match('/<div class="bc-utility-bar__actions">(.*?)<button class="bc-theme-toggle"/s', $html, $match);
    preg_match_all('/<a href="[^"]*"[^>]*>([^<]+)<\/a>/', $match[1] ?? '', $links);

    return array_map('trim', $links[1]);
}

/** Linktexte im Profilmenü der Kopfzeile (ohne die Abmeldung). */
function switcherMenu(string $html): array
{
    preg_match('/<ul class="bc-account-menu__list">(.*?)<\/ul>/s', $html, $match);
    preg_match_all('/<a href="[^"]*"[^>]*>([^<]+)<\/a>/', $match[1] ?? '', $links);

    return array_map('trim', $links[1]);
}

it('shows only the areas a person may use', function (string $role, array $expected): void {
    $html = $this->actingAs(switcherUser($role))->get(route('public.catalog.index'))->assertOk()->getContent();

    expect(switcherAreas($html))->toBe($expected);
})->with([
    'student' => ['student', ['Startseite', 'Katalog']],
    'ag basic' => ['student_ag_basic', ['Startseite', 'Katalog', 'Bibliotheksbetrieb']],
    'staff' => ['staff', ['Startseite', 'Katalog', 'Bibliotheksbetrieb']],
    'management' => ['management', ['Startseite', 'Katalog', 'Bibliotheksbetrieb', 'Verwaltung']],
    'technical admin' => ['technical_admin', ['Startseite', 'Katalog', 'Verwaltung']],
]);

it('fills the profile menu with the personal account pages only', function (string $role, array $expected): void {
    $html = $this->actingAs(switcherUser($role))->get(route('public.catalog.index'))->assertOk()->getContent();

    expect(switcherMenu($html))->toBe($expected);
})->with([
    'student' => ['student', ['Mein Konto', 'Meine Ausleihen', 'Meine Vormerkungen', 'Meine Merkliste', 'Meine Buchwünsche', 'Einstellungen', 'Meine Daten']],
    'staff' => ['staff', ['Mein Konto', 'Meine Ausleihen', 'Meine Vormerkungen', 'Meine Merkliste', 'Meine Buchwünsche', 'Einstellungen', 'Meine Daten']],
    'technical admin' => ['technical_admin', []],
]);

it('shows the initials and a logout button in the profile menu, not in the top bar', function (): void {
    $user = switcherUser('staff');
    $user->forceFill(['name' => 'Jan Brand'])->save();

    $html = $this->actingAs($user)->get(route('public.catalog.index'))->assertOk()->getContent();

    expect($html)->toContain('bc-account-menu')->and($html)->toContain('>JB<');
    expect(substr_count($html, 'Abmelden'))->toBe(1);
    expect(switcherAreas($html))->not->toContain('Abmelden');
    preg_match('/<div class="bc-account-menu__panel">.*?Abmelden/s', $html, $inside);
    expect($inside)->not->toBeEmpty();
});

it('shows a login button and the activation link in the masthead for visitors', function (): void {
    $html = $this->get(route('public.catalog.index'))->assertOk()->getContent();

    preg_match('/<div class="bc-account">(.*?)<\/div>/s', $html, $account);

    expect($account[1] ?? '')->toContain('Anmelden')->toContain('Konto aktivieren');
    expect(substr_count($html, 'Anmelden'))->toBe(1);
});

it('offers only start page and catalog to anonymous visitors', function (): void {
    $html = $this->get(route('public.catalog.index'))->assertOk()->getContent();

    expect(switcherAreas($html))->toBe(['Startseite', 'Katalog']);
});

it('marks the current area', function (): void {
    $this->actingAs(switcherUser('management'))
        ->get(route('administration.home'))
        ->assertOk()
        ->assertSee('aria-current="page"', false)
        ->assertSeeInOrder(['aria-current="page"', 'Verwaltung']);
});

it('offers an overflow menu with every navigation item instead of a scroll bar', function (): void {
    $html = $this->actingAs(switcherUser('staff'))->get(route('pos.home'))->assertOk()->getContent();

    $items = substr_count($html, 'data-nav-item');
    $inMenu = substr_count($html, 'data-nav-more-item');

    expect($items)->toBeGreaterThan(3)
        ->and($inMenu)->toBe($items)
        ->and($html)->toContain('data-nav-more hidden')
        ->and($html)->toContain('aria-label="Weitere Menüpunkte"');
});

it('keeps the navigation bar free of scroll bars in the stylesheet', function (): void {
    $css = (string) file_get_contents(base_path('resources/css/patterns/app-shell.css'));

    preg_match('/\.bc-nav__inner \{(.*?)\}/s', $css, $block);

    expect($block[1] ?? '')->not->toContain('overflow-x: auto')->and($block[1] ?? '')->not->toContain('overflow: auto');
});
