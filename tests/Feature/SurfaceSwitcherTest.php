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

it('shows only the areas a person may use', function (string $role, array $expected): void {
    $html = $this->actingAs(switcherUser($role))->get(route('public.catalog.index'))->assertOk()->getContent();

    expect(switcherAreas($html))->toBe($expected);
})->with([
    'student' => ['student', ['Startseite', 'Katalog', 'Mein Konto']],
    'ag basic' => ['student_ag_basic', ['Startseite', 'Katalog', 'Mein Konto', 'Bibliotheksbetrieb']],
    'staff' => ['staff', ['Startseite', 'Katalog', 'Mein Konto', 'Bibliotheksbetrieb']],
    'management' => ['management', ['Startseite', 'Katalog', 'Mein Konto', 'Bibliotheksbetrieb', 'Verwaltung']],
    'technical admin' => ['technical_admin', ['Startseite', 'Katalog', 'Verwaltung']],
]);

it('offers only start page and catalog to anonymous visitors', function (): void {
    $html = $this->get(route('public.catalog.index'))->assertOk()->getContent();

    expect(switcherAreas($html))->toBe(['Startseite', 'Katalog', 'Anmelden', 'Konto aktivieren']);
});

it('marks the current area', function (): void {
    $this->actingAs(switcherUser('management'))
        ->get(route('administration.home'))
        ->assertOk()
        ->assertSee('aria-current="page"', false)
        ->assertSeeInOrder(['aria-current="page"', 'Verwaltung']);
});
