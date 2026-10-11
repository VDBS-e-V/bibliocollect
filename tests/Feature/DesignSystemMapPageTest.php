<?php

declare(strict_types=1);

it('bietet die grafische Designsystem-Übersicht im lokalen Designlab an', function (): void {
    expect(app()->environment('testing'))->toBeTrue();

    $this->get(route('public.design-lab'))
        ->assertOk()
        ->assertSee('Das gesamte BiblioCollect Designsystem auf einer Seite.')
        ->assertSee(route('public.design-lab.designsystem'), false);

    $this->get(route('public.design-lab.designsystem'))
        ->assertOk()
        ->assertSee('Ein Design.')
        ->assertSee('Alle Möglichkeiten.')
        ->assertSee('323')
        ->assertSee('22 Baupläne.')
        ->assertSee('67')
        ->assertSee('Zur Komponentenbibliothek')
        ->assertSee('Designsystem-Masterplan');
});

it('zeigt alle 15 Familien, 22 Templates, vier Anwendungsbereiche und acht Umsetzungsphasen', function (): void {
    $html = (string) $this->get(route('public.design-lab.designsystem'))->assertOk()->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//main//h1')->length)->toBe(1)
        ->and($xpath->query('//details[contains(concat(" ", normalize-space(@class), " "), " bc-system-map__family ")]')->length)->toBe(15)
        ->and($xpath->query('//article[contains(concat(" ", normalize-space(@class), " "), " bc-system-map__template ")]')->length)->toBe(22)
        ->and($xpath->query('//article[contains(concat(" ", normalize-space(@class), " "), " bc-system-map__area ")]')->length)->toBe(4)
        ->and($xpath->query('//ol[contains(concat(" ", normalize-space(@class), " "), " bc-system-map__roadmap ")]/li')->length)->toBe(8)
        ->and($xpath->query('//section[@id="system-architecture" or @id="system-foundations" or @id="system-families" or @id="system-patterns" or @id="system-templates" or @id="system-areas" or @id="system-roadmap"]')->length)->toBe(7);
});

it('verwendet ausschliesslich demonstrative Daten und führt keine Schreibaktionen aus', function (): void {
    $html = (string) $this->get(route('public.design-lab.designsystem'))->assertOk()->getContent();

    expect($html)->toContain('Soll-Katalog')
        ->toContain('keine Live-Statistiken')
        ->not->toContain('method="POST" action="/ausleihe"')
        ->not->toContain('action="/verwaltung/import"');
});

it('lässt die übrigen Designlab-Ansichten unverändert erreichbar', function (): void {
    foreach ([
        'public.design-lab.home',
        'public.design-lab.media',
        'public.design-lab.wizard',
        'public.design-lab.dashboard',
        'public.design-lab.components',
    ] as $name) {
        $this->get(route($name))->assertOk();
    }
});
