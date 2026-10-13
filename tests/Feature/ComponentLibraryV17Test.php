<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt alle sechs K17-Komponenten im Designlab ohne echte Formularaktion', function (): void {
    $html = (string) $this->get(route('public.design-lab.components'))->assertOk()
        ->assertSee('Änderungen mit Zeitpunkt und Verantwortlichkeit nachvollziehen')
        ->assertSee('Strukturierte Metadatenpaare hinzufügen und entfernen')
        ->assertSee('Mehrere gleichartige Eingabegruppen kontrolliert verwalten')
        ->assertSee('Verfügbare Einträge und ausgewählte Einträge klar trennen')
        ->assertSee('Importzeilen vor dem eigentlichen Import sichtbar prüfen')
        ->assertSee('Verarbeitungsstände mehrerer Arbeitsschritte verständlich zusammenfassen')
        ->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    expect($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)->toBe(0)
        ->and($xpath->query('//*[@id="bc-core-repeater-field"]//*[@data-ui-repeater]')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-transfer-list"]//*[@data-ui-transfer-list]')->length)->toBe(2);
});

it('zeigt Änderungsereignisse mit Zeit- und Akteursangaben sowie wiederverwendetem Vergleich', function (): void {
    $html = Blade::render('<x-ui.audit-entry title="Titel geändert" kind="updated" actor="Redaktion" occurredAt="2026-10-12T10:00:00+02:00" dateLabel="12.10.2026" :changes="$changes" href="#details" />', [
        'changes' => [['label' => 'Titel', 'before' => 'Alt', 'after' => 'Neu']],
    ]);
    expect($html)->toContain('<article')->toContain('Geändert')->toContain('Redaktion')
        ->toContain('datetime="2026-10-12T10:00:00+02:00"')
        ->toContain('bc-change-review')
        ->toContain('href="#details"');
});

it('liefert für wiederholte Felder eindeutige Eingabe-IDs und indizierte Namen', function (): void {
    $html = Blade::render('<x-ui.repeater-field name="people" label="Personen" :fields="$fields" :items="$items" :min="1" :max="3" />', [
        'fields' => [['key' => 'name', 'label' => 'Name', 'required' => true]],
        'items' => [['name' => 'Person A'], ['name' => 'Person B']],
    ]);
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    expect($xpath->query('//input[@name="people[0][name]"]')->length)->toBe(1)
        ->and($xpath->query('//input[@name="people[1][name]"]')->length)->toBe(1)
        ->and($xpath->query('//input[@required]')->length)->toBeGreaterThan(1)
        ->and($html)->toContain('data-ui-repeater-template')
        ->toContain('data-ui-repeater-max="3"')
        ->toContain('Person A');
});

it('benutzt im Schlüssel-Wert-Editor genau den gemeinsamen Repeater', function (): void {
    $html = Blade::render('<x-ui.key-value-editor name="attributes" :items="$items" />', [
        'items' => [['key' => 'ISBN', 'value' => '123']],
    ]);
    expect($html)->toContain('data-ui-repeater')
        ->toContain('name="attributes[0][key]"')
        ->toContain('name="attributes[0][value]"')
        ->toContain('ISBN');
});

it('bewahrt bestehende Transferauswahl als Formularwerte auch ohne JavaScript', function (): void {
    $html = Blade::render('<x-ui.transfer-list name="tags" label="Themen" :items="$items" :selected="$selected" />', [
        'items' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B'], ['value' => 'c', 'label' => 'C', 'disabled' => true]],
        'selected' => ['b'],
    ]);
    expect($html)->toContain('data-ui-transfer-list')
        ->toContain('data-ui-transfer-hidden')
        ->toContain('name="tags[]" value="b"')
        ->toContain('data-ui-transfer-add disabled')
        ->toContain('data-ui-transfer-remove disabled')
        ->toContain('type="hidden"')
        ->not->toContain('<form');
});

it('zeigt importierte Tabellenwerte escaped mit ausgeschriebenen Status und Fehlertexten', function (): void {
    $html = Blade::render('<x-ui.import-preview :columns="$columns" :rows="$rows" />', [
        'columns' => [['key' => 'title', 'label' => 'Titel']],
        'rows' => [['number' => 2, 'state' => 'error', 'values' => ['title' => '<script>'], 'message' => 'Ungültiger Titel']],
    ]);
    $empty = Blade::render('<x-ui.import-preview :columns="[]" :rows="[]" />');
    expect($html)->toContain('<table')->toContain('scope="col"')->toContain('scope="row"')
        ->toContain('&lt;script&gt;')->toContain('Ungültiger Titel')->toContain('Fehler')
        ->not->toContain('<form')
        ->and($empty)->toContain('Keine Importdaten zur Vorschau.');
});

it('stellt echten Batchwert über vorhandenes Progress dar und erfindet sonst keinen Prozentwert', function (): void {
    $withValue = Blade::render('<x-ui.batch-progress title="Test" :value="2" :max="4" :items="$items" />', [
        'items' => [['title' => 'Start', 'state' => 'done'], ['title' => 'Prüfung', 'state' => 'running']],
    ]);
    $withoutValue = Blade::render('<x-ui.batch-progress :items="$items" />', [
        'items' => [['title' => 'Fehler', 'state' => 'failed']],
    ]);
    expect($withValue)->toContain('<progress')->toContain('value="2"')
        ->toContain('1 von 2 Schritten')
        ->toContain('In Bearbeitung')
        ->and($withoutValue)->not->toContain('<progress')
        ->toContain('Fehlgeschlagen');
});
