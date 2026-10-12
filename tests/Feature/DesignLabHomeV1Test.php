<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('bietet den neuen Startseitenentwurf nur im Designlab mit eigenständiger Route an', function (): void {
    expect(app()->environment('testing'))->toBeTrue();
    $this->get(route('public.design-lab.home-v1'))->assertOk()
        ->assertSee('Die Startseite als Ganzes.')
        ->assertSee('Seitenentwurf 01')
        ->assertSee('Die Suche öffnet den bestehenden Katalog.')
        ->assertSee('Jedes Buch öffnet eine andere Tür.')
        ->assertSee('Öffnungszeiten und Schließtage')
        ->assertSee('Ältere Startseitenstudie vergleichen');
});

it('verwendet die freigegebenen Komponenten und ausschließlich die echte GET-Katalogsuche', function (): void {
    $html = (string) $this->get(route('public.design-lab.home-v1'))->assertOk()->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    $forms = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-home-v1 ")]//form');

    expect($xpath->query('//*[@id="bc-home-v1-top"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-home-v1-search"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-home-v1-actions"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-home-v1-discovery"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-home-v1-world"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-home-v1-service"]')->length)->toBe(1)
        ->and($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-home-v1 ")]//h1')->length)->toBe(1)
        ->and($forms->length)->toBe(1);

    $form = $forms->item(0);
    expect($form instanceof DOMElement)->toBeTrue()
        ->and($form->getAttribute('method'))->toBe('get')
        ->and($form->getAttribute('action'))->toContain('/katalog')
        ->and($xpath->query('.//input[@name="q" and @type="search"]', $form)->length)->toBe(1)
        ->and($html)->toContain('bc-media-card--compact')
        ->toContain('bc-media-card--comfortable')
        ->toContain('bc-feature-overlap')
        ->toContain('bc-library-quick-action')
        ->toContain('bc-notice-panel')
        ->toContain('bc-back-to-top');
});

it('enthält keine erfundenen Öffnungszeiten oder Scheinlinks für Beispielmedien', function (): void {
    $html = (string) $this->get(route('public.design-lab.home-v1'))->assertOk()->getContent();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($html)->toContain('frei erfundene Demonstrationsdaten')
        ->toContain('Vier fiktive Beispiele')
        ->not->toContain('action="/verwaltung')
        ->and($xpath->query('//*[@id="bc-home-v1-discovery"]//a[contains(@href, "#nicht-vorhanden")]')->length)->toBe(0)
        ->and($xpath->query('//*[@id="bc-home-v1-discovery"]//h3/a')->length)->toBe(0);
});

it('hält die produktive Startseite und den bisherigen Altentwurf unverändert erreichbar', function (): void {
    $this->get(route('public.design-lab.home'))->assertOk()->assertSee('Einladend statt überladen.');
    $this->get(route('public.home'))->assertOk()->assertSee('bc-home-hero', false);
});


it('kombiniert Feature Overlap und beide Wochenplan-Dichten ohne behauptete Live-Zeiten', function (): void {
    $html = (string) $this->get(route('public.design-lab.home-v1'))->assertOk()
        ->assertSee('Geschichten verbinden Menschen.')
        ->assertSee('Ein Blick auf die Woche.')
        ->assertSee('Wenn sich Zeiten ändern.')
        ->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[@id="bc-home-v1-world"]//*[contains(concat(" ", normalize-space(@class), " "), " bc-feature-overlap--mint ")]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-home-v1-service"]//*[contains(concat(" ", normalize-space(@class), " "), " bc-week-schedule ")]')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-home-v1-service"]//*[contains(concat(" ", normalize-space(@class), " "), " bc-week-schedule--compact ")]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-home-v1-service"]//dl[contains(concat(" ", normalize-space(@class), " "), " bc-week-schedule__list ")]//dt')->length)->toBe(10)
        ->and($html)->toContain('frei erfundene Demonstrationsdaten')
        ->toContain('09:00–17:00')
        ->toContain('Ferienöffnungszeiten')
        ->toContain('Teamfortbildung');
});
