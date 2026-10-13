<?php

declare(strict_types=1);

it('zeigt das priorisierte Portal-Template nur als lokale Designstudie', function (): void {
    expect(app()->environment('testing'))->toBeTrue();

    $response = $this->get(route('public.design-lab.portal'))
        ->assertOk()
        ->assertSee('Meine Bibliothek.')
        ->assertSee('Deine nächsten Rückgaben')
        ->assertSee('Alle ausgeliehenen Medien')
        ->assertSee('Vormerkungen')
        ->assertSee('Mehr entdecken und verwalten')
        ->assertSee('fiktiv')
        ->assertSee('Verlängern (Demo)');

    $html = (string) $response->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//main//h1')->length)->toBe(1);

    $sections = [
        'portal-study-priority-heading',
        'portal-study-loans-heading',
        'portal-study-reservations-heading',
        'portal-study-extras-heading',
    ];

    $positions = [];
    foreach ($sections as $id) {
        $nodes = $xpath->query('//h2[@id="'.$id.'"]');
        expect($nodes->length)->toBe(1);
        $node = $nodes->item(0);
        expect($node)->not->toBeNull();
        $positions[] = strpos($html, 'id="'.$id.'"');
    }

    expect($positions)->toBe(collect($positions)->sort()->values()->all())
        ->and($xpath->query('//button[contains(@class, "bc-portal-study__demo-button") and @disabled]')->length)->toBeGreaterThan(0)
        ->and($xpath->query('//section[contains(@class, "bc-portal-study__priority")]')->length)->toBe(1);
});

it('zeigt lange Titel, Warnzustand und leere Konten ohne produktive Portalaktionen', function (): void {
    $html = (string) $this->get(route('public.design-lab.portal'))->assertOk()->getContent();

    expect($html)
        ->toContain('Die wunderbare und unglaublich aufregende Geschichte')
        ->toContain('Überfällig')
        ->toContain('Konto noch nicht verknüpft')
        ->toContain('Noch keine Ausleihen')
        ->toContain('Verlängern (Demo)');

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    // Die eigene Mustervorlage besitzt keine Schreibformulare.
    expect($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-portal-study ")]//form')->length)->toBe(0);
});
