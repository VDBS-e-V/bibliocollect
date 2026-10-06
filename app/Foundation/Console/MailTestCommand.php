<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class MailTestCommand extends Command
{
    protected $signature = 'mail:test {address : Empfängeradresse für die Testnachricht}';

    protected $description = 'Schickt eine Testnachricht, um die Mail-Einstellungen (SMTP) zu prüfen.';

    public function handle(): int
    {
        $address = (string) $this->argument('address');

        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Das ist keine gültige E-Mail-Adresse.');

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');

        try {
            Mail::raw('Das ist eine Testnachricht von BiblioCollect. Wenn du sie liest, funktioniert der Mailversand.', static function ($message) use ($address): void {
                $message->to($address)->subject('BiblioCollect: Testnachricht');
            });
        } catch (Throwable $exception) {
            $this->error('Der Versand ist fehlgeschlagen: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Testnachricht über „{$mailer}“ an {$address} übergeben.");

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn('Der Mailer ist „'.$mailer.'“: Es wurde nichts verschickt, die Nachricht steht nur im Log (storage/logs). Für echten Versand MAIL_MAILER=smtp und die SMTP-Daten in .env setzen.');
        }

        return self::SUCCESS;
    }
}
