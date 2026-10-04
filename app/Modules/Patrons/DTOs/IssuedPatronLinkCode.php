<?php

declare(strict_types=1);

namespace App\Modules\Patrons\DTOs;

use Carbon\CarbonImmutable;

final readonly class IssuedPatronLinkCode
{
    public function __construct(
        public string $code,
        public CarbonImmutable $expiresAt,
    ) {}
}
