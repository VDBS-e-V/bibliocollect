<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use Carbon\CarbonImmutable;
use DateTimeZone;

final class BusinessClock
{
    public function timezone(): DateTimeZone
    {
        return new DateTimeZone((string) config('foundation.business_timezone', 'Europe/Berlin'));
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }
}
