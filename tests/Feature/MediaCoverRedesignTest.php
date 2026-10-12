<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('zeigt für Medien ohne Cover ein individuelles, titelbasiertes Ersatzcover', function (): void {
    $view = '<x-library.media-cover :src="null" title="Zwischen den Zeilen" author="Lena Beispiel" />';

    $first = Blade::render($view);
    $second = Blade::render($view);

    expect($first)->toContain('bc-library-cover__art--')
        ->toContain('Zwischen den Zeilen')
        ->toContain('Lena Beispiel')
        ->not->toContain('catalog-cover-placeholder.svg')
        ->and($first)->toBe($second);
});

it('bevorzugt vorhandene lokale Coverbilder und rendert keinen Ersatzcovertext', function (): void {
    $html = Blade::render('<x-library.media-cover src="/storage/covers/beispiel.webp" title="Mein Buch" author="Test" />');

    expect($html)->toContain('/storage/covers/beispiel.webp')
        ->toContain('bc-library-cover__image')
        ->not->toContain('bc-library-cover__art--')
        ->not->toContain('bc-library-cover__brand');
});

it('ersetzt auch den bisherigen neutralen Platzhalter durch ein gestaltetes Cover', function (): void {
    $html = Blade::render('<x-library.media-cover src="/brand/vdbs/catalog-cover-placeholder.svg" title="Ein Testtitel" />');

    expect($html)->toContain('Ein Testtitel')
        ->toContain('bc-library-cover__art--')
        ->not->toContain('<img');
});

it('rendert Header und Footer mit den zentralen Einstiegen', function (): void {
    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('Deine nächste')
        ->assertSee('Geschichte')
        ->assertSee('wartet.')
        ->assertSee('bc-masthead__tools', false)
        ->assertSee('bc-footer-grid', false)
        ->assertSee('Katalog durchsuchen')
        ->assertSee('Buchwunsch abgeben')
        ->assertSee('Weil Schule uns alle angeht!');
});
