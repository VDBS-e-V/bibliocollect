<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Content\Models\ContentBlock;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

function blockUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

it('shows nothing until a block is switched on and then shows it on the home page and in the catalog', function (): void {
    $manager = blockUser('management');

    $this->get(route('public.home'))->assertOk()->assertDontSee('bc-content-notice', false);

    $this->actingAs($manager)->patch(route('administration.blocks.update', ['key' => 'home.notice']), [
        'body' => '<p>Ferien: Die Bibliothek ist <strong>geschlossen</strong>.</p><script>alert(1)</script>',
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $this->get(route('public.home'))->assertOk()->assertSee('bc-content-notice', false)->assertSee('<strong>geschlossen</strong>', false)->assertDontSee('alert(1)', false);
    $this->get(route('public.catalog.index'))->assertOk()->assertDontSee('geschlossen');

    $this->actingAs($manager)->patch(route('administration.blocks.update', ['key' => 'catalog.notice']), ['body' => 'Inventur bis Freitag', 'is_active' => '1']);
    $this->get(route('public.catalog.index'))->assertOk()->assertSee('Inventur bis Freitag');

    expect(AuditEvent::query()->where('action', 'content.block.updated')->count())->toBe(2);

    // Ausschalten: Text bleibt gespeichert, wird aber nicht angezeigt.
    $this->actingAs($manager)->patch(route('administration.blocks.update', ['key' => 'home.notice']), ['body' => '<p>Ferien</p>']);
    $this->get(route('public.home'))->assertDontSee('Ferien');
    expect(ContentBlock::query()->where('key', 'home.notice')->value('body'))->toBe('<p>Ferien</p>');
});

it('respects the time window and the day boundaries', function (): void {
    $block = ContentBlock::query()->create(['key' => 'home.notice', 'body' => '<p>Befristet</p>', 'is_active' => true, 'visible_from' => '2026-12-20', 'visible_until' => '2027-01-06']);

    foreach ([['2026-12-19', false], ['2026-12-20', true], ['2027-01-06', true], ['2027-01-07', false]] as [$day, $expected]) {
        expect($block->isVisibleOn(Carbon::parse($day)))->toBe($expected);
    }

    Carbon::setTestNow('2026-12-25 10:00:00');
    Cache::flush();
    $this->get(route('public.home'))->assertSee('Befristet');

    Carbon::setTestNow('2027-02-01 10:00:00');
    Cache::flush();
    $this->get(route('public.home'))->assertDontSee('Befristet');

    Carbon::setTestNow();
});

it('validates the block form and rejects switching on an empty block', function (): void {
    $manager = blockUser('management');

    $this->actingAs($manager)->patch(route('administration.blocks.update', ['key' => 'home.notice']), ['body' => '<p>&nbsp;</p>', 'is_active' => '1'])->assertSessionHasErrors('body');
    $this->actingAs($manager)->patch(route('administration.blocks.update', ['key' => 'home.notice']), ['body' => 'x', 'visible_from' => '2026-12-31', 'visible_until' => '2026-12-01'])->assertSessionHasErrors('visible_until');
    $this->actingAs($manager)->patch(route('administration.blocks.update', ['key' => 'gibt.es.nicht']), ['body' => 'x'])->assertNotFound();
    $this->actingAs($manager)->get(route('administration.blocks.edit', ['key' => 'gibt.es.nicht']))->assertNotFound();
});

it('lists the blocks for management and keeps them away from everyone else', function (): void {
    $this->actingAs(blockUser('management'))->get(route('administration.blocks.index'))
        ->assertOk()->assertSee('Hinweis auf der Startseite')->assertSee('Hinweis im Katalog')->assertSee('Aus');
    $this->get(route('administration.blocks.edit', ['key' => 'home.notice']))->assertOk()->assertSee('data-rich-text', false);

    foreach (['staff', 'technical_admin', 'student'] as $role) {
        $user = blockUser($role);
        $this->actingAs($user)->get(route('administration.blocks.index'))->assertForbidden();
        $this->actingAs($user)->patch(route('administration.blocks.update', ['key' => 'home.notice']), ['body' => 'x'])->assertForbidden();
    }
});
