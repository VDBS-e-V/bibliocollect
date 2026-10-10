<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Surfaces\Pos\Support\HelpLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function helpUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

it('lists the help articles by area to everyone who works in the library', function (string $role): void {
    $user = helpUser($role);

    $this->actingAs($user)->get(route('pos.help'))->assertOk()
        ->assertSee('Grundlagen')->assertSee('Ausleihe am Tresen')->assertSee('Katalog und Bestand')->assertSee('Verwaltung und Betrieb')
        ->assertSee('So läuft eine Ausleihe')->assertSee('Nummern und Codes: Was ist was?');
    $this->actingAs($user)->get(route('pos.help.show', ['topic' => 'ausleihe-ablauf']))
        ->assertOk()
        ->assertSee('Vorgang bestätigen')
        ->assertSee('Weitere Artikel zum Thema')
        ->assertDontSee('<h1>So läuft eine Ausleihe', false);
    $this->actingAs($user)->get(route('pos.help.show', ['topic' => 'etiketten-drucken']))->assertOk()->assertSee('<h2>Etiketten auf Vorrat</h2>', false);
})->with(['student_ag_basic', 'staff', 'management']);

it('has many articles that are written in plain steps', function (): void {
    $articles = app(HelpLibrary::class)->all();

    expect(count($articles))->toBeGreaterThanOrEqual(40);

    foreach ($articles as $article) {
        expect($article['title'])->not->toBe('')
            ->and($article['summary'])->not->toBe('')
            ->and($article['roles'])->not->toBe([])
            ->and($article['keywords'])->not->toBe([])
            ->and(strlen($article['body']))->toBeGreaterThan(80);
    }
});

it('finds articles by search words, also with umlauts written out, and ranks title matches first', function (): void {
    $user = helpUser('staff');

    $this->actingAs($user)->get(route('pos.help', ['q' => 'ueberfaellig']))->assertOk()->assertSee('Überfällige Medien');
    $this->actingAs($user)->get(route('pos.help', ['q' => 'Ausweis verloren']))->assertOk()->assertSee('Ausweise zuordnen, sperren und ersetzen');
    $this->actingAs($user)->get(route('pos.help', ['q' => 'qqqxyz']))->assertOk()->assertSee('Dazu gibt es keinen Artikel');

    $hits = app(HelpLibrary::class)->search('etikett', null, null);
    expect($hits)->not->toBeEmpty()->and($hits[0]['article']['title'])->toContain('Etikett')->and($hits[0]['snippet'])->not->toBeNull();

    // Ein Wort, das nur im Text vorkommt, liefert einen Auszug.
    expect(app(HelpLibrary::class)->search('abholfrist', null, null)[0]['snippet'])->toContain('bholfrist');
});

it('filters by area and by role and combines both with the search', function (): void {
    $user = helpUser('staff');
    $library = app(HelpLibrary::class);

    $rechte = $library->search('', 'verwaltung', 'verwaltung');
    expect(array_unique(array_map(static fn (array $hit): string => $hit['article']['area'], $rechte)))->toBe(['verwaltung']);

    $agOnly = $library->search('', null, 'ag');
    expect($agOnly)->not->toBeEmpty();
    foreach ($agOnly as $hit) {
        expect($hit['article']['roles'])->toContain('ag');
    }

    // Artikel nur für die Verwaltung sind für die Schüler-AG nicht dabei.
    $titles = array_map(static fn (array $hit): string => $hit['article']['title'], $agOnly);
    expect($titles)->not->toContain('Löschverlangen');

    $this->actingAs($user)->get(route('pos.help', ['bereich' => 'katalog']))->assertOk()->assertSee('Medium erfassen')->assertDontSee('Benutzerkonten und Rollen');
    $this->actingAs($user)->get(route('pos.help', ['bereich' => 'verwaltung', 'rolle' => 'verwaltung', 'q' => 'sicherung']))->assertOk()->assertSee('Systemzustand und Datensicherung');
    $this->actingAs($user)->get(route('pos.help', ['bereich' => 'quatsch']))->assertSessionHasErrors('bereich');
    $this->actingAs($user)->get(route('pos.help', ['rolle' => 'quatsch']))->assertSessionHasErrors('rolle');
});

it('sends the old long pages to the list of their area and knows only the listed articles', function (): void {
    $staff = helpUser('staff');

    $this->actingAs($staff)->get(route('pos.help.show', ['topic' => 'ausleihe']))->assertRedirect(route('pos.help', ['bereich' => 'ausleihe']));
    $this->actingAs($staff)->get(route('pos.help.show', ['topic' => 'verwaltung']))->assertRedirect(route('pos.help', ['bereich' => 'verwaltung']));
    $this->actingAs($staff)->get(route('pos.help.show', ['topic' => '../../.env']))->assertNotFound();
    $this->actingAs($staff)->get(route('pos.help.show', ['topic' => 'gibt-es-nicht']))->assertNotFound();

    $this->actingAs(helpUser('student'))->get(route('pos.help'))->assertForbidden();
    $this->actingAs(helpUser('technical_admin'))->get(route('pos.help'))->assertForbidden();
});

it('never runs html from an article and keeps the search word on the way to the article', function (): void {
    $staff = helpUser('staff');

    $this->actingAs($staff)->get(route('pos.help.show', ['topic' => 'nummern-und-codes', 'q' => 'ausweis']))
        ->assertOk()->assertSee('Suche „ausweis“', false)->assertDontSee('<script>alert', false);
    $this->actingAs($staff)->get(route('pos.help', ['q' => '<script>alert(1)</script>']))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
});
