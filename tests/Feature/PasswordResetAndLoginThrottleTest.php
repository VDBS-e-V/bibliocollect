<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('shows the forgot password page and links to it from the login', function (): void {
    $this->get(route('login'))->assertOk()->assertSee('Passwort vergessen?');
    $this->get(route('password.request'))->assertOk()->assertSee('Link schicken');
});

it('sends a reset link by mail and answers identically for unknown addresses', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'person@example.org', 'email_verified_at' => now()]);

    $known = $this->post(route('password.email'), ['email' => 'person@example.org']);
    $unknown = $this->post(route('password.email'), ['email' => 'niemand@example.org']);

    $known->assertRedirect(route('password.request'));
    $unknown->assertRedirect(route('password.request'));
    expect(session('status'))->toContain('Wenn zu dieser Adresse ein Konto besteht');

    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertCount(1);
});

it('renders the reset mail in German with a link to the reset page', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'person@example.org', 'email_verified_at' => now()]);

    $this->post(route('password.email'), ['email' => 'person@example.org']);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        return $mail->subject === 'Neues Passwort für BiblioCollect'
            && str_contains($mail->actionUrl, '/passwort-zuruecksetzen/')
            && str_contains($mail->actionUrl, urlencode('person@example.org'));
    });
});

it('does not send links to disabled accounts', function (): void {
    Notification::fake();
    User::factory()->create(['email' => 'gesperrt@example.org', 'disabled_at' => now()]);

    $this->post(route('password.email'), ['email' => 'gesperrt@example.org'])->assertRedirect(route('password.request'));

    Notification::assertNothingSent();
});

it('sets a new password with a valid token only once', function (): void {
    $user = User::factory()->create(['email' => 'person@example.org', 'email_verified_at' => now(), 'password' => Hash::make('altes-passwort-1')]);
    $token = Password::createToken($user);

    $this->get(route('password.reset', ['token' => $token, 'email' => 'person@example.org']))->assertOk()->assertSee('Neues Passwort festlegen');

    $this->post(route('password.update'), ['token' => $token, 'email' => 'person@example.org', 'password' => 'zu-kurz', 'password_confirmation' => 'zu-kurz'])
        ->assertSessionHasErrors('password');

    $this->post(route('password.update'), ['token' => 'falsches-token', 'email' => 'person@example.org', 'password' => 'neues-passwort-2', 'password_confirmation' => 'neues-passwort-2'])
        ->assertSessionHasErrors('email');

    $this->post(route('password.update'), ['token' => $token, 'email' => 'person@example.org', 'password' => 'neues-passwort-2', 'password_confirmation' => 'neues-passwort-2'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    expect(Hash::check('neues-passwort-2', $user->fresh()->password))->toBeTrue();

    // Der Link ist verbraucht.
    $this->post(route('password.update'), ['token' => $token, 'email' => 'person@example.org', 'password' => 'drittes-passwort-3', 'password_confirmation' => 'drittes-passwort-3'])
        ->assertSessionHasErrors('email');
});

it('blocks further attempts after five failed logins', function (): void {
    RateLimiter::clear('person@example.org|127.0.0.1');
    User::factory()->create(['email' => 'person@example.org', 'email_verified_at' => now(), 'password' => Hash::make('richtiges-passwort-1')]);

    foreach (range(1, 5) as $attempt) {
        $this->from(route('login'))->post(route('login.store'), ['email' => 'person@example.org', 'password' => 'falsch'])
            ->assertSessionHasErrors('email');
    }

    // Auch das richtige Passwort wird jetzt abgewiesen, bis die Sperre abläuft.
    $this->from(route('login'))->post(route('login.store'), ['email' => 'person@example.org', 'password' => 'richtiges-passwort-1'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(session('errors')->first('email'))->toContain('Zu viele Anmeldeversuche');

    RateLimiter::clear('person@example.org|127.0.0.1');

    $this->post(route('login.store'), ['email' => 'person@example.org', 'password' => 'richtiges-passwort-1'])->assertRedirect();
    $this->assertAuthenticated();
});
