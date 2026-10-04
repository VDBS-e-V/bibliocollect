<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class TitleData
{
    public function __construct(
        public string $preferredTitle,
        public ?string $subtitle,
        public ?string $sortTitle,
    ) {}
}
