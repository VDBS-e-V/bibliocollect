<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt K12 in der bestehenden Komponentengalerie', function (): void {
    $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Größere Entscheidungen als echte Radiogruppen und Checkboxen')
        ->assertSee('Bereits vorhandene Dokumente ohne Uploadlogik anzeigen')
        ->assertSee('Erfüllt, nicht erfüllt, offen und nicht anwendbar unterscheiden')
        ->assertSee('bc-choice-cards--compact', false)
        ->assertSee('bc-attachment-list--compact', false)
        ->assertSee('bc-criteria-checklist--compact', false);
});

it('rendert Auswahlkarten mit nativen Eingaben, Vorauswahl und deaktivierten Optionen', function (): void {
    $html = Blade::render('<x-ui.choice-cards name="modus" label="Modus" hint="Wähle eine Option" selected="a" :options="$options" />', [
        'options' => [
            ['value' => 'a', 'title' => 'Auswahl A', 'description' => 'Erklärung'],
            ['value' => 'b', 'title' => 'Auswahl B', 'disabled' => true],
        ],
    ]);

    expect($html)->toContain('<fieldset')
        ->toContain('<legend')
        ->toContain('type="radio"')
        ->toContain('name="modus"')
        ->toContain('checked')
        ->toContain('disabled')
        ->toContain('aria-describedby=')
        ->toContain('Nicht auswählbar')
        ->not->toContain('type="button"');
});

it('unterstützt Mehrfachauswahl und einen verständlichen Fehlertext', function (): void {
    $html = Blade::render('<x-ui.choice-cards name="felder" label="Felder" multiple :selected="$selected" error="Bitte auswählen" :options="$options" />', [
        'selected' => ['titel'],
        'options' => [['value' => 'titel', 'title' => 'Titel'], ['value' => 'jahr', 'title' => 'Jahr']],
    ]);
    expect($html)->toContain('type="checkbox"')->toContain('name="felder[]"')
        ->toContain('Bitte auswählen')->toContain('aria-invalid="true"')
        ->toContain('checked');
});

it('zeigt Dateianlagen ohne falsche Links oder Uploadoperationen', function (): void {
    $html = Blade::render('<x-ui.attachment-list :items="$items" />', [
        'items' => [
            ['name' => '<Dokument>.pdf', 'type' => 'PDF', 'size' => '2 MB', 'href' => '#datei'],
            ['name' => 'Gesperrt.txt', 'href' => '#gesperrt', 'disabled' => true],
            ['name' => 'Nur Metadaten.txt'],
        ],
    ]);
    $empty = Blade::render('<x-ui.attachment-list :items="[]" density="compact" />');
    expect($html)->toContain('&lt;Dokument&gt;.pdf')->toContain('href="#datei"')
        ->not->toContain('href="#gesperrt"')
        ->not->toContain('type="file"')
        ->toContain('Nicht verfügbar')
        ->and($empty)->toContain('0 Dateien')->toContain('Keine Dateien vorhanden.');
});

it('zeigt lesbare Prüfkriterien samt Textstatus und Leerzustand', function (): void {
    $html = Blade::render('<x-ui.criteria-checklist :items="$items" />', [
        'items' => [
            ['title' => 'A', 'state' => 'met'],
            ['title' => 'B', 'state' => 'unmet'],
            ['title' => 'C', 'state' => 'pending'],
            ['title' => 'D', 'state' => 'not-applicable'],
        ],
    ]);
    $empty = Blade::render('<x-ui.criteria-checklist :items="[]" density="compact" />');
    expect($html)->toContain('1 von 4 erfüllt')->toContain('Erfüllt')
        ->toContain('Nicht erfüllt')->toContain('Noch ungeprüft')
        ->toContain('Nicht anwendbar')
        ->not->toContain('<input')
        ->and($empty)->toContain('Keine Prüfkriterien vorhanden.');
});
