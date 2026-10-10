<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Content\Models\ContentPage;
use App\Modules\Content\Services\PageContentRenderer;
use App\Modules\Content\Services\RichTextSanitizer;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps editor formatting and removes everything dangerous', function (): void {
    $sanitizer = app(RichTextSanitizer::class);

    $clean = $sanitizer->sanitize('<h2>Titel</h2><p>Text <strong>fett</strong> <em>kursiv</em> <a href="https://example.org/x" onclick="x()" target="_blank">Link</a></p><ul><li>Eins</li></ul>'
        .'<table><thead><tr><th scope="col">A</th></tr></thead><tbody><tr><td colspan="2">B</td></tr></tbody></table><blockquote>Zitat</blockquote><hr>');

    expect($clean)->toContain('<h2>Titel</h2>')->toContain('<strong>fett</strong>')->toContain('<li>Eins</li>')
        ->toContain('<th scope="col">A</th>')->toContain('colspan="2"')->toContain('<blockquote>Zitat</blockquote>')
        ->toContain('rel="noopener noreferrer"')->not->toContain('onclick')->not->toContain('target=');
});

it('drops scripts, styles, event handlers and unsafe links', function (): void {
    $sanitizer = app(RichTextSanitizer::class);

    $dirty = '<script>alert(1)</script><p style="color:red" class="x" onmouseover="alert(2)">Hallo</p>'
        .'<a href="javascript:alert(3)">böse</a><a href="data:text/html;base64,AAAA">daten</a><a href="mailto:a@b.de">Mail</a><a href="/intern">intern</a>'
        .'<img src="x" onerror="alert(4)"><iframe src="https://evil.example"></iframe><form action="/x"><input></form><svg onload="alert(5)"></svg>'
        .'<style>body{display:none}</style><object data="x"></object>';

    $clean = $sanitizer->sanitize($dirty);

    expect($clean)->not->toContain('<script')->not->toContain('alert(')->not->toContain('style=')->not->toContain('class=')
        ->not->toContain('onmouseover')->not->toContain('javascript:')->not->toContain('data:text')->not->toContain('<img')
        ->not->toContain('<iframe')->not->toContain('<form')->not->toContain('<svg')->not->toContain('<style')->not->toContain('<object')
        ->and($clean)->toContain('<p>Hallo</p>')->toContain('href="mailto:a&#64;b.de"')->toContain('href="/intern"');
});

it('keeps umlauts and recognises empty content', function (): void {
    $sanitizer = app(RichTextSanitizer::class);

    expect($sanitizer->sanitize('<p>Öffnungszeiten für Schüler:innen &amp; Eltern</p>'))->toContain('Öffnungszeiten für Schüler:innen &amp; Eltern')
        ->and($sanitizer->hasText('<p>&nbsp;</p><p></p>'))->toBeFalse()
        ->and($sanitizer->hasText('<p>Text</p>'))->toBeTrue();
});

it('renders legacy plain text bodies and html bodies the same safe way', function (): void {
    $renderer = app(PageContentRenderer::class);

    $legacy = $renderer->toHtml("## Anbieter\n\nVDBS e. V.\n\n- Punkt eins\n- Punkt zwei");
    expect($legacy)->toContain('<h2>Anbieter</h2>')->toContain('<li>Punkt zwei</li>');

    expect($renderer->looksLikeHtml('<p>Schon HTML</p>'))->toBeTrue()
        ->and($renderer->looksLikeHtml("## Titel\n\nText"))->toBeFalse()
        ->and($renderer->toHtml('<p onclick="x()">Text</p><script>x</script>'))->toBe('<p>Text</p>');
});

it('converts stored legacy page bodies to html once and leaves html alone', function (): void {
    ContentPage::query()->where('slug', 'impressum')->update(['body' => "## Anbieter\n\nVDBS e. V.\nBeispielstraße 1"]);
    ContentPage::query()->where('slug', 'datenschutz')->update(['body' => '<p>Schon <strong>HTML</strong></p>']);

    $migration = require base_path('app/Modules/Content/database/migrations/2026_10_14_100000_convert_page_bodies_to_html.php');
    $migration->up();
    $migration->up();

    expect(ContentPage::query()->where('slug', 'impressum')->value('body'))->toContain('<h2>Anbieter</h2>')->toContain('Beispielstraße 1')
        ->and(ContentPage::query()->where('slug', 'datenschutz')->value('body'))->toBe('<p>Schon <strong>HTML</strong></p>');
});

it('saves sanitized html, shows legacy text as html in the editor and rejects empty content', function (): void {
    $manager = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($manager, 'management');
    ContentPage::query()->where('slug', 'impressum')->update(['body' => "## Alt\n\nText im alten Format"]);

    $this->actingAs($manager)->get(route('administration.pages.edit', ['slug' => 'impressum']))
        ->assertOk()->assertSee('&lt;h2&gt;Alt&lt;/h2&gt;', false)->assertSee('data-rich-text', false);

    $this->actingAs($manager)->patch(route('administration.pages.update', ['slug' => 'impressum']), [
        'title' => 'Impressum',
        'body' => '<h2>Anbieter</h2><p onclick="x()">Text<script>alert(1)</script></p>',
    ])->assertSessionHasNoErrors();

    $body = ContentPage::query()->where('slug', 'impressum')->value('body');
    expect($body)->toContain('<h2>Anbieter</h2>')->not->toContain('script')->not->toContain('onclick');

    $this->get('/impressum')->assertOk()->assertSee('<h2>Anbieter</h2>', false)->assertDontSee('alert(1)', false);

    $this->actingAs($manager)->patch(route('administration.pages.update', ['slug' => 'impressum']), ['title' => 'Impressum', 'body' => '<p>&nbsp;</p><script>x</script>'])
        ->assertSessionHasErrors('body');
});
