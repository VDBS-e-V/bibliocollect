<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Content\Models\ContentPage;
use App\Modules\Content\Services\PageTextRenderer;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pagesUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

it('serves the three information pages publicly with a placeholder notice and footer links', function (): void {
    foreach (['impressum' => 'Impressum', 'datenschutz' => 'Datenschutzerklärung', 'barrierefreiheit' => 'Erklärung zur Barrierefreiheit'] as $slug => $title) {
        $this->get('/'.$slug)->assertOk()->assertSee($title)->assertSee('Noch nicht ausgefüllt');
    }

    $this->get(route('public.catalog.index'))
        ->assertOk()
        ->assertSee('href="'.route('public.page', ['slug' => 'impressum']).'"', false)
        ->assertSee('Datenschutz')
        ->assertSee('Barrierefreiheit');

    $this->get('/unbekannt')->assertNotFound();
});

it('lets management edit a page and drops the placeholder notice', function (): void {
    $manager = pagesUser('management');

    $this->actingAs($manager)->get(route('administration.pages.index'))->assertOk()->assertSee('Platzhalter');

    $this->actingAs($manager)->patch(route('administration.pages.update', ['slug' => 'impressum']), [
        'title' => 'Impressum',
        'body' => "## Anbieter\n\nVDBS e. V.\nBeispielstraße 1\n\nKontakt: info@example.org und https://example.org/kontakt\n\n- Punkt eins\n- Punkt zwei",
    ])->assertRedirect(route('administration.pages.edit', ['slug' => 'impressum']));

    $page = ContentPage::query()->where('slug', 'impressum')->firstOrFail();

    expect($page->is_placeholder)->toBeFalse()
        ->and($page->updated_by_user_id)->toBe($manager->getKey())
        ->and(AuditEvent::query()->where('action', 'content.page.updated')->count())->toBe(1);

    $this->get('/impressum')
        ->assertOk()
        ->assertDontSee('Noch nicht ausgefüllt')
        ->assertSee('<h2>Anbieter</h2>', false)
        ->assertSee('<a href="mailto:info@example.org">info@example.org</a>', false)
        ->assertSee('<li>Punkt zwei</li>', false);
});

it('never executes html typed into a page', function (): void {
    $html = app(PageTextRenderer::class)->render("<script>alert(1)</script>\n\n## <b>Titel</b>\n\n[x](javascript:alert(1)) a@b.de");

    expect($html)->not->toContain('<script')
        ->and($html)->toContain('&lt;script&gt;')
        ->and($html)->toContain('<h2>&lt;b&gt;Titel&lt;/b&gt;</h2>')
        ->and($html)->not->toContain('href="javascript');
});

it('keeps page editing away from everyone but management', function (string $role): void {
    $user = pagesUser($role);

    $this->actingAs($user)->get(route('administration.pages.index'))->assertForbidden();
    $this->actingAs($user)->patch(route('administration.pages.update', ['slug' => 'impressum']), ['title' => 'x', 'body' => 'y'])->assertForbidden();
})->with(['staff', 'technical_admin', 'student_ag_extended', 'student']);

it('validates the page text', function (): void {
    $this->actingAs(pagesUser('management'))
        ->patch(route('administration.pages.update', ['slug' => 'datenschutz']), ['title' => '', 'body' => ''])
        ->assertSessionHasErrors(['title', 'body']);
});
