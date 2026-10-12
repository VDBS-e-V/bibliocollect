<?php

declare(strict_types=1);

use App\Modules\School\Models\LibraryOpeningHour;
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

    // Die neue Live-V1 behält fünf Hauptabschnitte und verwendet darin die freigegebenen Muster.
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

it('zeigt die freigegebene V1-Gestaltung auf der echten Startseitenroute', function (): void {
    $html = (string) $this->get(route('public.home'))->assertOk()
        ->assertSee('Deine nächste')
        ->assertSee('Geschichten verbinden Menschen.')
        ->assertSee('bc-home-v1__hero', false)
        ->assertSee('bc-feature-overlap--mint', false)
        ->assertSee('bc-home-v1__service', false)
        ->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//main//div[contains(concat(" ", normalize-space(@class), " "), " bc-home-live ")]')->length)->toBe(1)
        ->and($xpath->query('//main//section[contains(concat(" ", normalize-space(@class), " "), " bc-home-hero ")]//h1')->length)->toBe(1)
        ->and($xpath->query('//main//section[contains(concat(" ", normalize-space(@class), " "), " bc-home-editorial ")]//*[contains(concat(" ", normalize-space(@class), " "), " bc-feature-overlap--mint ")]')->length)->toBe(1);
});

it('zeigt nur hinterlegte Bibliothekszeiten und erfindet ohne Daten keine Öffnungszeiten', function (): void {
    $this->get(route('public.home'))->assertOk()
        ->assertSee('Die Öffnungszeiten werden noch eingetragen.')
        ->assertDontSee('09:00–17:00')
        ->assertDontSee('Ferienöffnungszeiten');

    LibraryOpeningHour::query()->create([
        'day_of_week' => 1,
        'is_open' => true,
        'opens_at' => '08:00',
        'closes_at' => '15:00',
    ]);

    $html = (string) $this->get(route('public.home'))->assertOk()
        ->assertSee('08:00 bis 15:00 Uhr')
        ->assertSee('Öffnungszeiten der Bibliothek')
        ->assertDontSee('09:00–17:00')
        ->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[@id="oeffnungszeiten"]//*[contains(concat(" ", normalize-space(@class), " "), " bc-week-schedule ")]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="oeffnungszeiten"]//dt[text()="Montag"]')->length)->toBe(1);
});
