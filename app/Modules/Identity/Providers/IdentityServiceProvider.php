<?php

declare(strict_types=1);

namespace App\Modules\Identity\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        ResetPassword::createUrlUsing(static fn (object $user, string $token): string => route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));

        VerifyEmail::toMailUsing(static fn (object $user, string $url): MailMessage => (new MailMessage)
            ->subject('Bitte bestätige deine E-Mail-Adresse')
            ->greeting('Hallo'.(is_string($user->name ?? null) && $user->name !== '' ? ' '.$user->name : '').',')
            ->line('Bitte **bestätige deine E-Mail-Adresse**, damit du dein **Onlinekonto** der Schulbibliothek nutzen kannst.')
            ->action('E-Mail-Adresse bestätigen', $url)
            ->line('Wenn du kein Konto angelegt hast, **musst du nichts tun**.'));

        ResetPassword::toMailUsing(static fn (object $user, string $token): MailMessage => (new MailMessage)
            ->subject('Neues Passwort für BiblioCollect')
            ->greeting('Hallo,')
            ->line('Du hast ein **neues Passwort** für dein Onlinekonto angefordert.')
            ->action('Passwort festlegen', route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]))
            ->line('Der Link gilt **60 Minuten**. Wenn du das nicht angefordert hast, **musst du nichts tun**.'));
    }
}
