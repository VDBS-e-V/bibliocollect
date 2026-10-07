<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Foundation\Mail\AlertMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Meldet Betriebsprobleme per Mail an die Administration (ALERT_EMAIL). Dieselbe Meldung geht höchstens einmal je
 * Sperrfrist hinaus, damit ein Dauerfehler kein Postfach füllt. Ohne Adresse bleibt es beim Logeintrag.
 */
final class AlertService
{
    /** @param list<string> $lines */
    public function notify(string $key, string $subject, array $lines): bool
    {
        $address = config('hosting.alert_email');
        $minutes = max(1, (int) config('hosting.alert_throttle_minutes', 30));

        if (! is_string($address) || trim($address) === '') {
            Log::warning('Betriebsmeldung (keine ALERT_EMAIL gesetzt): '.$subject);

            return false;
        }

        try {
            // Nur wer die Sperre als Erstes setzt, darf senden.
            if (! Cache::add('alert.sent.'.sha1($key), now()->toIso8601String(), now()->addMinutes($minutes))) {
                return false;
            }

            Mail::to(trim($address))->send(new AlertMail($subject, $lines));
        } catch (Throwable $exception) {
            Log::warning('Betriebsmeldung konnte nicht gesendet werden: '.$exception->getMessage());

            return false;
        }

        return true;
    }
}
