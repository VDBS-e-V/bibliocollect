<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

function accountUser(string $role, array $attributes = []): User
{
    $user = User::factory()->create(array_merge(['email_verified_at' => now()], $attributes));
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

it('keeps user account administration for the administration only', function (): void {
    $this->actingAs(accountUser('management'))->get(route('administration.users.index'))->assertOk()->assertSee('Benutzerkonten');

    foreach (['staff', 'technical_admin', 'student_ag_extended', 'student_ag_basic', 'teacher', 'student'] as $role) {
        $this->actingAs(accountUser($role))->get(route('administration.users.index'))->assertForbidden();
    }

    $this->actingAs(accountUser('staff'))->post(route('administration.users.store'), ['name' => 'X', 'email' => 'x@example.invalid', 'roles' => ['management']])->assertForbidden();
});

it('creates an account for staff and invites them with a link instead of a password', function (): void {
    Notification::fake();
    $admin = accountUser('management');

    $this->actingAs($admin)->post(route('administration.users.store'), ['name' => 'Mia Mitarbeit', 'email' => 'Mia@Example.Invalid', 'roles' => ['staff', 'student_ag_extended']])
        ->assertRedirect()->assertSessionHas('user_success');

    $user = User::query()->where('email', 'mia@example.invalid')->firstOrFail();
    expect($user->name)->toBe('Mia Mitarbeit')->and($user->email_verified_at)->toBeNull()->and($user->roleKeys())->toEqualCanonicalizing(['staff', 'student_ag_extended'])
        ->and(AuditEvent::query()->where('action', 'identity.user.created')->count())->toBe(1);

    Notification::assertSentTo($user, ResetPassword::class);

    // Fehler: doppelte Adresse, keine Rolle, unbekannte Rolle, kaputte Adresse.
    $this->actingAs($admin)->post(route('administration.users.store'), ['name' => 'Zweite', 'email' => 'mia@example.invalid', 'roles' => ['staff']])->assertSessionHasErrors('email');
    $this->actingAs($admin)->post(route('administration.users.store'), ['name' => 'Ohne Rolle', 'email' => 'ohne@example.invalid', 'roles' => []])->assertSessionHasErrors('roles');
    $this->actingAs($admin)->post(route('administration.users.store'), ['name' => 'Falsch', 'email' => 'falsch@example.invalid', 'roles' => ['superuser']])->assertSessionHasErrors('roles.0');
    $this->actingAs($admin)->post(route('administration.users.store'), ['name' => 'Mail', 'email' => 'keine-mail', 'roles' => ['staff']])->assertSessionHasErrors('email');
    expect(User::query()->count())->toBe(2);
});

it('lets the invited person set a password and thereby confirms the address', function (): void {
    Notification::fake();
    $admin = accountUser('management');
    $this->actingAs($admin)->post(route('administration.users.store'), ['name' => 'Neu Dabei', 'email' => 'neu@example.invalid', 'roles' => ['staff']]);
    $user = User::query()->where('email', 'neu@example.invalid')->firstOrFail();

    $token = Password::broker()->createToken($user);

    auth()->logout();
    $this->post(route('password.update'), ['token' => $token, 'email' => 'neu@example.invalid', 'password' => 'Ein-gutes-Passwort-7', 'password_confirmation' => 'Ein-gutes-Passwort-7'])->assertRedirect(route('login'));

    expect($user->refresh()->email_verified_at)->not->toBeNull()->and(Hash::check('Ein-gutes-Passwort-7', $user->password))->toBeTrue();

    $this->post(route('login.store'), ['email' => 'neu@example.invalid', 'password' => 'Ein-gutes-Passwort-7'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

it('changes roles and refuses to remove the last administration role', function (): void {
    $admin = accountUser('management');
    $other = accountUser('student_ag_basic');

    $this->actingAs($admin)->get(route('administration.users.edit', ['userId' => $other->getKey()]))->assertOk()->assertSee('Rollen')->assertSee($other->email);
    $this->actingAs($admin)->put(route('administration.users.roles', ['userId' => $other->getKey()]), ['roles' => ['staff']])->assertSessionHas('user_success');
    expect($other->refresh()->roleKeys())->toBe(['staff']);

    // Die einzige Verwaltung kann sich die Rolle nicht nehmen.
    $this->actingAs($admin)->put(route('administration.users.roles', ['userId' => $admin->getKey()]), ['roles' => ['staff']])->assertSessionHasErrors('roles');
    expect($admin->refresh()->roleKeys())->toBe(['management']);

    // Mit einem zweiten Konto in der Verwaltung geht es.
    $this->actingAs($admin)->put(route('administration.users.roles', ['userId' => $other->getKey()]), ['roles' => ['management']])->assertSessionHas('user_success');
    $this->actingAs($admin)->put(route('administration.users.roles', ['userId' => $admin->getKey()]), ['roles' => ['staff']])->assertSessionHas('user_success');
    expect($admin->refresh()->roleKeys())->toBe(['staff']);

    $this->actingAs($other)->put(route('administration.users.roles', ['userId' => $other->getKey()]), ['roles' => ['unbekannt']])->assertSessionHasErrors('roles.0');
});

it('disables an account, ends its sessions and blocks the login until it is enabled again', function (): void {
    $admin = accountUser('management');
    $staff = accountUser('staff', ['password' => Hash::make('Ein-gutes-Passwort-7')]);

    DB::table('sessions')->insert(['id' => 'abc123', 'user_id' => $staff->getKey(), 'ip_address' => '127.0.0.1', 'user_agent' => 'x', 'payload' => 'x', 'last_activity' => time()]);

    $this->actingAs($admin)->post(route('administration.users.disable', ['userId' => $staff->getKey()]), ['reason' => 'nicht mehr an der Schule'])->assertSessionHas('user_success');

    $staff->refresh();
    expect($staff->isEnabled())->toBeFalse()->and($staff->disabled_reason)->toBe('nicht mehr an der Schule')->and(DB::table('sessions')->where('user_id', $staff->getKey())->count())->toBe(0)
        ->and($staff->allowsPermission('catalog.manage'))->toBeFalse();

    auth()->logout();
    $this->post(route('login.store'), ['email' => $staff->email, 'password' => 'Ein-gutes-Passwort-7'])->assertSessionHasErrors();
    $this->assertGuest();

    $this->actingAs($admin)->post(route('administration.users.enable', ['userId' => $staff->getKey()]))->assertSessionHas('user_success');
    expect($staff->refresh()->isEnabled())->toBeTrue()->and($staff->allowsPermission('catalog.manage'))->toBeTrue();

    expect(AuditEvent::query()->whereIn('action', ['identity.user.disabled', 'identity.user.enabled'])->count())->toBe(2);
});

it('protects against locking the administration out', function (): void {
    $admin = accountUser('management');

    $this->actingAs($admin)->post(route('administration.users.disable', ['userId' => $admin->getKey()]))->assertSessionHasErrors('account');
    expect($admin->refresh()->isEnabled())->toBeTrue();

    $second = accountUser('management');
    $this->actingAs($admin)->post(route('administration.users.disable', ['userId' => $second->getKey()]))->assertSessionHas('user_success');

    // Das zweite Konto ist weg, das erste bleibt die letzte Verwaltung.
    $third = accountUser('staff');
    $this->actingAs($admin)->put(route('administration.users.roles', ['userId' => $admin->getKey()]), ['roles' => []])->assertSessionHasErrors('roles');
    expect($third->refresh()->isEnabled())->toBeTrue();
});

it('sends the invitation again and not to disabled accounts', function (): void {
    Notification::fake();
    $admin = accountUser('management');
    $invited = accountUser('staff', ['email_verified_at' => null]);

    $this->actingAs($admin)->get(route('administration.users.edit', ['userId' => $invited->getKey()]))->assertSee('Einladung erneut senden');
    $this->actingAs($admin)->post(route('administration.users.invite', ['userId' => $invited->getKey()]))->assertSessionHas('user_success');
    Notification::assertSentTo($invited, ResetPassword::class);

    $invited->forceFill(['disabled_at' => now()])->save();
    $this->actingAs($admin)->post(route('administration.users.invite', ['userId' => $invited->getKey()]))->assertSessionHasErrors('account');
});

it('lists, searches and filters the accounts', function (): void {
    $admin = accountUser('management', ['name' => 'Anna Verwaltung']);
    accountUser('staff', ['name' => 'Bernd Bibliothek', 'email' => 'bernd@example.invalid']);
    accountUser('student', ['name' => 'Clara Schülerin', 'email' => 'clara@example.invalid']);
    $off = accountUser('staff', ['name' => 'Dora Deaktiviert']);
    $off->forceFill(['disabled_at' => now()])->save();
    accountUser('staff', ['name' => 'Emil Eingeladen', 'email_verified_at' => null]);

    $page = $this->actingAs($admin);

    $page->get(route('administration.users.index'))->assertSee('Anna Verwaltung')->assertSee('Bernd Bibliothek')->assertSee('Clara Schülerin');
    $page->get(route('administration.users.index', ['q' => 'bernd']))->assertSee('Bernd Bibliothek')->assertDontSee('Clara Schülerin');
    $page->get(route('administration.users.index', ['rolle' => 'student']))->assertSee('Clara Schülerin')->assertDontSee('Bernd Bibliothek');
    $page->get(route('administration.users.index', ['status' => 'deaktiviert']))->assertSee('Dora Deaktiviert')->assertDontSee('Bernd Bibliothek');
    $page->get(route('administration.users.index', ['status' => 'eingeladen']))->assertSee('Emil Eingeladen')->assertDontSee('Bernd Bibliothek');

    $page->get(route('administration.users.create'))->assertOk()->assertSee('Konto anlegen und einladen')->assertSee('Mitarbeiter:in');
});
