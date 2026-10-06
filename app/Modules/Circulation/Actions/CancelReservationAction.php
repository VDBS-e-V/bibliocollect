<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Circulation\Services\ReservationQueueService;
use Illuminate\Support\Facades\DB;

final readonly class CancelReservationAction
{
    public function __construct(
        private ReservationQueueService $queue,
        private AuditRecorder $audit,
    ) {}

    /** Storniert eine offene Vormerkung; ein bereits zurückgelegtes Exemplar geht an die nächste Person in der Warteschlange. */
    public function execute(Reservation $reservation, User $actor): Reservation
    {
        return DB::transaction(function () use ($reservation, $actor): Reservation {
            $locked = Reservation::query()->whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->isOpen()) {
                throw LoanStateConflict::reservationClosed();
            }

            $this->queue->releaseHold($locked, ReservationStatus::Cancelled, (int) $actor->getKey());

            $this->audit->record(
                'circulation.reservation.cancelled',
                'Vormerkung storniert.',
                $locked,
                ['patron_id' => (string) $locked->patron_id, 'title_id' => (string) $locked->title_id],
                (int) $actor->getKey(),
            );

            return $locked->refresh();
        });
    }
}
