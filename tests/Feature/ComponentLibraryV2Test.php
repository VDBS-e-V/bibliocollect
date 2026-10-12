<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt zehn weitere wiederverwendbare Komponenten im aktuellen Designlab', function (): void {
    $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Tabs, Breadcrumbs und Seitenwechsel')
        ->assertSee('Checkboxen, Radios und Schalter')
        ->assertSee('Fortschritt und Ladezustände')
        ->assertSee('Ein- und ausklappbare Abschnitte')
        ->assertSee('Beispiel: 24 von 40 Einträgen')
        ->assertSee('Designvorschau ohne Suchanfrage')
        ->assertSee('bc-breadcrumbs', false)
        ->assertSee('bc-tabs', false)
        ->assertSee('bc-pagination', false)
        ->assertSee('bc-disclosure', false)
        ->assertSee('bc-choice', false)
        ->assertSee('bc-switch', false)
        ->assertSee('bc-progress', false)
        ->assertSee('bc-skeleton', false)
        ->assertSee('bc-search-field', false);
});

it('markiert frühere Musterseiten sichtbar als ältere Entwürfe', function (): void {
    $this->get(route('public.design-lab'))
        ->assertOk()
        ->assertSee('Älterer Entwurf')
        ->assertSee('Aktueller Arbeitsbereich: die öffentliche Startseite V1.');

    foreach ([
        'public.design-lab.home',
        'public.design-lab.media',
        'public.design-lab.wizard',
        'public.design-lab.dashboard',
        'public.design-lab.portal',
    ] as $route) {
        $this->get(route($route))
            ->assertOk()
            ->assertSee('Älterer Entwurf')
            ->assertSee('Diese Musterseite ist eine frühere Designstudie.')
            ->assertSee(route('public.design-lab.components'), false);
    }

    $this->get(route('public.design-review'))
        ->assertOk()
        ->assertSee('Älterer Entwurf');
});

it('rendert Navigation als echte Links und aktiven Zustand statt ARIA-Tabs ohne Steuerung', function (): void {
    $tabs = Blade::render(
        '<x-ui.tabs :items="$items" label="Beispielnavigation" density="compact" />',
        ['items' => [
            ['label' => 'Übersicht', 'href' => '/a', 'active' => true],
            ['label' => 'Weitere', 'href' => '/b'],
            ['label' => 'Gesperrt', 'disabled' => true],
        ]],
    );

    $breadcrumbs = Blade::render(
        '<x-ui.breadcrumbs :items="$items" />',
        ['items' => [
            ['label' => 'Start', 'href' => '/'],
            ['label' => 'Medien', 'href' => '/medien'],
            ['label' => 'Details'],
        ]],
    );

    expect($tabs)
        ->toContain('aria-label="Beispielnavigation"')
        ->toContain('aria-current="page"')
        ->toContain('bc-tabs--compact')
        ->toContain('aria-disabled="true"')
        ->not->toContain('role="tab"')
        ->and($breadcrumbs)
        ->toContain('<ol')
        ->toContain('aria-current="page"')
        ->toContain('href="/medien"');
});

it('rendert native Auswahlfelder mit Hilfetexten und barrierefreier Zuordnung', function (): void {
    $checkbox = Blade::render('<x-ui.checkbox name="favorit" label="Favorit" hint="Ein Hinweis" checked />');
    $radio = Blade::render('<x-ui.radio name="layout" value="liste" label="Liste" checked />');
    $switch = Blade::render('<x-ui.switch name="hints" label="Hinweise anzeigen" hint="Schaltet Hinweise um" />');

    expect($checkbox)
        ->toContain('type="checkbox"')
        ->toContain('checked')
        ->toContain('aria-describedby="favorit-')
        ->and($radio)
        ->toContain('type="radio"')
        ->toContain('name="layout"')
        ->toContain('value="liste"')
        ->and($switch)
        ->toContain('role="switch"')
        ->toContain('aria-describedby="hints-hint"')
        ->toContain('bc-switch__track');
});

it('beschreibt Fortschritt und Loading ohne erfundene Daten', function (): void {
    $progress = Blade::render('<x-ui.progress label="Importprüfung" :value="3" :max="5" />');
    $skeleton = Blade::render('<x-ui.skeleton :lines="4" />');
    $disclosure = Blade::render('<x-ui.disclosure title="Details anzeigen" open><p>Mehr Informationen</p></x-ui.disclosure>');

    expect($progress)
        ->toContain('<progress')
        ->toContain('max="5"')
        ->toContain('value="3"')
        ->toContain('60 %')
        ->and($skeleton)
        ->toContain('aria-hidden="true"')
        ->toContain('bc-skeleton__line')
        ->and(substr_count($skeleton, 'class="bc-skeleton__line"'))->toBe(4)
        ->and($disclosure)
        ->toContain('<details')
        ->toContain('<summary')
        ->toContain('Mehr Informationen');
});
