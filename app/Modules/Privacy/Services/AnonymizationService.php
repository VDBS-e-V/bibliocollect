<?php

declare(strict_types=1);

namespace App\Modules\Privacy\Services;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Models\BookWish;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\LoanTransaction;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Patrons\Enums\CardBlockReason;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronBlockEvent;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\Patrons\Models\PatronStatusEvent;
use App\Modules\Reminders\Models\ReminderLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Anonymisiert abgelaufene Daten. Anonymisiert heißt: Der Datensatz bleibt für Statistiken, verliert aber den Bezug
 * zu Personen (Ausleihkonto, Onlinekonto, handelnde Konten, Namen, Gründe). Der Lauf ist wiederholbar.
 */
final readonly class AnonymizationService
{
    public const MARKER = 'ANON-';

    public function __construct(
        private BusinessClock $clock,
        private AuditRecorder $audit,
    ) {}

    public function cutoff(): CarbonImmutable
    {
        return $this->clock->now()->startOfDay()->subYears(max(1, (int) config('privacy.retention_years', 3)));
    }

    /**
     * @return array{loans: int, reservations: int, transactions: int, wishes: int, patrons: int, accounts: int, audit_events: int, status_events: int, reminders: int}
     */
    public function run(bool $dryRun = false): array
    {
        $cutoff = $this->cutoff();

        $work = function () use ($cutoff, $dryRun): array {
            $counts = [
                'loans' => $this->loans($cutoff, $dryRun),
                'reservations' => $this->reservations($cutoff, $dryRun),
                'transactions' => $this->transactions($cutoff, $dryRun),
                'wishes' => $this->wishes($cutoff, $dryRun),
                'patrons' => 0,
                'accounts' => 0,
                'audit_events' => $this->auditEvents($cutoff, $dryRun),
                'status_events' => $this->patronEvents($cutoff, $dryRun),
                'reminders' => $this->reminders($cutoff, $dryRun),
            ];

            [$counts['patrons'], $counts['accounts']] = $this->patrons($cutoff, $dryRun);

            return $counts;
        };

        if ($dryRun) {
            return $work();
        }

        return DB::transaction(function () use ($work): array {
            $counts = $work();

            if (array_sum($counts) > 0) {
                $this->audit->record(
                    'privacy.anonymization.run',
                    'Abgelaufene Daten anonymisiert.',
                    null,
                    $counts,
                );
            }

            return $counts;
        });
    }

    private function loans(CarbonImmutable $cutoff, bool $dryRun): int
    {
        $query = Loan::query()
            ->whereNotNull('returned_at')
            ->where('returned_at', '<', $cutoff)
            ->where(static function ($inner): void {
                $inner->whereNotNull('patron_id')
                    ->orWhereNotNull('checked_out_by_user_id')
                    ->orWhereNotNull('returned_by_user_id')
                    ->orWhereNotNull('last_renewed_by_user_id');
            });

        $count = $query->count();

        if (! $dryRun && $count > 0) {
            $query->update([
                'patron_id' => null,
                'checked_out_by_user_id' => null,
                'returned_by_user_id' => null,
                'last_renewed_by_user_id' => null,
            ]);
        }

        return $count;
    }

    private function reservations(CarbonImmutable $cutoff, bool $dryRun): int
    {
        $query = Reservation::query()
            ->whereNotIn('status', ReservationStatus::openValues())
            ->whereNotNull('closed_at')
            ->where('closed_at', '<', $cutoff)
            ->where(static function ($inner): void {
                $inner->whereNotNull('patron_id')
                    ->orWhereNotNull('created_by_user_id')
                    ->orWhereNotNull('closed_by_user_id');
            });

        $count = $query->count();

        if (! $dryRun && $count > 0) {
            $query->update(['patron_id' => null, 'created_by_user_id' => null, 'closed_by_user_id' => null]);
        }

        return $count;
    }

    /** Belege verlieren Person, handelndes Konto und die E-Mail-Adresse, an die sie gingen. */
    private function transactions(CarbonImmutable $cutoff, bool $dryRun): int
    {
        $query = LoanTransaction::query()
            ->where('created_at', '<', $cutoff)
            ->where(static function ($inner): void {
                $inner->whereNotNull('patron_id')->orWhereNotNull('created_by_user_id')->orWhereNotNull('emailed_to');
            });

        $count = $query->count();

        if (! $dryRun && $count > 0) {
            $query->update(['patron_id' => null, 'created_by_user_id' => null, 'emailed_to' => null]);
        }

        return $count;
    }

    /** Abgeschlossene Wünsche verlieren nach der Frist die Person; der Titel bleibt als Anschaffungshinweis. */
    private function wishes(CarbonImmutable $cutoff, bool $dryRun): int
    {
        $query = BookWish::query()
            ->where(static function ($inner): void {
                $inner->whereNotNull('patron_id')->orWhereNotNull('contact_name')->orWhereNotNull('contact_email');
            })
            ->whereNotIn('status', WishStatus::openValues())
            ->where('updated_at', '<', $cutoff);

        $count = $query->count();

        if (! $dryRun && $count > 0) {
            $query->update(['patron_id' => null, 'contact_name' => null, 'contact_email' => null, 'decided_by_user_id' => null]);
        }

        return $count;
    }

    private function auditEvents(CarbonImmutable $cutoff, bool $dryRun): int
    {
        $count = 0;

        AuditEvent::query()
            ->where('occurred_at', '<', $cutoff)
            ->where(static function ($inner): void {
                $inner->whereNotNull('actor_user_id')->orWhere('context', 'like', '%patron_id%');
            })
            ->orderBy('id')
            ->chunkById(200, function ($events) use ($dryRun, &$count): void {
                foreach ($events as $event) {
                    $context = $event->context ?? [];
                    $hasPatron = array_key_exists('patron_id', $context);

                    if ($event->actor_user_id === null && ! $hasPatron) {
                        continue;
                    }

                    $count++;

                    if ($dryRun) {
                        continue;
                    }

                    unset($context['patron_id']);

                    $event->forceFill([
                        'actor_user_id' => null,
                        'context' => $context === [] ? null : $context,
                    ])->save();
                }
            });

        return $count;
    }

    private function patronEvents(CarbonImmutable $cutoff, bool $dryRun): int
    {
        $blocks = PatronBlockEvent::query()
            ->where('created_at', '<', $cutoff)
            ->where(static function ($inner): void {
                $inner->whereNotNull('reason')->orWhereNotNull('actor_user_id');
            });

        $statuses = PatronStatusEvent::query()
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('actor_user_id');

        $count = $blocks->count() + $statuses->count();

        if (! $dryRun && $count > 0) {
            $blocks->update(['reason' => null, 'actor_user_id' => null]);
            $statuses->update(['actor_user_id' => null]);
        }

        return $count;
    }

    private function reminders(CarbonImmutable $cutoff, bool $dryRun): int
    {
        $query = ReminderLog::query()->where('sent_at', '<', $cutoff);
        $count = $query->count();

        if (! $dryRun && $count > 0) {
            $query->delete();
        }

        return $count;
    }

    /** @return array{0: int, 1: int} Anzahl anonymisierter Ausleihkonten und Onlinekonten */
    private function patrons(CarbonImmutable $cutoff, bool $dryRun): array
    {
        $patrons = Patron::query()
            ->where('status', PatronStatus::Departed->value)
            ->whereNotNull('leaving_on')
            ->whereDate('leaving_on', '<', $cutoff->toDateString())
            ->where('library_number', 'not like', self::MARKER.'%')
            ->get();

        $accounts = 0;

        foreach ($patrons as $patron) {
            $user = User::query()->where('patron_id', $patron->getKey())->first();

            if ($user instanceof User) {
                $accounts++;
            }

            if ($dryRun) {
                continue;
            }

            $this->anonymizeOne($patron, $user);
        }

        return [$patrons->count(), $accounts];
    }

    /**
     * Anonymisiert ein ausgeschiedenes Ausleihkonto sofort, zum Beispiel bei einem Löschverlangen. Ohne Austritt nicht möglich.
     *
     * @throws \InvalidArgumentException wenn das Konto noch nicht ausgeschieden ist
     */
    public function anonymizePatron(Patron $patron): void
    {
        if ($patron->status !== PatronStatus::Departed) {
            throw new \InvalidArgumentException('Nur ausgeschiedene Ausleihkonten lassen sich anonymisieren. Zuerst den dauerhaften Austritt buchen.');
        }

        if (str_starts_with($patron->library_number, self::MARKER)) {
            throw new \InvalidArgumentException('Dieses Ausleihkonto ist schon anonymisiert.');
        }

        DB::transaction(function () use ($patron): void {
            $user = User::query()->where('patron_id', $patron->getKey())->first();

            $this->anonymizeOne($patron, $user);

            // Adressen, an die Belege gingen, gehören ebenfalls zur Person.
            LoanTransaction::query()->where('patron_id', $patron->getKey())->update(['emailed_to' => null]);

            $this->audit->record('privacy.patron.erased', 'Ausgeschiedenes Ausleihkonto auf Verlangen sofort anonymisiert.', null, ['patron_id' => (string) $patron->getKey()]);
        });
    }

    private function anonymizeOne(Patron $patron, ?User $user): void
    {
        if ($user instanceof User) {
            $user->forceFill([
                'name' => 'Anonymisiert',
                'email' => 'anonymisiert-'.Str::lower((string) Str::ulid()).'@anonym.invalid',
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'patron_id' => null,
            ])->save();
        }

        // Die Ausweisnummer bleibt für immer reserviert, verliert aber den Bezug zur Person.
        PatronCard::query()->where('patron_id', $patron->getKey())->update([
            'patron_id' => null,
            'status' => CardStatus::Blocked->value,
            'block_reason' => CardBlockReason::Withdrawn->value,
            'blocked_at' => $this->clock->now(),
        ]);

        $patron->forceFill([
            'library_number' => self::MARKER.$patron->getKey(),
            'first_name' => 'Anonymisiert',
            'last_name' => 'Anonymisiert',
            // Nur das Geburtsjahr bleibt (1. Januar) für Altersstatistiken.
            'birth_date' => $patron->birth_date->copy()->startOfYear()->toDateString(),
            'email' => null,
            'blocked_reason' => null,
            'school_class_id' => null,
        ])->save();
    }
}
