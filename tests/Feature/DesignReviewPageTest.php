<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('zeigt alle zwölf Designvorlagen und gemeinsame Komponenten in einer lokalen Vorschauseite', function (): void {
    expect(app()->environment('testing'))->toBeTrue();

    $response = $this->get(route('public.design-review'))
        ->assertOk()
        ->assertSee('Alle Vorlagen.')
        ->assertSee('fiktive Beispieldaten')
        ->assertSee('Gemeinsame Bausteine');

    $html = (string) $response->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    foreach ([
        'startseite', 'suche', 'detail', 'listen', 'formulare', 'portal',
        'scan', 'assistent', 'sammlungen', 'dashboard', 'hilfeseite', 'druck',
        'bausteine',
    ] as $section) {
        expect($xpath->query('//main//section[@id="'.$section.'"]')->length)->toBe(1);
    }

    expect($xpath->query('//main//h1')->length)->toBe(1)
        ->and($xpath->query('//main//section[contains(@class, "bc-review-section")]')->length)->toBe(13)
        ->and($xpath->query('//main//button[not(@disabled)]')->length)->toBe(1); // globaler Theme-Toggle
});

it('zeigt alle vier Beispiel-Covervarianten ohne produktive Datenbankeinträge', function (): void {
    $html = $this->get(route('public.design-review'))->assertOk()->getContent();

    foreach (['plum', 'mint', 'ink', 'sand'] as $variant) {
        expect($html)->toContain('bc-library-cover__art--'.$variant);
    }
});
