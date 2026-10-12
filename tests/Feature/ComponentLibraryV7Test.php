<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt Covergrößen und die Verwaltungs-Komponenten im aktuellen Designlab', function (): void {
    $response = $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Ein Cover. Vier Farbwelten. Vier Größen.')
        ->assertSee('Informationen ansehen und gezielt bearbeiten')
        ->assertSee('Hierarchische Navigation durch Regale und Bereiche')
        ->assertSee('Wochenplan in zwei Dichten')
        ->assertSee('Schilder und Rückenschilder als Druckvorschau')
        ->assertSee('Nachrichten vor dem Versand prüfen')
        ->assertSee('bc-library-cover--thumbnail', false)
        ->assertSee('bc-library-cover--small', false)
        ->assertSee('bc-library-cover--hero', false)
        ->assertSee('bc-inline-edit--compact', false)
        ->assertSee('bc-hierarchy-tree--compact', false)
        ->assertSee('bc-week-schedule--compact', false)
        ->assertSee('bc-shelf-label-preview--spine', false)
        ->assertSee('bc-message-preview--compact', false);

    $html = (string) $response->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)
        ->toBe(0)
        ->and($xpath->query('//*[@id="bc-core-inline-edit"]//details')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-hierarchy"]//nav[@aria-label]')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-message-preview"]//article')->length)->toBeGreaterThanOrEqual(2);
});

it('stellt Cover als Originalbild oder eine der bestehenden vier VDBS-Varianten dar', function (): void {
    $fallback = Blade::render('<x-library.media-cover title="Ein langer Titel über eine Reise zwischen mehreren Bibliotheken und Geschichten" variant="plum" size="thumbnail" />');
    $original = Blade::render('<x-library.media-cover src="/covers/demo.jpg" title="Originalcover" size="hero" />');

    expect($fallback)
        ->toContain('bc-library-cover--thumbnail')
        ->toContain('bc-library-cover__art--plum')
        ->toContain('bc-library-cover--long-title')
        ->and($original)
        ->toContain('bc-library-cover--hero')
        ->toContain('src="/covers/demo.jpg"')
        ->not->toContain('bc-library-cover__art--');
});

it('zeigt einen nativen aufklappbaren Inline-Editor ohne falsche Speichermeldung', function (): void {
    $html = Blade::render(
        '<x-ui.inline-edit name="example-title" label="Titel" value="Zwischen den Zeilen" hint="Nur Vorschau." compact />',
    );

    expect($html)
        ->toContain('<details')
        ->toContain('<summary')
        ->toContain('bc-inline-edit--compact')
        ->toContain('Zwischen den Zeilen')
        ->toContain('name="example-title"')
        ->toContain('Nur Vorschau.')
        ->not->toContain('<form')
        ->not->toContain('Gespeichert');
});

it('bildet verschachtelte Hierarchien als Listen und zugängliche aufklappbare Zweige ab', function (): void {
    $tree = Blade::render(
        '<x-ui.hierarchy-tree label="Regale" :items="$items" />',
        ['items' => [
            ['label' => 'Sachbücher', 'count' => 2, 'open' => true, 'children' => [
                ['label' => 'Geschichte', 'href' => '/regal/geschichte', 'active' => true],
                ['label' => 'Technik', 'count' => 12],
            ]],
        ]],
    );

    expect($tree)
        ->toContain('aria-label="Regale"')
        ->toContain('<details')
        ->toContain('<summary')
        ->toContain('Sachbücher')
        ->toContain('href="/regal/geschichte"')
        ->toContain('aria-current="page"')
        ->toContain('Technik')
        ->toContain('bc-hierarchy-tree__list--nested');
});

it('zeigt den Wochenplan mit geschlossenen Tagen und optionalen Hinweisen', function (): void {
    $html = Blade::render(
        '<x-ui.week-schedule label="Beispielzeiten" compact :items="$days" />',
        ['days' => [
            ['day' => 'Montag', 'hours' => '09:00–17:00'],
            ['day' => 'Dienstag', 'closed' => true, 'note' => 'Ferientag'],
        ]],
    );

    expect($html)
        ->toContain('aria-label="Beispielzeiten"')
        ->toContain('<dl')
        ->toContain('<dt>Montag</dt>')
        ->toContain('09:00–17:00')
        ->toContain('bc-week-schedule__day--closed')
        ->toContain('Geschlossen')
        ->toContain('Ferientag');
});

it('zeigt Etiketten und Nachrichten als reine Vorschau ohne Barcode- oder Versandfunktionen', function (): void {
    $label = Blade::render('<x-library.shelf-label-preview type="spine" signature="B 142" shelf="Regal B" />');
    $message = Blade::render('<x-ui.message-preview recipient="demo@invalid.example" subject="Hinweis"><p>Beispieltext</p></x-ui.message-preview>');

    expect($label)
        ->toContain('bc-shelf-label-preview--spine')
        ->toContain('B 142')
        ->not->toContain('<form')
        ->not->toContain('<canvas')
        ->and($message)
        ->toContain('demo@invalid.example')
        ->toContain('Hinweis')
        ->toContain('Beispieltext')
        ->not->toContain('<form')
        ->not->toContain('type="submit"');
});
