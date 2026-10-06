<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\DTOs\CatalogCoverImage;
use App\Modules\Catalog\Models\Edition;

interface CatalogCoverProvider
{
    public function configured(): bool;

    public function fetch(Edition $edition): ?CatalogCoverImage;
}
