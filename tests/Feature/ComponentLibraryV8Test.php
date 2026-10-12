<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt die größere K8-Komponentenrunde im aktuellen Designlab', function (): void {
    $response = $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Seitentoolbars und mobile Navigation')
        ->assertSee('Eine Filterseitenleiste, die auch mobil funktioniert')
        ->assertSee('Zeichenzähler und Dateiauswahl mit Rückmeldung')
        ->assertSee('Zeitliche Ereignisse und nachvollziehbare Veränderungen')
        ->assertSee('Bunte Datenbalken mit immer lesbarer Tabelle')
        ->assertSee('Einheitlicher Ladezustand statt leerer Flächen')
        ->assertSee('Dieselben Komponenten in allen wichtigen Zuständen prüfen')
        ->assertSee('data-ui-file-zone', false)
        ->assertSee('data-ui-char-count', false)
        ->assertSee('bc-metric-bars__data', false);

    $html = (string) $response->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[@aria-label="Komponentengruppen"]/details')->length)->toBe(7)
        ->and($xpath->query('//*[@id="bc-core-data-viz"]//table')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-filter-panel"]//details')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-review-matrix"]//input[@type="checkbox"]')->length)->toBe(8)
        ->and($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)->toBe(0);
});

it('stellt Toolbars und mobile Navigation ohne erfundene Aktionen dar', function (): void {
    $toolbar = Blade::render(
        '<x-ui.page-toolbar title="Medien" density="compact"><x-slot:actions><a href="/medien">Medien öffnen</a></x-slot:actions></x-ui.page-toolbar>',
    );
    $mobile = Blade::render(
        '<x-ui.mobile-navigation label="Bereiche" :items="$items" />',
        ['items' => [
            ['label' => 'Aktuelle Seite', 'href' => '/medien', 'active' => true],
            ['label' => 'Gesperrt', 'disabled' => true],
        ]],
    );

    expect($toolbar)->toContain('bc-page-toolbar--compact')
        ->toContain('Medien öffnen')
        ->and($mobile)->toContain('data-ui-dismissable')
        ->toContain('<details')
        ->toContain('aria-label="Bereiche"')
        ->toContain('aria-current="page"')
        ->toContain('aria-disabled="true"')
        ->not->toContain('role="menu"')
        ->not->toContain('href="#"');
});

it('stellt ein natives Filterpanel mit aktiven Filtern und echtem Reset-Link bereit', function (): void {
    $html = Blade::render(
        '<x-ui.filter-panel title="Zusätzliche Filter" :count="2" clearHref="/medien?reset=1" open><x-ui.checkbox name="filter-books" label="Bücher" /></x-ui.filter-panel>',
    );

    expect($html)->toContain('<details')
        ->toContain('<summary')
        ->toContain('2 aktiv')
        ->toContain('href="/medien?reset=1"')
        ->toContain('name="filter-books"')
        ->not->toContain('<form');
});

it('zählt Zeichen nur bei ausdrücklich aktiviertem Zähler und verbindet Hilfen', function (): void {
    $counted = Blade::render(
        '<x-ui.textarea name="review-notes" label="Notizen" value="ABC" :maxlength="120" showCount hint="Kurz beschreiben." />',
    );
    $normal = Blade::render(
        '<x-ui.textarea name="review-plain" label="Notizen" :maxlength="120" />',
    );

    expect($counted)->toContain('data-ui-char-count="review-notes-count"')
        ->toContain('3 / 120 Zeichen')
        ->toContain('id="review-notes-count"')
        ->toContain('aria-describedby="review-notes-hint review-notes-count"')
        ->and($normal)->not->toContain('data-ui-char-count')
        ->not->toContain('review-plain-count');
});

it('gibt im Dateifeld eine barrierefrei beschriftete lokale Auswahl statt eines Uploads an', function (): void {
    $html = Blade::render(
        '<x-ui.file-drop-zone name="review-cover" label="Cover" accept=".png,.jpg" hint="Nur Bilddateien." />',
    );

    expect($html)->toContain('data-ui-file-zone')
        ->toContain('data-ui-file-input')
        ->toContain('id="review-cover"')
        ->toContain('accept=".png,.jpg"')
        ->toContain('aria-describedby="review-cover-hint review-cover-feedback"')
        ->toContain('aria-live="polite"')
        ->toContain('Noch keine Datei ausgewählt.')
        ->not->toContain('<form')
        ->not->toContain('enctype="multipart/form-data"');
});

it('bildet einen semantischen Verlauf mit lesbarer Zeitangabe ab', function (): void {
    $html = Blade::render(
        '<x-ui.activity-timeline title="Verlauf" :items="$items" compact />',
        ['items' => [
            ['title' => 'Ausleihe begonnen', 'datetime' => '2026-10-05', 'dateLabel' => '5. Oktober', 'status' => 'success'],
            ['title' => 'Fristwarnung', 'dateLabel' => '12. Oktober', 'status' => 'warning'],
        ]],
    );

    expect($html)->toContain('<ol')
        ->toContain('aria-label="Verlauf"')
        ->toContain('<time datetime="2026-10-05">')
        ->toContain('bc-activity-timeline--compact')
        ->toContain('bc-activity-timeline__item--warning');
});

it('zeigt buntes Balkendiagramm mit tatsächlicher lesbarer Datentabelle', function (): void {
    $html = Blade::render(
        '<x-ui.metric-bars title="Ausleihen" :max="20" :items="$items" />',
        ['items' => [
            ['label' => 'Bücher', 'value' => 10, 'tone' => 'green'],
            ['label' => 'Hörbücher', 'value' => 5, 'tone' => 'purple'],
        ]],
    );

    expect($html)->toContain('bc-metric-bars__fill--green')
        ->toContain('bc-metric-bars__fill--purple')
        ->toContain('style="width:50%"')
        ->toContain('aria-hidden="true"')
        ->toContain('<table')
        ->toContain('zugängliche Datentabelle')
        ->toContain('<th scope="row">Bücher</th>')
        ->toContain('<td>10</td>');
});

it('unterscheidet busy und ready explizit ohne produktive Hintergrundarbeit', function (): void {
    $busy = Blade::render('<x-ui.busy-panel title="Laden" description="Beispiel" />');
    $ready = Blade::render('<x-ui.busy-panel title="Fertig" :busy="false"><p>Inhalt sichtbar</p></x-ui.busy-panel>');

    expect($busy)->toContain('aria-busy="true"')
        ->toContain('role="status"')
        ->toContain('bc-skeleton')
        ->and($ready)->toContain('aria-busy="false"')
        ->toContain('Inhalt sichtbar')
        ->not->toContain('bc-skeleton');
});
