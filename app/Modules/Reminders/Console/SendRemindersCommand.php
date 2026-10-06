<?php

declare(strict_types=1);

namespace App\Modules\Reminders\Console;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Reminders\Models\ReminderLog;
use App\Modules\Reminders\Notifications\LibraryReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

final class SendRemindersCommand extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Schickt Erinnerungen an verknüpfte Onlinekonten: Rückgabe bald fällig, überfällig und vorgemerkter Titel abholbereit.';

    public function handle(BusinessClock $clock): int
    {
        $today = $clock->now()->startOfDay();
        $soonDays = max(0, (int) config('reminders.due_soon_days', 2));
        $interval = max(1, (int) config('reminders.overdue_interval_days', 7));
        $sent = 0;

        $loans = Loan::query()
            ->whereNull('returned_at')
            ->whereDate('due_on', '<=', $today->addDays($soonDays)->toDateString())
            ->with(['copy.edition.title'])
            ->orderBy('id')
            ->get();

        foreach ($loans as $loan) {
            $user = $this->userFor($loan->patron_id);

            if (! $user instanceof User) {
                continue;
            }

            $due = CarbonImmutable::parse($loan->due_on->toDateString(), $today->getTimezone());
            $title = $loan->copy->edition->title->preferred_title;

            if ($due->lessThan($today)) {
                $daysOverdue = (int) $due->diffInDays($today);
                $stage = (string) intdiv($daysOverdue - 1, $interval);
                $sent += (int) $this->send($user, 'loan_overdue', (string) $loan->getKey(), $stage, new LibraryReminder('loan_overdue', $title, $due->format('d.m.Y'), $daysOverdue));
            } else {
                $sent += (int) $this->send($user, 'loan_due_soon', (string) $loan->getKey(), '', new LibraryReminder('loan_due_soon', $title, $due->format('d.m.Y')));
            }
        }

        $ready = Reservation::query()
            ->where('status', ReservationStatus::Ready->value)
            ->with('title')
            ->orderBy('id')
            ->get();

        foreach ($ready as $reservation) {
            $user = $this->userFor($reservation->patron_id);

            if ($user instanceof User) {
                $sent += (int) $this->send($user, 'reservation_ready', (string) $reservation->getKey(), '', new LibraryReminder(
                    'reservation_ready',
                    $reservation->title->preferred_title,
                    $reservation->pickup_until?->format('d.m.Y') ?? '—',
                ));
            }
        }

        $this->info("{$sent} Erinnerung(en) verschickt.");

        return self::SUCCESS;
    }

    private function userFor(string $patronId): ?User
    {
        return User::query()
            ->where('patron_id', $patronId)
            ->whereNotNull('email_verified_at')
            ->where('reminders_enabled', true)
            ->first();
    }

    /** Schickt eine Erinnerung höchstens einmal je Gegenstand und Stufe. */
    private function send(User $user, string $kind, string $subjectId, string $stage, LibraryReminder $notification): bool
    {
        try {
            $log = ReminderLog::query()->create([
                'user_id' => $user->getKey(),
                'kind' => $kind,
                'subject_id' => $subjectId,
                'stage' => $stage,
                'sent_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        try {
            $user->notify($notification);
        } catch (Throwable $exception) {
            // Nicht zugestellt: Eintrag zurücknehmen, damit der nächste Lauf es erneut versucht.
            $log->delete();
            report($exception);

            return false;
        }

        return true;
    }
}
