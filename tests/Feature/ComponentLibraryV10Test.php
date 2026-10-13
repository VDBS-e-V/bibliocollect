<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt vier neue K10-Bausteine zusammen mit vorhandenen Komponenten im Designlab', function (): void {
    $response = $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Treffer verstehen, bevor man filtert')
        ->assertSee('Einträge pro Seite und Pagination als ein System')
        ->assertSee('Fortschritt und Schrittaktionen sauber trennen')
        ->assertSee('Auf einen Blick wissen, welchen Datensatz man bearbeitet')
        ->assertSee('bc-result-summary--compact', false)
        ->assertSee('bc-page-size-selector--compact', false)
        ->assertSee('bc-step-actions--compact', false)
        ->assertSee('bc-context-strip--compact', false);

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.(string) $response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[@id="bc-core-results-overview"]//section[contains(@class, "bc-result-summary")]')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-pagination-size"]//select')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-step-actions"]//nav[contains(@class, "bc-step-actions")]')->length)->toBe(3)
        ->and($xpath->query('//*[@id="bc-core-context-strip"]//section[contains(@class, "bc-context-strip")]')->length)->toBe(2)
        ->and($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)->toBe(0);
});

it('erläutert die Trefferzahl auch bei null Ergebnissen ohne Duplikation des Empty-States', function (): void {
    $summary = Blade::render(
        '<x-ui.result-summary :count="0" query="Bibliotheken" density="compact" description="Keine Ergebnisse vorhanden."><x-slot:filters><span>Aktive Filter</span></x-slot:filters></x-ui.result-summary>',
    );
    $empty = Blade::render('<x-ui.empty-state title="Keine Treffer" />');

    expect($summary)
        ->toContain('bc-result-summary--compact')
        ->toContain('0 Treffer')
        ->toContain('für „Bibliotheken“')
        ->toContain('Aktive Filter')
        ->not->toContain('bc-empty-state')
        ->and($empty)
        ->toContain('bc-empty-state')
        ->toContain('Keine Treffer');
});

it('rendert native Seitengrößenwahl mit ID, Beschriftung und wirklicher Selektion', function (): void {
    $select = Blade::render(
        '<x-ui.page-size-selector name="per_page" label="Einträge pro Seite" :value="50" :options="[10,20,50,100]" hint="Auswahl wird später übernommen." density="compact" />',
    );
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$select, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    $field = $xpath->query('//select[@name="per_page"]')->item(0);

    expect($field)->not->toBeNull()
        ->and($field->getAttribute('id'))->toBe('per_page-size')
        ->and($field->getAttribute('aria-describedby'))->toBe('per_page-size-hint')
        ->and($xpath->query('//label[@for="per_page-size"]')->length)->toBe(1)
        ->and($xpath->query('//option[@value="50" and @selected]')->length)->toBe(1)
        ->and($select)->toContain('bc-page-size-selector--compact')
        ->not->toContain('<form')
        ->not->toContain('onchange=');
});

it('trennt Schrittaktionen vom Fortschrittsstepper und deaktiviert fehlende Ziele', function (): void {
    $actions = Blade::render(
        '<x-ui.step-actions previous="/assistent/start" next="/assistent/pruefen" cancel="/assistent" nextLabel="Weiter prüfen" />',
    );
    $first = Blade::render('<x-ui.step-actions next="/assistent/weiter" />');
    $last = Blade::render('<x-ui.step-actions previous="/assistent/zurueck" nextLabel="Abgeschlossen" />');

    expect($actions)->toContain('aria-label="Schrittnavigation"')
        ->toContain('href="/assistent/start"')
        ->toContain('href="/assistent/pruefen"')
        ->toContain('href="/assistent"')
        ->toContain('Weiter prüfen')
        ->and($first)->toContain('aria-disabled="true"')
        ->toContain('href="/assistent/weiter"')
        ->not->toContain('href="#"')
        ->and($last)->toContain('Abgeschlossen')
        ->toContain('aria-disabled="true"')
        ->not->toContain('href="#"');
});

it('zeigt Details als vollständige dl-dt-dd-Metadaten und kombiniert sie mit Status', function (): void {
    $html = Blade::render(
        '<x-ui.context-strip title="Ein besonders langer Titel zwischen den Bibliotheken" backHref="/medien" density="compact" :items="$items"><x-slot:status><x-library.due-date-status :daysRemaining="-2" compact /></x-slot:status></x-ui.context-strip>',
        ['items' => [
            ['label' => 'Barcode', 'value' => 'BC-00003146'],
            ['label' => 'Standort', 'value' => 'Regal B'],
        ]],
    );

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($html)->toContain('bc-context-strip--compact')
        ->toContain('href="/medien"')
        ->toContain('Ein besonders langer Titel')
        ->toContain('bc-due-status--overdue')
        ->toContain('Überfällig')
        ->and($xpath->query('//dl/dt')->length)->toBe(0)
        ->and($xpath->query('//dl/div/dt')->length)->toBe(2)
        ->and($xpath->query('//dl/div/dd')->length)->toBe(2);
});
