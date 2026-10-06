<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets public catalog visitors choose results per page and jump directly to a page', function (): void {
    foreach (range(1, 45) as $number) {
        $title = Title::query()->create([
            'preferred_title' => sprintf('Paging Öffentlich %02d', $number),
            'sort_title' => sprintf('Paging Öffentlich %02d', $number),
        ]);
        Edition::query()->create(['title_id' => $title->getKey()]);
    }

    $this->get(route('public.catalog.index', [
        'per_page' => 10,
        'page' => 2,
    ]))
        ->assertOk()
        ->assertSee('Paging Öffentlich 11')
        ->assertDontSee('Paging Öffentlich 01')
        ->assertDontSee('Paging Öffentlich 21')
        ->assertSee('Ergebnisse:')
        ->assertSee('11–20 von 45')
        ->assertSee('Ergebnisse pro Seite')
        ->assertSee('von 5');

    $this->get(route('public.catalog.index', ['per_page' => 100]))
        ->assertOk()
        ->assertSee('Paging Öffentlich 45')
        ->assertSee('1–45 von 45');
});

it('rejects unsupported catalog page sizes', function (): void {
    $this->get(route('public.catalog.index', ['per_page' => 25]))
        ->assertRedirect()
        ->assertSessionHasErrors('per_page');
});

it('keeps active catalog filters in the pagination links of both surfaces', function (): void {
    foreach (range(1, 25) as $number) {
        $title = Title::query()->create([
            'preferred_title' => sprintf('Filtertreffer %02d', $number),
            'sort_title' => sprintf('Filtertreffer %02d', $number),
        ]);
        Edition::query()->create([
            'title_id' => $title->getKey(),
            'media_type' => 'book',
            'language_code' => 'de',
        ]);
    }

    $response = $this->get(route('public.catalog.index', [
        'q' => 'Filtertreffer',
        'media_type' => 'book',
        'language_code' => 'de',
        'per_page' => 10,
    ]))->assertOk();

    $html = $response->getContent();

    expect($html)->toBeString();
    $html = (string) $html;

    // Der Vor-/Zurück-Pfeil muss die aktiven Filter mitführen, sonst bricht
    // der Filterkontext beim Seitenwechsel still weg.
    expect($html)->toContain('page=2')
        ->and($html)->toContain('q=Filtertreffer')
        ->and($html)->toContain('media_type=book')
        ->and($html)->toContain('language_code=de');

    // Die Seitenformulare reichen die Filter als Hidden-Inputs weiter.
    expect($html)->toContain('name="q"')
        ->and($html)->toContain('name="media_type"');

    $this->get(route('public.catalog.index', [
        'q' => 'Filtertreffer',
        'media_type' => 'book',
        'per_page' => 10,
        'page' => 2,
    ]))
        ->assertOk()
        ->assertSee('Filtertreffer 11')
        ->assertDontSee('Filtertreffer 01')
        ->assertSee('11–20 von 25');
});
