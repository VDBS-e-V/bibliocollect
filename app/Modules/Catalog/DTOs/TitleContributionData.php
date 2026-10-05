<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class TitleContributionData
{
    public function __construct(
        public string $displayName,
        public ?string $sortName,
        public string $roleKey,
        public int $position,
    ) {}
}
