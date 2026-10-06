<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Covers;

use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\DTOs\CatalogCoverImage;
use App\Modules\Catalog\Models\Edition;

final class NullCatalogCoverProvider implements CatalogCoverProvider
{
    public function configured(): bool
    {
        return false;
    }

    public function fetch(Edition $edition): ?CatalogCoverImage
    {
        return null;
    }
}
