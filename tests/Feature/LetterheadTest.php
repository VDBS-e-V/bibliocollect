<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function letterheadUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, 'staff');

    return $user;
}

it('ships the letterhead images as A4 at 300 dpi', function (): void {
    foreach (['briefpapier-farbe.png', 'briefpapier-sw.png'] as $file) {
        [$width, $height] = getimagesize(public_path('brand/vdbs/'.$file));

        expect([$width, $height])->toBe([2481, 3508]);
    }
});

it('puts the colour letterhead on every page that can be printed by default', function (): void {
    $staff = letterheadUser();

    foreach ([route('public.home'), route('pos.home'), route('pos.reports.class-loans'), route('pos.statistics')] as $url) {
        $this->actingAs($staff)->get($url)->assertOk()
            ->assertSee('<img class="bc-letterhead" src="/brand/vdbs/briefpapier-farbe.png"', false)
            ->assertSee('bc-has-letterhead', false);
    }
});

it('can switch the letterhead to black and white or off', function (): void {
    config(['foundation.letterhead' => 'sw']);
    $this->get(route('public.home'))->assertSee('briefpapier-sw.png', false)->assertDontSee('briefpapier-farbe.png', false);

    config(['foundation.letterhead' => 'aus']);
    $this->get(route('public.home'))->assertDontSee('briefpapier', false)->assertDontSee('bc-has-letterhead', false);
});

it('keeps the letterhead out of the screen layout and away from card and label sheets', function (): void {
    $css = (string) file_get_contents(resource_path('css/patterns/letterhead.css'));

    expect($css)->toContain('.bc-letterhead {')->toContain('display: none')
        ->and($css)->toContain('@media print')
        ->and($css)->toContain('box-decoration-break: clone');

    // Ausweis- und Etikettenbögen haben ein eigenes Layout ohne den Seitenrahmen.
    foreach (['cards-print', 'copies-print'] as $view) {
        expect((string) file_get_contents(resource_path('views/pages/surfaces/pos/labels/'.$view.'.blade.php')))->not->toContain('x-app-shell');
    }
});
