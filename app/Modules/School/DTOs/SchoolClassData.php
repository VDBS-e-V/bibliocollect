<?php

declare(strict_types=1);

namespace App\Modules\School\DTOs;

final readonly class SchoolClassData
{
    public function __construct(
        public string $name,
        public int $gradeLevel,
        public bool $isActive,
    ) {}
}
