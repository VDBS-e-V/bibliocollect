<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Events;

final readonly class PatronDeparted
{
    public function __construct(
        public string $patronId,
        public string $effectiveOn,
    ) {}
}
