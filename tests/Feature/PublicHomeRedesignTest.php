<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('zeigt die neu gestaltete Startseite mit funktionierender Katalogsuche und Schnellzugriffen', function (): void {
    $response = $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('bc-home-hero', false)
        ->assertSee('Katalog durchsuchen')
        ->assertSee('Medien finden')
        ->assertSee('Buchreihen')
        ->assertSee('Buchwunsch abgeben')
        ->assertSee('Öffnungszeiten')
        ->assertDontSee('bc-home-layout', false);

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.(string) $response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//main//h1')->length)->toBe(1);

    // Struktur entspricht der abgestimmten HTML-Referenz mit genau fünf Hauptbereichen.
    $sections = $xpath->query('//main//div[contains(concat(" ", normalize-space(@class), " "), " bc-library-home ")]/section');
    $sectionNames = [];

    foreach ($sections as $section) {
        if ($section instanceof DOMElement) {
            $sectionNames[] = explode(' ', $section->getAttribute('class'))[0];
        }
    }

    expect($sectionNames)->toBe([
        'bc-home-hero',
        'bc-home-actions',
        'bc-home-discovery',
        'bc-home-editorial',
        'bc-home-info',
    ]);

    $forms = $xpath->query('//main//form[@role="search"]');
    expect($forms->length)->toBe(1);

    $form = $forms->item(0);
    expect($form instanceof DOMElement)->toBeTrue();
    expect($form->getAttribute('method'))->toBe('get')
        ->and($form->getAttribute('action'))->toContain('/katalog');

    $search = $xpath->query('.//input[@type="search" and @name="q"]', $form);
    expect($search->length)->toBe(1);
});
