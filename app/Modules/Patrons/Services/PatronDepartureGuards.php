<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Services;

use App\Modules\Patrons\Contracts\PatronDepartureGuard;
use App\Modules\Patrons\Models\Patron;

final class PatronDepartureGuards
{
    public const TAG = 'patron.departure_guards';

    /** @return list<string> */
    public function blockReasons(Patron $patron): array
    {
        $reasons = [];

        foreach (app()->tagged(self::TAG) as $guard) {
            if ($guard instanceof PatronDepartureGuard) {
                array_push($reasons, ...$guard->blockReasons($patron));
            }
        }

        return $reasons;
    }
}
