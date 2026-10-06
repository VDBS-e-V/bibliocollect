<?php

declare(strict_types=1);

namespace App\Modules\Reminders\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Kurze Erinnerung an die eigene Ausleihe oder Vormerkung. Enthält nur Angaben zu den Medien der empfangenden Person. */
final class LibraryReminder extends Notification
{
    public function __construct(
        public readonly string $kind,
        public readonly string $titleName,
        public readonly string $date,
        public readonly int $daysOverdue = 0,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->greeting('Hallo!');

        return match ($this->kind) {
            'loan_due_soon' => $message
                ->subject('Erinnerung: Rückgabe bald fällig')
                ->line("„{$this->titleName}“ ist am {$this->date} fällig.")
                ->line('Du kannst die Ausleihe im Portal verlängern, solange niemand den Titel vorgemerkt hat.'),
            'loan_overdue' => $message
                ->subject('Erinnerung: Rückgabe überfällig')
                ->line("„{$this->titleName}“ war am {$this->date} fällig, das sind {$this->daysOverdue} Tage.")
                ->line('Bitte gib das Medium in der Bibliothek zurück.'),
            default => $message
                ->subject('Dein vorgemerkter Titel liegt bereit')
                ->line("„{$this->titleName}“ liegt zur Abholung für dich bereit, bis zum {$this->date}.")
                ->line('Danach geht das Exemplar an die nächste Person weiter.'),
        };
    }
}
