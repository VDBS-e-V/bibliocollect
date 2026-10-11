<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt universelle Basis-Komponenten und zwei Dichteprofile im lokalen Designlab', function (): void {
    $response = $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Erst die Bausteine.')
        ->assertSee('Zwei Dichten.')
        ->assertSee('Buttons und Aktionen')
        ->assertSee('Eingabefelder und Formularzustände')
        ->assertSee('Status, Rückmeldungen und Leerzustände')
        ->assertSee('Metadaten und Tabellen')
        ->assertSee('Feature Overlap')
        ->assertSee('Process Stepper');

    $html = (string) $response->getContent();

    expect($html)->toContain('id="bc-core-density-comfortable"')
        ->toContain('id="bc-core-density-compact"')
        ->toContain('bc-button--tertiary')
        ->toContain('bc-button--danger')
        ->toContain('bc-empty-state')
        ->toContain('bc-description-list')
        ->toContain('bc-section-title')
        ->toContain('bc-card--compact')
        ->toContain('bc-card--brand');
});

it('rendert Leerzustand, Metadaten und Abschnittstitel als fachneutrale Blade-Komponenten', function (): void {
    $empty = Blade::render('<x-ui.empty-state title="Keine Treffer" description="Bitte Suche ändern." tone="brand" compact />');

    $metadata = Blade::render(
        '<x-ui.description-list density="compact" :items="$items" />',
        ['items' => [
            ['label' => 'Ausgabe', 'value' => '2026'],
            ['label' => 'Autor:in', 'value' => 'Ein besonders langer Name'],
        ]],
    );

    $heading = Blade::render('<x-ui.section-heading title="Medienbestand" eyebrow="Verwaltung" :level="3" compact />');

    expect($empty)->toContain('Keine Treffer')
        ->toContain('bc-empty-state--brand')
        ->toContain('bc-empty-state--compact')
        ->and($metadata)->toContain('<dl')->toContain('<dt>Ausgabe</dt>')->toContain('<dd>2026</dd>')
        ->and($heading)->toContain('<h3')->toContain('Medienbestand')->toContain('bc-section-title--compact');
});

it('erweitert vorhandene Cards und Buttons ohne ihre bisherigen Defaults zu verändern', function (): void {
    $button = Blade::render('<x-ui.button variant="tertiary" size="lg">Mehr anzeigen</x-ui.button>');
    $defaultButton = Blade::render('<x-ui.button>Speichern</x-ui.button>');
    $card = Blade::render('<x-ui.card tone="muted" density="compact">Beispiel</x-ui.card>');
    $defaultCard = Blade::render('<x-ui.card>Normal</x-ui.card>');

    expect($button)->toContain('bc-button--tertiary')->toContain('bc-button--lg')
        ->and($defaultButton)->toContain('bc-button--primary')->not->toContain('bc-button--lg')
        ->and($card)->toContain('bc-card--muted')->toContain('bc-card--compact')
        ->and($defaultCard)->toContain('bc-card')->not->toContain('bc-card--compact');
});

it('enthält keine schreibenden Demoformulare im Komponentenbereich', function (): void {
    $html = (string) $this->get(route('public.design-lab.components'))->assertOk()->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)->toBe(0);
});
