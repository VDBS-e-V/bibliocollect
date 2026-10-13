<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt alle sechs K16-Priority-B-Komponenten im Designlab', function (): void {
    $response = $this->get(route('public.design-lab.components'))->assertOk()
        ->assertSee('Aus Cover und vorhandenen Datenelementen eine Detailübersicht bauen')
        ->assertSee('Position, Abholbereitschaft und abgelaufene Fristen erkennen')
        ->assertSee('Aktuelle Ausleihen und Rückgabefristen kompakt überblicken')
        ->assertSee('ISBN, ISSN und Barcode als Metadaten klar gegenüberstellen')
        ->assertSee('Vom Bibliotheksbereich bis zur Signatur orientieren')
        ->assertSee('Benachrichtigungskanäle als eine zugängliche Formulargruppe wählen');
    expect((string) $response->getContent())->toContain('bc-contact-preferences--compact')
        ->toContain('bc-loan-summary--compact')
        ->toContain('bc-hold-status--ready');
});

it('kombiniert die Mediendetails mit bestehendem Cover, Status und Beschreibungsliste', function (): void {
    $html = Blade::render('<x-library.media-detail-summary title="Langtitel" author="Muster" availability="available" :metadata="$metadata" density="compact" />', [
        'metadata' => [['label' => 'Jahr', 'value' => '2026']],
    ]);
    expect($html)->toContain('bc-media-detail-summary--compact')
        ->toContain('bc-library-cover')
        ->toContain('bc-availability--available')
        ->toContain('bc-description-list')
        ->toContain('Langtitel')->toContain('Muster')->toContain('2026');
});

it('zeigt vormerkungen samt echter Position und nur bei Bereitschaft Abholfrist', function (): void {
    $waiting = Blade::render('<x-library.hold-status state="waiting" :position="2" expiresLabel="19.11.2026" />');
    $ready = Blade::render('<x-library.hold-status state="ready" expiresLabel="19.11.2026" />');
    $unknown = Blade::render('<x-library.hold-status state="ungueltig" />');
    expect($waiting)->toContain('Vormerkung aktiv')->toContain('Position 2 in der Warteliste')
        ->not->toContain('Abholen bis:')
        ->and($ready)->toContain('Zur Abholung bereit')->toContain('Abholen bis: 19.11.2026')
        ->and($unknown)->toContain('Vormerkstatus unbekannt');
});

it('nutzt für Ausleihen die vorhandene Sieben-Tage-Statuslogik', function (): void {
    $soon = Blade::render('<x-library.loan-summary title="Medium" :dueDays="7" dueDateLabel="20.11.2026" href="#medium" />');
    $unknown = Blade::render('<x-library.loan-summary title="Unklar" density="compact" />');
    expect($soon)->toContain('bc-due-status--soon')
        ->toContain('href="#medium"')
        ->toContain('Rückgabe in 7 Tagen')
        ->toContain('20.11.2026')
        ->and($unknown)->toContain('bc-due-status--unknown');
});

it('zeigt lange Kennungen semantisch und nur bei echten Zielen als Link', function (): void {
    $html = Blade::render('<x-library.identifier-list :items="$items" />', [
        'items' => [['label' => 'ISBN', 'value' => '<123>', 'href' => '#id'],
            ['label' => 'Barcode', 'value' => '888', 'disabled' => true, 'href' => '#gesperrt']],
    ]);
    $empty = Blade::render('<x-library.identifier-list :items="[]" />');
    expect($html)->toContain('<dl')->toContain('<dt>ISBN</dt>')
        ->toContain('&lt;123&gt;')->toContain('href="#id"')
        ->not->toContain('href="#gesperrt"')
        ->and($empty)->toContain('Keine Kennungen vorhanden.');
});

it('zeigt Standortsegmente, Signatur und deaktivierte Bereiche ohne Scheinlinks', function (): void {
    $html = Blade::render('<x-library.location-path :segments="$segments" signature="B-17" />', [
        'segments' => [['label' => 'Bibliothek', 'href' => '#ort'],
            ['label' => 'Gesperrt', 'href' => '#no', 'disabled' => true]],
    ]);
    $empty = Blade::render('<x-library.location-path :segments="[]" density="compact" />');
    expect($html)->toContain('<ol')->toContain('href="#ort"')
        ->toContain('B-17')->not->toContain('href="#no"')
        ->and($empty)->toContain('Kein Standort angegeben.');
});

it('nutzt bestehende Checkboxen in einer beschrifteten Kontaktwahl ohne echtes Formular', function (): void {
    $html = Blade::render('<x-ui.contact-preferences name="kanal" label="Kontakt" :options="$options" :selected="$selected" error="Bitte prüfen" />', [
        'options' => [['value' => 'email', 'label' => 'E-Mail'],
            ['value' => 'sms', 'label' => 'SMS', 'disabled' => true]],
        'selected' => ['email'],
    ]);
    expect($html)->toContain('<fieldset')->toContain('<legend>Kontakt</legend>')
        ->toContain('name="kanal[]"')->toContain('type="checkbox"')
        ->toContain('checked')->toContain('disabled')
        ->toContain('Bitte prüfen')->toContain('aria-describedby=')
        ->not->toContain('<form');
});

it('bewahrt die Grenzwerte des Sieben-Tage-Status und verwirft ungültige Tagesdifferenzen', function (): void {
    $cases = [
        [8, 'normal', 'Ausgeliehen'],
        [7, 'soon', 'Bald fällig'],
        [1, 'soon', 'Bald fällig'],
        [0, 'today', 'Heute fällig'],
        [-1, 'overdue', 'Überfällig'],
        [null, 'unknown', 'Frist unbekannt'],
        ['7.5', 'unknown', 'Frist unbekannt'],
        ['kein Tageswert', 'unknown', 'Frist unbekannt'],
    ];

    foreach ($cases as [$days, $state, $label]) {
        $html = Blade::render('<x-library.due-date-status :daysRemaining="$days" />', ['days' => $days]);
        expect($html)->toContain('bc-due-status--'.$state)->toContain($label);

        if ($state === 'unknown') {
            expect($html)->not->toContain('Heute fällig')->not->toContain('bc-due-status--today');
        }
    }
});

it('demonstriert alle sechs Fristgrenzen im Designlab ohne echte Rückgabedaten', function (): void {
    $html = (string) $this->get(route('public.design-lab.components'))->assertOk()->getContent();
    expect($html)->toContain('Grenzwerte für Rückgabefristen')
        ->toContain('8 Tage · neutral')
        ->toContain('7 Tage · Warnung beginnt')
        ->toContain('0 Tage · heute fällig')
        ->toContain('−1 Tag · überfällig')
        ->toContain('Keine Tagesangabe · unbekannt')
        ->toContain('bc-deadline-matrix__case')
        ->toContain('bc-due-status--normal')
        ->toContain('bc-due-status--today')
        ->toContain('bc-due-status--overdue');
});
