<?php

declare(strict_types=1);

namespace App\Foundation\Navigation;

final readonly class NavigationItem
{
    public function __construct(
        public string $surface,
        public string $label,
        public string $route,
        public string $activePattern,
        public ?string $permission = null,
        public ?string $previewRoute = null,
        public int $order = 100,
    ) {}
}
