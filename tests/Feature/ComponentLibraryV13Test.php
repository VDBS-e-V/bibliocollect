<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt alle vier K13-Patterns in der bestehenden Galerie', function (): void {
    $this->get(route('public.design-lab.components'))->assertOk()
        ->assertSee('Kontaktangaben strukturiert, lesbar und erreichbar darstellen')
        ->assertSee('Rollen und Zugriffsarten als echte Datentabelle vergleichen')
        ->assertSee('Sonderöffnungszeiten und Schließtage neben Wochenplänen zeigen')
        ->assertSee('Mehrere ausgewählte Einträge vor der Weiterverarbeitung kontrollieren');
});

it('rendert Kontaktangaben mit zugänglichen nativen Links und ohne leere Platzhalter', function (): void {
    $html = Blade::render('<x-ui.contact-card title="Kontakt" name="Muster" email="info@example.org" phone="+49 123 45" :address-lines="$lines" />', ['lines' => ['Beispielweg 1']]);
    expect($html)->toContain('<address')->toContain('mailto:info@example.org')
        ->toContain('tel:+4912345')->toContain('Beispielweg 1')
        ->not->toContain('https://');
});

it('rendert eine Berechtigungsmatrix mit echten Tabellenüberschriften und Textstatus', function (): void {
    $html = Blade::render('<x-ui.permission-matrix :columns="$cols" :rows="$rows" />', [
        'cols' => [['key' => 'a', 'label' => 'Rolle A'], ['key' => 'b', 'label' => 'Rolle B']],
        'rows' => [['label' => 'Datensatz', 'values' => ['a' => 'allowed', 'b' => 'denied']]],
    ]);
    $empty = Blade::render('<x-ui.permission-matrix :columns="[]" :rows="[]" />');
    expect($html)->toContain('<table')->toContain('scope="col"')->toContain('scope="row"')
        ->toContain('Erlaubt')->toContain('Nicht erlaubt')
        ->and($empty)->toContain('Keine Berechtigungsdaten vorhanden.');
});

it('zeigt Ausnahmezeiträume als time und erklärt geschlossene Tage ohne Farbabhängigkeit', function (): void {
    $html = Blade::render('<x-ui.schedule-exceptions :items="$items" />', [
        'items' => [['label' => 'Feiertag', 'date' => '01.01.2027', 'datetime' => '2027-01-01', 'closed' => true], ['label' => 'Sondertag', 'hours' => '9–12 Uhr']],
    ]);
    expect($html)->toContain('datetime="2027-01-01"')->toContain('Geschlossen')
        ->toContain('9–12 Uhr')->not->toContain('<button');
});

it('zeigt nur echte Entfernen-Links, escaped Inhalte und einen Leerzustand', function (): void {
    $html = Blade::render('<x-ui.selection-summary :items="$items" density="compact" />', [
        'items' => [['title' => '<Script>', 'removeHref' => '#entfernen'], ['title' => 'Gesperrt', 'disabled' => true, 'removeHref' => '#gesperrt']],
    ]);
    $empty = Blade::render('<x-ui.selection-summary :items="[]" />');
    expect($html)->toContain('&lt;Script&gt;')->toContain('href="#entfernen"')
        ->not->toContain('href="#gesperrt"')
        ->toContain('bc-selection-summary--compact')
        ->and($empty)->toContain('Noch nichts ausgewählt.');
});
