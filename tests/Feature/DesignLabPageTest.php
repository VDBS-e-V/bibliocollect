<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('stellt fünf eigenständige Beispielseiten und das Designstudio nur in der Testumgebung bereit', function (): void {
    expect(app()->environment('testing'))->toBeTrue();

    foreach ([
        'public.design-lab' => 'Ein System.',
        'public.design-lab.home' => 'Einladend statt überladen.',
        'public.design-lab.media' => 'Titel, die Raum bekommen.',
        'public.design-lab.wizard' => 'Schritt für Schritt. Ohne Umwege.',
        'public.design-lab.dashboard' => 'Das Wichtige fällt auf.',
        'public.design-lab.components' => 'Einmal entwerfen. Überall verwenden.',
    ] as $route => $expected) {
        $this->get(route($route))
            ->assertOk()
            ->assertSee($expected)
            ->assertSee('fiktive Beispieldaten');
    }
});

it('rendert den Fortschrittstracker mit korrekten Schrittsignalen und aria-current', function (): void {
    $steps = [
        ['label' => 'Datei wählen', 'status' => 'done'],
        ['label' => 'Daten prüfen', 'status' => 'current', 'description' => 'Pflichtangaben'],
        ['label' => 'Bestätigen', 'status' => 'upcoming'],
    ];

    $html = Blade::render('<x-library.process-stepper :steps="$steps" label="Importstatus" />', compact('steps'));

    expect($html)->toContain('aria-current="step"')
        ->toContain('bc-process-stepper__step--done')
        ->toContain('bc-process-stepper__step--current')
        ->toContain('bc-process-stepper__step--upcoming')
        ->toContain('Daten prüfen')
        ->toContain('Importstatus');
});

it('zeigt strukturierte Meldungen mit einer passenden semantischen Rolle', function (): void {
    $error = Blade::render('<x-library.notice-panel variant="error" title="Prüfung fehlgeschlagen">Nochmals prüfen.</x-library.notice-panel>');
    $info = Blade::render('<x-library.notice-panel variant="info" title="Hinweis">Nur ein Beispiel.</x-library.notice-panel>');

    expect($error)->toContain('role="alert"')
        ->toContain('Prüfung fehlgeschlagen')
        ->and($info)->toContain('role="status"')
        ->toContain('Nur ein Beispiel.');
});

it('lässt lange Titel in der Coverkomponente flexibler umbrechen', function (): void {
    $html = Blade::render('<x-library.media-cover title="Die wunderbare und unglaublich aufregende Geschichte von einer Reise in eine unbekannte Welt" variant="sand" />');

    expect($html)->toContain('bc-library-cover--long-title')
        ->toContain('bc-library-cover--very-long-title')
        ->toContain('bc-library-cover__title');
});

it('verwendet keine aktiven Schreibaktionen auf den Demoseiten', function (): void {
    foreach ([
        'public.design-lab.home',
        'public.design-lab.media',
        'public.design-lab.wizard',
        'public.design-lab.dashboard',
        'public.design-lab.components',
    ] as $route) {
        $html = $this->get(route($route))->assertOk()->getContent();

        // Keine Formulare für echte Schreibvorgänge.
        expect($html)->not->toContain('method="POST" action="/ausleihe"')
            ->not->toContain('action="/verwaltung/import"');
    }
});
