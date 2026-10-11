<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt die universellen Interaktionsbausteine im Designlab', function (): void {
    $response = $this->get(route('public.design-lab.components'))
        ->assertOk()
        ->assertSee('Aktionsmenü, Popover und Tooltip')
        ->assertSee('Inhalte in echten Dialogen')
        ->assertSee('Rückmeldungen, die nicht im Weg stehen')
        ->assertSee('Laden ohne erfundene Prozentwerte')
        ->assertSee('data-ui-dialog-open="bc-ui-demo-information"', false)
        ->assertSee('data-ui-dialog-open="bc-ui-demo-warning"', false)
        ->assertSee('data-ui-toast-dismiss', false)
        ->assertSee('data-ui-dismissable', false)
        ->assertSee('bc-spinner', false);

    $html = (string) $response->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[@id="bc-ui-demo-information" and self::dialog]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="bc-ui-demo-warning" and self::dialog]')->length)->toBe(1)
        ->and($xpath->query('//dialog[@open]')->length)->toBe(0)
        ->and($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bc-core-gallery ")]//form')->length)->toBe(0);
});

it('rendert ein Aktionsmenü mit echten Links und gesperrten Einträgen ohne Fake-Aktion', function (): void {
    $html = Blade::render(
        '<x-ui.action-menu label="Weitere Optionen" :items="$items" density="compact" align="end" />',
        ['items' => [
            ['label' => 'Formular', 'href' => '/form'],
            ['divider' => true],
            ['label' => 'Gesperrt', 'disabled' => true],
        ]],
    );

    expect($html)
        ->toContain('<details')
        ->toContain('data-ui-dismissable')
        ->toContain('bc-action-menu--compact')
        ->toContain('bc-action-menu--end')
        ->toContain('href="/form"')
        ->toContain('aria-disabled="true"')
        ->not->toContain('role="menu"');
});

it('rendert Tooltip und Popover mit korrekter Semantik und IDs', function (): void {
    $tip = Blade::render('<x-ui.tooltip id="example-tooltip" label="ISBN" text="Eine kurze Erklärung." />');
    $pop = Blade::render('<x-ui.popover label="Information"><p>Weiterführende Information</p></x-ui.popover>');

    expect($tip)
        ->toContain('aria-describedby="example-tooltip"')
        ->toContain('id="example-tooltip"')
        ->toContain('role="tooltip"')
        ->toContain('Eine kurze Erklärung.')
        ->and($pop)
        ->toContain('<details')
        ->toContain('data-ui-dismissable')
        ->toContain('Weiterführende Information');
});

it('erzeugt einen zugänglichen Dialog und zwei Toast-Semantiken', function (): void {
    $dialog = Blade::render('<x-ui.dialog id="review-dialog" title="Details" description="Eine Erklärung."><p>Inhalt</p></x-ui.dialog>');
    $error = Blade::render('<x-ui.toast title="Fehler" message="Eine Beschreibung." variant="error" />');
    $success = Blade::render('<x-ui.toast title="Fertig" message="Ein Hinweis." variant="success" />');

    expect($dialog)
        ->toContain('<dialog')
        ->toContain('id="review-dialog"')
        ->toContain('aria-labelledby="review-dialog-title"')
        ->toContain('aria-describedby="review-dialog-description"')
        ->toContain('data-ui-dialog-close')
        ->and($error)
        ->toContain('role="alert"')
        ->toContain('data-ui-toast-dismiss')
        ->and($success)
        ->toContain('role="status"')
        ->toContain('bc-ui-toast--success');
});

it('hält den Ladeindikator lesbar und ohne erfundene Prozente', function (): void {
    $html = Blade::render('<x-ui.spinner label="Bitte warten" size="lg" />');

    expect($html)
        ->toContain('role="status"')
        ->toContain('Bitte warten')
        ->toContain('bc-spinner--lg')
        ->not->toContain('50 %');
});
