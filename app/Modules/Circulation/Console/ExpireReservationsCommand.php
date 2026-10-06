<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Console;

use App\Foundation\Support\BusinessClock;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Circulation\Services\ReservationQueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ExpireReservationsCommand extends Command
{
    protected $signature = 'circulation:reservations:expire';

    protected $description = 'Beendet abgelaufene Abholfristen und gibt die Exemplare an die nächste Vormerkung weiter.';

    public function handle(BusinessClock $clock, ReservationQueueService $queue): int
    {
        $today = $clock->now()->startOfDay();
        $expired = 0;
        $released = 0;

        $ready = Reservation::query()
            ->where('status', ReservationStatus::Ready->value)
            ->with('readyCopy')
            ->orderBy('id')
            ->get();

        foreach ($ready as $reservation) {
            DB::transaction(function () use ($reservation, $today, $queue, &$expired, &$released): void {
                $locked = Reservation::query()->whereKey($reservation->getKey())->lockForUpdate()->first();

                if (! $locked instanceof Reservation || $locked->status !== ReservationStatus::Ready) {
                    return;
                }

                if ($locked->pickup_until !== null && $locked->pickup_until->startOfDay()->lessThan($today)) {
                    $queue->releaseHold($locked, ReservationStatus::Expired, null);
                    $expired++;

                    return;
                }

                // Das zurückgelegte Exemplar ist nicht mehr ausleihbar (beschädigt, verloren, ausgesondert):
                // die Person rückt in der Warteschlange wieder nach vorn und wartet auf das nächste freie Exemplar.
                if ($locked->readyCopy === null || $locked->readyCopy->status !== CopyStatus::Active) {
                    $locked->forceFill([
                        'status' => ReservationStatus::Waiting,
                        'ready_copy_id' => null,
                        'ready_at' => null,
                        'pickup_until' => null,
                    ])->save();
                    $released++;
                }
            });
        }

        $this->info("{$expired} Vormerkung(en) abgelaufen, {$released} zurück in die Warteschlange.");

        return self::SUCCESS;
    }
}
