<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Services;

use App\Modules\Patrons\Models\PatronCard;

final class PatronCardLookup
{
    public function find(string $code): ?PatronCard
    {
        $code = trim($code);

        return $code === '' ? null : PatronCard::query()->with('patron.schoolClass')->where('number', $code)->first();
    }
}
