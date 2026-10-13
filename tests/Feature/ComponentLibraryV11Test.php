<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt K11 als vier neue Komponenten im bestehenden Designlab', function (): void {
    $response = $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Komplexe Such- und Fehlerzustände verständlich auflösen')
        ->assertSee('Lange Detailseiten ohne verschachtelte Tab-Reiter erschließen')
        ->assertSee('Mehrere Prüfbereiche ohne starren Schrittzwang überblicken')
        ->assertSee('Änderungen vor einer Entscheidung klar nachvollziehen')
        ->assertSee('bc-result-assistance--compact', false)
        ->assertSee('bc-section-index--compact', false)
        ->assertSee('bc-review-overview--compact', false)
        ->assertSee('bc-change-review--compact', false);

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.(string) $response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[@id="bc-core-result-assistance"]//section[contains(@class, "bc-result-assistance")]')->length)->toBe(3)
        ->and($xpath->query('//*[@id="bc-core-section-index"]//nav[contains(@class, "bc-section-index")]')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-review-overview"]//section[contains(@class, "bc-review-overview")]')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-change-review"]//section[contains(@class, "bc-change-review")]')->length)->toBe(2)
        ->and($xpath->query('//*[@id="bc-core-section-index"]//a[@href="#bc-core-change-review"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-core-review-overview"]//a[@href="#bc-core-step-actions"]')->length)->toBe(1);
});

it('verwendet im Ergebnishilfe-Pattern bestehende Empty-State- und Alert-Bausteine', function (): void {
    $empty = Blade::render(
        '<x-ui.result-assistance query="<script>unschön</script>" :suggestions="$suggestions" density="compact" title="Keine Treffer" />',
        ['suggestions' => [
            ['label' => 'Suche ändern', 'href' => '/katalog?q=andere'],
            ['label' => 'Gesperrt', 'disabled' => true, 'href' => '/nicht-erreichbar'],
        ]],
    );
    $error = Blade::render('<x-ui.result-assistance state="error" title="Fehler" description="Abfrage fehlgeschlagen" />');
    $unavailable = Blade::render('<x-ui.result-assistance state="unavailable" title="Nicht verfügbar" />');

    expect($empty)->toContain('bc-empty-state')
        ->toContain('bc-result-assistance--compact')
        ->toContain('href="/katalog?q=andere"')
        ->toContain('aria-disabled="true"')
        ->toContain('&lt;script&gt;unschön&lt;/script&gt;')
        ->not->toContain('href="/nicht-erreichbar"')
        ->and($error)->toContain('bc-alert--error')
        ->toContain('role="alert"')
        ->not->toContain('bc-empty-state')
        ->and($unavailable)->toContain('bc-alert--warning')
        ->toContain('Dieser Bereich steht momentan nicht zur Verfügung.');
});

it('rendert in der Abschnittsnavigation nur echte Anker und kennzeichnet die aktive Position', function (): void {
    $html = Blade::render(
        '<x-ui.section-index :items="$items" current="#details" label="Zu den Details" />',
        ['items' => [
            ['label' => 'Details', 'href' => '#details'],
            ['label' => 'Weitere', 'href' => '#weitere'],
            ['label' => 'Gesperrt', 'disabled' => true, 'href' => '#gesperrt'],
        ]],
    );

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//nav[@aria-label="Zu den Details" and @data-ui-section-index]')->length)->toBe(1)
        ->and($xpath->query('//ol/li')->length)->toBe(3)
        ->and($xpath->query('//a[@href="#details" and @aria-current="location"]')->length)->toBe(1)
        ->and($xpath->query('//a[@href="#weitere"]')->length)->toBe(1)
        ->and($xpath->query('//a[@href="#gesperrt"]')->length)->toBe(0)
        ->and($xpath->query('//*[@aria-disabled="true"]')->length)->toBe(1);
});

it('zeigt nichtlineare Prüfstände inklusive Textstatus und gesperrten Bereichen', function (): void {
    $html = Blade::render(
        '<x-ui.review-overview :items="$items" density="compact" />',
        ['items' => [
            ['title' => 'Erledigt', 'status' => 'complete', 'href' => '#done'],
            ['title' => 'Aktuell', 'status' => 'current', 'href' => '#current'],
            ['title' => 'Fehler', 'status' => 'attention'],
            ['title' => 'Gesperrt', 'status' => 'pending', 'disabled' => true, 'href' => '#no'],
        ]],
    );

    expect($html)->toContain('bc-review-overview--compact')
        ->toContain('1 von 4 Bereichen geprüft')
        ->toContain('Geprüft')
        ->toContain('In Bearbeitung')
        ->toContain('Prüfung nötig')
        ->toContain('Noch offen')
        ->toContain('aria-current="step"')
        ->toContain('href="#done"')
        ->not->toContain('href="#no"')
        ->not->toContain('bc-process-stepper');
});

it('stellt vorher-nachher-Werte samt leeren Werten und unveränderten Feldern zugänglich dar', function (): void {
    $html = Blade::render(
        '<x-ui.change-review :items="$items" beforeLabel="Alt" afterLabel="Neu" density="compact" />',
        ['items' => [
            ['label' => 'Langer Titel', 'before' => 'Kurztitel', 'after' => 'Ein sehr langer deutscher Buchtitel zwischen zwei Zeilen'],
            ['label' => 'Standort', 'before' => null, 'after' => 'Regal B', 'status' => 'added'],
            ['label' => 'Kategorie', 'before' => 'Buch', 'after' => 'Buch'],
            ['label' => 'Notiz', 'before' => 'Entfällt', 'after' => null, 'status' => 'removed'],
        ]],
    );
    $empty = Blade::render('<x-ui.change-review :items="[]" />');
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//section[contains(@class, "bc-change-review--compact")]')->length)->toBe(1)
        ->and($xpath->query('//ul/li')->length)->toBe(4)
        ->and($xpath->query('//dl/div/dt')->length)->toBe(8)
        ->and($xpath->query('//dl/div/dd')->length)->toBe(8)
        ->and($html)->toContain('Geändert')
        ->toContain('Hinzugefügt')
        ->toContain('Unverändert')
        ->toContain('Entfernt')
        ->toContain('Ein sehr langer deutscher Buchtitel')
        ->and($empty)->toContain('0 Felder')
        ->toContain('Keine Vergleichsdaten vorhanden.');
});
