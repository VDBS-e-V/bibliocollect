<?php

declare(strict_types=1);

namespace App\Modules\School\DTOs;

final readonly class SchoolYearData
{
    public function __construct(
        public string $name,
        public string $startsOn,
        public string $endsOn,
    ) {}
}
