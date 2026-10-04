<?php

declare(strict_types=1);

namespace App\Modules\Identity\DTOs;

final readonly class LinkablePatron
{
    public function __construct(
        public string $id,
        public string $displayName,
        public string $kind,
    ) {}
}
