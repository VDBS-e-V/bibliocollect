<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt K15 als vier neue interaktive Patterns in der Galerie', function (): void {
    $this->get(route('public.design-lab.components'))->assertOk()
        ->assertSee('Navigation und Aktionen gezielt durchsuchen')
        ->assertSee('Sortierbare Datensätze und Auswahl als zusammengesetzte Tabelle')
        ->assertSee('Detailinformationen in einem fokussierten Seitenpanel anzeigen')
        ->assertSee('Komplexe Aktionen erst nach bewusster Prüfung freigeben');
});

it('kombiniert die Command-Palette mit dem vorhandenen Dialog und nur echten Links', function (): void {
    $html = Blade::render('<x-ui.command-palette id="demo-cmd" :items="$items" shortcut />', [
        'items' => [['label' => 'Echter Link', 'href' => '#echt'], ['label' => 'Gesperrt', 'href' => '#falsch', 'disabled' => true]],
    ]);
    expect($html)->toContain('data-ui-command-dialog')->toContain('data-ui-dialog-open="demo-cmd"')
        ->toContain('data-ui-command-shortcut')->toContain('href="#echt"')
        ->not->toContain('href="#falsch"')->toContain('role="searchbox"')->not->toContain('role="menu"');
});

it('hat semantische Tabellenköpfe und deaktivierte native Auswahlfelder', function (): void {
    $html = Blade::render('<x-ui.data-grid selectable :columns="$columns" :rows="$rows" />', [
        'columns' => [['key' => 'name', 'label' => 'Name', 'sortHref' => '#sort', 'direction' => 'ascending']],
        'rows' => [['id' => 'a', 'cells' => ['name' => 'A']], ['id' => 'b', 'disabled' => true, 'cells' => ['name' => 'B']]],
    ]);
    $empty = Blade::render('<x-ui.data-grid :columns="[]" :rows="[]" />');
    expect($html)->toContain('scope="col"')->toContain('aria-sort="ascending"')
        ->toContain('href="#sort"')->toContain('data-ui-grid-select-all')
        ->toContain('data-ui-grid-item')->toContain('disabled')
        ->and($empty)->toContain('Keine Datensätze vorhanden.');
});

it('verwendet im Seitenpanel native Dialogsemantik und vorhandene Close-Hooks', function (): void {
    $html = Blade::render('<x-ui.side-panel id="details" title="Details" description="Beispiel">Text</x-ui.side-panel>');
    expect($html)->toContain('<dialog')->toContain('bc-ui-dialog bc-side-panel')
        ->toContain('aria-labelledby="details-title"')->toContain('aria-describedby="details-description"')
        ->toContain('data-ui-dialog-close');
});

it('verhindert in der ungebundenen Bestätigung echte Aktionen und escaped den Begriff', function (): void {
    $html = Blade::render('<x-ui.confirmation-flow id="check" title="Prüfen" phrase="<TEST>" />');
    expect($html)->toContain('data-ui-confirm-flow')->toContain('data-ui-confirm-phrase')->toContain('name="confirmed"')->toContain('name="confirmation_phrase"')
        ->toContain('&lt;TEST&gt;')->toContain('type="button"')
        ->toContain('data-ui-confirm-submit disabled')
        ->not->toContain('data-ui-confirm-submittable')
        ->not->toContain('action=');
});
