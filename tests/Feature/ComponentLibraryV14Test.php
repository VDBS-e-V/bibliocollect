<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt alle vier K14-Komponenten in der bestehenden Galerie', function (): void {
    $this->get(route('public.design-lab.components'))->assertOk()
        ->assertSee('Auswahl auch aus langen Vorschlagslisten schnell finden')
        ->assertSee('Termine über die nativen Browserfelder erfassen')
        ->assertSee('Beginn und Ende als zusammengehörige Uhrzeiten erfassen')
        ->assertSee('Speichern und Abbrechen mit einheitlicher Hierarchie anbieten');
});

it('hat eine nutzbare native Combobox-Basis mit Optionswert und Fehlerbezug', function (): void {
    $html = Blade::render('<x-ui.combobox name="example" label="Auswahl" value="a" error="Ungültig" :options="$options" />', [
        'options' => [['value' => 'a', 'label' => 'Titel A'], ['value' => 'b', 'label' => 'Titel B', 'disabled' => true]],
    ]);
    expect($html)->toContain('data-ui-combobox')->toContain('<select')
        ->toContain('name="example"')->toContain('selected')->toContain('disabled')
        ->toContain('aria-invalid="true"')->toContain('example-error')
        ->toContain('Titel A');
});

it('beschriftet Datum/Uhrzeit und verknüpft Hinweise und Fehler', function (): void {
    $html = Blade::render('<x-ui.date-time-picker name="termin" label="Termin" value="2026-11-14T10:30" error="Falsch" required />');
    expect($html)->toContain('type="datetime-local"')->toContain('name="termin"')
        ->toContain('2026-11-14T10:30')->toContain('termin-error')
        ->toContain('aria-invalid="true"')->toContain('required');
});

it('zeigt das Zeitfenster mit beiden Labels und gruppiertem Fehler', function (): void {
    $html = Blade::render('<x-ui.time-range name="fenster" label="Zeit" from="10:00" to="11:00" error="Ungültiger Zeitraum" />');
    expect($html)->toContain('<fieldset')->toContain('<legend')
        ->toContain('name="fenster_from"')->toContain('name="fenster_to"')
        ->toContain('aria-describedby="fenster-error"')
        ->toContain('Ungültiger Zeitraum');
});

it('benutzt bestehende Buttons im Formularabschluss und stellt Links nur bei Ziel dar', function (): void {
    $html = Blade::render('<x-ui.form-actions submitLabel="Speichern" cancelHref="#cancel" disabled status="Entwurf" />');
    expect($html)->toContain('type="submit"')->toContain('disabled')
        ->toContain('href="#cancel"')->toContain('role="status"')
        ->not->toContain('href=""')->toContain('Speichern');
});
