<?php

declare(strict_types=1);

it('enthält alle 24 geplanten Bausteine K14 bis K18 genau einmal in der Galerie', function (): void {
    $html = (string) $this->get(route('public.design-lab.components'))->assertOk()->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    $sections = [
        'bc-core-combobox',
        'bc-core-date-time',
        'bc-core-time-range',
        'bc-core-form-actions',
        'bc-core-command-palette',
        'bc-core-data-grid',
        'bc-core-side-panel',
        'bc-core-confirmation-flow',
        'bc-core-media-detail-summary',
        'bc-core-hold-status',
        'bc-core-loan-summary',
        'bc-core-identifier-list',
        'bc-core-location-path',
        'bc-core-contact-preferences',
        'bc-core-audit-entry',
        'bc-core-key-value-editor',
        'bc-core-repeater-field',
        'bc-core-transfer-list',
        'bc-core-import-preview',
        'bc-core-batch-progress',
        'bc-core-skip-links',
        'bc-core-back-to-top',
        'bc-core-shortcut-hint',
        'bc-core-read-more',
    ];

    expect($sections)->toHaveCount(24);
    foreach ($sections as $id) {
        expect($xpath->query('//*[@id="'.$id.'"]')->length)->toBe(1);
        expect($xpath->query('//nav[contains(@class, "bc-core-gallery__jump")]//a[@href="#'.$id.'"]')->length)->toBe(1);
    }
});

it('behält die gesamte Galerie ohne echte Formulare oder POST-Aktionen', function (): void {
    $html = (string) $this->get(route('public.design-lab.components'))->assertOk()->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)->toBe(0)
        ->and($xpath->query('//*[@id="bc-core-top"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-core-skip-links"]//a[@href="#bc-core-top"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-core-back-to-top"]//a[@href="#bc-core-top"]')->length)->toBe(1);
});
