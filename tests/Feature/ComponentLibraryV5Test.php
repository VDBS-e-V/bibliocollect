<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt Daten- und Bibliothekskomponenten in der aktiven Designlab-Galerie', function (): void {
    $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Filterleisten für jede Oberfläche')
        ->assertSee('Sortierbare Tabellenköpfe und Mehrfachauswahl')
        ->assertSee('Wiederverwendbare Medienkarten')
        ->assertSee('Verfügbarkeit und Rückgabefristen')
        ->assertSee('Scannerfeld mit einheitlicher Formularlogik')
        ->assertSee('data-ui-bulk-demo', false)
        ->assertSee('data-ui-select-all', false)
        ->assertSee('data-ui-select-row', false)
        ->assertSee('bc-media-card--compact', false)
        ->assertSee('bc-due-status--soon', false);

    $html = (string) $this->get(route('public.design-lab.components'))->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)
        ->toBe(0)
        ->and($xpath->query('//table//th[@aria-sort="ascending"]')->length)->toBeGreaterThanOrEqual(1)
        ->and($xpath->query('//*[@data-ui-bulk-demo]//input[@data-ui-select-row]')->length)->toBe(3);
});

it('markiert Fälligkeit ab sieben Tagen und unterscheidet heute, überfällig und unbekannt', function (): void {
    $cases = [
        [8, 'normal', 'Ausgeliehen'],
        [7, 'soon', 'Bald fällig'],
        [1, 'soon', 'Rückgabe in 1 Tag'],
        [0, 'today', 'Heute fällig'],
        [-1, 'overdue', 'Seit 1 Tag überfällig'],
        [-2, 'overdue', 'Seit 2 Tagen überfällig'],
        [null, 'unknown', 'Frist unbekannt'],
    ];

    foreach ($cases as [$days, $state, $text]) {
        $html = Blade::render('<x-library.due-date-status :daysRemaining="$days" />', ['days' => $days]);

        expect($html)->toContain('bc-due-status--'.$state)->toContain($text);
    }
});

it('rendert Verfügbarkeitsstatus und Medienkarte ohne Datenbank oder Pflichtlinks', function (): void {
    $availability = Blade::render('<x-library.availability-status status="available" detail="Ein Exemplar" compact />');
    $unknown = Blade::render('<x-library.availability-status status="nonsens" />');
    $media = Blade::render(
        '<x-library.media-card title="Die außergewöhnlich lange Reise zwischen zwei Bibliotheken" author="N. Beispiel" density="compact"><x-slot:status><x-library.availability-status status="on-loan" /></x-slot:status></x-library.media-card>',
    );

    expect($availability)->toContain('bc-availability--available')->toContain('Ein Exemplar')
        ->and($unknown)->toContain('Status unbekannt')
        ->and($media)->toContain('bc-media-card--compact')
        ->toContain('Die außergewöhnlich lange Reise')
        ->toContain('bc-library-cover')
        ->toContain('Ausgeliehen');
});

it('zeigt Sortierung als semantischen Tabellenkopf ohne erfundene Sortieraktion', function (): void {
    $sorted = Blade::render('<x-ui.sort-header label="Titel" href="/suche?sort=title" direction="ascending" />');
    $static = Blade::render('<x-ui.sort-header label="Standort" />');

    expect($sorted)->toContain('<th')
        ->toContain('scope="col"')
        ->toContain('aria-sort="ascending"')
        ->toContain('href="/suche?sort=title"')
        ->and($static)->toContain('Standort')
        ->not->toContain('<a ')
        ->not->toContain('aria-sort="ascending"');
});

it('zeigt Sammelaktionen nur bei Auswahl und scannt in der Demo keine echten Daten', function (): void {
    $selected = Blade::render(
        '<x-ui.bulk-action-bar :selected="2" :total="5"><x-slot:actions><button type="button">Demoaktion</button></x-slot:actions></x-ui.bulk-action-bar>',
    );
    $none = Blade::render(
        '<x-ui.bulk-action-bar :selected="0" :total="5"><x-slot:actions><button type="button">Demoaktion</button></x-slot:actions></x-ui.bulk-action-bar>',
    );
    $scan = Blade::render('<x-library.scan-input name="review-barcode" label="Exemplarbarcode" error="Barcode ungültig" hint="Hinweis" />');

    expect($selected)->toContain('2 ausgewählt')->toContain('Demoaktion')
        ->and($none)->toContain('0 ausgewählt')->not->toContain('Demoaktion')
        ->and($scan)->toContain('aria-describedby="review-barcode-error review-barcode-hint"')
        ->toContain('id="review-barcode-error"')
        ->toContain('id="review-barcode-hint"')
        ->toContain('bc-scan-input__field');
});

it('filter toolbar nutzt Slots und zeigt Trefferzahl ohne eigene Fachlogik', function (): void {
    $html = Blade::render(
        '<x-ui.filter-toolbar title="Medienfilter" :resultCount="24" density="compact"><x-slot:filters><x-ui.filter-chip label="Bücher" active /></x-slot:filters></x-ui.filter-toolbar>',
    );

    expect($html)->toContain('bc-filter-toolbar--compact')
        ->toContain('24 Treffer')
        ->toContain('bc-filter-chip--active')
        ->toContain('Bücher');
});
