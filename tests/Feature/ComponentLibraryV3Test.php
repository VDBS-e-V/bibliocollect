<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt erweiterte Formulare und Datenelemente in der aktiven Komponentenbibliothek', function (): void {
    $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Mehrzeilige Eingaben und Vorschläge')
        ->assertSee('Abschnitte und Fehler verständlich gruppieren')
        ->assertSee('Filter sichtbar und kombinierbar')
        ->assertSee('Dieselbe Medienzeile in zwei Dichten')
        ->assertSee('bc-form-section', false)
        ->assertSee('bc-error-summary', false)
        ->assertSee('bc-filter-chip', false)
        ->assertSee('bc-list-row--compact', false)
        ->assertSee('bc-field--upload', false)
        ->assertSee('bc-field--textarea', false)
        ->assertSee('bc-field--autocomplete', false);
});

it('reiht Eingabefeld und zugehörige Hilfen korrekt an und referenziert beide Texte', function (): void {
    $textarea = Blade::render(
        '<x-ui.textarea name="bc-review-note" label="Beschreibung" value="Ein Text" error="Bitte ergänzen." hint="Bis zu 600 Zeichen." />',
    );
    $upload = Blade::render(
        '<x-ui.file-upload name="bc-review-file" label="Cover" error="Falsches Format." hint="Nur Bilder." accept="image/png" />',
    );
    $autocomplete = Blade::render(
        '<x-ui.autocomplete name="bc-review-tags" label="Thema" error="Fehlende Angabe." hint="Bitte auswählen." :options="$options" />',
        ['options' => ['Bücher', 'Natur']],
    );

    foreach ([
        ['html' => $textarea, 'tag' => 'textarea', 'id' => 'bc-review-note'],
        ['html' => $upload, 'tag' => 'input', 'id' => 'bc-review-file'],
        ['html' => $autocomplete, 'tag' => 'input', 'id' => 'bc-review-tags'],
    ] as $case) {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$case['html'], LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);

        $control = $xpath->query('//'.$case['tag'].'[@id="'.$case['id'].'"]')->item(0);
        $hint = $xpath->query('//p[@id="'.$case['id'].'-hint"]')->item(0);
        $error = $xpath->query('//p[@id="'.$case['id'].'-error"]')->item(0);

        expect($control)->not->toBeNull()
            ->and($hint)->not->toBeNull()
            ->and($error)->not->toBeNull()
            ->and($control->getAttribute('aria-invalid'))->toBe('true')
            ->and($control->getAttribute('aria-describedby'))->toBe($case['id'].'-error '.$case['id'].'-hint');

        $html = $case['html'];
        expect(strpos($html, '<label'))->toBeLessThan(strpos($html, '<'.$case['tag']))
            ->and(strpos($html, 'id="'.$case['id'].'-error"'))->toBeGreaterThan(strpos($html, '<'.$case['tag']))
            ->and(strpos($html, 'id="'.$case['id'].'-hint"'))->toBeGreaterThan(strpos($html, 'id="'.$case['id'].'-error"'));
    }

    expect($autocomplete)->toContain('<datalist id="bc-review-tags-options">')
        ->toContain('value="Bücher"');
});

it('rendert native Feldgruppen und Fehlerverweise korrekt', function (): void {
    $fieldset = Blade::render('<x-ui.form-section title="Angaben zum Medium"><x-ui.input label="Titel" name="title" /></x-ui.form-section>');
    $summary = Blade::render(
        '<x-ui.error-summary id="review-errors" :items="$errors" />',
        ['errors' => [
            ['label' => 'Titel fehlt', 'href' => '#title'],
            ['label' => 'Beschreibung prüfen', 'href' => '#description'],
        ]],
    );

    expect($fieldset)->toContain('<fieldset')
        ->toContain('<legend')
        ->toContain('Angaben zum Medium')
        ->and($summary)->toContain('role="alert"')
        ->toContain('aria-labelledby="review-errors-title"')
        ->toContain('href="#title"')
        ->toContain('href="#description"');
});

it('verlinkt Filterchips nur mit vorhandenem Ziel und lässt Datentexte umbrechen', function (): void {
    $static = Blade::render('<x-ui.filter-chip label="Bücher" active />');
    $linked = Blade::render('<x-ui.filter-chip label="Verfügbar" href="/suche" active removable />');
    $row = Blade::render(
        '<x-ui.list-row title="Ein sehr langer Titel über unglaubliche Entdeckungen" density="compact" meta="In 7 Tagen"><x-slot:trailing><span>Ausgeliehen</span></x-slot:trailing></x-ui.list-row>',
    );

    expect($static)->toContain('<span')
        ->toContain('bc-filter-chip--active')
        ->not->toContain('<a ')
        ->and($linked)->toContain('href="/suche"')
        ->toContain('aria-label="Verfügbar entfernen"')
        ->and($row)->toContain('bc-list-row--compact')
        ->toContain('Ein sehr langer Titel über unglaubliche Entdeckungen')
        ->toContain('Ausgeliehen');
});

it('überträgt in der Designlab-Demovorschau keine Daten', function (): void {
    $html = (string) $this->get(route('public.design-lab.components'))->assertOk()->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)
        ->toBe(0);
});
