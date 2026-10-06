<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Foundation\Support\BusinessClock;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use Illuminate\Support\Facades\DB;

/**
 * Warteschlange je Titel: Wird ein Exemplar frei, rückt die erste berechtigte Vormerkung nach und das
 * Exemplar wird für sie zurückgelegt. Aufrufer müssen in einer Transaktion laufen und das Exemplar gesperrt haben.
 */
final readonly class ReservationQueueService
{
    public function __construct(
        private BusinessClock $clock,
        private CirculationRuleEvaluator $rules,
        private LoanDueDateService $dueDates,
        private AuditRecorder $audit,
    ) {}

    /**
     * Legt das Exemplar für die erste wartende, berechtigte Vormerkung des Titels zurück.
     * Nichts passiert, wenn das Exemplar nicht frei ist oder niemand wartet.
     */
    public function promoteForCopy(Copy $copy): ?Reservation
    {
        return DB::transaction(function () use ($copy): ?Reservation {
            if ($copy->status !== CopyStatus::Active || $this->isLoaned($copy) || $this->isHeld($copy)) {
                return null;
            }

            $edition = Edition::query()->findOrFail($copy->edition_id);

            $waiting = Reservation::query()
                ->where('title_id', $edition->title_id)
                ->where('status', ReservationStatus::Waiting->value)
                ->orderBy('requested_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->with('patron')
                ->get();

            foreach ($waiting as $reservation) {
                if ($this->rules->checkoutViolations($reservation->patron, $copy, $edition, false) !== []) {
                    // Gesperrte, ausgeschiedene oder zu junge Personen überspringen; ihre Vormerkung bleibt bestehen.
                    continue;
                }

                $now = $this->clock->now();

                $reservation->forceFill([
                    'status' => ReservationStatus::Ready,
                    'ready_copy_id' => $copy->getKey(),
                    'ready_at' => $now,
                    'pickup_until' => $this->dueDates->forPickupDeadline($now)->toDateString(),
                ])->save();

                $this->audit->record(
                    'circulation.reservation.ready',
                    "Exemplar {$copy->barcode} für Vormerkung zurückgelegt.",
                    $reservation,
                    ['patron_id' => (string) $reservation->patron_id, 'copy_id' => (string) $copy->getKey(), 'pickup_until' => $reservation->pickup_until?->toDateString()],
                );

                return $reservation;
            }

            return null;
        });
    }

    /**
     * Gibt eine bereitgelegte Vormerkung wieder frei (Storno oder Fristablauf) und gibt das Exemplar an die Nächsten weiter.
     */
    public function releaseHold(Reservation $reservation, ReservationStatus $newStatus, ?int $actorId): ?Reservation
    {
        return DB::transaction(function () use ($reservation, $newStatus, $actorId): ?Reservation {
            $copyId = $reservation->ready_copy_id;

            $reservation->forceFill([
                'status' => $newStatus,
                'closed_at' => $this->clock->now(),
                'closed_by_user_id' => $actorId,
            ])->save();

            if ($newStatus === ReservationStatus::Expired) {
                $this->audit->record(
                    'circulation.reservation.expired',
                    'Abholfrist einer Vormerkung abgelaufen.',
                    $reservation,
                    ['patron_id' => (string) $reservation->patron_id, 'title_id' => (string) $reservation->title_id],
                    $actorId,
                );
            }

            if ($copyId === null) {
                return null;
            }

            $copy = Copy::query()->whereKey($copyId)->lockForUpdate()->first();

            return $copy instanceof Copy ? $this->promoteForCopy($copy) : null;
        });
    }

    /** Hinweis für die Rückgabe: für wen das Exemplar zurückzulegen ist (leer, wenn niemand wartet). */
    public function holdNotice(Copy $copy): string
    {
        $hold = Reservation::query()
            ->where('ready_copy_id', $copy->getKey())
            ->where('status', ReservationStatus::Ready->value)
            ->with('patron')
            ->first();

        if (! $hold instanceof Reservation || $hold->patron === null) {
            return '';
        }

        return ' Das Exemplar bitte für '.$hold->patron->displayName().' ('.$hold->patron->library_number.') zurücklegen, Abholung bis '
            .($hold->pickup_until?->format('d.m.Y') ?? '—').'.';
    }

    public function isHeld(Copy $copy): bool
    {
        return Reservation::query()
            ->where('ready_copy_id', $copy->getKey())
            ->where('status', ReservationStatus::Ready->value)
            ->exists();
    }

    /** Offene Vormerkung, für die dieses Exemplar zurückgelegt ist. */
    public function holdFor(Copy $copy): ?Reservation
    {
        return Reservation::query()
            ->where('ready_copy_id', $copy->getKey())
            ->where('status', ReservationStatus::Ready->value)
            ->first();
    }

    private function isLoaned(Copy $copy): bool
    {
        return Loan::query()
            ->where('copy_id', $copy->getKey())
            ->whereNull('returned_at')
            ->exists();
    }
}
