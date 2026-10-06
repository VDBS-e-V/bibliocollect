<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;

/**
 * Entscheidet, ob Vormerkungen die Verlängerung einer Ausleihe verhindern: Sobald jemand auf den Titel wartet,
 * soll das ausgeliehene Exemplar nach der Rückgabe weitergegeben und nicht erneut verlängert werden.
 */
class ReservationBlockChecker
{
    public function blocksRenewal(Loan $loan, Copy $copy): bool
    {
        $titleId = Edition::query()->whereKey($copy->edition_id)->value('title_id');

        if (! is_string($titleId)) {
            return false;
        }

        return Reservation::query()
            ->where('title_id', $titleId)
            ->where('status', ReservationStatus::Waiting->value)
            ->exists();
    }
}
