<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt Exemplarzeilen, Personenauswahl und Scan-Rückmeldungen in der aktiven Galerie', function (): void {
    $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Eine Exemplarzeile für alle Arbeitsansichten')
        ->assertSee('Personenauswahl als zugängliche Auswahlkarte')
        ->assertSee('Farbige Rückmeldungen nach dem Scannen')
        ->assertSee('bc-copy-row--compact', false)
        ->assertSee('bc-patron-choice--compact', false)
        ->assertSee('bc-scan-feedback--error', false)
        ->assertSee('Exemplar erkannt')
        ->assertSee('Barcode nicht gefunden');

    $html = (string) $this->get(route('public.design-lab.components'))->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[@id="bc-core-patron-choice"]//fieldset')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-patron-choice"]//input[@type="radio"]')->length)->toBe(5)
        ->and($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)->toBe(0);
});

it('setzt Exemplarstatus und 7-Tage-Frist per vorhandenen Komponenten zusammen', function (): void {
    $row = Blade::render(
        '<x-library.copy-row title="Der besonders lange Titel von einem Buch" barcode="BC-00042" location="Regal B" availability="on-loan" :dueDays="7" density="compact" />',
    );
    $unknown = Blade::render('<x-library.copy-row title="Ohne Status" barcode="BC-00001" />');

    expect($row)
        ->toContain('bc-copy-row--compact')
        ->toContain('Der besonders lange Titel von einem Buch')
        ->toContain('BC-00042')
        ->toContain('<dt>Barcode</dt>')
        ->toContain('<dt>Standort</dt>')
        ->toContain('bc-availability--on-loan')
        ->toContain('bc-due-status--soon')
        ->toContain('Rückgabe in 7 Tagen')
        ->and($unknown)
        ->toContain('Status unbekannt')
        ->not->toContain('bc-due-status');
});

it('rendert barrierefrei beschriftete Personenauswahl mit getrennten Gruppen und Disabled', function (): void {
    $option = Blade::render(
        '<x-library.patron-choice name="review-person" value="p1" displayName="Mara Beispiel" identifier="Ausweis 42" detail="Aktiv" checked />',
    );
    $disabled = Blade::render(
        '<x-library.patron-choice name="review-person" value="p2" displayName="Robin Muster" disabled density="compact" />',
    );

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$option, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    $input = $xpath->query('//input[@type="radio"]')->item(0);

    expect($input)->not->toBeNull()
        ->and($input->getAttribute('name'))->toBe('review-person')
        ->and($input->getAttribute('value'))->toBe('p1')
        ->and($input->hasAttribute('checked'))->toBeTrue()
        ->and($input->getAttribute('aria-describedby'))->not->toBe('')
        ->and($option)->toContain('Mara Beispiel')
        ->toContain('Ausweis 42')
        ->and($disabled)->toContain('disabled')
        ->toContain('bc-patron-choice--compact');
});

it('verwendet für Scanrückmeldungen farbige Symbole und semantische Statusrollen', function (): void {
    $success = Blade::render('<x-library.scan-feedback title="Erkannt" message="Beispielmedium gefunden" barcode="BC-12" status="success" />');
    $warning = Blade::render('<x-library.scan-feedback title="Prüfen" status="warning" compact />');
    $error = Blade::render('<x-library.scan-feedback title="Nicht gefunden" status="error" />');

    expect($success)
        ->toContain('role="status"')
        ->toContain('bc-scan-feedback--success')
        ->toContain('Barcode:')
        ->toContain('BC-12')
        ->and($warning)
        ->toContain('bc-scan-feedback--warning')
        ->toContain('bc-scan-feedback--compact')
        ->and($error)
        ->toContain('role="alert"')
        ->toContain('bc-scan-feedback--error');
});

it('hält den Scanner bei Fokus und Hover in gleicher äußerer Höhe', function (): void {
    $css = file_get_contents(resource_path('css/patterns/library-data-components.css'));

    expect($css)->toBeString()
        ->toContain('box-sizing:border-box; min-height:calc(3.25rem - 2px)')
        ->toContain('margin:1px; padding:0 .85rem')
        ->toContain('.bc-scan-input__control:is(:hover,:focus-within)')
        ->toContain('min-height:3.25rem')
        ->toContain('margin:0')
        ->toContain('border-width:2px')
        ->toContain('.bc-core-gallery .bc-scan-input .bc-scan-input__field:focus-visible')
        ->not->toContain('.bc-scan-input__control:focus-within { outline:3px');
});
