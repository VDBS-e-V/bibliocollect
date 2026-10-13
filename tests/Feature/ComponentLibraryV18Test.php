<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('bindet alle vier K18-Bausteine in die Designlab-Galerie ein', function (): void {
    $response = $this->get(route('public.design-lab.components'))->assertOk()
        ->assertSee('Wichtige Seitenbereiche ohne lange Tabreihenfolge erreichen')
        ->assertSee('Zurück zum Beginn einer langen Seite springen')
        ->assertSee('Verfügbare Tastaturkürzel lesbar erklären')
        ->assertSee('Längere Beschreibungen ohne neue JavaScript-Engine ein- und ausklappen');

    $html = (string) $response->getContent();
    expect($html)->toContain('bc-skip-links--compact')
        ->toContain('bc-back-to-top--compact')
        ->toContain('bc-shortcut-hint--compact')
        ->toContain('bc-read-more--compact')
        ->toContain('id="bc-core-top"');
});

it('gibt Sprunglinks nur mit übergebenen Zielen und ohne nichtbenutzbare Anker aus', function (): void {
    $html = Blade::render('<x-ui.skip-links :items="$items" />', [
        'items' => [
            ['href' => '#hauptinhalt', 'label' => 'Direkt zum Inhalt'],
            ['href' => null, 'label' => 'Ohne Ziel'],
            ['href' => '#gesperrt', 'label' => 'Gesperrt', 'disabled' => true],
        ],
    ]);
    $empty = Blade::render('<x-ui.skip-links :items="[]" />');
    expect($html)->toContain('aria-label="Direkt zu einem Seitenbereich springen"')
        ->toContain('href="#hauptinhalt"')
        ->not->toContain('href="#gesperrt"')
        ->not->toContain('Ohne Ziel')
        ->and($empty)->not->toContain('<nav');
});

it('nutzt für Rücksprung echte Anker und ohne Ziel keine interaktive Attrappe', function (): void {
    $link = Blade::render('<x-ui.back-to-top href="#anfang" density="compact" />');
    $inactive = Blade::render('<x-ui.back-to-top />');
    expect($link)->toContain('href="#anfang"')
        ->toContain('bc-back-to-top--compact')
        ->toContain('Zurück zum Seitenanfang')
        ->and($inactive)->toContain('aria-disabled="true"')
        ->not->toContain('<a')
        ->not->toContain('href=""');
});

it('beschriftet Tastenkombinationen und erstellt keine eigenen Tastaturaktionen', function (): void {
    $html = Blade::render('<x-ui.shortcut-hint label="Schnellzugriff" :keys="$keys" />', [
        'keys' => ['Strg', 'K'],
    ]);
    $empty = Blade::render('<x-ui.shortcut-hint :keys="[]" />');
    expect($html)->toContain('<kbd')->toContain('Schnellzugriff')
        ->toContain('aria-hidden="true"')->toContain('sr-only')->toContain('Strg plus K')
        ->not->toContain('onkeydown')
        ->and($empty)->not->toContain('<kbd');
});

it('öffnet lange Beschreibungen über native Details ohne doppelten Anrisstext', function (): void {
    $html = Blade::render('<x-ui.read-more title="Beschreibung" excerpt="Kurzfassung">Volltext und mehr.</x-ui.read-more>');
    $open = Blade::render('<x-ui.read-more title="Weitere Daten" open>Weitere Details.</x-ui.read-more>');
    expect($html)->toContain('<details')->toContain('<summary')
        ->toContain('Kurzfassung')->toContain('Volltext und mehr.')
        ->toContain('bc-read-more__excerpt')->toContain('bc-read-more__body')
        ->and($open)->toContain(' open')->toContain('Weitere Details.');
});
