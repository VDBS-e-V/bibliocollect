<?php

declare(strict_types=1);

namespace App\Modules\Privacy\Services;

use App\Models\User;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronBlockEvent;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\Patrons\Models\PatronStatusEvent;
use App\Modules\Reminders\Models\ReminderLog;

/**
 * Stellt alle zu einem Ausleihkonto gespeicherten Angaben zusammen (Auskunft nach Art. 15 DSGVO).
 * Was nach der Aufbewahrungsfrist anonymisiert wurde, ist der Person nicht mehr zuzuordnen und fehlt hier.
 */
final class PatronDataExport
{
    /** @return array<string, mixed> */
    public function export(Patron $patron): array
    {
        $patron->loadMissing('schoolClass');
        $user = User::query()->where('patron_id', $patron->getKey())->with('roleAssignments')->first();

        return [
            'erstellt_am' => now()->toIso8601String(),
            'hinweis' => 'Ausleihen, Vormerkungen und Ereignisse werden drei Jahre nach Abschluss anonymisiert und stehen danach nicht mehr in dieser Auskunft.',
            'ausleihkonto' => [
                'bibliotheksnummer' => $patron->library_number,
                'art' => $patron->kind->value,
                'status' => $patron->status->value,
                'vorname' => $patron->first_name,
                'nachname' => $patron->last_name,
                'geburtsdatum' => $patron->birth_date->toDateString(),
                'email' => $patron->email,
                'klasse' => $patron->schoolClass?->name,
                'austritt_am' => $patron->leaving_on?->toDateString(),
                'gesperrt_seit' => $patron->blocked_at?->toIso8601String(),
                'sperrgrund' => $patron->blocked_reason,
                'angelegt_am' => $patron->created_at?->toIso8601String(),
            ],
            'onlinekonto' => $user instanceof User ? [
                'name' => $user->name,
                'email' => $user->email,
                'email_bestaetigt_am' => $user->email_verified_at?->toIso8601String(),
                'rollen' => $user->roleAssignments->pluck('role_key')->values()->all(),
                'erinnerungen_per_mail' => (bool) $user->reminders_enabled,
                'deaktiviert_am' => $user->disabled_at?->toIso8601String(),
            ] : null,
            'ausleihen' => Loan::query()
                ->where('patron_id', $patron->getKey())
                ->with('copy.edition.title')
                ->orderBy('checked_out_at')
                ->get()
                ->map(static fn (Loan $loan): array => [
                    'titel' => $loan->copy->edition->title->preferred_title,
                    'barcode' => $loan->copy->barcode,
                    'ausgeliehen_am' => $loan->checked_out_at->toIso8601String(),
                    'faellig_am' => $loan->due_on->toDateString(),
                    'zurueckgegeben_am' => $loan->returned_at?->toIso8601String(),
                    'verlaengert' => $loan->renewal_count,
                ])->all(),
            'vormerkungen' => Reservation::query()
                ->where('patron_id', $patron->getKey())
                ->with('title')
                ->orderBy('requested_at')
                ->get()
                ->map(static fn (Reservation $reservation): array => [
                    'titel' => $reservation->title->preferred_title,
                    'status' => $reservation->status->value,
                    'vorgemerkt_am' => $reservation->requested_at->toIso8601String(),
                    'abgeschlossen_am' => $reservation->closed_at?->toIso8601String(),
                ])->all(),
            'ausweise' => PatronCard::query()
                ->where('patron_id', $patron->getKey())
                ->orderBy('assigned_at')
                ->get()
                ->map(static fn (PatronCard $card): array => [
                    'nummer' => $card->number,
                    'status' => $card->status->value,
                    'zugeordnet_am' => $card->assigned_at?->toIso8601String(),
                    'gesperrt_am' => $card->blocked_at?->toIso8601String(),
                ])->all(),
            'sperren' => PatronBlockEvent::query()
                ->where('patron_id', $patron->getKey())
                ->orderBy('created_at')
                ->get()
                ->map(static fn (PatronBlockEvent $event): array => [
                    'aktion' => $event->action,
                    'grund' => $event->reason,
                    'am' => $event->created_at?->toIso8601String(),
                ])->all(),
            'statuswechsel' => PatronStatusEvent::query()
                ->where('patron_id', $patron->getKey())
                ->orderBy('created_at')
                ->get()
                ->map(static fn (PatronStatusEvent $event): array => [
                    'von' => $event->from_status,
                    'nach' => $event->to_status,
                    'wirksam_am' => $event->effective_on?->toDateString(),
                ])->all(),
            'erinnerungen' => $user instanceof User
                ? ReminderLog::query()->where('user_id', $user->getKey())->orderBy('sent_at')->get()
                    ->map(static fn (ReminderLog $log): array => ['art' => $log->kind, 'gesendet_am' => $log->sent_at->toIso8601String()])->all()
                : [],
        ];
    }
}
