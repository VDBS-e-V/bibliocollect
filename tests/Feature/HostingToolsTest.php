<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;

uses(RefreshDatabase::class);

const SETUP_TOKEN = 'ein-ausreichend-langes-setup-token';

function withHostingTokens(): void
{
    config(['hosting.setup_token' => SETUP_TOKEN, 'hosting.cron_token' => 'ein-ausreichend-langes-cron-token']);

    // Die Routen werden beim Start geladen; für den Test erneut registrieren.
    app('router')->setRoutes(new RouteCollection);
    require base_path('routes/maintenance.php');
    app('router')->getRoutes()->refreshNameLookups();
}

it('offers no setup or cron address without a long token', function (): void {
    $this->get('/_setup')->assertNotFound();
    $this->get('/_cron/irgendwas')->assertNotFound();
});

it('runs migrations, creates the first management account once and checks the installation', function (): void {
    withHostingTokens();

    $this->get('/_setup')->assertOk()->assertSee('BiblioCollect einrichten');

    $this->post('/_setup/migrate', ['token' => 'falsch'])->assertForbidden();
    $this->post('/_setup/migrate', ['token' => SETUP_TOKEN])->assertOk()->assertSee('Migrationen ausgeführt');
    $this->post('/_setup/doctor', ['token' => SETUP_TOKEN])->assertOk()->assertSee('Prüfung abgeschlossen');

    $this->post('/_setup/admin', ['token' => SETUP_TOKEN, 'name' => 'Erste Person', 'email' => 'erste@example.org', 'password' => 'kurz'])
        ->assertStatus(422);

    $this->post('/_setup/admin', ['token' => SETUP_TOKEN, 'name' => 'Erste Person', 'email' => 'erste@example.org', 'password' => 'ein-langes-passwort-1'])
        ->assertOk()
        ->assertSee('Verwaltungskonto wurde angelegt');

    $user = User::query()->where('email', 'erste@example.org')->firstOrFail();

    expect($user->email_verified_at)->not->toBeNull()
        ->and(UserRoleAssignment::query()->where('user_id', $user->getKey())->where('role_key', 'management')->exists())->toBeTrue();

    // Ein zweites Verwaltungskonto lässt sich über die Einrichtungsseite nicht anlegen.
    $this->post('/_setup/admin', ['token' => SETUP_TOKEN, 'name' => 'Zweite Person', 'email' => 'zweite@example.org', 'password' => 'ein-langes-passwort-2'])
        ->assertStatus(409);

    expect(User::query()->where('email', 'zweite@example.org')->exists())->toBeFalse();
});

it('accepts the cron key in a header as well', function (): void {
    withHostingTokens();

    $this->get('/_cron')->assertNotFound();
    $this->withHeader('X-Api-Key', 'falsch-falsch-falsch-falsch-falsch')->get('/_cron')->assertNotFound();

    $this->withHeader('X-Api-Key', 'ein-ausreichend-langes-cron-token')->get('/_cron')
        ->assertOk()
        ->assertSee('Warteschlange abgearbeitet');

    $this->withHeader('Authorization', 'Bearer ein-ausreichend-langes-cron-token')->post('/_cron')
        ->assertOk();
});

it('runs the cron job through the web address only with the right token', function (): void {
    withHostingTokens();

    $this->get('/_cron/falsches-token-falsches-token')->assertNotFound();

    $this->get('/_cron/ein-ausreichend-langes-cron-token')
        ->assertOk()
        ->assertSee('Warteschlange abgearbeitet');
});
