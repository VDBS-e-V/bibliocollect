<?php

declare(strict_types=1);

namespace App\Modules\Reminders\Console;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Reminders\Models\ReminderLog;
use App\Modules\Reminders\Notifications\LibraryReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class SendRemindersCommand extends Command
{
    protected $signature = 'reminders:send {--limit=100 : Höchstzahl Mails je Lauf (der Rest kommt beim nächsten Lauf)}';

    protected $description = 'Schickt Erinnerungen: Rückgabe bald fällig, überfällig und vorgemerkter Titel abholbereit, an Onlinekonten oder an die Adresse am Ausleihkonto.';

    private int $limit = 100;

    private int $sent = 0;

    public function handle(BusinessClock $clock): int
    {
        $today = $clock->now()->startOfDay();
        $soonDays = max(0, (int) config('reminders.due_soon_days', 2));
        $interval = max(1, (int) config('reminders.overdue_interval_days', 7));
        $this->limit = max(1, (int) $this->option('limit'));
        $this->sent = 0;

        $loans = Loan::query()
            ->whereNull('returned_at')
            ->whereDate('due_on', '<=', $today->addDays($soonDays)->toDateString())
            ->with(['copy.edition.title', 'patron'])
            ->orderBy('id')
            ->get();

        foreach ($loans as $loan) {
            $recipient = $this->recipientFor($loan->patron);

            if ($recipient === null) {
                continue;
            }

            $due = CarbonImmutable::parse($loan->due_on->toDateString(), $today->getTimezone());
            $title = $loan->copy->edition->title->preferred_title;

            if ($due->lessThan($today)) {
                $daysOverdue = (int) $due->diffInDays($today);
                $stage = (string) intdiv($daysOverdue - 1, $interval);
                $this->send($recipient, 'loan_overdue', (string) $loan->getKey(), $stage, new LibraryReminder('loan_overdue', $title, $due->format('d.m.Y'), $daysOverdue, $recipient['name']));
            } else {
                $this->send($recipient, 'loan_due_soon', (string) $loan->getKey(), '', new LibraryReminder('loan_due_soon', $title, $due->format('d.m.Y'), 0, $recipient['name']));
            }
        }

        $ready = Reservation::query()
            ->where('status', ReservationStatus::Ready->value)
            ->with(['title', 'patron'])
            ->orderBy('id')
            ->get();

        foreach ($ready as $reservation) {
            $recipient = $reservation->patron !== null ? $this->recipientFor($reservation->patron) : null;

            if ($recipient !== null) {
                $this->send($recipient, 'reservation_ready', (string) $reservation->getKey(), '', new LibraryReminder(
                    'reservation_ready',
                    $reservation->title->preferred_title,
                    $reservation->pickup_until?->format('d.m.Y') ?? '—',
                    0,
                    $recipient['name'],
                ));
            }
        }

        $this->info("{$this->sent} Erinnerung(en) verschickt.");

        return self::SUCCESS;
    }

    /**
     * Wer bekommt die Mail? Ein bestätigtes Onlinekonto, sonst die Adresse am Ausleihkonto. Hat jemand die Erinnerungen
     * im Onlinekonto abgeschaltet, bleibt es dabei. Am Ausleihkonto schaltet das Personal sie je Konto ab.
     *
     * @return array{user: ?User, patron: Patron, email: ?string, name: ?string}|null
     */
    private function recipientFor(Patron $patron): ?array
    {
        $user = User::query()->where('patron_id', $patron->getKey())->whereNotNull('email_verified_at')->first();

        if ($user instanceof User) {
            return $user->reminders_enabled ? ['user' => $user, 'patron' => $patron, 'email' => null, 'name' => null] : null;
        }

        $email = is_string($patron->email) ? trim($patron->email) : '';

        if ($patron->status !== PatronStatus::Active || ! $patron->reminders_enabled || $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return ['user' => null, 'patron' => $patron, 'email' => $email, 'name' => $patron->first_name];
    }

    /**
     * Schickt eine Erinnerung höchstens einmal je Gegenstand und Stufe.
     *
     * @param  array{user: ?User, patron: Patron, email: ?string, name: ?string}  $recipient
     */
    private function send(array $recipient, string $kind, string $subjectId, string $stage, LibraryReminder $notification): void
    {
        if ($this->sent >= $this->limit) {
            return;
        }

        try {
            $log = ReminderLog::query()->create([
                'user_id' => $recipient['user']?->getKey(),
                'patron_id' => $recipient['user'] === null ? (string) $recipient['patron']->getKey() : null,
                'kind' => $kind,
                'subject_id' => $subjectId,
                'stage' => $stage,
                'sent_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return;
        }

        try {
            if ($recipient['user'] instanceof User) {
                $recipient['user']->notify($notification);
            } else {
                Notification::route('mail', [(string) $recipient['email'] => $recipient['patron']->displayName()])->notify($notification);
            }
        } catch (Throwable $exception) {
            // Nicht zugestellt: Eintrag zurücknehmen, damit der nächste Lauf es erneut versucht.
            $log->delete();
            report($exception);

            return;
        }

        $this->sent++;
    }
}
