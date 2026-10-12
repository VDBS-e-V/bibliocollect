<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt die vier neuen K9-Komponenten und die bereits vorhandenen Leere-/Pagination-Muster im aktiven Designlab', function (): void {
    $response = $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Zeiträume ohne verschobene Eingabefelder')
        ->assertSee('Segmentierte Auswahl ohne zusätzliche JavaScript-Abhängigkeit')
        ->assertSee('Datensatzkarten als Alternative zu breiten Tabellen')
        ->assertSee('Eine gemeinsame, bewusst bunte Statuslegende')
        ->assertSee('Leere Ergebnisse und Pagination sauber kombinieren')
        ->assertSee('bc-date-range--compact', false)
        ->assertSee('bc-segmented-choice--compact', false)
        ->assertSee('bc-record-card--compact', false)
        ->assertSee('bc-status-legend--compact', false);

    $html = (string) $response->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[@id="bc-core-date-range"]//input[@type="date"]')->length)->toBe(4)
        ->and($xpath->query('//*[@id="bc-core-segmented-choice"]//input[@type="radio"]')->length)->toBe(6)
        ->and($xpath->query('//*[@id="bc-core-record-cards"]//article')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-empty-pagination"]//nav[@aria-label]')->length)->toBe(2)
        ->and($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)->toBe(0);
});

it('beschriftet beide Datumsfelder und verknüpft Gruppenfehler unter den Inputs', function (): void {
    $html = Blade::render(
        '<x-ui.date-range name="report" label="Zeitraum" from="2026-10-01" to="2026-10-31" hint="Hinweis" error="Bitte prüfen" density="compact" />',
    );

    expect($html)
        ->toContain('<fieldset')
        ->toContain('bc-date-range--compact')
        ->toContain('<legend')
        ->toContain('Zeitraum')
        ->toContain('name="report_from"')
        ->toContain('name="report_to"')
        ->toContain('type="date"')
        ->toContain('value="2026-10-31"')
        ->toContain('aria-invalid="true"')
        ->toContain('aria-describedby="report-error report-hint"')
        ->toContain('id="report-error"')
        ->toContain('id="report-hint"');
});

it('verwendet für die segmentierte Auswahl native Radiobuttons mit Tastaturverhalten', function (): void {
    $html = Blade::render(
        '<x-ui.segmented-choice name="view" label="Ansicht" selected="list" :options="$choices" />',
        ['choices' => [
            ['value' => 'grid', 'label' => 'Kacheln'],
            ['value' => 'list', 'label' => 'Liste'],
            ['value' => 'table', 'label' => 'Tabelle', 'disabled' => true],
        ]],
    );
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    $radios = $xpath->query('//input[@type="radio"]');

    expect($radios->length)->toBe(3)
        ->and($radios->item(0)->getAttribute('name'))->toBe('view')
        ->and($radios->item(1)->hasAttribute('checked'))->toBeTrue()
        ->and($radios->item(2)->hasAttribute('disabled'))->toBeTrue()
        ->and($xpath->query('//legend')->item(0)->textContent)->toBe('Ansicht');
});

it('gibt Datensatzkarten mit echten dl-dt-dd Paaren und langen Titeln aus', function (): void {
    $html = Blade::render(
        '<x-ui.record-card title="Ein sehr langer, beispielhafter bibliografischer Datensatztitel" density="compact" :items="$fields"><x-slot:status><x-library.availability-status status="available" /></x-slot:status></x-ui.record-card>',
        ['fields' => [
            ['label' => 'Standort', 'value' => 'Regal A'],
            ['label' => 'Signatur', 'value' => 'A-123'],
        ]],
    );
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($html)->toContain('bc-record-card--compact')
        ->toContain('bc-availability--available')
        ->toContain('Ein sehr langer, beispielhafter')
        ->and($xpath->query('//article')->length)->toBe(1)
        ->and($xpath->query('//dl/dt')->length)->toBe(2)
        ->and($xpath->query('//dl/dd')->length)->toBe(2);
});

it('zeigt Statuslegenden mit Text und beschränkt Farben auf bekannte VDBS-Tokens', function (): void {
    $html = Blade::render(
        '<x-ui.status-legend label="Zustände" :items="$items" />',
        ['items' => [
            ['tone' => 'green', 'label' => 'Verfügbar'],
            ['tone' => 'red', 'label' => 'Überfällig'],
            ['tone' => 'falsch', 'label' => 'Unbekannt'],
        ]],
    );

    expect($html)->toContain('aria-label="Zustände"')
        ->toContain('bc-status-legend__marker--green')
        ->toContain('bc-status-legend__marker--red')
        ->toContain('bc-status-legend__marker--neutral')
        ->toContain('Verfügbar')
        ->toContain('Überfällig')
        ->toContain('aria-hidden="true"');
});

it('hält Empty-State und Pagination als vorhandene Einzelkomponenten wiederverwendbar', function (): void {
    $empty = Blade::render('<x-ui.empty-state title="Keine Treffer" description="Bitte andere Filter versuchen." tone="brand" />');
    $pages = Blade::render('<x-ui.pagination :current="2" :total="4" previous="/medien?page=1" next="/medien?page=3" />');

    expect($empty)->toContain('bc-empty-state--brand')
        ->toContain('Keine Treffer')
        ->and($pages)->toContain('Seite 2 von 4')
        ->toContain('href="/medien?page=1"')
        ->toContain('href="/medien?page=3"')
        ->toContain('aria-label="Seitennavigation"');
});

it('gibt dem gemeinsamen VDBS-Footer genügend Kontrast statt transparenter kleiner Schrift', function (): void {
    $css = file_get_contents(resource_path('css/patterns/shell-redesign.css'));

    expect($css)->toContain('.bc-footer-brand small')
        ->toContain('color: var(--vdbs-white)')
        ->not->toContain(".bc-footer-brand small {\n    opacity: .8;");
});
