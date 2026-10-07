<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;

/** Was spricht dagegen, ein Exemplar jetzt auszusondern? Ausgeliehen oder für jemanden zurückgelegt. */
final class CopyWithdrawalCheck
{
    /** @return list<string> */
    public function problems(Copy $copy): array
    {
        $problems = [];

        if (Loan::query()->where('copy_id', $copy->getKey())->whereNull('returned_at')->exists()) {
            $problems[] = 'ist noch ausgeliehen';
        }

        if (Reservation::query()->where('ready_copy_id', $copy->getKey())->where('status', ReservationStatus::Ready->value)->exists()) {
            $problems[] = 'ist für eine Vormerkung zurückgelegt';
        }

        return $problems;
    }
}
