<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('zeigt die neu gestaltete Startseite mit funktionierender Katalogsuche und Schnellzugriffen', function (): void {
    $response = $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('bc-public-home-stage', false)
        ->assertSee('Katalog durchsuchen')
        ->assertSee('Medien finden')
        ->assertSee('Buchreihen')
        ->assertSee('Buchwunsch abgeben')
        ->assertSee('Öffnungszeiten');

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.(string) $response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//main//h1')->length)->toBe(1);

    $forms = $xpath->query('//main//form[@role="search"]');
    expect($forms->length)->toBe(1);

    $form = $forms->item(0);
    expect($form instanceof DOMElement)->toBeTrue();
    expect($form->getAttribute('method'))->toBe('get')
        ->and($form->getAttribute('action'))->toContain('/katalog');

    $search = $xpath->query('.//input[@type="search" and @name="q"]', $form);
    expect($search->length)->toBe(1);
});
