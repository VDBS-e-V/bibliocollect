<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Listeners;

use App\Modules\Catalog\Events\CopyBecameAvailable;
use App\Modules\Circulation\Services\ReservationQueueService;

/** Legt ein neues oder wiedergefundenes Exemplar für die erste wartende Vormerkung des Titels zurück. */
final readonly class PromoteReservationsForAvailableCopy
{
    public function __construct(private ReservationQueueService $queue) {}

    public function handle(CopyBecameAvailable $event): void
    {
        $this->queue->promoteForCopy($event->copy->refresh());
    }
}
