<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Covers;

use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\DTOs\CatalogCoverImage;
use App\Modules\Catalog\Models\Edition;

/**
 * Fragt mehrere Cover-Quellen nacheinander ab. Die erste Quelle mit Treffer gewinnt;
 * nicht konfigurierte Quellen werden übersprungen.
 */
final readonly class ChainedCatalogCoverProvider implements CatalogCoverProvider
{
    /** @param list<CatalogCoverProvider> $providers */
    public function __construct(private array $providers) {}

    public function configured(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->configured()) {
                return true;
            }
        }

        return false;
    }

    public function fetch(Edition $edition): ?CatalogCoverImage
    {
        foreach ($this->providers as $provider) {
            if (! $provider->configured()) {
                continue;
            }

            $image = $provider->fetch($edition);

            if ($image !== null) {
                return $image;
            }
        }

        return null;
    }
}
