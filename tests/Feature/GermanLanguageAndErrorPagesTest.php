<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

it('defaults to German and ships German validation, auth and mail texts', function (): void {
    expect(app()->getLocale())->toBe('de')->and(config('app.fallback_locale'))->toBe('de');

    $validator = Validator::make(['email' => 'kaputt', 'name' => ''], ['email' => ['required', 'email'], 'name' => ['required']]);

    expect($validator->errors()->first('email'))->toBe('Das Feld „E-Mail-Adresse“ muss eine gültige E-Mail-Adresse sein.')
        ->and($validator->errors()->first('name'))->toBe('Bitte fülle das Feld „Name“ aus.')
        ->and(__('auth.failed'))->toContain('Zugangsdaten')
        ->and(__('passwords.sent'))->toContain('Link')
        ->and(__('All rights reserved.'))->toBe('Alle Rechte vorbehalten.')
        ->and(__('Regards,'))->toBe('Viele Grüße');
});

it('sends the verification mail in German', function (): void {
    $user = User::factory()->create(['name' => 'Mia Muster']);
    $mail = (new VerifyEmail)->toMail($user);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('Bitte bestätige deine E-Mail-Adresse')
        ->and($html)->toContain('Hallo Mia Muster,')
        ->and($html)->toContain('E-Mail-Adresse bestätigen')
        ->and($html)->not->toContain('Verify Email Address')
        ->and($html)->not->toContain('Regards');
});

it('shows branded German error pages', function (int $code, string $heading): void {
    $html = view('errors.'.$code)->render();

    expect($html)->toContain($heading)->toContain('Fehler '.$code)->toContain('Zur Startseite')->toContain('lang="de"');
})->with([
    [403, 'Kein Zugriff'],
    [404, 'Seite nicht gefunden'],
    [419, 'Die Sitzung ist abgelaufen'],
    [429, 'Zu viele Anfragen'],
    [500, 'Etwas ist schiefgelaufen'],
    [503, 'Gerade nicht erreichbar'],
]);

it('answers unknown pages with the German 404 page', function (): void {
    $this->get('/gibt-es-nicht-'.bin2hex(random_bytes(4)))->assertNotFound()->assertSee('Seite nicht gefunden');
});
